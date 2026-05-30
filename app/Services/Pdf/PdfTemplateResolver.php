<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use App\Models\Estimate;
use App\Models\EstimateLineItem;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Receipt;
use App\Models\ServiceCompletionReport;
use App\Models\ServiceRequest;
use App\Models\WorkOrder;
use App\Services\Pdf\ViewModels\EstimatePdfViewModel;
use App\Services\Pdf\ViewModels\InvoicePdfViewModel;
use App\Services\Pdf\ViewModels\PdfViewModel;
use App\Services\Pdf\ViewModels\ProofPacketPdfViewModel;
use App\Services\Pdf\ViewModels\ReceiptPdfViewModel;
use App\Services\Pdf\ViewModels\ServiceCompletionReportPdfViewModel;
use App\Services\Pdf\ViewModels\WorkOrderPdfViewModel;

/**
 * Maps `generated_documents.document_type` to a constructed view model.
 *
 * Centralises three concerns that used to live inline in
 * `DocumentController`:
 *   - The list of valid PDF document types (kept in sync with
 *     `GeneratedDocument::TYPES`).
 *   - Loading + recalculation of the source row(s) for each type.
 *   - Mapping each type to the redirect path used by the controller
 *     when the PDF finishes generating (or fails validation).
 *
 * Adding a new PDF type is now a single edit here plus the matching
 * view model — controllers do not grow new switch arms.
 */
final class PdfTemplateResolver
{
    /**
     * Set of document types the system can render today. Kept aligned
     * with `App\Models\GeneratedDocument::TYPES`.
     *
     * @var array<int, string>
     */
    public const TYPES = [
        'estimate_pdf',
        'invoice_pdf',
        'receipt_pdf',
        'work_order_pdf',
        'service_completion_pdf',
        'proof_packet_pdf',
    ];

    /**
     * Load the source records, force any stored totals to recompute,
     * and hand the data to a view model. Returns null when the source
     * row was deleted between the request and the load (caller
     * redirects to a "not found" path).
     */
    public function resolve(string $documentType, int $relatedId): ?PdfViewModel
    {
        switch ($documentType) {
            case 'estimate_pdf':
                $estimateModel = new Estimate();
                $estimateModel->recalculate($relatedId);
                $estimate = $estimateModel->findWithDetails($relatedId);
                if (!$estimate) {
                    return null;
                }
                return new EstimatePdfViewModel(
                    $estimate,
                    (new EstimateLineItem())->forEstimate($relatedId)
                );

            case 'invoice_pdf':
                $invoiceModel = new Invoice();
                $invoiceModel->recalculate($relatedId);
                $invoice = $invoiceModel->findWithDetails($relatedId);
                if (!$invoice) {
                    return null;
                }
                return new InvoicePdfViewModel(
                    $invoice,
                    (new InvoiceLineItem())->forInvoice($relatedId)
                );

            case 'receipt_pdf':
                $receipt = (new Receipt())->findWithDetails($relatedId);
                if (!$receipt) {
                    return null;
                }
                return new ReceiptPdfViewModel($receipt);

            case 'work_order_pdf':
                $workOrder = (new WorkOrder())->findWithDetails($relatedId);
                if (!$workOrder) {
                    return null;
                }
                return new WorkOrderPdfViewModel($workOrder);

            case 'service_completion_pdf':
                $report = (new ServiceCompletionReport())->findWithDetails($relatedId);
                if (!$report) {
                    return null;
                }
                return new ServiceCompletionReportPdfViewModel($report);

            case 'proof_packet_pdf':
                $packet = (new ServiceRequest())->proofPacket($relatedId);
                if (!$packet || empty($packet['service_request'])) {
                    return null;
                }
                return new ProofPacketPdfViewModel($packet);
        }

        return null;
    }

    /**
     * The path the controller redirects to after a successful generate
     * or a validation failure — both want the user back at the source
     * record's detail screen.
     */
    public function successRedirectFor(string $documentType, int $relatedId): string
    {
        switch ($documentType) {
            case 'estimate_pdf':
                return '/estimates/' . $relatedId;
            case 'invoice_pdf':
                return '/invoices/' . $relatedId;
            case 'receipt_pdf':
                return '/receipts/' . $relatedId;
            case 'work_order_pdf':
                return '/work-orders/' . $relatedId;
            case 'service_completion_pdf':
                return '/service-reports/' . $relatedId;
            case 'proof_packet_pdf':
                return '/service-requests/' . $relatedId . '/proof-packet';
        }
        return '/';
    }

    /**
     * The path the controller falls back to when the source row is
     * missing entirely (index of the related listing).
     */
    public function indexRedirectFor(string $documentType): string
    {
        switch ($documentType) {
            case 'estimate_pdf':
                return '/estimates';
            case 'invoice_pdf':
                return '/invoices';
            case 'receipt_pdf':
                return '/payments';
            case 'work_order_pdf':
                return '/work-orders';
            case 'service_completion_pdf':
                return '/service-requests';
            case 'proof_packet_pdf':
                return '/service-requests';
        }
        return '/';
    }

    /** What `generated_documents.related_type` should be set to for a given doc type. */
    public function relatedTypeFor(string $documentType): string
    {
        switch ($documentType) {
            case 'estimate_pdf':
                return 'estimate';
            case 'invoice_pdf':
                return 'invoice';
            case 'receipt_pdf':
                return 'receipt';
            case 'work_order_pdf':
                return 'work_order';
            case 'service_completion_pdf':
                return 'service_completion_report';
            case 'proof_packet_pdf':
                return 'service_request';
        }
        return $documentType;
    }
}
