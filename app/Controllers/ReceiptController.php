<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\GeneratedDocument;
use App\Models\Receipt;

final class ReceiptController extends Controller
{
    public function show(string $id): void
    {
        $receipt = (new Receipt())->findWithDetails((int) $id);

        if (!$receipt) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Receipt not found',
                'message' => 'That receipt could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => $receipt['receipt_number'],
            'active' => 'payments',
            'content' => 'receipts/show',
            'receipt' => $receipt,
            'documents' => (new GeneratedDocument())->forRelated('receipt', (int) $id),
        ]);
    }
}
