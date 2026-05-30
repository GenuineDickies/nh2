<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CustomerLinkToken;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;

final class PublicInvoiceController extends Controller
{
    public function show(string $token): void
    {
        $tokenRow = (new CustomerLinkToken())->lookup(
            $token,
            'invoice',
            CustomerLinkToken::PURPOSE_INVOICE_VIEW
        );

        if (!$tokenRow) {
            $this->renderInvalid();
            return;
        }

        $invoice = (new Invoice())->findWithDetails((int) $tokenRow['related_id']);
        if (!$invoice) {
            $this->renderInvalid();
            return;
        }

        $this->view('layouts/public', [
            'title' => 'Invoice ' . $invoice['invoice_number'],
            'content' => 'public/invoice',
            'invoice' => $invoice,
            'lines' => (new InvoiceLineItem())->forInvoice((int) $invoice['id']),
        ]);
    }

    private function renderInvalid(): void
    {
        http_response_code(404);
        $this->view('layouts/public', [
            'title' => 'Link no longer valid',
            'content' => 'public/invalid',
        ]);
    }
}
