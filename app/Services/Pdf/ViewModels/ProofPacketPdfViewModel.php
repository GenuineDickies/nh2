<?php

declare(strict_types=1);

namespace App\Services\Pdf\ViewModels;

use App\Core\View;
use App\Services\Pdf\PdfDataValidator;
use App\Services\Pdf\PdfDocument;
use App\Services\Pdf\PdfMoney;

/**
 * Proof Packet PDF — the end-to-end paper trail for one service request.
 *
 * Source-of-truth fields all come from ServiceRequest::proofPacket():
 *   service_request, estimate, approval, work_order, service_report,
 *   invoice, payments[], receipts[], ledger_entries[], attachments[],
 *   documents[], timeline[], missing_items[]
 *
 * Missing records are shown as "Not yet recorded" rather than being
 * silently omitted, because the absence is itself a piece of audit data.
 */
final class ProofPacketPdfViewModel extends PdfViewModel
{
    private array $packet;

    public function __construct(array $packet)
    {
        parent::__construct();
        $this->packet = $packet;
    }

    public function title(): string
    {
        $sr = $this->packet['service_request'] ?? [];
        return 'Proof Packet ' . ($sr['service_request_number'] ?? '');
    }

    public function fileCaption(): string
    {
        return $this->title();
    }

    public function render(PdfDocument $document): void
    {
        $sr = $this->packet['service_request'] ?? null;
        $validator = new PdfDataValidator();
        if (!$sr) {
            $validator->add('Service request not found');
        } else {
            $validator->require($sr['service_request_number'] ?? null, 'Service request number');
            $validator->require(trim(($sr['first_name'] ?? '') . ' ' . ($sr['last_name'] ?? '')), 'Customer name');
        }
        $validator->failIfAny();

        $createdAt = $sr['created_at'] ?? date('Y-m-d H:i:s');
        $createdTs = strtotime((string) $createdAt) ?: time();

        $this->paintMasthead($document, [
            ['Job', (string) $sr['service_request_number']],
            ['Status', ucwords((string) $sr['status'])],
            ['Date', date('M j, Y', $createdTs)],
            ['Time', date('g:i A', $createdTs)],
        ]);
        $document->banner('Proof Packet');

        $totalsRow = $this->packetTotals();
        $document->metaStrip(
            $this->customerBlock($sr, $sr),
            $this->vehicleTable($sr),
            $totalsRow['rows'],
            $totalsRow['grandLabel']
        );

        $document->detailBar([
            ['Service Type', (string) ($sr['requested_service'] ?? 'Not specified')],
            ['Location', View::address($sr, 'Not captured')],
        ]);

        $document->sectionHeading('Job Lifecycle');
        $document->kvTable($this->lifecycleRows());

        if (!empty($this->packet['payments'])) {
            $document->sectionHeading('Payments');
            $rows = [];
            foreach ($this->packet['payments'] as $payment) {
                $paidTs = strtotime((string) ($payment['paid_at'] ?? '')) ?: 0;
                $rows[] = [
                    (string) ($payment['payment_number'] ?? '—'),
                    $this->formatMethod((string) ($payment['payment_method'] ?? '')),
                    $paidTs ? date('M j, Y', $paidTs) : '—',
                    PdfMoney::format($payment['amount'] ?? 0),
                ];
            }
            $document->chargesTable(
                [
                    ['label' => 'Payment #', 'align' => 'left'],
                    ['label' => 'Method', 'align' => 'left', 'width' => 110.0],
                    ['label' => 'Date', 'align' => 'left', 'width' => 100.0],
                    ['label' => 'Amount', 'align' => 'right', 'width' => 90.0],
                ],
                $rows
            );
        }

        if (!empty($this->packet['ledger_entries'])) {
            $document->sectionHeading('Accounting Entries');
            $rows = [];
            foreach ($this->packet['ledger_entries'] as $entry) {
                $rows[] = [
                    (string) ($entry['entry_number'] ?? '—'),
                    (string) ($entry['source_type'] ?? '—'),
                    PdfMoney::format($entry['debit_total'] ?? 0),
                    PdfMoney::format($entry['credit_total'] ?? 0),
                ];
            }
            $document->chargesTable(
                [
                    ['label' => 'Entry #', 'align' => 'left'],
                    ['label' => 'Source', 'align' => 'left', 'width' => 120.0],
                    ['label' => 'Debit', 'align' => 'right', 'width' => 90.0],
                    ['label' => 'Credit', 'align' => 'right', 'width' => 90.0],
                ],
                $rows
            );
        }

        $document->sectionHeading('Photos & Signatures on File');
        $attachments = [];
        foreach ($this->packet['attachments'] ?? [] as $attachment) {
            $caption = trim((string) ($attachment['caption'] ?? ''));
            $label = ucwords(str_replace('_', ' ', (string) $attachment['file_type']))
                . ': ' . ($attachment['original_filename'] ?? 'unnamed');
            if ($caption !== '') {
                $label .= ' — ' . $caption;
            }
            $attachments[] = $label;
        }
        if (!$attachments) {
            $attachments[] = 'No photos or signatures attached';
        }
        $document->bullets($attachments);

        $missing = $this->packet['missing_items'] ?? [];
        $document->sectionHeading('Readiness Check');
        if ($missing) {
            $document->paragraphs(['Outstanding items before this job is fully closed:']);
            $document->bullets(array_map(static fn ($m) => (string) $m, $missing));
        } else {
            $document->paragraphs(['All required records are on file.']);
        }

        $document->footer(
            'This packet is a snapshot of records on file at the time of generation. '
            . 'Underlying records remain authoritative — regenerate this packet to refresh.',
            null,
            null
        );
    }

    /**
     * @return array{rows: array<int, array{0:string,1:string}>, grandLabel: ?string}
     */
    private function packetTotals(): array
    {
        $rows = [];
        $estimateTotal = $this->packet['estimate']['total'] ?? null;
        $invoiceTotal = $this->packet['invoice']['total'] ?? null;
        $paid = $this->packet['invoice']['amount_paid'] ?? null;
        $balance = $this->packet['invoice']['balance_due'] ?? null;

        if ($estimateTotal !== null) {
            $rows[] = ['Estimate', PdfMoney::format($estimateTotal)];
        }
        if ($invoiceTotal !== null) {
            $rows[] = ['Invoice', PdfMoney::format($invoiceTotal)];
        }
        if ($paid !== null) {
            $rows[] = ['Paid', PdfMoney::format($paid)];
        }
        $grandLabel = null;
        if ($balance !== null) {
            $rows[] = ['Balance', PdfMoney::format($balance)];
            $grandLabel = 'Balance';
        }

        if (!$rows) {
            $rows[] = ['Status', 'Not yet billed'];
        }

        return ['rows' => $rows, 'grandLabel' => $grandLabel];
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    private function lifecycleRows(): array
    {
        $rows = [];
        $sr = $this->packet['service_request'];

        $rows[] = [
            'Service Request',
            (string) $sr['service_request_number'] . ' — ' . ucwords((string) $sr['status']),
        ];

        if (!empty($this->packet['estimate'])) {
            $est = $this->packet['estimate'];
            $rows[] = [
                'Estimate',
                (string) $est['estimate_number'] . ' — ' . PdfMoney::format($est['total'] ?? 0),
            ];
        } else {
            $rows[] = ['Estimate', 'Not yet recorded'];
        }

        if (!empty($this->packet['approval'])) {
            $appr = $this->packet['approval'];
            $rows[] = [
                'Customer Approval',
                (string) $appr['approval_number'] . ' — '
                    . ucwords(str_replace('_', ' ', (string) $appr['approval_method'])),
            ];
        } else {
            $rows[] = ['Customer Approval', 'Not yet recorded'];
        }

        if (!empty($this->packet['work_order'])) {
            $wo = $this->packet['work_order'];
            $rows[] = [
                'Work Order',
                (string) $wo['work_order_number'] . ' — ' . ucwords(str_replace('_', ' ', (string) $wo['status'])),
            ];
        } else {
            $rows[] = ['Work Order', 'Not yet recorded'];
        }

        if (!empty($this->packet['service_report'])) {
            $rep = $this->packet['service_report'];
            $rows[] = [
                'Service Completion',
                (string) $rep['report_number'] . ' — ' . ucwords(str_replace('_', ' ', (string) $rep['completion_status'])),
            ];
        } else {
            $rows[] = ['Service Completion', 'Not yet recorded'];
        }

        if (!empty($this->packet['invoice'])) {
            $inv = $this->packet['invoice'];
            $rows[] = [
                'Invoice',
                (string) $inv['invoice_number'] . ' — ' . PdfMoney::format($inv['total'] ?? 0)
                    . ', balance ' . PdfMoney::format($inv['balance_due'] ?? 0),
            ];
        } else {
            $rows[] = ['Invoice', 'Not yet recorded'];
        }

        if (!empty($this->packet['receipts'])) {
            foreach ($this->packet['receipts'] as $receipt) {
                $rows[] = ['Receipt', (string) ($receipt['receipt_number'] ?? '—')];
            }
        }

        return $rows;
    }

    private function formatMethod(string $value): string
    {
        if ($value === '') {
            return 'Unknown';
        }
        return ucwords(str_replace('_', ' ', $value));
    }
}
