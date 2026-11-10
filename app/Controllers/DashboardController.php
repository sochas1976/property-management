<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Tenant;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Applicant;

class DashboardController extends Controller {
    public function index(): void {
        $properties = Property::all();
        $units = Unit::all();
        $tenants = Tenant::all();
        $openMaintenance = MaintenanceRequest::all('new');
        $financialTotals = Invoice::getTotals();
        $recentPayments = Payment::recent(5);

        $totalUnits = count($units);
        $occupiedUnits = 0;
        foreach ($units as $u) {
            if ($u['status'] === 'occupied') {
                $occupiedUnits++;
            }
        }

        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;
        $applicants = Applicant::all();
        $pendingApplicants = 0;
        foreach ($applicants as $app) {
            if ($app['status'] === 'pending') {
                $pendingApplicants++;
            }
        }

        $this->render('dashboard.index', [
            'title'              => 'Dashboard',
            'propertiesCount'    => count($properties),
            'totalUnits'         => $totalUnits,
            'occupiedUnits'      => $occupiedUnits,
            'occupancyRate'      => $occupancyRate,
            'tenantsCount'       => count($tenants),
            'openTicketsCount'   => MaintenanceRequest::getOpenCount(),
            'pendingApplicants'  => $pendingApplicants,
            'financialTotals'    => $financialTotals,
            'recentPayments'     => $recentPayments,
            'recentTickets'      => array_slice($openMaintenance, 0, 5)
        ]);
    }
}
