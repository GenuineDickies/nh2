<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\CustomerLinkToken;
use App\Models\GeneratedDocument;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\ServiceCompletionReport;
use App\Services\AccountingService;

final class InvoiceController extends Controller
{
    public function index(): void
    {
        $q = $this->query('q', '') ?? '';
        $this->view('layouts/app', [
            'title' => 'Invoices',
            'active' => 'invoices',
            'content' => 'invoices/index',
            'invoices' => (new Invoice())->search($q),
            'q' => $q,
        ]);
    }

    public function createFromServiceReport(string $id): void
    {
        $report = (new ServiceCompletionReport())->findWithDetails((int) $id);

        if (!$report || $report['completion_status'] !== 'completed') {
            $this->redirect('/service-reports/' . (int) $id);
        }

        $invoiceId = (new Invoice())->createFromServiceReport($report);
        (new AuditLog())->record('invoice_created', 'service_request', (int) $report['service_request_id'], null, [
            'invoice_id' => $invoiceId,
            'service_report_id' => (int) $report['id'],
        ]);

        $this->redirect('/invoices/' . $invoiceId);
    }

    public function issue(string $id): void
    {
        $invoiceModel = new Invoice();
        $invoice = $invoiceModel->findWithDetails((int) $id);

        if (!$invoice) {
            $this->redirect('/invoices');
        }

        $change = $invoiceModel->issue((int) $id);
        if ($change) {
            $ledgerEntryId = (new AccountingService())->postInvoice((int) $id);
            (new AuditLog())->record('invoice_issued', 'invoice', (int) $id, [
                'status' => $change['old_status'],
            ], [
                'status' => $change['new_status'],
                'service_request_id' => (int) $invoice['service_request_id'],
                'ledger_entry_id' => $ledgerEntryId,
            ]);
        }

        $this->redirect('/invoices/' . (int) $id);
    }

    public function show(string $id): void
    {
        $invoiceModel = new Invoice();
        $invoice = $invoiceModel->findWithDetails((int) $id);

        if (!$invoice) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Invoice not found',
                'message' => 'That invoice could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => $invoice['invoice_number'],
            'active' => 'invoices',
            'content' => 'invoices/show',
            'invoice' => $invoice,
            'lines' => (new InvoiceLineItem())->forInvoice((int) $id),
            'documents' => (new GeneratedDocument())->forRelated('invoice', (int) $id),
            'publicToken' => (new CustomerLinkToken())->latestForRelated('invoice', (int) $id, CustomerLinkToken::PURPOSE_INVOICE_VIEW),
            'validationErrors' => $invoiceModel->validationErrors($invoice),
        ]);
    }

    public function mintPublicLink(string $id): void
    {
        $invoiceId = (int) $id;
        $invoice = (new Invoice())->findWithDetails($invoiceId);
        if (!$invoice) {
            $this->redirect('/invoices');
        }

        $expires = date('Y-m-d H:i:s', strtotime('+90 days'));
        $token = (new CustomerLinkToken())->mint(
            'invoice',
            $invoiceId,
            CustomerLinkToken::PURPOSE_INVOICE_VIEW,
            false,
            $expires,
            Auth::userId()
        );

        (new AuditLog())->record('customer_link_minted', 'invoice', $invoiceId, null, [
            'purpose' => CustomerLinkToken::PURPOSE_INVOICE_VIEW,
            'expires_at' => $expires,
            'token_suffix' => substr($token, -8),
        ]);

        $this->redirect('/invoices/' . $invoiceId);
    }
}
