<?php

declare(strict_types=1);

/**
 * Seed realistic demo data so the operator can explore the full workflow.
 *
 * Creates: catalog items, vendors, customers + vehicles, and six service
 * requests covering every lifecycle stage from "just opened" through
 * "completed and paid". All data goes through the same models the UI uses,
 * so audit logs, numbering, and accounting entries are populated.
 *
 * Run again to add additional records (Customer::createIfMissing dedupes by
 * phone). To start fresh, delete storage/app.sqlite and re-run.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Env;
use App\Core\MigrationRunner;
use App\Models\AuditLog;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\CustomerApproval;
use App\Models\Estimate;
use App\Models\EstimateLineItem;
use App\Models\Intake;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\ServiceCompletionReport;
use App\Models\ServiceRequest;
use App\Models\Vehicle;
use App\Models\Vendor;
use App\Models\WorkOrder;
use App\Services\AccountingService;

Env::load(dirname(__DIR__) . '/.env');
$db = Database::connection();
(new MigrationRunner($db))->run(dirname(__DIR__) . '/database/migrations');

$created = [
    'catalog_items' => 0,
    'vendors' => 0,
    'customers' => 0,
    'vehicles' => 0,
    'intakes' => 0,
    'service_requests' => 0,
    'estimates' => 0,
    'approvals' => 0,
    'work_orders' => 0,
    'service_reports' => 0,
    'invoices' => 0,
    'payments' => 0,
    'receipts' => 0,
];

// --- Catalog -------------------------------------------------------------
$catalog = new CatalogItem();
$catalogIds = [];
$catalogSeed = [
    ['SVC-JUMP',    'service',  'Battery Jumpstart',          'Roadside',        '85.00',  'flat_rate', 0],
    ['SVC-TIRE',    'service',  'Tire Change',                'Roadside',       '125.00',  'flat_rate', 0],
    ['SVC-LOCKOUT', 'service',  'Lockout Service',            'Roadside',        '95.00',  'flat_rate', 0],
    ['SVC-FUEL',    'service',  'Fuel Delivery',              'Roadside',        '75.00',  'flat_rate', 0],
    ['SVC-TOW',     'service',  'Short-Distance Tow',         'Recovery',       '175.00',  'starting_at', 0],
    ['PRT-BATTERY', 'part',     'Replacement Battery 12V',    'Parts',          '149.99',  'flat_rate', 1],
    ['MTL-FUEL5',   'material', 'Gasoline (5 gal)',           'Materials',       '24.95',  'flat_rate', 1],
    ['FEE-AFTER',   'fee',      'After-Hours Surcharge',      'Fees',            '35.00',  'flat_rate', 0],
];
foreach ($catalogSeed as [$sku, $type, $name, $cat, $price, $priceType, $taxable]) {
    $catalogIds[$sku] = $catalog->create([
        'sku' => $sku,
        'item_type' => $type,
        'name' => $name,
        'category' => $cat,
        'price' => $price,
        'price_type' => $priceType,
        'taxable' => $taxable,
        'status' => 'active',
        'short_description' => $name,
        'long_description' => '',
    ]);
    $created['catalog_items']++;
}

// --- Vendors -------------------------------------------------------------
$vendors = new Vendor();
$vendorIds = [];
$vendorIds['napa'] = $vendors->create([
    'name' => 'NAPA Auto Parts - Northside',
    'phone' => '(555) 700-1100',
    'email' => 'orders@napa-northside.example.com',
    'website' => 'https://www.napaonline.com',
    'address' => '210 Industrial Blvd, Northside, TX 75002',
    'notes' => 'Net-30 account #4471',
    'status' => 'active',
]);
$vendorIds['costco'] = $vendors->create([
    'name' => 'Costco Business Center',
    'phone' => '(555) 700-2200',
    'email' => '',
    'website' => '',
    'address' => '500 Wholesale Way, Plano, TX 75024',
    'notes' => 'Bulk fuel + cleaning supplies',
    'status' => 'active',
]);
$created['vendors'] += 2;

// --- Customers + vehicles -------------------------------------------------
$customer = new Customer();
$vehicle = new Vehicle();
$people = [
    [
        'first' => 'Maria', 'last' => 'Alvarez', 'phone' => '(214) 555-0181',
        'vehicles' => [
            ['year' => '2019', 'make' => 'Honda',  'model' => 'CR-V',     'color' => 'Silver', 'vin' => '5J6RW2H56KL012345', 'plate' => 'KLM-228'],
        ],
    ],
    [
        'first' => 'James',  'last' => 'Okafor',  'phone' => '(469) 555-0210',
        'vehicles' => [
            ['year' => '2017', 'make' => 'Ford',   'model' => 'F-150',    'color' => 'Black',  'vin' => '1FTEW1EP0HKE54321', 'plate' => 'TRK-991'],
            ['year' => '2022', 'make' => 'Toyota', 'model' => 'Corolla',  'color' => 'White',  'vin' => null,                'plate' => 'NEW-110'],
        ],
    ],
    [
        'first' => 'Priya',  'last' => 'Shah',    'phone' => '(972) 555-0344',
        'vehicles' => [
            ['year' => '2020', 'make' => 'Tesla',  'model' => 'Model 3',  'color' => 'Red',    'vin' => '5YJ3E1EA7LF112233', 'plate' => 'EV-7K2'],
        ],
    ],
    [
        'first' => 'Daniel', 'last' => 'Webb',    'phone' => '(817) 555-0492',
        'vehicles' => [
            ['year' => '2016', 'make' => 'Chevy',  'model' => 'Silverado','color' => 'Blue',   'vin' => '1GCVKREC0GZ334455', 'plate' => 'SIL-204'],
        ],
    ],
    [
        'first' => 'Aisha',  'last' => 'Khan',    'phone' => '(214) 555-0566',
        'vehicles' => [
            ['year' => '2018', 'make' => 'Subaru', 'model' => 'Outback',  'color' => 'Green',  'vin' => null,                'plate' => null],
        ],
    ],
];

$customerIds = [];
$vehicleIds = [];
foreach ($people as $i => $p) {
    $cid = $customer->createIfMissing($p['first'], $p['last'], $p['phone']);
    $customerIds[$i] = $cid;
    $created['customers']++;

    foreach ($p['vehicles'] as $v) {
        $stmt = $db->prepare(
            'INSERT INTO vehicles (customer_id, vin, plate_number, year, make, model, color, no_plate_flag, created_at, updated_at)
             VALUES (:c, :vin, :plate, :y, :mk, :md, :col, :np, :now, :now)'
        );
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            'c' => $cid,
            'vin' => $v['vin'],
            'plate' => $v['plate'],
            'y' => $v['year'],
            'mk' => $v['make'],
            'md' => $v['model'],
            'col' => $v['color'],
            'np' => $v['plate'] ? 0 : 1,
            'now' => $now,
        ]);
        $vehicleIds[] = (int) $db->lastInsertId();
        $created['vehicles']++;
    }
}

// --- Helper for a fresh location row -------------------------------------
$loc = new Location();
$mkLoc = function (string $addr, string $city, string $state, string $zip) use ($loc): int {
    return $loc->create([
        'location_address' => $addr,
        'location_city' => $city,
        'location_state' => $state,
        'location_postal_code' => $zip,
    ]);
};

// --- Story A: standalone intake (NOT yet converted) -----------------------
$intakeId = (new Intake())->create([
    'first_name' => 'Walk',
    'last_name' => 'Inlead',
    'phone' => '(214) 555-0700',
    'service_requested' => 'Battery jump',
    'location_address' => '88 Greenline Ave',
    'location_city' => 'Dallas',
    'location_state' => 'TX',
    'location_postal_code' => '75201',
    'vehicle_year' => '2014',
    'vehicle_make' => 'Nissan',
    'vehicle_model' => 'Altima',
    'vehicle_color' => 'Gray',
    'lead_source' => 'google_ads',
    'notes' => 'Caller said battery clicks when starting.',
]);
$created['intakes']++;

// --- Story B: pending SR (no estimate yet) --------------------------------
$sr = new ServiceRequest();
$srB = $sr->create([
    'customer_id' => $customerIds[0],
    'vehicle_id' => $vehicleIds[0],
    'location_id' => $mkLoc('1450 Mockingbird Ln', 'Dallas', 'TX', '75205'),
    'requested_service' => 'Tire change - rear driver',
    'problem_description' => 'Flat after curb impact. Customer waiting on side road.',
    'priority' => 'urgent',
    'lead_source' => 'direct',
]);
$created['service_requests']++;
(new AuditLog())->record('created_directly', 'service_request', $srB, null, ['status' => 'pending']);

// --- Story C: estimate sent, awaiting approval ----------------------------
$srC = $sr->create([
    'customer_id' => $customerIds[1],
    'vehicle_id' => $vehicleIds[1],
    'location_id' => $mkLoc('220 Industrial Blvd', 'Plano', 'TX', '75074'),
    'requested_service' => 'Battery replacement',
    'problem_description' => 'Will not hold a charge, needs new battery installed onsite.',
    'priority' => 'normal',
    'lead_source' => 'referral',
]);
$created['service_requests']++;
$sr->updateStatus($srC, 'accepted');
$estC = (new Estimate())->createFromServiceRequest($sr->findWithDetails($srC));
$created['estimates']++;
(new EstimateLineItem())->create($estC, [
    'catalog_item_id' => $catalogIds['SVC-JUMP'], 'line_type' => 'service',
    'description' => 'Battery diagnostic and removal labor',
    'quantity' => 1, 'unit_price' => 85.00, 'taxable' => 0,
]);
(new EstimateLineItem())->create($estC, [
    'catalog_item_id' => $catalogIds['PRT-BATTERY'], 'line_type' => 'part',
    'description' => 'Replacement Battery 12V',
    'quantity' => 1, 'unit_price' => 149.99, 'taxable' => 1,
]);
(new Estimate())->recalculate($estC);
(new Estimate())->updateStatus($estC, 'sent');

// --- Story D: approved + dispatched (work in progress) --------------------
$srD = $sr->create([
    'customer_id' => $customerIds[2],
    'vehicle_id' => $vehicleIds[3],
    'location_id' => $mkLoc('7800 Preston Rd', 'Frisco', 'TX', '75034'),
    'requested_service' => 'Lockout - keys in trunk',
    'problem_description' => 'Tesla Model 3 with active key in trunk after closure.',
    'priority' => 'urgent',
    'lead_source' => 'direct',
]);
$created['service_requests']++;
$sr->updateStatus($srD, 'accepted');
$estD = (new Estimate())->createFromServiceRequest($sr->findWithDetails($srD));
$created['estimates']++;
(new EstimateLineItem())->create($estD, [
    'catalog_item_id' => $catalogIds['SVC-LOCKOUT'], 'line_type' => 'service',
    'description' => 'Lockout service - Tesla', 'quantity' => 1, 'unit_price' => 95.00, 'taxable' => 0,
]);
(new EstimateLineItem())->create($estD, [
    'catalog_item_id' => $catalogIds['FEE-AFTER'], 'line_type' => 'fee',
    'description' => 'After-hours surcharge', 'quantity' => 1, 'unit_price' => 35.00, 'taxable' => 0,
]);
(new Estimate())->recalculate($estD);
(new Estimate())->updateStatus($estD, 'sent');
$estDFull = (new Estimate())->findWithDetails($estD);
(new CustomerApproval())->createForEstimate($estDFull, 'Priya Shah', 'phone_confirmed');
(new Estimate())->updateStatus($estD, 'approved');
$created['approvals']++;
$estDFull = (new Estimate())->findWithDetails($estD);
$woD = (new WorkOrder())->createFromEstimate($estDFull);
$created['work_orders']++;
(new WorkOrder())->updateStatus($woD, 'dispatched');
(new WorkOrder())->markArrived($woD);

// --- Story E: completed, invoiced, partially paid -------------------------
$srE = $sr->create([
    'customer_id' => $customerIds[3],
    'vehicle_id' => $vehicleIds[4],
    'location_id' => $mkLoc('3300 Mainland Hwy', 'Garland', 'TX', '75040'),
    'requested_service' => 'Out of fuel',
    'problem_description' => 'Customer ran out of gas on the way home from work.',
    'priority' => 'normal',
    'lead_source' => 'google_ads',
]);
$created['service_requests']++;
$sr->updateStatus($srE, 'accepted');
$estE = (new Estimate())->createFromServiceRequest($sr->findWithDetails($srE));
$created['estimates']++;
(new EstimateLineItem())->create($estE, [
    'catalog_item_id' => $catalogIds['SVC-FUEL'], 'line_type' => 'service',
    'description' => 'Fuel delivery service', 'quantity' => 1, 'unit_price' => 75.00, 'taxable' => 0,
]);
(new EstimateLineItem())->create($estE, [
    'catalog_item_id' => $catalogIds['MTL-FUEL5'], 'line_type' => 'material',
    'description' => 'Gasoline (5 gallons)', 'quantity' => 1, 'unit_price' => 24.95, 'taxable' => 1,
]);
(new Estimate())->recalculate($estE);
$estEFull = (new Estimate())->findWithDetails($estE);
(new CustomerApproval())->createForEstimate($estEFull, 'Daniel Webb', 'sms_link');
(new Estimate())->updateStatus($estE, 'approved');
$created['approvals']++;
$estEFull = (new Estimate())->findWithDetails($estE);
$woE = (new WorkOrder())->createFromEstimate($estEFull);
$created['work_orders']++;
(new WorkOrder())->updateStatus($woE, 'dispatched');
(new WorkOrder())->markArrived($woE);
(new WorkOrder())->updateStatus($woE, 'completed');
$woEFull = (new WorkOrder())->findWithDetails($woE);
$rptE = (new ServiceCompletionReport())->createFromWorkOrder($woEFull, [
    'actual_work_performed' => 'Delivered 5 gallons of regular unleaded. Confirmed engine start before departure.',
    'technician_notes' => 'No spillage. Customer happy.',
    'customer_notes' => null,
    'odometer' => '92500',
    'vin_captured' => '1GCVKREC0GZ334455',
    'no_vehicle_serviced_flag' => 0,
    'completion_status' => 'completed',
]);
$created['service_reports']++;
$rptEFull = (new ServiceCompletionReport())->findWithDetails($rptE);
$invE = (new Invoice())->createFromServiceReport($rptEFull);
$created['invoices']++;
(new Invoice())->issue($invE);
$invEFull = (new Invoice())->findWithDetails($invE);
$resE = (new Payment())->record($invEFull, 'card', 50.00, 'DEMO-CARD-001');
(new AccountingService())->postInvoice($invE);
(new AccountingService())->postPayment($resE['payment_id']);
$created['payments']++;
$created['receipts']++;

// --- Story F: completed, invoiced, paid in full ---------------------------
$srF = $sr->create([
    'customer_id' => $customerIds[4],
    'vehicle_id' => $vehicleIds[5],
    'location_id' => $mkLoc('19 Caruth Park Dr', 'Dallas', 'TX', '75225'),
    'requested_service' => 'Tire change with parts',
    'problem_description' => 'Spare not available; needs replacement tire mounted.',
    'priority' => 'normal',
    'lead_source' => 'repeat_customer',
]);
$created['service_requests']++;
$sr->updateStatus($srF, 'accepted');
$estF = (new Estimate())->createFromServiceRequest($sr->findWithDetails($srF));
$created['estimates']++;
(new EstimateLineItem())->create($estF, [
    'catalog_item_id' => $catalogIds['SVC-TIRE'], 'line_type' => 'service',
    'description' => 'Tire change service', 'quantity' => 1, 'unit_price' => 125.00, 'taxable' => 0,
]);
(new Estimate())->recalculate($estF);
$estFFull = (new Estimate())->findWithDetails($estF);
(new CustomerApproval())->createForEstimate($estFFull, 'Aisha Khan', 'onsite_signature');
(new Estimate())->updateStatus($estF, 'approved');
$created['approvals']++;
$estFFull = (new Estimate())->findWithDetails($estF);
$woF = (new WorkOrder())->createFromEstimate($estFFull);
$created['work_orders']++;
(new WorkOrder())->updateStatus($woF, 'completed');
$woFFull = (new WorkOrder())->findWithDetails($woF);
$rptF = (new ServiceCompletionReport())->createFromWorkOrder($woFFull, [
    'actual_work_performed' => 'Removed flat, installed customer-supplied spare, torqued lug nuts to spec.',
    'technician_notes' => 'Recommended customer get spare replaced soon.',
    'customer_notes' => 'Five-star service!',
    'odometer' => '64200',
    'vin_captured' => '',
    'no_vehicle_serviced_flag' => 0,
    'completion_status' => 'completed',
]);
$created['service_reports']++;
$rptFFull = (new ServiceCompletionReport())->findWithDetails($rptF);
$invF = (new Invoice())->createFromServiceReport($rptFFull);
$created['invoices']++;
(new Invoice())->issue($invF);
$invFFull = (new Invoice())->findWithDetails($invF);
$resF = (new Payment())->record($invFFull, 'cash', (float) $invFFull['total'], null);
(new AccountingService())->postInvoice($invF);
(new AccountingService())->postPayment($resF['payment_id']);
$created['payments']++;
$created['receipts']++;

// --- Summary -------------------------------------------------------------
echo "Demo data seeded:\n";
foreach ($created as $k => $n) {
    printf("  %-20s +%d\n", $k, $n);
}
echo "\nLog in and explore:\n";
echo "  /intake             one fresh intake awaiting conversion\n";
echo "  /service-requests   one pending, one estimate-sent, one in-progress, two completed\n";
echo "  /estimates          drafts, sent, approved variants\n";
echo "  /work-orders        one dispatched (en route), two completed\n";
echo "  /invoices           one partially paid, one paid in full\n";
echo "  /payments           card + cash methods\n";
echo "  /accounting/ledger  invoice + payment journal entries from the two paid invoices\n";
echo "  /catalog/services   eight catalog rows\n";
echo "  /vendors            NAPA + Costco\n";
