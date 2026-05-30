<?php

namespace App\Models;

use App\Core\Repository;

final class Report extends Repository
{
    public function summary(): array
    {
        return [
            'revenue' => (float) $this->scalar("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE status IN ('sent', 'partially_paid', 'paid')"),
            'payments' => (float) $this->scalar("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'completed'"),
            'unpaid' => (float) $this->scalar("SELECT COALESCE(SUM(balance_due), 0) FROM invoices WHERE status IN ('sent', 'partially_paid')"),
            'open_invoices' => (int) $this->scalar("SELECT COUNT(*) FROM invoices WHERE status IN ('sent', 'partially_paid') AND balance_due > 0"),
            'jobs_missing_records' => (int) $this->scalar(
                "SELECT COUNT(*)
                 FROM service_requests sr
                 LEFT JOIN estimates e ON e.service_request_id = sr.id
                 LEFT JOIN work_orders wo ON wo.service_request_id = sr.id
                 LEFT JOIN service_completion_reports scr ON scr.service_request_id = sr.id
                 LEFT JOIN invoices i ON i.service_request_id = sr.id
                 WHERE e.id IS NULL OR wo.id IS NULL OR scr.id IS NULL OR i.id IS NULL"
            ),
        ];
    }

    public function revenueByDate(): array
    {
        return $this->db->query(
            "SELECT COALESCE(DATE(issued_at), DATE(created_at)) AS report_date,
                    COUNT(*) AS invoice_count,
                    COALESCE(SUM(subtotal), 0) AS subtotal,
                    COALESCE(SUM(tax_total), 0) AS tax_total,
                    COALESCE(SUM(total), 0) AS total
             FROM invoices
             WHERE status IN ('sent', 'partially_paid', 'paid')
             GROUP BY COALESCE(DATE(issued_at), DATE(created_at))
             ORDER BY report_date DESC"
        )->fetchAll();
    }

    public function paymentsByDate(): array
    {
        return $this->db->query(
            "SELECT DATE(paid_at) AS report_date,
                    payment_method,
                    COUNT(*) AS payment_count,
                    COALESCE(SUM(amount), 0) AS total
             FROM payments
             WHERE payment_status = 'completed'
             GROUP BY DATE(paid_at), payment_method
             ORDER BY report_date DESC, payment_method ASC"
        )->fetchAll();
    }

    public function unpaidInvoices(): array
    {
        return $this->db->query(
            "SELECT i.*, c.first_name, c.last_name, c.phone, sr.service_request_number
             FROM invoices i
             JOIN customers c ON c.id = i.customer_id
             JOIN service_requests sr ON sr.id = i.service_request_id
             WHERE i.status IN ('sent', 'partially_paid') AND i.balance_due > 0
             ORDER BY i.issued_at ASC, i.created_at ASC"
        )->fetchAll();
    }

    public function grossMarginByJob(): array
    {
        return $this->db->query(
            "SELECT sr.id AS service_request_id, sr.service_request_number, sr.requested_service,
                    c.first_name, c.last_name,
                    COALESCE(i.subtotal, 0) AS revenue_subtotal,
                    COALESCE(i.total, 0) AS revenue_total,
                    COALESCE(i.tax_total, 0) AS revenue_tax,
                    COALESCE(parts.parts_cost, 0) AS parts_cost,
                    (COALESCE(i.subtotal, 0) - COALESCE(parts.parts_cost, 0)) AS gross_margin
             FROM service_requests sr
             JOIN customers c ON c.id = sr.customer_id
             LEFT JOIN invoices i ON i.service_request_id = sr.id AND i.status IN ('sent', 'partially_paid', 'paid')
             LEFT JOIN (
                 SELECT vdli.service_request_id, SUM(vdli.line_total) AS parts_cost
                 FROM vendor_document_line_items vdli
                 JOIN vendor_documents vd ON vd.id = vdli.vendor_document_id
                 WHERE vdli.service_request_id IS NOT NULL
                   AND vdli.category IN ('resold_part', 'inventory_part', 'consumable', 'material')
                   AND vd.status = 'posted'
                 GROUP BY vdli.service_request_id
             ) parts ON parts.service_request_id = sr.id
             WHERE i.id IS NOT NULL OR parts.parts_cost > 0
             ORDER BY i.issued_at DESC, sr.created_at DESC, sr.id DESC"
        )->fetchAll();
    }

    public function leadSourceRevenue(): array
    {
        return $this->db->query(
            "SELECT COALESCE(sr.lead_source, 'unknown') AS lead_source,
                    COUNT(DISTINCT sr.id) AS job_count,
                    COUNT(DISTINCT i.id) AS invoice_count,
                    COALESCE(SUM(i.subtotal), 0) AS revenue_subtotal,
                    COALESCE(SUM(i.tax_total), 0) AS revenue_tax,
                    COALESCE(SUM(i.total), 0) AS revenue_total
             FROM service_requests sr
             LEFT JOIN invoices i ON i.service_request_id = sr.id AND i.status IN ('sent', 'partially_paid', 'paid')
             GROUP BY COALESCE(sr.lead_source, 'unknown')
             ORDER BY revenue_total DESC, job_count DESC"
        )->fetchAll();
    }

    public function taxSummary(): array
    {
        // Aggregate by month in PHP so the query stays portable across SQLite and MySQL.
        $invoices = $this->db->query(
            "SELECT COALESCE(issued_at, created_at) AS effective_at, tax_total
             FROM invoices
             WHERE status IN ('sent', 'partially_paid', 'paid')"
        )->fetchAll();

        $vendorDocs = $this->db->query(
            "SELECT COALESCE(posted_at, document_date, uploaded_at) AS effective_at, tax_total
             FROM vendor_documents
             WHERE status = 'posted'"
        )->fetchAll();

        $months = [];
        $ensure = static function (array &$months, string $key): void {
            if (!isset($months[$key])) {
                $months[$key] = [
                    'month_key' => $key,
                    'invoice_count' => 0,
                    'tax_collected' => 0.0,
                    'document_count' => 0,
                    'tax_paid' => 0.0,
                ];
            }
        };

        foreach ($invoices as $row) {
            $key = substr((string) $row['effective_at'], 0, 7);
            if ($key === '') {
                continue;
            }
            $ensure($months, $key);
            $months[$key]['invoice_count']++;
            $months[$key]['tax_collected'] = round($months[$key]['tax_collected'] + (float) $row['tax_total'], 2);
        }

        foreach ($vendorDocs as $row) {
            $key = substr((string) $row['effective_at'], 0, 7);
            if ($key === '') {
                continue;
            }
            $ensure($months, $key);
            $months[$key]['document_count']++;
            $months[$key]['tax_paid'] = round($months[$key]['tax_paid'] + (float) $row['tax_total'], 2);
        }

        foreach ($months as &$month) {
            $month['net'] = round($month['tax_collected'] - $month['tax_paid'], 2);
        }
        unset($month);

        krsort($months);

        return array_values($months);
    }

    public function jobsMissingRecords(): array
    {
        return $this->db->query(
            "SELECT sr.id, sr.service_request_number, sr.requested_service, sr.status,
                    c.first_name, c.last_name,
                    e.id AS estimate_id,
                    wo.id AS work_order_id,
                    scr.id AS service_report_id,
                    i.id AS invoice_id
             FROM service_requests sr
             JOIN customers c ON c.id = sr.customer_id
             LEFT JOIN estimates e ON e.service_request_id = sr.id
             LEFT JOIN work_orders wo ON wo.service_request_id = sr.id
             LEFT JOIN service_completion_reports scr ON scr.service_request_id = sr.id
             LEFT JOIN invoices i ON i.service_request_id = sr.id
             WHERE e.id IS NULL OR wo.id IS NULL OR scr.id IS NULL OR i.id IS NULL
             ORDER BY sr.created_at DESC, sr.id DESC"
        )->fetchAll();
    }

    private function scalar(string $sql)
    {
        return $this->db->query($sql)->fetchColumn();
    }
}
