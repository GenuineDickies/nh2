<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Report;

final class ReportController extends Controller
{
    public function index(): void
    {
        $report = new Report();
        $this->view('layouts/app', [
            'title' => 'Reports',
            'active' => 'reports',
            'content' => 'reports/index',
            'summary' => $report->summary(),
        ]);
    }

    public function revenue(): void
    {
        $this->view('layouts/app', [
            'title' => 'Revenue Report',
            'active' => 'reports',
            'content' => 'reports/revenue',
            'rows' => (new Report())->revenueByDate(),
        ]);
    }

    public function payments(): void
    {
        $this->view('layouts/app', [
            'title' => 'Payments Report',
            'active' => 'reports',
            'content' => 'reports/payments',
            'rows' => (new Report())->paymentsByDate(),
        ]);
    }

    public function unpaid(): void
    {
        $this->view('layouts/app', [
            'title' => 'Unpaid Invoices',
            'active' => 'reports',
            'content' => 'reports/unpaid',
            'invoices' => (new Report())->unpaidInvoices(),
        ]);
    }

    public function missingRecords(): void
    {
        $this->view('layouts/app', [
            'title' => 'Jobs Missing Records',
            'active' => 'reports',
            'content' => 'reports/missing-records',
            'jobs' => (new Report())->jobsMissingRecords(),
        ]);
    }

    public function grossMargin(): void
    {
        $this->view('layouts/app', [
            'title' => 'Gross Margin by Job',
            'active' => 'reports',
            'content' => 'reports/gross-margin',
            'rows' => (new Report())->grossMarginByJob(),
        ]);
    }

    public function leadSources(): void
    {
        $this->view('layouts/app', [
            'title' => 'Lead Source Revenue',
            'active' => 'reports',
            'content' => 'reports/lead-sources',
            'rows' => (new Report())->leadSourceRevenue(),
        ]);
    }

    public function taxSummary(): void
    {
        $this->view('layouts/app', [
            'title' => 'Tax Summary',
            'active' => 'reports',
            'content' => 'reports/tax-summary',
            'rows' => (new Report())->taxSummary(),
        ]);
    }
}
