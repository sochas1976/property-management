<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Storage;
use App\Core\DocxTemplateProcessor;
use App\Core\AppDate;
use App\Core\Database;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\Tenant;
use App\Models\Applicant;
use App\Models\Document;
use App\Models\LeaseTemplate;
use Exception;

class LeaseController extends Controller {
    public function index(): void {
        if (!empty($_GET['paused'])) {
            $this->setFlash('info', 'Workflow progress for Lease #' . (int)$_GET['paused'] . ' has been safely paused and saved. You can resume at any time from the In-Progress table below.');
        }

        $allLeases = Lease::all();
        $todayStr = AppDate::todayString();

        // Categorize leases into In-Progress Workflows, Active Leases, and Expired Leases
        $inProgressLeases = [];
        $activeLeases = [];
        $expiredLeases = [];

        foreach ($allLeases as $l) {
            $isWorkflow = ($l['status'] === 'draft' || in_array($l['applicant_status'] ?? '', ['lease_created', 'lease_sent'], true));
            if ($isWorkflow) {
                $inProgressLeases[] = $l;
                continue;
            }

            // Check if expired or terminated
            $endDate = !empty($l['end_date']) ? $l['end_date'] : null;
            $isPastEnd = ($endDate && $todayStr > $endDate);
            $isExpired = ($l['status'] === 'expired' || $l['status'] === 'terminated' || $isPastEnd);

            if ($isExpired) {
                if ($l['status'] === 'active' && $isPastEnd) {
                    $l['status'] = 'expired';
                }
                $expiredLeases[] = $l;
            } else {
                $activeLeases[] = $l;
            }
        }

        $activeTab = $_GET['tab'] ?? 'all';

        $this->render('leases.index', [
            'title'            => 'Lease Agreements',
            'leases'           => $allLeases,
            'inProgressLeases' => $inProgressLeases,
            'activeLeases'     => $activeLeases,
            'expiredLeases'    => $expiredLeases,
            'activeTab'        => $activeTab,
        ]);
    }

    /**
     * Lease Templates view: upload DOCX templates, inspect [ ] placeholders,
     * map them to Application / Unit / Property / Calendar fields, and save mappings.
     */
    public function templates(): void {
        LeaseTemplate::seedDefaultIfNeeded(Auth::id());
        $templates = LeaseTemplate::all();

        $selectedTemplateId = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;
        $selectedTemplate = null;

        if ($selectedTemplateId > 0) {
            $selectedTemplate = LeaseTemplate::findById($selectedTemplateId);
        }
        if (!$selectedTemplate && !empty($templates)) {
            $selectedTemplate = $templates[0];
        }

        $placeholders = [];
        $templateError = null;
        if ($selectedTemplate && !empty($selectedTemplate['file_path'])) {
            if (file_exists($selectedTemplate['file_path'])) {
                try {
                    $placeholders = DocxTemplateProcessor::extractPlaceholders(
                        $selectedTemplate['file_path'],
                        null,
                        $selectedTemplate['mappings'] ?? []
                    );
                    // Initialize default mappings in DB if none were saved yet
                    if (empty($selectedTemplate['mappings']) && !empty($placeholders)) {
                        $defaultMappings = DocxTemplateProcessor::buildDefaultMappings($selectedTemplate['file_path']);
                        LeaseTemplate::updateMappings((int)$selectedTemplate['id'], $defaultMappings);
                        $selectedTemplate['mappings'] = $defaultMappings;
                    }
                } catch (Exception $e) {
                    $templateError = "Failed to read template: " . $e->getMessage();
                }
            } else {
                $templateError = "Template file is missing on server disk.";
            }
        }

        $this->render('leases.templates', [
            'title'            => 'Lease Templates',
            'templates'        => $templates,
            'selectedTemplate' => $selectedTemplate,
            'placeholders'     => $placeholders,
            'fieldCatalog'     => DocxTemplateProcessor::getAvailableFieldCatalog(),
            'templateError'    => $templateError
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

        LeaseTemplate::seedDefaultIfNeeded(Auth::id());
        $templates = LeaseTemplate::all();

        $selectedTemplateId = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;
        $selectedTemplate = null;

        if ($selectedTemplateId > 0) {
            $selectedTemplate = LeaseTemplate::findById($selectedTemplateId);
        }
        if (!$selectedTemplate && !empty($templates)) {
            $selectedTemplate = $templates[0];
        }

        $placeholders = [];
        $templateError = null;
        if ($selectedTemplate && !empty($selectedTemplate['file_path'])) {
            if (file_exists($selectedTemplate['file_path'])) {
                try {
                    // Ensure mappings are initialized
                    if (empty($selectedTemplate['mappings'])) {
                        $defaultMappings = DocxTemplateProcessor::buildDefaultMappings($selectedTemplate['file_path']);
                        LeaseTemplate::updateMappings((int)$selectedTemplate['id'], $defaultMappings);
                        $selectedTemplate['mappings'] = $defaultMappings;
                    }
                    $placeholders = DocxTemplateProcessor::extractPlaceholders(
                        $selectedTemplate['file_path'],
                        $lease,
                        $selectedTemplate['mappings'] ?? []
                    );
                } catch (Exception $e) {
                    $templateError = "Failed to parse template: " . $e->getMessage();
                }
            } else {
                $templateError = "Template file is missing on server disk.";
            }
        }

        $renewalDraft = Lease::findRenewalDraft((int)$id);

        $this->render('leases.view', [
            'title'            => 'Residential Lease Agreement',
            'lease'            => $lease,
            'documents'        => $documents,
            'templates'        => $templates,
            'selectedTemplate' => $selectedTemplate,
            'placeholders'     => $placeholders,
            'templateError'    => $templateError,
            'renewalDraft'     => $renewalDraft
        ]);
    }

    public function updateStatus(string $id): void {
        $status = $_POST['status'] ?? 'active';
        Lease::updateStatus((int)$id, $status);
        $statusLabel = ($status === 'expiring_soon') ? 'Expiring Soon' : ucfirst($status);
        $this->setFlash('success', 'Lease status updated to ' . htmlspecialchars($statusLabel) . '.');
        $redirect = '/leases/' . $id;
        if (!empty($_POST['from'])) {
            $redirect .= '?from=' . urlencode($_POST['from']);
            if (!empty($_POST['applicant_id'])) {
                $redirect .= '&applicant_id=' . (int)$_POST['applicant_id'];
            }
        }
        $this->redirect($redirect);
    }

    public function terminate(string $id): void {
        $lease = Lease::findById((int)$id);
        if (!$lease) {
            $this->setFlash('error', 'Lease not found.');
            $this->redirect('/leases');
        }

        $timingChoice = $_POST['timing_choice'] ?? 'specify';
        $todayStr = AppDate::todayString();

        if ($timingChoice === 'immediate') {
            $terminationDate = $todayStr;
        } else {
            $terminationDate = trim($_POST['termination_date'] ?? '');
            if (empty($terminationDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $terminationDate)) {
                $this->setFlash('error', 'Please provide a valid early termination date (YYYY-MM-DD).');
                $this->redirect('/leases/' . $id);
                return;
            }
        }

        // Validate that early termination date is before scheduled end date
        if (!empty($lease['end_date']) && $terminationDate >= $lease['end_date']) {
            $this->setFlash('error', 'Early termination date (' . htmlspecialchars($terminationDate) . ') must be before the scheduled lease end date (' . htmlspecialchars($lease['end_date']) . ').');
            $this->redirect('/leases/' . $id);
            return;
        }

        $reason = trim($_POST['reason'] ?? '');
        $success = Lease::terminateEarly((int)$id, $terminationDate, $reason);

        if ($success) {
            $this->setFlash('success', 'Lease Agreement #' . $id . ' has been terminated early (effective ' . htmlspecialchars($terminationDate) . ') and moved to Expired Leases.');
            $this->redirect('/leases?tab=expired');
        } else {
            $this->setFlash('error', 'Failed to terminate lease agreement.');
            $this->redirect('/leases/' . $id);
        }
    }

    public function renew(string $id): void {
        $lease = Lease::findById((int)$id);
        if (!$lease) {
            $this->setFlash('error', 'Lease not found.');
            $this->redirect('/leases');
        }

        // Prevent duplicate renewal workflows for the same lease
        $existingRenewal = Lease::findRenewalDraft((int)$id);
        if ($existingRenewal) {
            $this->setFlash('info', 'A renewal workflow is already in progress for this lease.');
            $this->redirect('/leases/' . $existingRenewal['id']);
            return;
        }

        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : (!empty($lease['end_date']) ? date('Y-m-d', strtotime($lease['end_date'] . ' +1 day')) : AppDate::todayString());
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime($startDate . ' +1 year -1 day'));
        $rentAmount = isset($_POST['rent_amount']) && $_POST['rent_amount'] !== '' ? (float)$_POST['rent_amount'] : (float)$lease['rent_amount'];
        $depositHeld = isset($_POST['deposit_held']) && $_POST['deposit_held'] !== '' ? (float)$_POST['deposit_held'] : (float)$lease['deposit_held'];
        $paymentFrequency = !empty($_POST['payment_frequency']) ? $_POST['payment_frequency'] : ($lease['payment_frequency'] ?? 'monthly');

        $tenantName = $lease['tenant_name'] ?: 'Resident';
        $terms = 'Residential lease renewal agreement for ' . $tenantName . ' (Renewing Lease #' . $lease['id'] . ').';

        // Create new draft renewal lease
        $newLeaseId = Lease::create([
            'unit_id'           => (int)$lease['unit_id'],
            'tenant_id'         => !empty($lease['tenant_id']) ? (int)$lease['tenant_id'] : null,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'rent_amount'       => $rentAmount,
            'deposit_held'      => $depositHeld,
            'payment_frequency' => $paymentFrequency,
            'status'            => 'draft',
            'terms'             => $terms
        ]);

        // Link prospective applicants for the 4-step workflow
        $leaseApplicants = $lease['applicants'] ?? [];
        $unitId = (int)$lease['unit_id'];
        $propertyId = (int)$lease['property_id'];

        if (!empty($leaseApplicants)) {
            foreach ($leaseApplicants as $app) {
                $newAppId = Applicant::create([
                    'full_name'                 => $app['full_name'],
                    'first_name'                => $app['first_name'] ?? null,
                    'middle_name'               => $app['middle_name'] ?? null,
                    'last_name'                 => $app['last_name'] ?? null,
                    'email'                     => $app['email'] ?? null,
                    'cell_phone'                => $app['cell_phone'] ?? null,
                    'home_phone'                => $app['home_phone'] ?? null,
                    'present_address'           => $app['present_address'] ?? null,
                    'target_property_id'        => $propertyId,
                    'target_unit_id'            => $unitId,
                    'status'                    => 'lease_created',
                    'term_start_date'           => $startDate,
                    'term_end_date'             => $endDate,
                    'notes'                     => 'Renewal application for Lease #' . $id . ' (New Draft Lease #' . $newLeaseId . ')'
                ]);
                Applicant::assignToDraftLease($newAppId, $newLeaseId, $unitId, $propertyId);
                if (!empty($app['tenant_id'])) {
                    $db = Database::getConnection();
                    $db->prepare("UPDATE applicants SET tenant_id = ? WHERE id = ?")->execute([(int)$app['tenant_id'], $newAppId]);
                }
            }
        } else {
            $parts = preg_split('/\s+/', trim($tenantName));
            $firstName = array_shift($parts) ?: 'Resident';
            $lastName = !empty($parts) ? implode(' ', $parts) : '';
            $newAppId = Applicant::create([
                'full_name'                 => $tenantName,
                'first_name'                => $firstName,
                'last_name'                 => $lastName,
                'email'                     => $lease['tenant_email'] ?? null,
                'cell_phone'                => $lease['tenant_phone'] ?? null,
                'target_property_id'        => $propertyId,
                'target_unit_id'            => $unitId,
                'status'                    => 'lease_created',
                'term_start_date'           => $startDate,
                'term_end_date'             => $endDate,
                'notes'                     => 'Renewal application for Lease #' . $id . ' (New Draft Lease #' . $newLeaseId . ')'
            ]);
            Applicant::assignToDraftLease($newAppId, $newLeaseId, $unitId, $propertyId);
            if (!empty($lease['tenant_id'])) {
                $db = Database::getConnection();
                $db->prepare("UPDATE applicants SET tenant_id = ? WHERE id = ?")->execute([(int)$lease['tenant_id'], $newAppId]);
            }
        }

        $this->setFlash('success', 'Renewal workflow started for ' . htmlspecialchars($tenantName) . '. You are now in Step 2 of 4 (Review Lease Agreement).');
        $this->redirect('/leases/' . $newLeaseId);
    }

    /**
     * Upload a new DOCX lease template and initialize its [ ] placeholder mappings.
     */
    public function uploadTemplate(?string $id = null): void {
        $returnToLeaseId = !empty($id) ? (int)$id : (!empty($_POST['lease_id']) ? (int)$_POST['lease_id'] : 0);

        if (empty($_FILES['template_file']['name']) || $_FILES['template_file']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', 'Please choose a valid .docx lease template file to upload.');
            $this->redirect($returnToLeaseId > 0 ? '/leases/templates?lease_id=' . $returnToLeaseId : '/leases/templates');
        }

        $originalName = $_FILES['template_file']['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($ext !== 'docx') {
            $this->setFlash('error', 'Invalid file type. Only Microsoft Word .docx documents are supported as templates.');
            $this->redirect($returnToLeaseId > 0 ? '/leases/templates?lease_id=' . $returnToLeaseId : '/leases/templates');
        }

        try {
            $name = !empty($_POST['template_name']) ? trim($_POST['template_name']) : pathinfo($originalName, PATHINFO_FILENAME);
            $description = !empty($_POST['template_description']) ? trim($_POST['template_description']) : null;

            $dir = STORAGE_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'leases';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $uniqueName = bin2hex(random_bytes(16)) . '.docx';
            $destination = $dir . DIRECTORY_SEPARATOR . $uniqueName;

            if (!move_uploaded_file($_FILES['template_file']['tmp_name'], $destination)) {
                throw new Exception("Failed to store template file on disk.");
            }

            $defaultMappings = DocxTemplateProcessor::buildDefaultMappings($destination);

            $templateId = LeaseTemplate::create([
                'name'                 => $name,
                'description'          => $description,
                'original_name'        => $originalName,
                'stored_filename'      => $uniqueName,
                'file_path'            => $destination,
                'file_size'            => filesize($destination),
                'placeholder_mappings' => $defaultMappings,
                'uploaded_by_user_id'  => Auth::id()
            ]);

            $placeholderCount = count($defaultMappings);
            $this->setFlash('success', "Lease template '{$name}' uploaded. Found {$placeholderCount} [ ] placeholder(s) ready to map.");
            $redirectUrl = '/leases/templates?template_id=' . $templateId;
            if ($returnToLeaseId > 0) {
                $redirectUrl .= '&lease_id=' . $returnToLeaseId;
            }
            $this->redirect($redirectUrl);
        } catch (Exception $e) {
            $this->setFlash('error', 'Template upload failed: ' . $e->getMessage());
            $this->redirect('/leases/templates');
        }
    }

    /**
     * Save placeholder-to-field mappings for a lease template.
     */
    public function saveTemplateMapping(string $templateId): void {
        $template = LeaseTemplate::findById((int)$templateId);
        if (!$template) {
            $this->setFlash('error', 'Lease template not found.');
            $this->redirect('/leases/templates');
        }

        $keys = $_POST['placeholder_keys'] ?? [];
        $fields = $_POST['mapped_fields'] ?? [];
        $manuals = $_POST['manual_values'] ?? [];

        $mappings = [];
        if (is_array($keys)) {
            foreach ($keys as $idx => $key) {
                $cleanKey = trim((string)$key);
                if ($cleanKey === '') continue;
                $mappings[$cleanKey] = [
                    'field'        => isset($fields[$idx]) ? trim((string)$fields[$idx]) : 'manual',
                    'manual_value' => isset($manuals[$idx]) ? trim((string)$manuals[$idx]) : ''
                ];
            }
        }

        LeaseTemplate::updateMappings((int)$templateId, $mappings);
        $this->setFlash('success', "Placeholder mappings saved for template '{$template['name']}'.");

        $redirectUrl = '/leases/templates?template_id=' . (int)$templateId;
        if (!empty($_POST['lease_id'])) {
            $redirectUrl .= '&lease_id=' . (int)$_POST['lease_id'];
        }
        $this->redirect($redirectUrl);
    }

    /**
     * Generate populated lease document (.docx) from template using saved mappings and applicant/unit/property data.
     */
    public function generateDocument(string $id): void {
        $lease = Lease::findById((int)$id);
        if (!$lease) {
            $this->setFlash('error', 'Lease not found.');
            $this->redirect('/leases');
        }

        $templateId = !empty($_POST['template_id']) ? (int)$_POST['template_id'] : 0;
        $template = LeaseTemplate::findById($templateId);

        if (!$template || empty($template['file_path']) || !file_exists($template['file_path'])) {
            $this->setFlash('error', 'Selected template file could not be found.');
            $this->redirect('/leases/' . $id);
        }

        $replacements = $_POST['replacements'] ?? [];
        if (!empty($_POST['placeholder_keys']) && is_array($_POST['placeholder_keys'])) {
            foreach ($_POST['placeholder_keys'] as $idx => $key) {
                if (isset($_POST['placeholder_values'][$idx]) && trim((string)$_POST['placeholder_values'][$idx]) !== '') {
                    $replacements[$key] = trim((string)$_POST['placeholder_values'][$idx]);
                }
            }
        }
        $removeRedColor = !empty($_POST['remove_red_color']);

        try {
            $dir = STORAGE_PATH . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'leases';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $safeTenant = preg_replace('/[^a-zA-Z0-9_-]/', '_', $lease['tenant_name'] ?: 'Resident');
            $safeUnit = preg_replace('/[^a-zA-Z0-9_-]/', '_', $lease['unit_number'] ?: 'Unit');
            $originalDocName = "Lease_Agreement_Unit_{$safeUnit}_{$safeTenant}_" . date('Ymd') . ".docx";
            $uniqueFilename = bin2hex(random_bytes(16)) . '.docx';
            $outputPath = $dir . DIRECTORY_SEPARATOR . $uniqueFilename;

            // Generate populated document using the template's saved mappings + lease/application/unit/property data
            DocxTemplateProcessor::generateDocument(
                $template['file_path'],
                $replacements,
                $outputPath,
                $removeRedColor,
                $lease,
                $template['mappings'] ?? []
            );

            // Stream the generated document directly for download without attaching to vault
            Storage::stream($outputPath, $originalDocName, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', true);
            exit;
        } catch (Exception $e) {
            $this->setFlash('error', 'Failed to generate lease document: ' . $e->getMessage());
            $this->redirect('/leases/' . $id . '?template_id=' . $templateId);
        }
    }

    /**
     * Upload an executed/signed contract or attachment directly to this lease.
     */
    public function uploadDocument(string $id): void {
        Auth::requireRole(['admin', 'manager']);
        $lease = Lease::findById((int)$id);
        if (!$lease) {
            $this->setFlash('error', 'Lease not found.');
            $this->redirect('/leases');
        }

        if (empty($_FILES['document']['name']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', 'Please select a valid document file to attach.');
            $this->redirect('/leases/' . $id);
        }

        try {
            $storedFile = Storage::store($_FILES['document'], 'documents/leases');

            Document::create([
                'related_type'        => 'lease',
                'related_id'          => (int)$id,
                'original_name'       => $storedFile['original_name'],
                'stored_filename'     => $storedFile['stored_filename'],
                'file_path'           => $storedFile['file_path'],
                'mime_type'           => $storedFile['mime_type'],
                'file_size'           => $storedFile['file_size'],
                'uploaded_by_user_id' => Auth::id()
            ]);

            $this->setFlash('success', 'Document attached to lease agreement successfully.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Failed to upload document: ' . $e->getMessage());
        }

        $this->redirect('/leases/' . $id);
    }

    /**
     * Cancel and discard a draft lease workflow.
     */
    public function cancel(string $id): void {
        $lease = Lease::findById((int)$id);
        if (!$lease) {
            $this->setFlash('error', 'Lease not found.');
            $this->redirect('/leases');
            return;
        }

        if ($lease['status'] !== 'draft') {
            $this->setFlash('error', 'Only draft lease agreements can be cancelled.');
            $this->redirect('/leases/' . $id);
            return;
        }

        $db = Database::getConnection();

        // Delete any renewal applicants created specifically for this renewal draft
        $db->prepare("DELETE FROM applicants WHERE lease_id = ? AND notes LIKE '%Renewal%'")->execute([(int)$id]);

        // If regular applicants are attached, detach them
        $db->prepare("UPDATE applicants SET lease_id = NULL WHERE lease_id = ?")->execute([(int)$id]);

        // Delete attached draft documents if any
        $db->prepare("DELETE FROM documents WHERE related_type = 'lease' AND related_id = ?")->execute([(int)$id]);

        // Delete the draft lease
        Lease::delete((int)$id);

        $this->setFlash('success', 'Draft lease workflow cancelled.');
        $this->redirect('/leases');
    }

    /**
     * Delete a template from the template library.
     */
    public function deleteTemplate(string $idOrTemplateId, ?string $maybeTemplateId = null): void {
        Auth::requireRole(['admin', 'manager']);
        $templateId = $maybeTemplateId !== null ? (int)$maybeTemplateId : (int)$idOrTemplateId;
        LeaseTemplate::delete($templateId);
        $this->setFlash('success', 'Lease template deleted.');
        if ($maybeTemplateId !== null) {
            $this->redirect('/leases/' . (int)$idOrTemplateId);
        } else {
            $this->redirect('/leases/templates');
        }
    }
}
