<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $cards = [
            'Active Jobs' => (int) $db->query("SELECT COUNT(*) FROM service_requests WHERE status IN ('pending', 'accepted')")->fetchColumn(),
            'New Intake' => (int) $db->query("SELECT COUNT(*) FROM intakes WHERE status IN ('draft', 'saved')")->fetchColumn(),
            'Converted Intake' => (int) $db->query("SELECT COUNT(*) FROM intakes WHERE status = 'converted'")->fetchColumn(),
            'Pending Requests' => (int) $db->query("SELECT COUNT(*) FROM service_requests WHERE status = 'pending'")->fetchColumn(),
            'Customers' => (int) $db->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
            'Vehicles' => (int) $db->query('SELECT COUNT(*) FROM vehicles')->fetchColumn(),
            'Missing VIN' => (int) $db->query("SELECT COUNT(*) FROM vehicles WHERE vin IS NULL OR vin = ''")->fetchColumn(),
            'Accepted Jobs' => (int) $db->query("SELECT COUNT(*) FROM service_requests WHERE status = 'accepted'")->fetchColumn(),
        ];
        $latestIntakes = $db->query('SELECT * FROM intakes ORDER BY created_at DESC, id DESC LIMIT 5')->fetchAll();
        $latestRequests = $db->query(
            'SELECT sr.*, c.first_name, c.last_name
             FROM service_requests sr
             JOIN customers c ON c.id = sr.customer_id
             ORDER BY sr.created_at DESC, sr.id DESC
             LIMIT 5'
        )->fetchAll();

        $this->view('layouts/app', [
            'title' => 'Dashboard',
            'active' => 'dashboard',
            'content' => 'dashboard/index',
            'cards' => $cards,
            'latestIntakes' => $latestIntakes,
            'latestRequests' => $latestRequests,
        ]);
    }
}
