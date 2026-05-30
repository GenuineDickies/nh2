<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Env;
use App\Models\GeneratedDocument;
use App\Models\ServiceRequest;

Env::load(dirname(__DIR__) . '/.env');
$db = Database::connection();

[$srId, $estId, $invId, $rctId] = [(int) $argv[1], (int) $argv[2], (int) $argv[3], (int) $argv[4]];
$root = dirname(__DIR__);
$failures = 0;

function check(bool $cond, string $label): void {
    global $failures;
    if ($cond) {
        echo "PASS: {$label}\n";
    } else {
        echo "FAIL: {$label}\n";
        $failures++;
    }
}

$documentTargets = [
    'estimate' => ['estimate', $estId, 'estimate_pdf'],
    'invoice' => ['invoice', $invId, 'invoice_pdf'],
    'receipt' => ['receipt', $rctId, 'receipt_pdf'],
    'proof_packet' => ['service_request', $srId, 'proof_packet_pdf'],
];

$collectedDocIds = [];

foreach ($documentTargets as $label => [$relatedType, $relatedId, $documentType]) {
    $docs = (new GeneratedDocument())->forRelated($relatedType, $relatedId);
    $matching = array_values(array_filter($docs, fn ($d) => $d['document_type'] === $documentType));
    check(count($matching) === 1, "{$label}: exactly one {$documentType} record");

    if (!$matching) continue;
    $doc = $matching[0];
    $collectedDocIds[$label] = (int) $doc['id'];

    check($doc['status'] === 'generated', "{$label}: status=generated");
    check(!empty($doc['file_path']), "{$label}: has file_path");

    $abs = $root . '/' . $doc['file_path'];
    check(is_file($abs), "{$label}: PDF file exists at {$doc['file_path']}");
    if (is_file($abs)) {
        $head = (string) file_get_contents($abs, false, null, 0, 8);
        check(strpos($head, '%PDF-') === 0, "{$label}: file begins with %PDF-");
        $tail = (string) file_get_contents($abs, false, null, max(0, filesize($abs) - 32));
        check(strpos($tail, '%%EOF') !== false, "{$label}: file ends with %%EOF");
    }

    $withFile = (new GeneratedDocument())->findWithFile((int) $doc['id']);
    check(!empty($withFile['file_attachment_id']), "{$label}: file_attachment_id populated");
    check($withFile['mime_type'] === 'application/pdf', "{$label}: mime_type=application/pdf");
    check((int) $withFile['file_size'] > 0, "{$label}: file_size > 0");
}

$auditEvents = (int) $db->query(
    "SELECT COUNT(*) FROM audit_logs WHERE action = 'document_generated' AND related_id IN ({$srId}, {$estId}, {$invId}, {$rctId})"
)->fetchColumn();
check($auditEvents === 4, "Four document_generated audit events for these IDs (got {$auditEvents})");

$packet = (new ServiceRequest())->proofPacket($srId);
$packetDocsWithFiles = array_values(array_filter($packet['documents'], static fn ($d) => !empty($d['file_path'])));
check(count($packetDocsWithFiles) >= 3, 'Proof packet exposes >=3 generated documents with file paths (estimate + invoice + receipt + packet)');

echo "\nDoc IDs: " . json_encode($collectedDocIds) . "\n";

if ($failures > 0) {
    fwrite(STDERR, "FAILURES: {$failures}\n");
    exit(1);
}

echo "ALL CHECKS PASSED\n";
