<?php

namespace App\Models;

use App\Core\Repository;

final class InvoiceLineItem extends Repository
{
    public function forInvoice(int $invoiceId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM invoice_line_items WHERE invoice_id = :invoice_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['invoice_id' => $invoiceId]);

        return $stmt->fetchAll();
    }

    public function copyFromEstimate(int $invoiceId, int $estimateId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO invoice_line_items (
                invoice_id, catalog_item_id, line_type, description, quantity,
                unit_price, taxable, line_subtotal, sort_order
             )
             SELECT :invoice_id, catalog_item_id, line_type, description, quantity,
                    unit_price, taxable, line_subtotal, sort_order
             FROM estimate_line_items
             WHERE estimate_id = :estimate_id
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([
            'invoice_id' => $invoiceId,
            'estimate_id' => $estimateId,
        ]);
    }
}

