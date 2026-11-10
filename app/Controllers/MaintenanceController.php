<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Storage;
use App\Models\MaintenanceRequest;
use App\Models\Unit;
use App\Models\Tenant;
use App\Models\Document;
use Exception;

class MaintenanceController extends Controller {
    public function index(): void {
        $statusFilter = $_GET['status'] ?? 'all';
        $requests = MaintenanceRequest::all($statusFilter);

        $this->render('maintenance.index', [
            'title'        => 'Maintenance & Work Orders',
            'requests'     => $requests,
            'statusFilter' => $statusFilter
        ]);
    }

    public function create(): void {
        $units = Unit::all();
        $tenants = Tenant::all();

        $this->render('maintenance.create', [
            'title'   => 'Submit Maintenance Ticket',
            'units'   => $units,
            'tenants' => $tenants
        ]);
    }

    public function store(): void {
        $data = $this->sanitize($_POST);

        if (empty($data['unit_id']) || empty($data['title']) || empty($data['description'])) {
            $this->setFlash('error', 'Please fill in unit, title, and issue description.');
            $this->redirect('/maintenance/create');
        }

        try {
            $requestId = MaintenanceRequest::create($data);

            // Handle optional photo/document upload
            if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $storedFile = Storage::store($_FILES['photo'], 'photos/maintenance');

                Document::create([
                    'related_type'        => 'maintenance',
                    'related_id'          => $requestId,
                    'original_name'       => $storedFile['original_name'],
                    'stored_filename'     => $storedFile['stored_filename'],
                    'file_path'           => $storedFile['file_path'],
                    'mime_type'           => $storedFile['mime_type'],
                    'file_size'           => $storedFile['file_size'],
                    'uploaded_by_user_id' => Auth::id()
                ]);
            }

            $this->setFlash('success', 'Maintenance request #' . $requestId . ' submitted successfully.');
            $this->redirect('/maintenance/' . $requestId);
        } catch (Exception $e) {
            $this->setFlash('error', 'Failed to submit request: ' . $e->getMessage());
            $this->redirect('/maintenance/create');
        }
    }

    public function view(string $id): void {
        $request = MaintenanceRequest::findById((int)$id);
        if (!$request) {
            $this->setFlash('error', 'Maintenance request not found.');
            $this->redirect('/maintenance');
        }

        $documents = Document::getByRelated('maintenance', (int)$id);

        $this->render('maintenance.view', [
            'title'     => 'Ticket #' . $request['id'] . ' - ' . $request['title'],
            'request'   => $request,
            'documents' => $documents
        ]);
    }

    public function updateStatus(string $id): void {
        $status = $_POST['status'] ?? 'new';
        $assignedTo = !empty($_POST['assigned_to']) ? trim($_POST['assigned_to']) : null;
        $notes = !empty($_POST['resolution_notes']) ? trim($_POST['resolution_notes']) : null;

        MaintenanceRequest::updateStatus((int)$id, $status, $assignedTo, $notes);
        $this->setFlash('success', 'Ticket status updated to ' . htmlspecialchars($status) . '.');
        $this->redirect('/maintenance/' . $id);
    }
}
