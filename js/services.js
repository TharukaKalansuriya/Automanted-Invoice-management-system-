/**
 * Service Management Module
 * Handles service selection, discounts, and calculations
 */

class ServiceManager {
    constructor() {
        this.services = [];
        this.serviceDiscounts = {};
        this.selectedServices = new Set();
    }

    /**
     * Initialize service selection interface
     */
    async loadServiceSelection() {
        const container = document.getElementById("servicesList");
        const loadingElement = document.getElementById('servicesLoading');
        
        if (loadingElement) loadingElement.style.display = 'block';
        
        try {
            // Load services if not already loaded
            if (this.services.length === 0) {
                const result = await apiRequest('get_services');
                if (result.success) {
                    this.services = result.data;
                } else {
                    throw new Error(result.message);
                }
            }
            
            if (loadingElement) loadingElement.style.display = 'none';
            this.renderServiceSelection(container);
            
        } catch (error) {
            if (loadingElement) loadingElement.style.display = 'none';
            console.error('Error loading services:', error);
            container.innerHTML = '<p style="text-align: center; color: #dc3545; padding: 20px;">Error loading services. Please try again.</p>';
        }
    }

    /**
     * Render service selection interface
     */
    renderServiceSelection(container) {
        if (!container) return;

        container.innerHTML = "";
        
        if (this.services.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No services available. Please add services first.</p>';
            return;
        }
        
        this.services.forEach((service, index) => {
            const serviceItem = this.createServiceItem(service, index);
            container.appendChild(serviceItem);
        });
        
        // Initialize service discounts object
        this.serviceDiscounts = {};
        this.selectedServices.clear();
    }

    /**
     * Create individual service item DOM element
     */
    createServiceItem(service, index) {
        const serviceDiv = document.createElement('div');
        serviceDiv.className = 'service-item';
        serviceDiv.id = `service-${index}`;
        
        serviceDiv.innerHTML = `
            <div class="service-row">
                <div class="service-checkbox">
                    <input type="checkbox" data-index="${index}" onchange="serviceManager.toggleService(${index})">
                </div>
                <div class="service-name">${service.name}</div>
                <div class="service-qty">
                    <input type="number" min="1" value="1" step="0.01" onchange="serviceManager.updateTotal()" disabled>
                </div>
                <div class="service-rate">
                    <input type="number" value="${service.default_price}" step="0.01" onchange="serviceManager.updateTotal()" disabled>
                </div>
                <div class="service-amount">0.00</div>
            </div>
            <div class="service-discount-controls" id="discount-controls-${index}" style="display: none;">
                <button type="button" class="discount-toggle" onclick="serviceManager.toggleServiceDiscount(${index})">Add Discount</button>
                <input type="number" class="discount-input" placeholder="Amount" min="0" step="0.01" onchange="serviceManager.updateTotal()" disabled>
                <select class="discount-input" onchange="serviceManager.updateTotal()" disabled>
                    <option value="percentage">%</option>
                    <option value="fixed">LKR</option>
                </select>
                <input type="text" class="discount-name-input" placeholder="Discount name" onchange="serviceManager.updateTotal()" disabled>
            </div>
        `;
        
        return serviceDiv;
    }

    /**
     * Toggle service selection
     */
    toggleService(index) {
        const serviceItem = document.getElementById(`service-${index}`);
        if (!serviceItem) return;

        const checkbox = serviceItem.querySelector('input[type="checkbox"]');
        const qtyInput = serviceItem.querySelector('.service-qty input');
        const rateInput = serviceItem.querySelector('.service-rate input');
        const discountControls = serviceItem.querySelector('.service-discount-controls');
        
        if (checkbox.checked) {
            // Enable service
            this.selectedServices.add(index);
            qtyInput.disabled = false;
            rateInput.disabled = false;
            discountControls.style.display = 'flex';
            
            // Initialize discount for this service
            if (!this.serviceDiscounts[index]) {
                this.serviceDiscounts[index] = {
                    enabled: false,
                    amount: 0,
                    type: 'percentage',
                    name: `Discount for ${this.services[index].name}`
                };
            }
        } else {
            // Disable service
            this.selectedServices.delete(index);
            qtyInput.disabled = true;
            rateInput.disabled = true;
            discountControls.style.display = 'none';
            
            // Reset discount for this service
            this.resetServiceDiscount(index);
        }
        
        this.updateTotal();
    }

    /**
     * Toggle discount for specific service
     */
    toggleServiceDiscount(index) {
        const discountControls = document.getElementById(`discount-controls-${index}`);
        if (!discountControls) return;

        const discountToggle = discountControls.querySelector('.discount-toggle');
        const discountInputs = discountControls.querySelectorAll('input, select');
        
        // Initialize discount object if it doesn't exist
        if (!this.serviceDiscounts[index]) {
            this.serviceDiscounts[index] = {
                enabled: false,
                amount: 0,
                type: 'percentage',
                name: `Discount for ${this.services[index].name}`
            };
        }
        
        // Toggle discount state
        this.serviceDiscounts[index].enabled = !this.serviceDiscounts[index].enabled;
        
        if (this.serviceDiscounts[index].enabled) {
            // Enable discount
            discountToggle.textContent = 'Remove Discount';
            discountToggle.classList.add('active');
            discountInputs.forEach(input => input.disabled = false);
            
            // Set default discount name if empty
            const nameInput = discountControls.querySelector('.discount-name-input');
            if (!nameInput.value.trim()) {
                nameInput.value = this.serviceDiscounts[index].name;
            }
        } else {
            // Disable discount
            discountToggle.textContent = 'Add Discount';
            discountToggle.classList.remove('active');
            discountInputs.forEach(input => input.disabled = true);
            
            // Reset discount values
            const amountInput = discountControls.querySelector('.discount-input[type="number"]');
            const typeSelect = discountControls.querySelector('select');
            const nameInput = discountControls.querySelector('.discount-name-input');
            
            amountInput.value = '';
            typeSelect.selectedIndex = 0;
            nameInput.value = '';
        }
        
        this.updateTotal();
    }

    /**
     * Reset service discount
     */
    resetServiceDiscount(index) {
        // Remove from discounts object
        delete this.serviceDiscounts[index];
        
        // Reset UI elements
        const discountControls = document.getElementById(`discount-controls-${index}`);
        if (discountControls) {
            const discountToggle = discountControls.querySelector('.discount-toggle');
            const discountInputs = discountControls.querySelectorAll('input, select');
            
            discountToggle.textContent = 'Add Discount';
            discountToggle.classList.remove('active');
            discountInputs.forEach(input => {
                input.disabled = true;
                if (input.type === 'number' || input.type === 'text') {
                    input.value = '';
                } else if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                }
            });
        }
    }

    /**
     * Calculate discount for a specific service
     */
    calculateServiceDiscount(serviceIndex, serviceAmount) {
        if (!this.serviceDiscounts[serviceIndex] || !this.serviceDiscounts[serviceIndex].enabled) {
            return 0;
        }
        
        const discountControls = document.getElementById(`discount-controls-${serviceIndex}`);
        if (!discountControls) return 0;

        const discountInput = discountControls.querySelector('.discount-input[type="number"]');
        const typeSelect = discountControls.querySelector('select');
        const nameInput = discountControls.querySelector('.discount-name-input');
        
        const discountValue = parseFloat(discountInput.value) || 0;
        const discountType = typeSelect.value;
        
        if (discountValue <= 0) return 0;
        
        let discountAmount = 0;
        
        if (discountType === 'percentage') {
            // Limit percentage to 100%
            const limitedPercentage = Math.min(discountValue, 100);
            if (limitedPercentage !== discountValue) {
                discountInput.value = limitedPercentage;
            }
            discountAmount = (serviceAmount * limitedPercentage) / 100;
        } else {
            // Fixed amount - cannot exceed service amount
            discountAmount = Math.min(discountValue, serviceAmount);
        }
        
        // Update discount object
        this.serviceDiscounts[serviceIndex].amount = discountValue;
        this.serviceDiscounts[serviceIndex].type = discountType;
        this.serviceDiscounts[serviceIndex].name = nameInput.value || `Discount for ${this.services[serviceIndex].name}`;
        
        return discountAmount;
    }

    /**
     * Calculate global discount
     */
    calculateGlobalDiscount(subtotal) {
        const globalDiscountValue = parseFloat(document.getElementById('globalDiscountValue')?.value) || 0;
        const globalDiscountTypeElement = document.querySelector('input[name="globalDiscountType"]:checked');
        
        if (!globalDiscountTypeElement || globalDiscountValue <= 0) return 0;
        
        const globalDiscountType = globalDiscountTypeElement.value;
        let discountAmount = 0;
        
        if (globalDiscountType === 'percentage') {
            // Limit percentage to 100%
            const limitedPercentage = Math.min(globalDiscountValue, 100);
            const globalDiscountInput = document.getElementById('globalDiscountValue');
            if (globalDiscountInput && limitedPercentage !== globalDiscountValue) {
                globalDiscountInput.value = limitedPercentage;
            }
            discountAmount = (subtotal * limitedPercentage) / 100;
        } else {
            // Fixed amount - cannot exceed subtotal
            discountAmount = Math.min(globalDiscountValue, subtotal);
        }
        
        return discountAmount;
    }

    /**
     * Update total calculations
     */
    updateTotal() {
        let subtotal = 0;
        let totalServiceDiscounts = 0;
        const discountLines = [];
        
        // Calculate service amounts and discounts
        this.selectedServices.forEach(index => {
            const serviceItem = document.getElementById(`service-${index}`);
            if (!serviceItem) return;

            const checkbox = serviceItem.querySelector('input[type="checkbox"]');
            const qtyInput = serviceItem.querySelector('.service-qty input');
            const rateInput = serviceItem.querySelector('.service-rate input');
            const amountDisplay = serviceItem.querySelector('.service-amount');
            
            if (!checkbox.checked) return;

            const qty = parseFloat(qtyInput.value) || 0;
            const rate = parseFloat(rateInput.value) || 0;
            const serviceAmount = qty * rate;
            
            subtotal += serviceAmount;
            amountDisplay.textContent = serviceAmount.toFixed(2);
            
            // Calculate service-specific discount
            const serviceDiscount = this.calculateServiceDiscount(index, serviceAmount);
            if (serviceDiscount > 0) {
                totalServiceDiscounts += serviceDiscount;
                const discountName = this.serviceDiscounts[index].name || `Discount for ${this.services[index].name}`;
                discountLines.push({
                    name: discountName,
                    amount: serviceDiscount
                });
            }
        });

        // Calculate global discount on remaining amount
        const subtotalAfterServiceDiscounts = subtotal - totalServiceDiscounts;
        const globalDiscountAmount = this.calculateGlobalDiscount(subtotalAfterServiceDiscounts);
        
        // Add global discount to lines if applicable
        if (globalDiscountAmount > 0) {
            const globalDiscountNameElement = document.getElementById('globalDiscountName');
            const globalDiscountName = globalDiscountNameElement?.value || 'Global Discount';
            discountLines.push({
                name: globalDiscountName,
                amount: globalDiscountAmount
            });
        }
        
        const totalAmount = subtotal - totalServiceDiscounts - globalDiscountAmount;
        
        // Update display elements
        this.updateAmountDisplay(subtotal, discountLines, totalAmount);
    }

    /**
     * Update amount display in UI
     */
    updateAmountDisplay(subtotal, discountLines, totalAmount) {
        const subtotalElement = document.getElementById("subtotalAmount");
        const totalElement = document.getElementById("totalAmount");
        const discountLinesContainer = document.getElementById('discountLines');
        
        if (subtotalElement) subtotalElement.textContent = subtotal.toFixed(2);
        if (totalElement) totalElement.textContent = Math.max(0, totalAmount).toFixed(2);
        
        if (discountLinesContainer) {
            discountLinesContainer.innerHTML = '';
            discountLines.forEach(discount => {
                discountLinesContainer.innerHTML += `
                    <div class="discount-line">
                        <span>${discount.name}:</span>
                        <span>- LKR ${discount.amount.toFixed(2)}</span>
                    </div>
                `;
            });
        }
    }

    /**
     * Get selected services data for invoice creation
     */
    getSelectedServicesData() {
        const selectedServices = [];
        const serviceSpecificDiscounts = [];
        
        this.selectedServices.forEach(index => {
            const serviceItem = document.getElementById(`service-${index}`);
            if (!serviceItem) return;

            const checkbox = serviceItem.querySelector('input[type="checkbox"]');
            if (!checkbox.checked) return;

            const qtyInput = serviceItem.querySelector('.service-qty input');
            const rateInput = serviceItem.querySelector('.service-rate input');
            
            const qty = parseFloat(qtyInput.value) || 1;
            const rate = parseFloat(rateInput.value) || 0;
            const serviceAmount = qty * rate;
            
            selectedServices.push({
                name: this.services[index].name,
                qty: qty,
                rate: rate,
                amount: serviceAmount
            });
            
            // Add service-specific discount if enabled
            if (this.serviceDiscounts[index] && this.serviceDiscounts[index].enabled) {
                const discountAmount = this.calculateServiceDiscount(index, serviceAmount);
                if (discountAmount > 0) {
                    serviceSpecificDiscounts.push({
                        serviceIndex: index,
                        serviceName: this.services[index].name,
                        discountName: this.serviceDiscounts[index].name,
                        discountType: this.serviceDiscounts[index].type,
                        discountValue: this.serviceDiscounts[index].amount,
                        discountAmount: discountAmount
                    });
                }
            }
        });
        
        return { selectedServices, serviceSpecificDiscounts };
    }

    /**
     * Clear all selections and reset form
     */
    clearAll() {
        this.selectedServices.clear();
        this.serviceDiscounts = {};
        
        // Reset all checkboxes and inputs
        document.querySelectorAll('#servicesList .service-item').forEach((item, index) => {
            const checkbox = item.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = false;
                this.toggleService(index); // This will handle the cleanup
            }
        });
        
        // Reset global discount
        const globalDiscountValue = document.getElementById('globalDiscountValue');
        const globalDiscountName = document.getElementById('globalDiscountName');
        if (globalDiscountValue) globalDiscountValue.value = '';
        if (globalDiscountName) globalDiscountName.value = '';
        
        // Update totals
        this.updateTotal();
    }

    /**
     * Validate selections
     */
    validateSelections() {
        if (this.selectedServices.size === 0) {
            return { valid: false, message: 'Please select at least one service' };
        }
        
        // Check if all selected services have valid quantities and rates
        for (let index of this.selectedServices) {
            const serviceItem = document.getElementById(`service-${index}`);
            if (!serviceItem) continue;

            const qtyInput = serviceItem.querySelector('.service-qty input');
            const rateInput = serviceItem.querySelector('.service-rate input');
            
            const qty = parseFloat(qtyInput.value);
            const rate = parseFloat(rateInput.value);
            
            if (isNaN(qty) || qty <= 0) {
                return { valid: false, message: `Please enter a valid quantity for ${this.services[index].name}` };
            }
            
            if (isNaN(rate) || rate < 0) {
                return { valid: false, message: `Please enter a valid rate for ${this.services[index].name}` };
            }
        }
        
        return { valid: true };
    }

    /**
     * Refresh services list (call after services are added/deleted)
     */
    async refreshServices() {
        this.services = [];
        await this.loadServiceSelection();
    }
}

// Create global instance
const serviceManager = new ServiceManager();