<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Lease;
use Exception;

class FinanceController extends Controller {
    public function index(): void {
        $invoices = Invoice::all();
        $totals = Invoice::getTotals();
        $recentPayments = Payment::recent(15);
        $activeLeases = Lease::all();

        $this->render('finances.index', [
            'title'          => 'Billing & Financials',
            'invoices'       => $invoices,
            'totals'         => $totals,
            'recentPayments' => $recentPayments,
            'activeLeases'   => $activeLeases
        ]);
    }

    public function createInvoice(): void {
        $data = $this->sanitize($_POST);

        if (empty($data['lease_id']) || empty($data['amount']) || empty($data['due_date'])) {
            $this->setFlash('error', 'Lease, amount, and due date are required to create an invoice.');
            $this->redirect('/finances');
        }

        Invoice::create($data);
        $this->setFlash('success', 'Rent invoice created successfully.');
        $this->redirect('/finances');
    }

    public function recordPayment(): void {
        $data = $this->sanitize($_POST);

        if (empty($data['invoice_id']) || empty($data['amount_paid']) || empty($data['payment_date'])) {
            $this->setFlash('error', 'Invoice ID, payment amount, and date are required.');
            $this->redirect('/finances');
        }

        try {
            Payment::create($data);
            $this->setFlash('success', 'Payment of $' . number_format((float)$data['amount_paid'], 2) . ' recorded successfully.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Failed to record payment: ' . $e->getMessage());
        }

        $this->redirect('/finances');
    }
}
