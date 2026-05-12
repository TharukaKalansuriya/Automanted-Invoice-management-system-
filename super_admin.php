<?php
// Start session with the same lifetime as in login.php
ini_set('session.gc_maxlifetime', 7200);
session_set_cookie_params(7200);
session_start();

//Check if user is logged in and session not expired
if (
    !isset($_SESSION['admin_id']) ||
!isset($_SESSION['role']) ||
   $_SESSION['role'] !== 'super_admin' || // ensure role is super admin-----------------------------------------------------------------
   !isset($_SESSION['expires']) ||
   time() > $_SESSION['expires']
) {
    // Destroy session for security
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header('Location: index.php');
    exit();
}

// Optional: extend session expiration on activity
$_SESSION['expires'] = time() + 7200;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETrack Biz Invoice Management</title>
     <!-- Load jsPDF libraries -->
    <script src="https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://unpkg.com/jspdf-autotable@3.5.28/dist/jspdf.plugin.autotable.js"></script>
    <script type="text/javascript">
        // Initialize jsPDF globally
        window.jsPDF = window.jspdf.jsPDF;
    </script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('images/inbg1.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding: 20px;
        }
        .header {
        background: linear-gradient(140deg, #055bbdff, #bb1919ff);
        color: white;
        padding: 30px;
        text-align: center;
        }
        /* Additional CSS for enhanced functionality */
        .paid-stamp {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            color: #ff000057;
            font-size: 72px;
            font-weight: bold;
            opacity: 0.3;
            z-index: 10;
            pointer-events: none;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            border: 8px solid #ff000081;
            padding: 20px 40px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .invoice-preview {
            position: relative;
        }
        
        .status-paid {
            background-color: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .invoice-type-badge {
            display: inline-block;
            background-color: #17a2b8;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        
        .proforma-badge {
            background-color: #ffc107;
            color: #212529;
        }

        /* Service discount controls */
        .service-discount-controls {
            display: flex;
            gap: 5px;
            align-items: center;
            margin-top: 5px;
        }

        .discount-input {
            width: 60px;
            padding: 2px 4px;
            font-size: 11px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        .discount-name-input {
            width: 120px;
            padding: 2px 4px;
            font-size: 11px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        .discount-toggle {
            font-size: 11px;
            padding: 2px 6px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            min-width: 85px;
        }

        .discount-toggle:hover {
            background-color: #218838;
        }

        .discount-toggle.active {
            background-color: #dc3545;
        }

        .discount-amount {
            color: #dc3545;
            font-weight: bold;
        }

        .service-item {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
            transition: background-color 0.2s ease;
        }

        .service-item:hover {
            background-color: #f0f0f0;
        }

        .service-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .service-checkbox {
            min-width: 20px;
        }

        .service-name {
            flex: 2;
            font-weight: bold;
            color: #333;
        }

        .service-qty, .service-rate, .service-amount {
            flex: 1;
            text-align: center;
        }

        .service-qty input, .service-rate input {
            width: 80px;
            padding: 4px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        .service-amount {
            font-weight: bold;
            color: #007bff;
        }

        /* Global discount section styling */
        .discount-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }

        .discount-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .discount-controls input {
            max-width: 150px;
        }

        .discount-controls label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: normal;
        }

        .amount-breakdown {
            text-align: right;
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }

        .amount-breakdown div {
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
        }

        .subtotal {
            font-size: 1.1em;
            color: #666;
        }

        .discount-line {
            color: #dc3545;
            font-weight: bold;
        }

        .final-total {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
            border-top: 2px solid #333;
            padding-top: 5px;
            margin-top: 10px;
        }

        /* RED DISCOUNT STYLING FOR PREVIEW */
        .discount-row {
            color: #dc3545 !important;
            font-weight: bold;
        }

        .discount-row td {
            color: #dc3545 !important;
            font-weight: bold;
        }

        /* Loading states */
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }

        /* Button improvements */
        .btn {
            transition: all 0.2s ease;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Error states */
        .service-error {
            text-align: center;
            padding: 20px;
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin: 10px 0;
        }
        .company-info h1 {
        font-size: 2.5em;
        margin-bottom: 5px;
        align-items: left;
        }
        .portal{
        font-size: 1.5em;
        color: yellow;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header" style="display: flex; justify-content: space-between; align-items: center; padding: 10px;">
            <div class="logo-section" style="display: flex; align-items: center;">
                <div class="company-info">
                    <h1>ETrack Biz</h1>
                    <h2 class="portal">Super Admin Portal</h2>
                    <p class="tagline">Powering Digital Growth</p>
                </div>
            </div>

            <!--add admin-->
            <a href="add_admin.php" 
               style="background-color: white; color: red; padding: 10px 20px; text-decoration: none; border-radius: 10px; font-weight: bold;">
                Edit Admins
            </a>
            <!-- Logout Button -->
            <a href="logout.php" 
               style="background-color: red; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Logout
            </a>
        </div>

        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('create')">Create Invoice</button>
            <button class="nav-tab" onclick="showTab('view')">View Invoices</button>
            <button class="nav-tab" onclick="showTab('services')">Manage Services</button>
        </div>
        
        <!-- Create Invoice Tab -->
        <div id="create" class="tab-content active">
            <h2 style="margin-bottom: 20px; color: #333;">Create New Invoice</h2>
            
            <div class="alert alert-success" id="createSuccess"></div>
            <div class="alert alert-error" id="createError"></div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="customerName">Customer Name *</label>
                    <input type="text" id="customerName" required>
                </div>
                <div class="form-group">
                    <label for="customerPhone">Customer Phone</label>
                    <input type="tel" id="customerPhone">
                </div>
            </div>
            <div class="form-group">
                <label for="customerAddress">Customer Address *</label>
                <textarea id="customerAddress" rows="3" required></textarea>
            </div>
            
            <!-- Due Date Section -->
            <div class="form-group">
                <label for="dueDate">Due Date *</label>
                <input type="date" id="dueDate" required>
            </div>
            
            <div class="services-section">
                <h3 style="margin-bottom: 15px; color: #333;">Select Services & Apply Discounts</h3>
                <div class="loading" id="servicesLoading">Loading services...</div>
                <div id="servicesList"></div>
            </div>
            
            <!-- Global Discount Section -->
            <div class="discount-section">
                <h3 style="margin-bottom: 10px; color: #333;">Global Discount (Optional)</h3>
                <div class="discount-controls">
                    <label>
                        <input type="radio" name="globalDiscountType" value="percentage" checked onchange="serviceManager.updateTotal()"> 
                        Percentage (%)
                    </label>
                    <label>
                        <input type="radio" name="globalDiscountType" value="fixed" onchange="serviceManager.updateTotal()"> 
                        Fixed Amount (LKR)
                    </label>
                    <input type="number" id="globalDiscountValue" placeholder="Enter discount" min="0" step="0.01" onchange="serviceManager.updateTotal()">
                    <input type="text" id="globalDiscountName" placeholder="Discount name (optional)" onchange="serviceManager.updateTotal()">
                </div>
            </div>
            
            <div class="amount-breakdown" id="amountBreakdown">
                <div class="subtotal">
                    <span>Subtotal:</span>
                    <span>LKR <span id="subtotalAmount">0.00</span></span>
                </div>
                <div id="discountLines"></div>
                <div class="final-total">
                    <span>Total:</span>
                    <span>LKR <span id="totalAmount">0.00</span></span>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <button class="btn" id="generateInvoiceBtn" onclick="generateInvoice()">Generate Invoice & Proforma</button>
                <button class="btn btn-secondary" id="generateProformaBtn" onclick="generateProforma()">Generate Proforma Only</button>
            </div>
        </div>
        
        <!-- View Invoices Tab -->
        <div id="view" class="tab-content">
            <h2 style="margin-bottom: 20px; color: #333;">Invoice History</h2>
            
            <div class="alert alert-success" id="viewSuccess"></div>
            <div class="alert alert-error" id="viewError"></div>
            
            <div class="loading" id="invoicesLoading">Loading invoices...</div>
            
            <table class="invoice-table" id="invoiceTable">
                <thead>
                    <tr>
                        <th>Invoice No.</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount (LKR)</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="invoiceTableBody"></tbody>
            </table>
        </div>
        
        <!-- Manage Services Tab -->
        <div id="services" class="tab-content">
            <h2 style="margin-bottom: 20px; color: #333;">Manage Services</h2>
            
            <div class="alert alert-success" id="servicesSuccess"></div>
            <div class="alert alert-error" id="servicesError"></div>
            
            <div style="margin-bottom: 20px;">
                <h3 style="margin-bottom: 10px;">Add New Service</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="serviceName">Service Name</label>
                        <input type="text" id="serviceName">
                    </div>
                    <div class="form-group">
                        <label for="servicePrice">Default Price (LKR)</label>
                        <input type="number" id="servicePrice" step="0.01">
                    </div>
                </div>
                <button class="btn" id="addServiceBtn" onclick="addService()">Add Service</button>
            </div>
            
            <h3 style="margin-bottom: 10px;">Current Services</h3>
            <div class="loading" id="currentServicesLoading">Loading services...</div>
            <div id="currentServices"></div>
        </div>
    </div>
    
    <!-- Invoice Preview Modal -->
    <div id="invoiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Invoice Preview</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="invoicePreview" class="invoice-preview"></div>
            <div style="padding: 20px; text-align: center; border-top: 1px solid #eee;">
                <button class="btn" onclick="downloadPDF()">Download PDF</button>
                <button class="btn btn-secondary" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Include the service manager first -->
    <script src="js/services.js"></script>
    <script>
        const API_BASE = 'api.php';
        let services = [];
        let invoices = [];
        let currentInvoice = null;

        // Initialize due date to 30 days from now
        function initializeDueDate() {
            const today = new Date();
            const dueDate = new Date(today.getTime() + (30 * 24 * 60 * 60 * 1000));
            document.getElementById('dueDate').value = dueDate.toISOString().split('T')[0];
        }

        // API Helper Functions
        async function apiRequest(action, method = 'GET', data = null) {
            const url = `${API_BASE}?action=${action}`;
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };
            
            if (data && method !== 'GET') {
                options.body = JSON.stringify(data);
            }
            
            try {
                const response = await fetch(url, options);
                const result = await response.json();
                return result;
            } catch (error) {
                console.error('API Error:', error);
                return { success: false, message: 'Network error occurred' };
            }
        }

        // Alert Functions
        function showAlert(elementId, message, isSuccess = true) {
            const alertElement = document.getElementById(elementId);
            if (!alertElement) return;
            
            alertElement.textContent = message;
            alertElement.className = `alert ${isSuccess ? 'alert-success' : 'alert-error'}`;
            alertElement.style.display = 'block';
            
            setTimeout(() => {
                alertElement.style.display = 'none';
            }, 5000);
        }

        function hideAlert(elementId) {
            const element = document.getElementById(elementId);
            if (element) element.style.display = 'none';
        }

        // Tab Management
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.nav-tab').forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
            
            hideAlert('createSuccess');
            hideAlert('createError');
            hideAlert('viewSuccess');
            hideAlert('viewError');
            hideAlert('servicesSuccess');
            hideAlert('servicesError');
            
            if(tabId === 'view') loadInvoices();
            if(tabId === 'services') loadServices();
            if(tabId === 'create') {
                serviceManager.loadServiceSelection();
            }
        }

        // Service Management for Services Tab
        async function loadServices() {
            document.getElementById('currentServicesLoading').style.display = 'block';
            
            const result = await apiRequest('get_services');
            
            document.getElementById('currentServicesLoading').style.display = 'none';
            
            if (result.success) {
                services = result.data;
                serviceManager.services = result.data; // Update service manager
                displayCurrentServices();
            } else {
                showAlert('servicesError', result.message, false);
            }
        }

        function displayCurrentServices() {
            const container = document.getElementById("currentServices");
            container.innerHTML = "";
            
            if (services.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No services found. Add some services to get started.</p>';
                return;
            }
            
            services.forEach((srv) => {
                container.innerHTML += `
                    <div class="service-item">
                        <div class="service-name">${srv.name}</div>
                        <div class="service-rate">LKR ${parseFloat(srv.default_price).toFixed(2)}</div>
                        <button class="btn btn-danger btn-sm" onclick="deleteService(${srv.service_id})">Delete</button>
                    </div>
                `;
            });
        }

        async function addService() {
            const name = document.getElementById("serviceName").value.trim();
            const price = parseFloat(document.getElementById("servicePrice").value);
            
            if(!name || isNaN(price) || price <= 0) {
                showAlert('servicesError', 'Please enter valid service name and price', false);
                return;
            }
            
            document.getElementById('addServiceBtn').disabled = true;
            
            const result = await apiRequest('add_service', 'POST', { name, price });
            
            document.getElementById('addServiceBtn').disabled = false;
            
            if (result.success) {
                document.getElementById("serviceName").value = '';
                document.getElementById("servicePrice").value = '';
                showAlert('servicesSuccess', result.message);
                loadServices();
                serviceManager.refreshServices(); // Refresh service manager
            } else {
                showAlert('servicesError', result.message, false);
            }
        }

        async function deleteService(serviceId) {
            if (!confirm('Are you sure you want to delete this service?')) return;
            
            const result = await apiRequest(`delete_service&id=${serviceId}`, 'DELETE');
            
            if (result.success) {
                showAlert('servicesSuccess', result.message);
                loadServices();
                serviceManager.refreshServices(); // Refresh service manager
            } else {
                showAlert('servicesError', result.message, false);
            }
        }

        // Invoice Creation - Updated to use Service Manager
        async function generateInvoice(isProformaOnly = false) {
            const customerName = document.getElementById("customerName").value.trim();
            const customerAddress = document.getElementById("customerAddress").value.trim();
            const customerPhone = document.getElementById("customerPhone").value.trim();
            const dueDate = document.getElementById("dueDate").value;
            
            if(!customerName || !customerAddress || !dueDate) {
                showAlert('createError', 'Please fill in required fields (Customer Name, Address, and Due Date)', false);
                return;
            }
            
            // Validate service selections
            const validation = serviceManager.validateSelections();
            if (!validation.valid) {
                showAlert('createError', validation.message, false);
                return;
            }
            
            // Get service data from service manager
            const { selectedServices, serviceSpecificDiscounts } = serviceManager.getSelectedServicesData();
            
            const subtotalAmount = parseFloat(document.getElementById("subtotalAmount").textContent);
            const totalAmount = parseFloat(document.getElementById("totalAmount").textContent);
            
            // Get global discount information
            const globalDiscountValue = parseFloat(document.getElementById('globalDiscountValue').value) || 0;
            const globalDiscountTypeElement = document.querySelector('input[name="globalDiscountType"]:checked');
            const globalDiscountType = globalDiscountTypeElement ? globalDiscountTypeElement.value : 'percentage';
            const globalDiscountName = document.getElementById('globalDiscountName').value || 'Global Discount';
            
            // Calculate global discount amount
            const subtotalAfterServiceDiscounts = subtotalAmount - serviceSpecificDiscounts.reduce((sum, disc) => sum + disc.discountAmount, 0);
            const globalDiscountAmount = serviceManager.calculateGlobalDiscount(subtotalAfterServiceDiscounts);
            
            // Disable buttons during creation
            document.getElementById('generateInvoiceBtn').disabled = true;
            document.getElementById('generateProformaBtn').disabled = true;
            
            try {
                if (isProformaOnly) {
                    // Generate only proforma
                    const proformaData = {
                        customerName,
                        customerPhone,
                        customerAddress,
                        dueDate,
                        services: selectedServices,
                        serviceDiscounts: serviceSpecificDiscounts,
                        globalDiscountType,
                        globalDiscountValue,
                        globalDiscountName,
                        globalDiscountAmount,
                        subtotalAmount,
                        totalAmount,
                        isProforma: true
                    };
                    
                    const result = await apiRequest('create_invoice', 'POST', proformaData);
                    
                    if (result.success) {
                        showAlert('createSuccess', 'Proforma invoice generated successfully!');
                        clearForm();
                        
                        const invoiceResult = await apiRequest(`get_invoice&id=${result.data.invoice_id}`);
                        if (invoiceResult.success) {
                            showInvoicePreview(invoiceResult.data);
                        }
                    } else {
                        showAlert('createError', result.message, false);
                    }
                } else {
                    // Generate both invoice and proforma
                    const invoiceData = {
                        customerName,
                        customerPhone,
                        customerAddress,
                        dueDate,
                        services: selectedServices,
                        serviceDiscounts: serviceSpecificDiscounts,
                        globalDiscountType,
                        globalDiscountValue,
                        globalDiscountName,
                        globalDiscountAmount,
                        subtotalAmount,
                        totalAmount,
                        isProforma: false
                    };
                    
                    const proformaData = {
                        customerName,
                        customerPhone,
                        customerAddress,
                        dueDate,
                        services: selectedServices,
                        serviceDiscounts: serviceSpecificDiscounts,
                        globalDiscountType,
                        globalDiscountValue,
                        globalDiscountName,
                        globalDiscountAmount,
                        subtotalAmount,
                        totalAmount,
                        isProforma: true
                    };
                    
                    // Create proforma first
                    const proformaResult = await apiRequest('create_invoice', 'POST', proformaData);
                    
                    if (proformaResult.success) {
                        // Create invoice
                        const invoiceResult = await apiRequest('create_invoice', 'POST', invoiceData);
                        
                        if (invoiceResult.success) {
                            showAlert('createSuccess', 'Invoice and Proforma generated successfully!');
                            clearForm();
                            
                            const invoiceDetailResult = await apiRequest(`get_invoice&id=${invoiceResult.data.invoice_id}`);
                            if (invoiceDetailResult.success) {
                                showInvoicePreview(invoiceDetailResult.data);
                            }
                        } else {
                            showAlert('createError', 'Invoice creation failed: ' + invoiceResult.message, false);
                        }
                    } else {
                        showAlert('createError', 'Proforma creation failed: ' + proformaResult.message, false);
                    }
                }
            } catch (error) {
                showAlert('createError', 'An error occurred: ' + error.message, false);
            } finally {
                // Re-enable buttons
                document.getElementById('generateInvoiceBtn').disabled = false;
                document.getElementById('generateProformaBtn').disabled = false;
            }
        }

        function generateProforma() {
            generateInvoice(true);
        }

        function clearForm() {
            document.getElementById("customerName").value = '';
            document.getElementById("customerPhone").value = '';
            document.getElementById("customerAddress").value = '';
            document.getElementById("globalDiscountValue").value = '';
            document.getElementById("globalDiscountName").value = '';
            
            // Clear service manager selections
            serviceManager.clearAll();
            
            initializeDueDate();
        }

        // Invoice Viewing
        async function loadInvoices() {
            document.getElementById('invoicesLoading').style.display = 'block';
            document.getElementById('invoiceTableBody').innerHTML = '';
            
            const result = await apiRequest('get_invoices');
            
            document.getElementById('invoicesLoading').style.display = 'none';
            
            if (result.success) {
                invoices = result.data;
                displayInvoices();
            } else {
                showAlert('viewError', result.message, false);
            }
        }

        function displayInvoices() {
            const tbody = document.getElementById('invoiceTableBody');
            tbody.innerHTML = '';
            
            if (invoices.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px; color: #666;">No invoices found.</td></tr>';
                return;
            }
            
            invoices.forEach((invoice, index) => {
                const statusClass = `status-${invoice.status.toLowerCase()}`;
                const invoiceType = invoice.is_proforma == 1 ? 'Proforma' : 'Invoice';
                const typeBadgeClass = invoice.is_proforma == 1 ? 'proforma-badge' : 'invoice-type-badge';
                
                tbody.innerHTML += `
                    <tr>
                        <td>${invoice.invoice_number}</td>
                        <td>${invoice.customer_name}</td>
                        <td>${new Date(invoice.invoice_date).toLocaleDateString()}</td>
                        <td>${parseFloat(invoice.total_amount).toFixed(2)}</td>
                        <td><span class="${typeBadgeClass}">${invoiceType}</span></td>
                        <td><span class="${statusClass}">${invoice.status}</span></td>
                        <td>
                            <button class="btn btn-sm" onclick="viewInvoice(${invoice.invoice_id})">View</button>
                            ${invoice.status === 'Pending' && invoice.is_proforma != 1 ? `<button class="btn btn-sm btn-warning" onclick="markPaid(${invoice.invoice_id})">Mark Paid</button>` : ''}
                            <button class="btn btn-sm btn-danger" onclick="deleteInvoice(${invoice.invoice_id})">Delete</button>
                        </td>
                    </tr>
                `;
            });
        }

        async function viewInvoice(invoiceId) {
            const result = await apiRequest(`get_invoice&id=${invoiceId}`);
            
            if (result.success) {
                showInvoicePreview(result.data);
            } else {
                showAlert('viewError', result.message, false);
            }
        }

        async function markPaid(invoiceId) {
            if (!confirm('Mark this invoice as paid?')) return;
            
            const result = await apiRequest(`update_invoice_status&id=${invoiceId}`, 'PUT', { status: 'Paid' });
            
            if (result.success) {
                showAlert('viewSuccess', result.message);
                loadInvoices();
            } else {
                showAlert('viewError', result.message, false);
            }
        }

        async function deleteInvoice(invoiceId) {
            if (!confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) return;
            
            const result = await apiRequest(`delete_invoice&id=${invoiceId}`, 'DELETE');
            
            if (result.success) {
                showAlert('viewSuccess', result.message);
                loadInvoices();
            } else {
                showAlert('viewError', result.message, false);
            }
        }

        // FIXED: Invoice Preview Function with proper discount calculations
function showInvoicePreview(invoice) {
    currentInvoice = invoice;
    const preview = document.getElementById("invoicePreview");
    
    // Format dates
    const invoiceDate = new Date(invoice.invoice_date).toLocaleDateString();
    const dueDate = new Date(invoice.due_date).toLocaleDateString();
    
    // Add paid stamp if invoice is paid
    const paidStamp = invoice.status === 'Paid' && invoice.is_proforma != 1 ? '<div class="paid-stamp">PAID</div>' : '';
    
    // Calculate amounts properly
    let itemSubtotal = 0;
    invoice.items.forEach(item => {
        itemSubtotal += parseFloat(item.amount);
    });
    
    let discountRows = '';
    let totalDiscountAmount = 0;
    
    // Parse service discounts from JSON if it's a string
    let serviceDiscounts = [];
    if (invoice.service_discounts) {
        if (typeof invoice.service_discounts === 'string') {
            try {
                serviceDiscounts = JSON.parse(invoice.service_discounts);
            } catch (e) {
                console.error('Error parsing service discounts:', e);
            }
        } else if (Array.isArray(invoice.service_discounts)) {
            serviceDiscounts = invoice.service_discounts;
        }
    }
    
    // Calculate original subtotal (before any discounts)
    let originalSubtotal = itemSubtotal;
    
    // Add back service discounts to get original subtotal
    if (serviceDiscounts.length > 0) {
        serviceDiscounts.forEach(discount => {
            originalSubtotal += parseFloat(discount.discount_amount || discount.discountAmount || 0);
        });
    }
    
    // Add back global discount to get original subtotal
    if (invoice.global_discount_amount && parseFloat(invoice.global_discount_amount) > 0) {
        originalSubtotal += parseFloat(invoice.global_discount_amount);
    }
    
    // Check if we have any discounts
    const hasServiceDiscounts = serviceDiscounts.length > 0;
    const hasGlobalDiscount = invoice.global_discount_amount && parseFloat(invoice.global_discount_amount) > 0;
    
    if (hasServiceDiscounts || hasGlobalDiscount) {
        // Add subtotal row
        discountRows += `
            <tr>
                <td colspan="3"><strong>Subtotal</strong></td>
                <td><strong>LKR ${originalSubtotal.toFixed(2)}</strong></td>
            </tr>
        `;
        
        // Add service-specific discount rows - RED COLOR
        if (hasServiceDiscounts) {
            serviceDiscounts.forEach(discount => {
                const discountAmount = parseFloat(discount.discount_amount || discount.discountAmount || 0);
                const discountName = discount.discount_name || discount.discountName || 'Service Discount';
                totalDiscountAmount += discountAmount;
                
                discountRows += `
                    <tr class="discount-row">
                        <td colspan="3" style="color: #dc3545; font-weight: bold;">${discountName}</td>
                        <td style="color: #dc3545; font-weight: bold;">- LKR ${discountAmount.toFixed(2)}</td>
                    </tr>
                `;
            });
        }
        
        // Add global discount row - RED COLOR
        if (hasGlobalDiscount) {
            const globalDiscountAmount = parseFloat(invoice.global_discount_amount);
            const discountName = invoice.global_discount_name || 'Global Discount';
            totalDiscountAmount += globalDiscountAmount;
            
            discountRows += `
                <tr class="discount-row">
                    <td colspan="3" style="color: #dc3545; font-weight: bold;">${discountName}</td>
                    <td style="color: #dc3545; font-weight: bold;">- LKR ${globalDiscountAmount.toFixed(2)}</td>
                </tr>
            `;
        }
    }
    
    preview.innerHTML = `
        ${paidStamp}
        <div class="invoice-header">
            <div>
                <div class="invoice-title">${invoice.is_proforma == 1 ? 'Proforma Invoice' : 'Invoice'}</div>
                <div>${invoice.invoice_number}</div>
                <div>Invoice Date: ${invoiceDate}</div>
                <div>Due Date: ${dueDate}</div>
            </div>
            <div style="text-align: right;">
                <div style="width: 160px; height: 80px; color: white; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-weight: bold;">
                   <img src="images/logo.png" alt="ETrack Biz Logo" style="width: 100%; height: auto; border-radius: 8px;" onerror="this.style.display='none'">
                </div>
            </div>
        </div>
        <div class="billing-section">
            <div style="text-align:left; width: 48%;">
                <div><strong>Billed By:</strong></div>
                <div>ETrackBiz Pvt LTD</div>
                <div>Sri Lanka</div>
                <div>Phone: 077 4466168</div>
            </div>
            <div class="billing-box">
                <h3>Bill To</h3>
                <div>${invoice.customer_name}</div>
                <div>${invoice.customer_address.replace(/\n/g, '<br>')}</div>
                <div>${invoice.customer_phone || ''}</div>
            </div>
        </div>
        <div class="invoice-items">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Service</th><th>Qty</th><th>Rate (LKR)</th><th>Amount (LKR)</th>
                    </tr>
                </thead>
                <tbody>
                    ${invoice.items.map(item => `
                        <tr>
                            <td>${item.service_name}</td>
                            <td>${parseFloat(item.quantity)}</td>
                            <td>${parseFloat(item.rate).toFixed(2)}</td>
                            <td>${parseFloat(item.amount).toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    ${discountRows}
                    <tr class="total-row">
                        <td colspan="3"><strong>Total</strong></td>
                        <td><strong>LKR ${parseFloat(invoice.total_amount).toFixed(2)}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="bank-details">
            <h3>Bank Details</h3>
            <div class="bank-accounts">
                <div class="account">
                    <strong>Account 1</strong><br>
                    Account Name: ETrack Biz Pvt LTD<br>
                    Account Number: 1000897843<br>
                    Bank/Branch: Commercial Kalutara Branch
                </div>
                <div class="account">
                    <strong>Account 2</strong><br>
                    Account Name: ETrack Biz Pvt LTD<br>
                    Account Number: 94569468<br>
                    Bank/Branch: BOC Milagiriya Branch
                </div>
            </div>
        </div>
    `;
    document.getElementById("invoiceModal").style.display = "block";
}

// FIXED: PDF Generation Function with proper jsPDF and autoTable usage
function generateEnhancedPDF() {
    if (!window.jsPDF || !window.jsPDF.jsPDF) {
        console.error('jsPDF not loaded properly');
        alert('PDF generation library not loaded. Please refresh the page and try again.');
        return;
    }

    const { jsPDF } = window.jsPDF;
    const doc = new jsPDF();
    
    // Verify autoTable is available
    if (!doc.autoTable) {
        console.error('autoTable plugin not available');
        alert('PDF table plugin not loaded. Please refresh the page and try again.');
        return;
    }

    try {
        generatePDFContent(doc);
    } catch (error) {
        console.error('PDF generation error:', error);
        alert('Error generating PDF: ' + error.message);
    }
}

function generatePDFContent(doc, logoData = null) {
    // Page dimensions
    const pageWidth = 210;
    const pageHeight = 297;
    const margin = 15;
    const contentWidth = pageWidth - (2 * margin);
    
    // Parse service discounts if they're in JSON format
    let serviceDiscounts = [];
    if (currentInvoice.service_discounts) {
        if (typeof currentInvoice.service_discounts === 'string') {
            try {
                serviceDiscounts = JSON.parse(currentInvoice.service_discounts);
            } catch (e) {
                console.error('Error parsing service discounts for PDF:', e);
            }
        } else if (Array.isArray(currentInvoice.service_discounts)) {
            serviceDiscounts = currentInvoice.service_discounts;
        }
    }

     // Remove the main border code that was here
    // doc.setDrawColor(74, 144, 226);
    // doc.setLineWidth(1.5);
    // doc.rect(margin - 5, 10, contentWidth + 10, pageHeight - 20);
    
    // Header section - adjusted positioning without border
    doc.setFillColor(248, 249, 250);
    doc.rect(margin, 10, contentWidth, 25, 'F');
    
    // Title
    doc.setFontSize(20);
    doc.setTextColor(74, 144, 226);
    doc.setFont('helvetica', 'bold');
    doc.text(currentInvoice.is_proforma == 1 ? 'Proforma Invoice' : 'Invoice', margin, 25);
    
    // Add logo if available
    if (logoData) {
        try {
            // Center logo horizontally
            const logoWidth = 50;
            const logoHeight = 20;
            const logoX = (pageWidth - logoWidth) / 2;
            doc.addImage(logoData, 'PNG', logoX, 12, logoWidth, logoHeight);
        } catch (e) {
            console.log('Failed to add logo to PDF');
        }
    }
    
    // Invoice details
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    doc.setTextColor(0, 0, 0);
    doc.text(`Invoice #: ${currentInvoice.invoice_number}`, pageWidth - margin - 50, 20);
    doc.text(`Date: ${new Date(currentInvoice.invoice_date).toLocaleDateString()}`, pageWidth - margin - 50, 26);
    doc.text(`Due: ${new Date(currentInvoice.due_date).toLocaleDateString()}`, pageWidth - margin - 50, 32);
    
    // Billing sections - FIXED: Use rect instead of roundedRect for better compatibility
    doc.setFillColor(235, 245, 255);
    doc.setDrawColor(74, 144, 226);
    doc.setLineWidth(0.5);
    doc.rect(margin, 40, 80, 30, 'FD'); // Removed roundedRect
    
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.text('Billed By:', margin + 3, 48);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.text('ETrackBiz Pvt LTD', margin + 3, 54);
    doc.text('Sri Lanka', margin + 3, 58);
    doc.text('Phone: 077 4466168', margin + 3, 62);
    
    doc.setFillColor(235, 245, 255);
    doc.rect(pageWidth - margin - 80, 40, 80, 30, 'FD'); // Removed roundedRect
    
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.text('Bill To:', pageWidth - margin - 77, 48);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.text(currentInvoice.customer_name, pageWidth - margin - 77, 54);
    
    const addressLines = currentInvoice.customer_address.split('\n');
    let yPos = 58;
    addressLines.forEach((line, index) => {
        if (yPos <= 66 && line.trim()) {
            const truncatedLine = line.length > 35 ? line.substring(0, 35) + '...' : line;
            doc.text(truncatedLine, pageWidth - margin - 77, yPos);
            yPos += 4;
        }
    });
    
    if (currentInvoice.customer_phone && yPos <= 66) {
        doc.text(currentInvoice.customer_phone, pageWidth - margin - 77, yPos);
    }
    
    // Calculate original subtotal for display
    let itemSubtotal = 0;
    currentInvoice.items.forEach(item => {
        itemSubtotal += parseFloat(item.amount);
    });
    
    let originalSubtotal = itemSubtotal;
    if (serviceDiscounts.length > 0) {
        serviceDiscounts.forEach(discount => {
            originalSubtotal += parseFloat(discount.discount_amount || discount.discountAmount || 0);
        });
    }
    if (currentInvoice.global_discount_amount && parseFloat(currentInvoice.global_discount_amount) > 0) {
        originalSubtotal += parseFloat(currentInvoice.global_discount_amount);
    }
    
    // Services table data
    const tableData = currentInvoice.items.map(item => [
        item.service_name.length > 40 ? item.service_name.substring(0, 40) + '...' : item.service_name,
        parseFloat(item.quantity).toString(),
        parseFloat(item.rate).toFixed(2),
        parseFloat(item.amount).toFixed(2)
    ]);
    
    // Footer data with discounts
    const footerData = [];
    const hasDiscounts = serviceDiscounts.length > 0 || (currentInvoice.global_discount_amount && parseFloat(currentInvoice.global_discount_amount) > 0);
    
    // Add subtotal if there are discounts
    if (hasDiscounts) {
        footerData.push({
            content: ['', '', 'Subtotal', `LKR ${originalSubtotal.toFixed(2)}`],
            styles: { textColor: [0, 0, 0] }
        });
    }
    
    // Add service discounts in red
    if (serviceDiscounts.length > 0) {
        serviceDiscounts.forEach(discount => {
            const discountAmount = parseFloat(discount.discount_amount || discount.discountAmount || 0);
            const discountName = discount.discount_name || discount.discountName || 'Service Discount';
            footerData.push({
                content: ['', '', discountName, `- LKR ${discountAmount.toFixed(2)}`],
                styles: { textColor: [220, 53, 69] } // Red color for discounts
            });
        });
    }
    
    // Add global discount in red
    if (currentInvoice.global_discount_amount && parseFloat(currentInvoice.global_discount_amount) > 0) {
        const globalDiscountAmount = parseFloat(currentInvoice.global_discount_amount);
        const discountName = currentInvoice.global_discount_name || 'Global Discount';
        footerData.push({
            content: ['', '', discountName, `- LKR ${globalDiscountAmount.toFixed(2)}`],
            styles: { textColor: [220, 53, 69] } // Red color for discounts
        });
    }
    
    // Add total in black
    footerData.push({
        content: ['', '', 'Total', `LKR ${parseFloat(currentInvoice.total_amount).toFixed(2)}`],
        styles: { textColor: [0, 0, 0] }
    });
    
    // FIXED: Generate table with better compatibility
    doc.autoTable({
        startY: 75,
        head: [['Service', 'Qty', 'Rate (LKR)', 'Amount (LKR)']],
        body: tableData,
        foot: footerData.map(row => row.content),
        margin: { left: margin, right: margin },
        tableWidth: contentWidth,
        columnStyles: {
            0: { cellWidth: contentWidth * 0.5 },
            1: { cellWidth: contentWidth * 0.15, halign: 'center' },
            2: { cellWidth: contentWidth * 0.175, halign: 'right' },
            3: { cellWidth: contentWidth * 0.175, halign: 'right' }
        },
        didParseCell: function(data) {
            // Apply red color to discount rows in footer
            if (data.section === 'foot' && footerData[data.row.index] && footerData[data.row.index].styles) {
                data.cell.styles.textColor = footerData[data.row.index].styles.textColor;
            }
        },
        headStyles: { 
            fillColor: [74, 144, 226],
            textColor: [255, 255, 255],
            fontSize: 10,
            fontStyle: 'bold',
            halign: 'center'
        },
        bodyStyles: {
            fontSize: 9,
            cellPadding: 4
        },
        footStyles: { 
            fillColor: [248, 249, 250], 
            textColor: [0, 0, 0], 
            fontStyle: 'bold',
            fontSize: 10,
            halign: 'center'
        },
        alternateRowStyles: {
            fillColor: [248, 249, 250]
        },
        styles: {
            lineColor: [74, 144, 226],
            lineWidth: 0.3,
            cellPadding: 3
        },
        showFoot: 'lastPage',
    
        didDrawCell: function(data) {
            // Reset styles after each cell
            doc.setTextColor(0, 0, 0);
            doc.setFont('helvetica', 'normal');
        },
        // FIXED: Simplified didDrawPage
        didDrawPage: function(data) {
            // Add border to each page
            if (data.pageNumber > 1) {
                doc.setDrawColor(74, 144, 226);
                doc.setLineWidth(1.5);
                doc.rect(margin - 5, 10, contentWidth + 10, pageHeight - 20);
            }
            
            // Add footer text at bottom of each page
            doc.setFont('helvetica', 'italic');
            doc.setFontSize(8);
            doc.setTextColor(0, 0, 0);
            doc.text('This invoice is generated by automated system', 
                pageWidth / 2, 
                pageHeight - 10, 
                { align: 'center' }
            );
        }
    });
    
    // Bank details
    const finalY = doc.lastAutoTable.finalY || 200; // Fallback if finalY is undefined
    const bankY = Math.max(finalY + 10, pageHeight - 60);
    
    if (bankY > pageHeight - 50) {
        doc.addPage();
        doc.setDrawColor(74, 144, 226);
        doc.setLineWidth(1.5);
        doc.rect(margin - 5, 10, contentWidth + 10, pageHeight - 20);
        addBankDetails(doc, 30, margin, contentWidth, pageWidth);
    } else {
        addBankDetails(doc, bankY, margin, contentWidth, pageWidth);
    }
    
// PAID RIBBON in top-right corner (like a flag/banner)
if (currentInvoice.status === 'Paid' && currentInvoice.is_proforma != 1) {
    // Save graphics state
    doc.saveGraphicsState();
    
    // Ribbon dimensions (larger and more prominent)
    const ribbonSize = 50;
    const cornerX = pageWidth;
    const cornerY = 0;
    
    // Draw red triangle ribbon background
    doc.setFillColor(220, 53, 69);
    doc.triangle(
        cornerX, cornerY,                      // Top-right corner (exact corner)
        cornerX - ribbonSize, cornerY,         // Left point
        cornerX, cornerY + ribbonSize,         // Bottom point
        'F'
    );
    
    // Add white PAID text rotated 45 degrees
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(20);
    doc.setFont('helvetica', 'bold');
    
    // Position text in center of triangle
    const textX = cornerX - 20;
    const textY = cornerY + 20;
    
    doc.text('PAID', textX, textY, {
        angle: -45,
        align: 'center'
    });
    
    // Restore graphics state
    doc.restoreGraphicsState();
    
    // Reset colors
    doc.setTextColor(0, 0, 0);
}
    
    // Save the PDF
    const filename = currentInvoice.is_proforma == 1 ? 
        `proforma_${currentInvoice.invoice_number}.pdf` : 
        `invoice_${currentInvoice.invoice_number}.pdf`;
    doc.save(filename);
}

function addBankDetails(doc, bankY, margin, contentWidth, pageWidth) {
    doc.setFillColor(235, 245, 255);
    doc.setDrawColor(74, 144, 226);
    doc.setLineWidth(0.5);
    
    // FIXED: Use regular rect instead of roundedRect
    doc.rect(margin, bankY, contentWidth, 25, 'FD');
    
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(74, 144, 226);
    doc.text('Bank Details:', margin + 3, bankY + 7);
    
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(0, 0, 0);
    
    doc.setFont('helvetica', 'bold');
    doc.text('Account 1:', margin + 3, bankY + 12);
    doc.setFont('helvetica', 'normal');
    doc.text('ETrack Biz Pvt LTD', margin + 3, bankY + 16);
    doc.text('A/C: 1000897843', margin + 3, bankY + 19);
    doc.text('Commercial Kalutara Branch', margin + 3, bankY + 22);
    
    doc.setFont('helvetica', 'bold');
    doc.text('Account 2:', margin + 90, bankY + 12);
    doc.setFont('helvetica', 'normal');
    doc.text('ETrack Biz Pvt LTD', margin + 90, bankY + 16);
    doc.text('A/C: 94569468', margin + 90, bankY + 19);
    doc.text('BOC Milagiriya Branch', margin + 90, bankY + 22);
}
function closeModal() {
            document.getElementById("invoiceModal").style.display = "none";
            currentInvoice = null;
        }
    
// FIXED: Download PDF function
function downloadPDF() {
    if (!currentInvoice) {
        alert('No invoice data available');
        return;
    }
    
    try {
        if (!window.jsPDF) {
            throw new Error('jsPDF library not loaded');
        }

        // Create new document with proper configuration
        const doc = new window.jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'a4',
            compress: true
        });
        
        if (!doc.autoTable) {
            throw new Error('autoTable plugin not loaded');
        }
        
        // Load and add logo
        const img = new Image();
        img.src = 'images/logo.png';
        
        img.onload = function() {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            const logoData = canvas.toDataURL('image/png');
            
            // Generate PDF content with logo
            generatePDFContent(doc, logoData);
        };
        
        img.onerror = function() {
            console.warn('Logo not found, generating PDF without logo');
            generatePDFContent(doc);
        };
        
    } catch (error) {
        console.error('PDF Download Error:', error);
        alert('PDF generation failed. Please ensure you have a stable internet connection and try again.');
    }
}
        // Modal close on outside click
        window.onclick = function(event) {
            const modal = document.getElementById("invoiceModal");
            if (event.target == modal) {
                closeModal();
            }
        }

        // Initialize app
        window.onload = function() {
            serviceManager.loadServiceSelection();
            initializeDueDate();
            
            // Verify PDF libraries
            setTimeout(() => {
                if (!window.jsPDF) {
                    console.warn('Attempting to initialize jsPDF...');
                    window.jsPDF = window.jspdf.jsPDF;
                }
                
                try {
                    // Test PDF generation
                    const doc = new window.jsPDF();
                    if (typeof doc.autoTable === 'undefined') {
                        console.warn('Loading autoTable plugin...');
                        import('https://unpkg.com/jspdf-autotable@3.5.28/dist/jspdf.plugin.autotable.js')
                            .then(() => console.log('autoTable plugin loaded'))
                            .catch(err => console.error('Failed to load autoTable:', err));
                    }
                } catch (error) {
                    console.error('PDF initialization error:', error);
                    // Attempt to reload the libraries
                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js';
                    script.onload = () => {
                        window.jsPDF = window.jspdf.jsPDF;
                        console.log('jsPDF reloaded successfully');
                    };
                    document.head.appendChild(script);
                }
            }, 500);
        };

        // Verify PDF libraries before generation
        function verifyPDFLibraries() {
            if (typeof window.jspdf === 'undefined') {
                throw new Error('jsPDF not loaded');
            }
            const { jsPDF } = window.jspdf;
            if (!jsPDF) {
                throw new Error('jsPDF not properly initialized');
            }
            return true;
        }
    </script>
</body>
</html>