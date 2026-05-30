<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CatalogController;
use App\Controllers\AccountingController;
use App\Controllers\DashboardController;
use App\Controllers\DocumentController;
use App\Controllers\DocumentIntakeController;
use App\Controllers\EstimateController;
use App\Controllers\CustomerController;
use App\Controllers\IntakeController;
use App\Controllers\InvoiceController;
use App\Controllers\PaymentController;
use App\Controllers\PublicEstimateController;
use App\Controllers\PublicInvoiceController;
use App\Controllers\PublicLocationController;
use App\Controllers\PublicStatusController;
use App\Controllers\ProfileController;
use App\Controllers\ReceiptController;
use App\Controllers\ReportController;
use App\Controllers\ServiceRequestController;
use App\Controllers\ServiceReportController;
use App\Controllers\SquareSettingsController;
use App\Controllers\VehicleController;
use App\Controllers\VendorController;
use App\Controllers\VendorDocumentController;
use App\Controllers\WorkOrderController;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Env;
use App\Core\MigrationRunner;
use App\Core\Router;
use App\Core\View;
use App\Models\User;

require dirname(__DIR__) . '/app/bootstrap.php';

Env::load(dirname(__DIR__) . '/.env');

$appDebug = filter_var((string) (Env::get('APP_DEBUG', '0')), FILTER_VALIDATE_BOOLEAN);
ini_set('log_errors', '1');
ini_set('display_errors', $appDebug ? '1' : '0');
error_reporting(E_ALL);

set_exception_handler(function (\Throwable $e) use ($appDebug): void {
    error_log(sprintf(
        '[uncaught] %s: %s at %s:%d',
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<h1>Application error</h1>';
    if ($appDebug) {
        echo '<p><strong>' . htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8') . '</strong>: '
            . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p>at <code>' . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') . ':'
            . (int) $e->getLine() . '</code></p>';
        echo '<pre style="white-space:pre-wrap;background:#111;color:#fafafa;padding:1rem;border-radius:.5rem;overflow:auto;">'
            . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        echo '<p>The page failed to render. The error has been written to the PHP error log. '
            . 'Set <code>APP_DEBUG=true</code> in <code>.env</code> to see details here.</p>';
    }
});

$requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$staticFile = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, rawurldecode($requestedPath));

if (PHP_SAPI === 'cli-server' && is_file($staticFile)) {
    return false;
}

try {
    (new MigrationRunner(Database::connection()))->run(dirname(__DIR__) . '/database/migrations');
} catch (Throwable $exception) {
    http_response_code(500);
    echo '<h1>Application setup failed</h1>';
    echo '<p>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}

Auth::startSession();

// Auth guard: redirect unauthenticated requests to /setup (when no users exist) or /login.
$pathOnly = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (!Auth::isPublicPath($pathOnly) && !Auth::check()) {
    $target = (new User())->count() === 0 ? '/setup' : '/login';
    header('Location: ' . $target);
    exit;
}

$router = new Router();
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/setup', [AuthController::class, 'showSetup']);
$router->post('/setup', [AuthController::class, 'setup']);
$router->post('/forgot-password', [AuthController::class, 'requestPasswordReset']);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'applyPasswordReset']);

$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

$router->get('/profile', [ProfileController::class, 'show']);
$router->post('/profile', [ProfileController::class, 'update']);
$router->post('/profile/password', [ProfileController::class, 'updatePassword']);
$router->get('/users/{id}/profile', [ProfileController::class, 'show']);
$router->post('/users/{id}/profile', [ProfileController::class, 'update']);
$router->post('/users/{id}/profile/password', [ProfileController::class, 'updatePassword']);

$router->get('/intake', [IntakeController::class, 'index']);
$router->get('/intake/new', [IntakeController::class, 'new']);
$router->post('/intake', [IntakeController::class, 'create']);
$router->get('/intake/{id}/edit', [IntakeController::class, 'edit']);
$router->post('/intake/{id}', [IntakeController::class, 'update']);
$router->get('/intake/{id}', [IntakeController::class, 'show']);
$router->post('/intake/{id}/convert', [IntakeController::class, 'convert']);

$router->get('/service-requests', [ServiceRequestController::class, 'index']);
$router->get('/service-requests/new', [ServiceRequestController::class, 'new']);
$router->post('/service-requests', [ServiceRequestController::class, 'create']);
$router->get('/service-requests/{id}/edit', [ServiceRequestController::class, 'edit']);
$router->post('/service-requests/{id}', [ServiceRequestController::class, 'update']);
$router->get('/service-requests/{id}/proof-packet', [ServiceRequestController::class, 'proofPacket']);
$router->get('/service-requests/{id}', [ServiceRequestController::class, 'show']);
$router->post('/service-requests/{id}/status', [ServiceRequestController::class, 'updateStatus']);
$router->post('/service-requests/{id}/status-link', [ServiceRequestController::class, 'mintStatusLink']);
$router->post('/service-requests/{id}/location-link', [ServiceRequestController::class, 'mintLocationLink']);

$router->get('/customers', [CustomerController::class, 'index']);
$router->get('/customers/{id}', [CustomerController::class, 'show']);

$router->get('/vehicles', [VehicleController::class, 'index']);
$router->get('/vehicles/{id}', [VehicleController::class, 'show']);

$router->get('/vendors', [VendorController::class, 'index']);
$router->get('/vendors/new', [VendorController::class, 'new']);
$router->post('/vendors', [VendorController::class, 'create']);
$router->get('/vendors/{id}/edit', [VendorController::class, 'edit']);
$router->post('/vendors/{id}', [VendorController::class, 'update']);
$router->get('/vendors/{id}', [VendorController::class, 'show']);

$router->get('/document-intake', [DocumentIntakeController::class, 'index']);
$router->get('/document-intake/upload', [DocumentIntakeController::class, 'upload']);
$router->post('/document-intake', [DocumentIntakeController::class, 'store']);
$router->get('/document-intake/{id}/review', [DocumentIntakeController::class, 'review']);
$router->post('/document-intake/{id}/save-draft', [DocumentIntakeController::class, 'saveDraft']);
$router->post('/document-intake/{id}/approve', [DocumentIntakeController::class, 'approve']);
$router->post('/document-intake/{id}/confirm-duplicate', [DocumentIntakeController::class, 'confirmDuplicate']);
$router->post('/document-intake/{id}/reject', [DocumentIntakeController::class, 'reject']);
$router->post('/document-intake/{id}/reprocess', [DocumentIntakeController::class, 'reprocess']);
$router->get('/document-intake/{id}/file', [DocumentIntakeController::class, 'downloadFile']);

$router->get('/vendor-documents', [VendorDocumentController::class, 'index']);
$router->get('/vendor-documents/upload', [VendorDocumentController::class, 'upload']);
$router->post('/vendor-documents', [VendorDocumentController::class, 'store']);
$router->post('/vendor-documents/{id}/lines', [VendorDocumentController::class, 'addLine']);
$router->post('/vendor-documents/{id}/lines/{lineId}', [VendorDocumentController::class, 'updateLine']);
$router->post('/vendor-documents/{id}/lines/{lineId}/delete', [VendorDocumentController::class, 'deleteLine']);
$router->post('/vendor-documents/{id}/review', [VendorDocumentController::class, 'markReview']);
$router->post('/vendor-documents/{id}/approve', [VendorDocumentController::class, 'approve']);
$router->post('/vendor-documents/{id}/post', [VendorDocumentController::class, 'post']);
$router->get('/vendor-documents/{id}', [VendorDocumentController::class, 'show']);

$router->get('/catalog/services', [CatalogController::class, 'services']);
$router->get('/catalog/services/new', [CatalogController::class, 'newService']);
$router->post('/catalog/services', [CatalogController::class, 'createService']);
$router->get('/catalog/services/{id}/edit', [CatalogController::class, 'editService']);
$router->post('/catalog/services/{id}', [CatalogController::class, 'updateService']);

$router->get('/catalog/items', [CatalogController::class, 'items']);
$router->get('/catalog/items/new', [CatalogController::class, 'newItem']);
$router->post('/catalog/items', [CatalogController::class, 'createItem']);
$router->get('/catalog/items/{id}/edit', [CatalogController::class, 'editItem']);
$router->post('/catalog/items/{id}', [CatalogController::class, 'updateItem']);

$router->get('/estimates', [EstimateController::class, 'index']);
$router->get('/estimates/new', [EstimateController::class, 'new']);
$router->post('/estimates', [EstimateController::class, 'create']);
$router->get('/estimates/{id}', [EstimateController::class, 'show']);
$router->post('/estimates/{id}/status', [EstimateController::class, 'updateStatus']);
$router->post('/estimates/{id}/approve', [EstimateController::class, 'approve']);
$router->post('/estimates/{id}/lines', [EstimateController::class, 'addLine']);
$router->post('/estimates/{id}/public-link', [EstimateController::class, 'mintPublicLink']);
$router->post('/estimates/{id}/documents/generate', [DocumentController::class, 'generateEstimate']);

$router->get('/work-orders', [WorkOrderController::class, 'index']);
$router->post('/work-orders/from-estimate/{id}', [WorkOrderController::class, 'createFromEstimate']);
$router->get('/work-orders/{id}', [WorkOrderController::class, 'show']);
$router->get('/work-orders/{id}/field', [WorkOrderController::class, 'field']);
$router->post('/work-orders/{id}/status', [WorkOrderController::class, 'updateStatus']);
$router->post('/work-orders/{id}/arrived', [WorkOrderController::class, 'markArrived']);
$router->post('/work-orders/{id}/documents/generate', [DocumentController::class, 'generateWorkOrder']);

$router->get('/service-reports/new', [ServiceReportController::class, 'new']);
$router->post('/service-reports', [ServiceReportController::class, 'create']);
$router->post('/service-reports/{id}/attachments', [ServiceReportController::class, 'uploadAttachment']);
$router->get('/service-reports/{id}', [ServiceReportController::class, 'show']);
$router->post('/service-reports/{id}/documents/generate', [DocumentController::class, 'generateServiceCompletion']);

$router->get('/invoices', [InvoiceController::class, 'index']);
$router->post('/invoices/from-service-report/{id}', [InvoiceController::class, 'createFromServiceReport']);
$router->post('/invoices/{id}/issue', [InvoiceController::class, 'issue']);
$router->post('/invoices/{id}/public-link', [InvoiceController::class, 'mintPublicLink']);
$router->post('/invoices/{id}/documents/generate', [DocumentController::class, 'generateInvoice']);
$router->get('/invoices/{id}', [InvoiceController::class, 'show']);

$router->get('/payments', [PaymentController::class, 'index']);
$router->get('/payments/new', [PaymentController::class, 'new']);
$router->post('/payments', [PaymentController::class, 'create']);
$router->get('/payments/{id}', [PaymentController::class, 'show']);

$router->get('/receipts/{id}', [ReceiptController::class, 'show']);
$router->post('/receipts/{id}/documents/generate', [DocumentController::class, 'generateReceipt']);

$router->post('/service-requests/{id}/proof-packet/documents/generate', [DocumentController::class, 'generateProofPacket']);
$router->get('/documents/{id}/download', [DocumentController::class, 'download']);
$router->post('/documents/{id}/regenerate', [DocumentController::class, 'regenerate']);

$router->get('/accounting/accounts', [AccountingController::class, 'accounts']);
$router->get('/accounting/ledger', [AccountingController::class, 'ledger']);
$router->get('/accounting/ledger/{id}', [AccountingController::class, 'ledgerEntry']);

$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/revenue', [ReportController::class, 'revenue']);
$router->get('/reports/payments', [ReportController::class, 'payments']);
$router->get('/reports/unpaid', [ReportController::class, 'unpaid']);
$router->get('/reports/missing-records', [ReportController::class, 'missingRecords']);
$router->get('/reports/gross-margin', [ReportController::class, 'grossMargin']);
$router->get('/reports/lead-sources', [ReportController::class, 'leadSources']);
$router->get('/reports/tax-summary', [ReportController::class, 'taxSummary']);

$router->get('/admin/settings/square', [SquareSettingsController::class, 'show']);
$router->post('/admin/settings/square', [SquareSettingsController::class, 'update']);

// Customer portal — public, token-gated.
$router->get('/p/estimate/{token}', [PublicEstimateController::class, 'show']);
$router->post('/p/estimate/{token}/approve', [PublicEstimateController::class, 'approve']);
$router->post('/p/estimate/{token}/decline', [PublicEstimateController::class, 'decline']);
$router->get('/p/invoice/{token}', [PublicInvoiceController::class, 'show']);
$router->get('/p/status/{token}', [PublicStatusController::class, 'show']);
$router->get('/p/location/{token}', [PublicLocationController::class, 'show']);
$router->post('/p/location/{token}/confirm', [PublicLocationController::class, 'confirm']);
$router->post('/p/location/{token}/update', [PublicLocationController::class, 'update']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
