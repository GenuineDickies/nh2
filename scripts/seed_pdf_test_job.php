<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Env;
use App\Core\MigrationRunner;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\CustomerApproval;
use App\Models\Estimate;
use App\Models\EstimateLineItem;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\ServiceCompletionReport;
use App\Models\ServiceRequest;
use App\Models\WorkOrder;
use App\Services\AccountingService;

Env::load(dirname(__DIR__) . '/.env');
$db = Database::connection();
(new MigrationRunner($db))->run(dirname(__DIR__) . '/database/migrations');

$customerId = (new Customer())->createIfMissing('PdfTest', 'Driver', '555-PDF-TEST');

$stmt = $db->prepare(
    "INSERT INTO vehicles (customer_id, vin, year, make, model, color, no_plate_flag, created_at, updated_at)
     VALUES (:customer_id, :vin, :year, :make, :model, :color, 0, :created_at, :updated_at)"
);
$now = date('Y-m-d H:i:s');
$stmt->execute([
    'customer_id' => $customerId,
    'vin' => '1FTEW1EP0JKE12345',
    'year' => '2018',
    'make' => 'Ford',
    'model' => 'F-150',
    'color' => 'White',
    'created_at' => $now,
    'updated_at' => $now,
]);
$vehicleId = (int) $db->lastInsertId();

$locationId = (new Location())->create([
    'location_address' => '12 PDF Test Lane',
    'location_city' => 'Testville',
    'location_state' => 'TX',
    'location_postal_code' => '75001',
]);

$serviceRequestId = (new ServiceRequest())->create([
    'customer_id' => $customerId,
    'vehicle_id' => $vehicleId,
    'location_id' => $locationId,
    'requested_service' => 'Roadside Tire Change',
    'problem_description' => 'PDF test seed - front passenger tire',
    'priority' => 'normal',
    'lead_source' => 'direct',
]);

$catalogId = (new CatalogItem())->create([
    'sku' => 'SVC-PDF-TIRE',
    'item_type' => 'service',
    'name' => 'PDF Test Tire Change Service',
    'category' => 'Roadside',
    'price' => '175.00',
    'price_type' => 'flat_rate',
    'taxable' => 0,
    'status' => 'active',
    'short_description' => 'Test tire change',
    'long_description' => 'Used by PDF generation test',
]);

$serviceRequest = (new ServiceRequest())->findWithDetails($serviceRequestId);
$estimateId = (new Estimate())->createFromServiceRequest($serviceRequest);
(new EstimateLineItem())->create($estimateId, [
    'catalog_item_id' => $catalogId,
    'line_type' => 'service',
    'description' => 'PDF Test Tire Change Service',
    'quantity' => 1,
    'unit_price' => 175.00,
    'taxable' => 0,
]);
(new Estimate())->recalculate($estimateId);

$estimate = (new Estimate())->findWithDetails($estimateId);
(new CustomerApproval())->createForEstimate($estimate, 'PdfTest Driver', 'phone_confirmed');
(new Estimate())->updateStatus($estimateId, 'approved');

$estimate = (new Estimate())->findWithDetails($estimateId);
$workOrderId = (new WorkOrder())->createFromEstimate($estimate);
$workOrder = (new WorkOrder())->findWithDetails($workOrderId);

$reportId = (new ServiceCompletionReport())->createFromWorkOrder($workOrder, [
    'actual_work_performed' => 'Replaced front passenger tire with spare',
    'technician_notes' => 'PDF test seed',
    'customer_notes' => null,
    'odometer' => '85000',
    'vin_captured' => '1FTEW1EP0JKE12345',
    'no_vehicle_serviced_flag' => 0,
    'completion_status' => 'completed',
]);
$report = (new ServiceCompletionReport())->findWithDetails($reportId);

$invoiceId = (new Invoice())->createFromServiceReport($report);
(new Invoice())->issue($invoiceId);

$invoice = (new Invoice())->findWithDetails($invoiceId);
$result = (new Payment())->record($invoice, 'cash', (float) $invoice['total'], 'PDF-TEST-REF');
$paymentId = (int) $result['payment_id'];
$receiptId = (int) $result['receipt_id'];

(new AccountingService())->postInvoice($invoiceId);
(new AccountingService())->postPayment($paymentId);

echo json_encode([
    'customer_id' => $customerId,
    'vehicle_id' => $vehicleId,
    'location_id' => $locationId,
    'service_request_id' => $serviceRequestId,
    'catalog_id' => $catalogId,
    'estimate_id' => $estimateId,
    'work_order_id' => $workOrderId,
    'report_id' => $reportId,
    'invoice_id' => $invoiceId,
    'payment_id' => $paymentId,
    'receipt_id' => $receiptId,
]) . "\n";
