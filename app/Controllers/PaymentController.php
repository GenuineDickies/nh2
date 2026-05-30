<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AccountingService;

final class PaymentController extends Controller
{
    public function index(): void
    {
        $this->view('layouts/app', [
            'title' => 'Payments',
            'active' => 'payments',
            'content' => 'payments/index',
            'payments' => (new Payment())->all(),
        ]);
    }

    public function new(): void
    {
        $invoiceId = (int) ($_GET['invoice_id'] ?? 0);
        $invoice = (new Invoice())->findWithDetails($invoiceId);

        if (!$invoice) {
            $this->redirect('/invoices');
        }

        $this->view('layouts/app', [
            'title' => 'Take Payment',
            'active' => 'payments',
            'content' => 'payments/new',
            'invoice' => $invoice,
            'methods' => Payment::METHODS,
            'errors' => [],
            'values' => [
                'payment_method' => 'cash',
                'amount' => number_format((float) $invoice['balance_due'], 2, '.', ''),
                'transaction_reference' => '',
            ],
        ]);
    }

    public function create(): void
    {
        $invoice = (new Invoice())->findWithDetails((int) $this->input('invoice_id', '0'));

        if (!$invoice) {
            $this->redirect('/invoices');
        }

        $method = $this->input('payment_method', 'cash') ?? 'cash';
        $amount = round((float) ($this->input('amount', '0') ?? '0'), 2);
        $reference = $this->input('transaction_reference', '');
        $paymentModel = new Payment();
        $errors = $paymentModel->validationErrors($invoice, $method, $amount);

        if ($errors) {
            $this->view('layouts/app', [
                'title' => 'Take Payment',
                'active' => 'payments',
                'content' => 'payments/new',
                'invoice' => $invoice,
                'methods' => Payment::METHODS,
                'errors' => $errors,
                'values' => [
                    'payment_method' => $method,
                    'amount' => $this->input('amount', '0'),
                    'transaction_reference' => $reference,
                ],
            ]);
            return;
        }

        $result = $paymentModel->record($invoice, $method, $amount, $reference);
        $ledgerEntryId = (new AccountingService())->postPayment((int) $result['payment_id']);
        (new AuditLog())->record('payment_recorded', 'invoice', (int) $invoice['id'], null, [
            'payment_id' => $result['payment_id'],
            'receipt_id' => $result['receipt_id'],
            'ledger_entry_id' => $ledgerEntryId,
            'amount' => $amount,
            'payment_method' => $method,
        ]);

        $this->redirect('/payments/' . $result['payment_id']);
    }

    public function show(string $id): void
    {
        $payment = (new Payment())->findWithDetails((int) $id);

        if (!$payment) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Payment not found',
                'message' => 'That payment could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => $payment['payment_number'],
            'active' => 'payments',
            'content' => 'payments/show',
            'payment' => $payment,
        ]);
    }
}
