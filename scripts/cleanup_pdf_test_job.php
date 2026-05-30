<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Env;

Env::load(dirname(__DIR__) . '/.env');
$db = Database::connection();

$ids = json_decode($argv[1], true);
if (!is_array($ids)) {
    fwrite(STDERR, "Pass IDs JSON as argv[1]\n");
    exit(1);
}

$root = dirname(__DIR__);

$attachments = $db->prepare(
    "SELECT file_path FROM file_attachments WHERE
        (related_type = 'generated_document' AND related_id IN (
            SELECT id FROM generated_documents WHERE
                (related_type = 'service_request' AND related_id = :sr) OR
                (related_type = 'estimate' AND related_id = :est) OR
                (related_type = 'invoice' AND related_id = :inv) OR
                (related_type = 'receipt' AND related_id = :rct)
        ))"
);
$attachments->execute([
    'sr' => $ids['service_request_id'],
    'est' => $ids['estimate_id'],
    'inv' => $ids['invoice_id'],
    'rct' => $ids['receipt_id'],
]);
$files = $attachments->fetchAll(\PDO::FETCH_COLUMN);

$db->beginTransaction();
try {
    // generated_documents.file_attachment_id has a FK to file_attachments.id, so
    // delete the generated_documents rows first to drop the reference, then the
    // file_attachments rows they pointed at.
    $params = [
        'sr' => $ids['service_request_id'],
        'est' => $ids['estimate_id'],
        'inv' => $ids['invoice_id'],
        'rct' => $ids['receipt_id'],
    ];
    $attachmentIdRows = $db->prepare(
        "SELECT file_attachment_id FROM generated_documents WHERE
            file_attachment_id IS NOT NULL AND (
                (related_type = 'service_request' AND related_id = :sr) OR
                (related_type = 'estimate' AND related_id = :est) OR
                (related_type = 'invoice' AND related_id = :inv) OR
                (related_type = 'receipt' AND related_id = :rct)
            )"
    );
    $attachmentIdRows->execute($params);
    $attachmentIds = $attachmentIdRows->fetchAll(\PDO::FETCH_COLUMN);

    $db->prepare(
        "DELETE FROM generated_documents WHERE
            (related_type = 'service_request' AND related_id = :sr) OR
            (related_type = 'estimate' AND related_id = :est) OR
            (related_type = 'invoice' AND related_id = :inv) OR
            (related_type = 'receipt' AND related_id = :rct)"
    )->execute($params);

    if ($attachmentIds) {
        $placeholders = implode(',', array_fill(0, count($attachmentIds), '?'));
        $db->prepare("DELETE FROM file_attachments WHERE id IN ({$placeholders})")
            ->execute($attachmentIds);
    }

    // Ledger entries for this invoice and payment.
    $db->prepare(
        "DELETE FROM ledger_entry_lines WHERE ledger_entry_id IN (
            SELECT id FROM ledger_entries WHERE
                (source_type = 'invoice' AND source_id = :inv) OR
                (source_type = 'payment' AND source_id = :pay)
        )"
    )->execute(['inv' => $ids['invoice_id'], 'pay' => $ids['payment_id']]);
    $db->prepare(
        "DELETE FROM ledger_entries WHERE
            (source_type = 'invoice' AND source_id = :inv) OR
            (source_type = 'payment' AND source_id = :pay)"
    )->execute(['inv' => $ids['invoice_id'], 'pay' => $ids['payment_id']]);

    $db->prepare('DELETE FROM receipts WHERE id = :id')->execute(['id' => $ids['receipt_id']]);
    $db->prepare('DELETE FROM payments WHERE id = :id')->execute(['id' => $ids['payment_id']]);
    $db->prepare('DELETE FROM invoice_line_items WHERE invoice_id = :id')->execute(['id' => $ids['invoice_id']]);
    $db->prepare('DELETE FROM invoices WHERE id = :id')->execute(['id' => $ids['invoice_id']]);
    $db->prepare('DELETE FROM service_completion_reports WHERE id = :id')->execute(['id' => $ids['report_id']]);
    $db->prepare('DELETE FROM work_orders WHERE id = :id')->execute(['id' => $ids['work_order_id']]);
    $db->prepare('DELETE FROM customer_approvals WHERE estimate_id = :id')->execute(['id' => $ids['estimate_id']]);
    $db->prepare('DELETE FROM estimate_line_items WHERE estimate_id = :id')->execute(['id' => $ids['estimate_id']]);
    $db->prepare('DELETE FROM estimates WHERE id = :id')->execute(['id' => $ids['estimate_id']]);
    $db->prepare('DELETE FROM catalog_items WHERE id = :id')->execute(['id' => $ids['catalog_id']]);
    $db->prepare("DELETE FROM audit_logs WHERE related_type IN ('service_request','estimate','invoice','receipt') AND related_id IN (:sr,:est,:inv,:rct)")
        ->execute(['sr' => $ids['service_request_id'], 'est' => $ids['estimate_id'], 'inv' => $ids['invoice_id'], 'rct' => $ids['receipt_id']]);
    $db->prepare('DELETE FROM service_requests WHERE id = :id')->execute(['id' => $ids['service_request_id']]);
    $db->prepare('DELETE FROM locations WHERE id = :id')->execute(['id' => $ids['location_id']]);
    $db->prepare('DELETE FROM vehicles WHERE id = :id')->execute(['id' => $ids['vehicle_id']]);
    $db->prepare('DELETE FROM customers WHERE id = :id')->execute(['id' => $ids['customer_id']]);

    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();
    throw $e;
}

$removedFiles = 0;
foreach ($files as $relPath) {
    $abs = $root . '/' . $relPath;
    if (is_file($abs) && unlink($abs)) {
        $removedFiles++;
    }
}

echo "Cleanup complete. Removed {$removedFiles} PDF file(s).\n";
