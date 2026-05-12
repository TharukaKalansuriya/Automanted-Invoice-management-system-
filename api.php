<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'Database.php';

class InvoiceAPI {
    private $db;
    private $pdo;
    
    public function __construct() {
        try {
            $this->db = new Database();
            $this->pdo = $this->db->getConnection();
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $_GET['action'] ?? '';
        
        // Debug logging
        error_log("API Request - Method: $method, Action: $action");
        if ($input) {
            error_log("API Input: " . json_encode($input));
        }
        
        try {
            switch ($action) {
                case 'get_services':
                    return $this->getServices();
                    
                case 'add_service':
                    return $this->addService($input);
                    
                case 'delete_service':
                    return $this->deleteService($_GET['id'] ?? null);
                    
                case 'create_invoice':
                    return $this->createInvoice($input);
                    
                case 'get_invoices':
                    return $this->getInvoices();
                    
                case 'get_invoice':
                    return $this->getInvoice($_GET['id'] ?? null);
                    
                case 'update_invoice_status':
                    return $this->updateInvoiceStatus($_GET['id'] ?? null, $input['status'] ?? null);
                    
                case 'delete_invoice':
                    return $this->deleteInvoice($_GET['id'] ?? null);
                    
                default:
                    return $this->error("Invalid action: $action. Available actions: get_services, add_service, delete_service, create_invoice, get_invoices, get_invoice, update_invoice_status, delete_invoice");
            }
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->error('Server error: ' . $e->getMessage());
        }
    }
    
    private function getServices() {
        try {
            $sql = "SELECT service_id, name, default_price FROM services ORDER BY name";
            $services = $this->db->fetchAll($sql);
            
            error_log("Services fetched: " . count($services) . " services found");
            
            return $this->success($services);
        } catch (Exception $e) {
            error_log("getServices error: " . $e->getMessage());
            return $this->error("Failed to fetch services: " . $e->getMessage());
        }
    }
    
    private function addService($data) {
        if (empty($data['name']) || !isset($data['price'])) {
            return $this->error('Service name and price are required');
        }
        
        $sql = "INSERT INTO services (name, default_price) VALUES (?, ?)";
        try {
            $this->db->execute($sql, [$data['name'], $data['price']]);
            $serviceId = $this->db->lastInsertId();
            
            return $this->success([
                'service_id' => $serviceId,
                'name' => $data['name'],
                'default_price' => $data['price']
            ], 'Service added successfully');
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return $this->error('Service name already exists');
            }
            throw $e;
        }
    }
    
    private function deleteService($serviceId) {
        if (!$serviceId) {
            return $this->error('Service ID is required');
        }
        
        $sql = "DELETE FROM services WHERE service_id = ?";
        $result = $this->db->execute($sql, [$serviceId]);
        
        if ($result->rowCount() > 0) {
            return $this->success(null, 'Service deleted successfully');
        } else {
            return $this->error('Service not found');
        }
    }
    
    private function createInvoice($data) {
        if (empty($data['customerName']) || empty($data['customerAddress']) || empty($data['services'])) {
            return $this->error('Customer name, address and services are required');
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Check if customer exists, if not create new one
            $customerSql = "SELECT customer_id FROM customers WHERE name = ? AND address = ? LIMIT 1";
            $stmt = $this->pdo->prepare($customerSql);
            $stmt->execute([$data['customerName'], $data['customerAddress']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                // Insert new customer
                $insertCustomerSql = "INSERT INTO customers (name, phone, address) VALUES (?, ?, ?)";
                $stmt = $this->pdo->prepare($insertCustomerSql);
                $stmt->execute([
                    $data['customerName'], 
                    $data['customerPhone'] ?? '', 
                    $data['customerAddress']
                ]);
                $customerId = $this->pdo->lastInsertId();
            } else {
                $customerId = $customer['customer_id'];
                
                // Update customer phone if provided and different
                if (!empty($data['customerPhone'])) {
                    $updatePhoneSql = "UPDATE customers SET phone = ? WHERE customer_id = ?";
                    $stmt = $this->pdo->prepare($updatePhoneSql);
                    $stmt->execute([$data['customerPhone'], $customerId]);
                }
            }
            
            // Generate invoice number
            $prefix = $data['isProforma'] ? 'PRO-' : 'INV-';
            $stmt = $this->pdo->query("SELECT MAX(CAST(SUBSTRING(invoice_number, 5) AS UNSIGNED)) as max_num FROM invoices WHERE invoice_number LIKE '{$prefix}%'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNumber = ($result['max_num'] ?? 0) + 1;
            $invoiceNumber = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            // Prepare service discounts JSON
            $serviceDiscountsJson = null;
            if (!empty($data['serviceDiscounts'])) {
                $processedDiscounts = [];
                foreach ($data['serviceDiscounts'] as $discount) {
                    $processedDiscounts[] = [
                        'service_index' => $discount['serviceIndex'] ?? null,
                        'service_name' => $discount['serviceName'] ?? '',
                        'discount_name' => $discount['discountName'] ?? 'Service Discount',
                        'discount_type' => $discount['discountType'] ?? 'percentage',
                        'discount_value' => floatval($discount['discountValue'] ?? 0),
                        'discount_amount' => floatval($discount['discountAmount'] ?? 0)
                    ];
                }
                $serviceDiscountsJson = json_encode($processedDiscounts);
            }
            
            // Insert invoice with customer_id reference
            $sql = "INSERT INTO invoices (
                invoice_number, customer_id, 
                due_date, subtotal_amount, total_amount, status, is_proforma,
                service_discounts, global_discount_type, global_discount_value, 
                global_discount_name, global_discount_amount
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $invoiceNumber,
                $customerId,
                $data['dueDate'] ?? date('Y-m-d', strtotime('+30 days')),
                floatval($data['subtotalAmount'] ?? 0),
                floatval($data['totalAmount']),
                'Pending',
                $data['isProforma'] ? 1 : 0,
                $serviceDiscountsJson,
                $data['globalDiscountType'] ?? null,
                floatval($data['globalDiscountValue'] ?? 0),
                $data['globalDiscountName'] ?? null,
                floatval($data['globalDiscountAmount'] ?? 0)
            ]);
            
            $invoiceId = $this->pdo->lastInsertId();
            
            // Insert invoice items
            if (!empty($data['services'])) {
                $itemSql = "INSERT INTO invoice_items (invoice_id, service_name, quantity, rate, amount) VALUES (?, ?, ?, ?, ?)";
                $itemStmt = $this->pdo->prepare($itemSql);
                
                foreach ($data['services'] as $service) {
                    $itemStmt->execute([
                        $invoiceId,
                        $service['name'],
                        floatval($service['qty']),
                        floatval($service['rate']),
                        floatval($service['amount'])
                    ]);
                }
            }
            
            $this->pdo->commit();
            
            return $this->success([
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber
            ], ($data['isProforma'] ? 'Proforma' : 'Invoice') . ' created successfully');
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("createInvoice error: " . $e->getMessage());
            return $this->error('Failed to create invoice: ' . $e->getMessage());
        }
    }
    
    private function getInvoices() {
        try {
            $sql = "SELECT i.invoice_id, i.invoice_number, c.name as customer_name, c.phone as customer_phone, 
                    c.address as customer_address, i.invoice_date, i.due_date, i.total_amount, i.status, i.is_proforma,
                    i.service_discounts, i.global_discount_amount, i.global_discount_name
                    FROM invoices i 
                    JOIN customers c ON i.customer_id = c.customer_id 
                    ORDER BY i.invoice_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process each invoice to parse service discounts
            foreach ($invoices as &$invoice) {
                $serviceDiscounts = [];
                if (!empty($invoice['service_discounts'])) {
                    $decoded = json_decode($invoice['service_discounts'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $serviceDiscounts = $decoded;
                    }
                }
                $invoice['service_discounts'] = $serviceDiscounts;
            }
            
            return $this->success($invoices);
            
        } catch (Exception $e) {
            error_log("getInvoices error: " . $e->getMessage());
            return $this->error('Failed to retrieve invoices: ' . $e->getMessage());
        }
    }
    
    private function getInvoice($invoiceId) {
        if (!$invoiceId) {
            return $this->error('Invoice ID is required');
        }
        
        try {
            // Get invoice details with customer information
            $sql = "SELECT i.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address
                   FROM invoices i 
                   JOIN customers c ON i.customer_id = c.customer_id 
                   WHERE i.invoice_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$invoiceId]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$invoice) {
                return $this->error('Invoice not found');
            }
            
            // Get invoice items
            $sql = "SELECT * FROM invoice_items WHERE invoice_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$invoiceId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse service discounts JSON
            $serviceDiscounts = [];
            if (!empty($invoice['service_discounts'])) {
                $decoded = json_decode($invoice['service_discounts'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $serviceDiscounts = $decoded;
                }
            }
            
            $invoice['items'] = $items;
            $invoice['service_discounts'] = $serviceDiscounts;
            
            return $this->success($invoice);
            
        } catch (Exception $e) {
            error_log("getInvoice error: " . $e->getMessage());
            return $this->error('Failed to retrieve invoice: ' . $e->getMessage());
        }
    }
    
    private function updateInvoiceStatus($invoiceId, $status) {
        if (!$invoiceId || !$status) {
            return $this->error('Invoice ID and status are required');
        }
        
        $validStatuses = ['Pending', 'Paid', 'Cancelled'];
        if (!in_array($status, $validStatuses)) {
            return $this->error('Invalid status. Valid statuses: ' . implode(', ', $validStatuses));
        }
        
        try {
            $sql = "UPDATE invoices SET status = ? WHERE invoice_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$status, $invoiceId]);
            
            if ($stmt->rowCount() > 0) {
                return $this->success(null, 'Invoice status updated successfully');
            } else {
                return $this->error('Invoice not found or status unchanged');
            }
        } catch (Exception $e) {
            error_log("updateInvoiceStatus error: " . $e->getMessage());
            return $this->error('Failed to update invoice status: ' . $e->getMessage());
        }
    }
    
    private function deleteInvoice($invoiceId) {
        if (!$invoiceId) {
            return $this->error('Invoice ID is required');
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Delete invoice items first (foreign key constraint)
            $stmt = $this->pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
            $stmt->execute([$invoiceId]);
            
            // Delete invoice
            $stmt = $this->pdo->prepare("DELETE FROM invoices WHERE invoice_id = ?");
            $stmt->execute([$invoiceId]);
            
            if ($stmt->rowCount() > 0) {
                $this->pdo->commit();
                return $this->success(null, 'Invoice deleted successfully');
            } else {
                $this->pdo->rollBack();
                return $this->error('Invoice not found');
            }
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("deleteInvoice error: " . $e->getMessage());
            return $this->error('Failed to delete invoice: ' . $e->getMessage());
        }
    }
    
    private function success($data = null, $message = 'Success') {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }
    
    private function error($message) {
        return [
            'success' => false,
            'message' => $message,
            'data' => null
        ];
    }
}

// Initialize and handle request
$api = new InvoiceAPI();
echo json_encode($api->handleRequest());
?>