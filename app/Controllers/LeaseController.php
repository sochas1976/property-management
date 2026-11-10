<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Storage;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\Tenant;
use App\Models\Document;
use Exception;

class LeaseController extends Controller {
    public function index(): void {
        $leases = Lease::all();
        $this->render('leases.index', [
            'title'  => 'Lease Agreements',
            'leases' => $leases
        ]);
    }

    public function create(): void {
        $vacantUnits = Unit::getVacant();
        $tenants = Tenant::all();

        $this->render('leases.create', [
            'title'       => 'Create Lease Agreement',
            'vacantUnits' => $vacantUnits,
            'tenants'     => $tenants
        ]);
    }

    public function store(): void {
        $data = $this->sanitize($_POST);

        if (empty($data['unit_id']) || empty($data['tenant_id']) || empty($data['start_date']) || empty($data['end_date']) || empty($data['rent_amount'])) {
            $this->setFlash('error', 'Please fill in all mandatory lease terms.');
            $this->redirect('/leases/create');
        }

        try {
            $leaseId = Lease::create($data);

            // Handle optional local file upload for signed Lease Agreement PDF
            if (!empty($_FILES['lease_document']['name']) && $_FILES['lease_document']['error'] === UPLOAD_ERR_OK) {
                $storedFile = Storage::store($_FILES['lease_document'], 'documents/leases');
                
                Document::create([
                    'related_type'        => 'lease',
                    'related_id'          => $leaseId,
                    'original_name'       => $storedFile['original_name'],
                    'stored_filename'     => $storedFile['stored_filename'],
                    'file_path'           => $storedFile['file_path'],
                    'mime_type'           => $storedFile['mime_type'],
                    'file_size'           => $storedFile['file_size'],
                    'uploaded_by_user_id' => Auth::id()
                ]);
            }

            $this->setFlash('success', 'Lease agreement created successfully and unit status updated to occupied.');
            $this->redirect('/leases/' . $leaseId);
        } catch (Exception $e) {
            $this->setFlash('error', 'Failed to create lease: ' . $e->getMessage());
            $this->redirect('/leases/create');
        }
    }

    public function view(string $id): void {
        $lease = Lease::findById((int)$id);
        if (!$lease) {
            $this->setFlash('error', 'Lease not found.');
            $this->redirect('/leases');
        }

        $documents = Document::getByRelated('lease', (int)$id);

        $this->render('leases.view', [
            'title'     => 'Lease Agreement #' . $lease['id'],
            'lease'     => $lease,
            'documents' => $documents
        ]);
    }

    public function updateStatus(string $id): void {
        $status = $_POST['status'] ?? 'active';
        Lease::updateStatus((int)$id, $status);
        $this->setFlash('success', 'Lease status updated to ' . htmlspecialchars($status) . '.');
        $this->redirect('/leases/' . $id);
    }
}
