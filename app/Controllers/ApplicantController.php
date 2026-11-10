<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\PdfApplicationParser;
use App\Models\Applicant;
use App\Models\Tenant;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Lease;

class ApplicantController extends Controller {
    public function index(): void {
        $applicants = Applicant::all();
        $this->render('applicants.index', [
            'title'      => 'Rental Applicants',
            'applicants' => $applicants
        ]);
    }

    public function create(): void {
        $properties = Property::all();
        $allUnits = Unit::all();

        $this->render('applicants.create', [
            'title'       => 'New Rental Application',
            'properties'  => $properties,
            'allUnits'    => $allUnits
        ]);
    }

    public function store(): void {
        $data = $this->sanitize($_POST);

        // Normalize first, middle, last name and full_name
        $data = $this->resolveNameFields($data);

        if (empty($data['full_name'])) {
            $this->setFlash('error', 'Applicant first and last name are required.');
            $this->redirect('/applicants/create');
        }

        $data = $this->formatPhoneFields($data);

        // Handle uploaded documents if present
        $newDocs = $this->handleDocumentsUpload('applicant_documents');
        if (empty($newDocs)) {
            $newDocs = $this->handleDocumentsUpload('documents');
        }
        if (!empty($newDocs)) {
            $data['documents'] = json_encode(array_values($newDocs));
        }

        $id = Applicant::create($data);
        $this->setFlash('success', 'Rental application for ' . htmlspecialchars($data['full_name']) . ' submitted successfully.');
        $this->redirect('/applicants/' . $id);
    }

    public function view(string $id): void {
        $applicant = Applicant::findById((int)$id);
        if (!$applicant) {
            $this->setFlash('error', 'Applicant not found.');
            $this->redirect('/applicants');
        }

        $properties = Property::all();
        $allUnits = Unit::all();
        $otherApplicants = Applicant::allPendingAndApproved();

        $this->render('applicants.view', [
            'title'            => 'Application: ' . $applicant['full_name'],
            'applicant'        => $applicant,
            'properties'       => $properties,
            'allUnits'         => $allUnits,
            'otherApplicants'  => $otherApplicants
        ]);
    }

    public function edit(string $id): void {
        $applicant = Applicant::findById((int)$id);
        if (!$applicant) {
            $this->setFlash('error', 'Applicant not found.');
            $this->redirect('/applicants');
        }

        $properties = Property::all();
        $allUnits = Unit::all();

        $this->render('applicants.edit', [
            'title'       => 'Edit Application: ' . $applicant['full_name'],
            'applicant'   => $applicant,
            'properties'  => $properties,
            'allUnits'    => $allUnits
        ]);
    }

    public function update(string $id): void {
        $applicant = Applicant::findById((int)$id);
        if (!$applicant) {
            $this->setFlash('error', 'Applicant not found.');
            $this->redirect('/applicants');
        }

        $data = $this->sanitize($_POST);

        $data = $this->resolveNameFields($data);

        if (empty($data['full_name'])) {
            $this->setFlash('error', 'Applicant first and last name are required.');
            $this->redirect('/applicants/' . $id . '/edit');
        }

        $data = $this->formatPhoneFields($data);

        // Preserve existing documents or merge any newly submitted files
        $newDocs = $this->handleDocumentsUpload('applicant_documents');
        if (empty($newDocs)) {
            $newDocs = $this->handleDocumentsUpload('documents');
        }
        if (!empty($newDocs)) {
            $existingDocs = [];
            if (!empty($applicant['documents'])) {
                $decoded = json_decode($applicant['documents'], true);
                if (is_array($decoded)) {
                    $existingDocs = $decoded;
                }
            }
            $data['documents'] = json_encode(array_values(array_merge($existingDocs, $newDocs)));
        } elseif (!isset($data['documents'])) {
            $data['documents'] = $applicant['documents'] ?? null;
        }

        Applicant::update((int)$id, $data);
        $this->setFlash('success', 'Application details updated.');
        $this->redirect('/applicants/' . $id);
    }

    public function updateStatus(string $id): void {
        $applicant = Applicant::findById((int)$id);
        if (!$applicant) {
            $this->setFlash('error', 'Applicant not found.');
            $this->redirect('/applicants');
        }

        $status = $_POST['status'] ?? 'pending';
        if (!in_array($status, ['pending', 'approved', 'rejected', 'converted'], true)) {
            $status = 'pending';
        }

        Applicant::updateStatus((int)$id, $status);
        $this->setFlash('success', 'Applicant status updated to ' . ucfirst($status) . '.');
        $this->redirect('/applicants/' . $id);
    }

    public function convert(string $id): void {
        $primaryApplicant = Applicant::findById((int)$id);
        if (!$primaryApplicant) {
            $this->setFlash('error', 'Applicant not found.');
            $this->redirect('/applicants');
        }

        $unitId = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null;
        if (!$unitId) {
            $this->setFlash('error', 'Please select an apartment/unit to assign to this new tenant.');
            $this->redirect('/applicants/' . $id);
        }

        $unit = Unit::findById($unitId);
        if (!$unit) {
            $this->setFlash('error', 'Selected unit could not be found.');
            $this->redirect('/applicants/' . $id);
        }

        // Collect all applicant IDs being converted together
        $applicantIds = [(int)$id];
        if (!empty($_POST['co_applicant_ids']) && is_array($_POST['co_applicant_ids'])) {
            foreach ($_POST['co_applicant_ids'] as $coId) {
                $coId = (int)$coId;
                if ($coId > 0 && !in_array($coId, $applicantIds, true)) {
                    $applicantIds[] = $coId;
                }
            }
        }

        // Check if user requested terminating previous lease (if expiring/turnover)
        if (!empty($_POST['terminate_previous_lease'])) {
            $prevLease = Lease::findActiveByUnit($unitId);
            if ($prevLease) {
                Lease::updateStatus((int)$prevLease['id'], 'expired');
            }
        }

        $startDate = !empty($_POST['lease_start_date']) ? $_POST['lease_start_date'] : (!empty($primaryApplicant['term_start_date']) ? $primaryApplicant['term_start_date'] : date('Y-m-d'));
        $endDate = !empty($_POST['lease_end_date']) ? $_POST['lease_end_date'] : (!empty($primaryApplicant['term_end_date']) ? $primaryApplicant['term_end_date'] : date('Y-m-d', strtotime('+1 year')));
        $rentAmount = !empty($_POST['rent_amount']) ? (float)$_POST['rent_amount'] : (float)$unit['target_rent'];
        $securityDeposit = !empty($_POST['security_deposit']) ? (float)$_POST['security_deposit'] : (float)$unit['security_deposit'];

        $convertedTenantNames = [];
        $firstTenantId = null;

        // Loop over all selected applicants, create Tenant records and active Leases
        foreach ($applicantIds as $appId) {
            $applicant = Applicant::findById($appId);
            if (!$applicant) continue;

            // Split full name
            $nameParts = preg_split('/\s+/', trim($applicant['full_name']));
            $firstName = array_shift($nameParts);
            $lastName = !empty($nameParts) ? implode(' ', $nameParts) : '—';

            $phone = !empty($applicant['cell_phone']) ? $applicant['cell_phone'] : (!empty($applicant['home_phone']) ? $applicant['home_phone'] : '—');
            $email = !empty($applicant['email']) ? $applicant['email'] : strtolower($firstName . '.' . time() . rand(10,99) . '@noemail.local');

            $tenantData = [
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'email'             => $email,
                'phone'             => $phone,
                'emergency_contact' => $applicant['emergency_contact_1_name'] ?: null,
                'emergency_phone'   => $applicant['emergency_contact_1_phone'] ?: null,
                'notes'             => 'Converted from Rental Application #' . $applicant['id'] . ' on ' . date('Y-m-d') . '.'
            ];

            $tenantId = Tenant::create($tenantData);
            if (!$firstTenantId) {
                $firstTenantId = $tenantId;
            }

            // Create lease assignment
            $leaseTerms = count($applicantIds) > 1 
                ? 'Joint household residential tenancy agreement with co-tenants.'
                : 'Standard residential lease agreement created upon application approval.';

            Lease::create([
                'unit_id'           => $unitId,
                'tenant_id'         => $tenantId,
                'start_date'        => $startDate,
                'end_date'          => $endDate,
                'rent_amount'       => $rentAmount,
                'deposit_held'      => $securityDeposit,
                'payment_frequency' => 'monthly',
                'status'            => 'active',
                'terms'             => $leaseTerms
            ]);

            Applicant::markConverted($appId, $tenantId, $unitId);
            $convertedTenantNames[] = htmlspecialchars($applicant['full_name']);
        }

        $namesList = implode(' & ', $convertedTenantNames);
        $this->setFlash('success', 'Successfully approved and assigned ' . $namesList . ' to Unit #' . htmlspecialchars($unit['unit_number']) . '!');
        $this->redirect('/tenants/' . $firstTenantId);
    }

    public function uploadDocument(string $id): void {
        $applicant = Applicant::findById((int)$id);
        if (!$applicant) {
            $this->json(['success' => false, 'message' => 'Applicant not found.'], 404);
            return;
        }

        $newDocs = $this->handleDocumentsUpload('applicant_documents');
        if (empty($newDocs)) {
            $newDocs = $this->handleDocumentsUpload('documents');
        }

        if (empty($newDocs)) {
            $this->json(['success' => false, 'message' => 'No valid document files were uploaded. Accepted formats: JPG, PNG, WEBP, GIF, PDF (up to 15MB).'], 400);
            return;
        }

        $existingDocs = [];
        if (!empty($applicant['documents'])) {
            $decoded = json_decode($applicant['documents'], true);
            if (is_array($decoded)) {
                $existingDocs = $decoded;
            }
        }

        $allDocs = array_merge($existingDocs, $newDocs);
        Applicant::updateDocuments((int)$id, json_encode(array_values($allDocs)));

        // If AJAX request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || !empty($_POST['ajax'])) {
            $this->json([
                'success'   => true,
                'documents' => array_values($allDocs),
                'uploaded'  => $newDocs
            ]);
            return;
        }

        $this->setFlash('success', count($newDocs) . ' document(s) uploaded successfully.');
        $this->redirect('/applicants/' . $id);
    }

    public function deleteDocument(string $id): void {
        $applicant = Applicant::findById((int)$id);
        if (!$applicant) {
            $this->json(['success' => false, 'message' => 'Applicant not found.'], 404);
            return;
        }

        $document = $_POST['document'] ?? '';
        if (empty($document)) {
            $this->json(['success' => false, 'message' => 'Document path is required.'], 400);
            return;
        }

        $docs = [];
        if (!empty($applicant['documents'])) {
            $decoded = json_decode($applicant['documents'], true);
            if (is_array($decoded)) {
                $docs = $decoded;
            }
        }

        $newDocs = [];
        $found = false;
        foreach ($docs as $d) {
            if ($d === $document) {
                $found = true;
                $filePath = dirname(__DIR__, 2) . '/public' . $document;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            } else {
                $newDocs[] = $d;
            }
        }

        if ($found) {
            Applicant::updateDocuments((int)$id, !empty($newDocs) ? json_encode(array_values($newDocs)) : null);
            $this->json(['success' => true]);
        } else {
            $this->json(['success' => false, 'message' => 'Document not found on applicant.'], 404);
        }
    }

    public function delete(string $id): void {
        $applicant = Applicant::findById((int)$id);
        if ($applicant && !empty($applicant['documents'])) {
            $docs = json_decode($applicant['documents'], true);
            if (is_array($docs)) {
                foreach ($docs as $doc) {
                    $filePath = dirname(__DIR__, 2) . '/public' . $doc;
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
        }

        Applicant::delete((int)$id);
        $this->setFlash('success', 'Application removed.');
        $this->redirect('/applicants');
    }

    /**
     * AJAX endpoint to parse an uploaded PDF rental application (typed, printed, or handwritten)
     * and return extracted form fields + signature crop geometry.
     */
    public function parsePdf(): void {
        $tmpPath = null;
        if (!empty($_FILES['pdf_file']) && ($_FILES['pdf_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['pdf_file']['tmp_name'];
        }

        $clientText = isset($_POST['client_text']) ? (string)$_POST['client_text'] : '';
        $clientItems = [];
        if (!empty($_POST['client_items'])) {
            $decodedItems = json_decode((string)$_POST['client_items'], true);
            if (is_array($decodedItems)) {
                $clientItems = $decodedItems;
            }
        }

        if (!$tmpPath && $clientText === '' && empty($clientItems)) {
            $this->json([
                'success' => false,
                'message' => 'Please select a valid PDF application file to import.'
            ], 400);
            return;
        }

        $result = PdfApplicationParser::parse($tmpPath, $clientText, $clientItems);
        $this->json($result);
    }

    /**
     * Handle document files upload.
     * Returns an array of web-relative paths.
     */
    private function handleDocumentsUpload(string $inputName = 'applicant_documents'): array {
        if (empty($_FILES[$inputName]) || !isset($_FILES[$inputName]['name'])) {
            return [];
        }

        $fileData = $_FILES[$inputName];
        $isMultiple = is_array($fileData['name']);

        $names = $isMultiple ? $fileData['name'] : [$fileData['name']];
        $tmpNames = $isMultiple ? $fileData['tmp_name'] : [$fileData['tmp_name']];
        $errors = $isMultiple ? $fileData['error'] : [$fileData['error']];
        $sizes = $isMultiple ? $fileData['size'] : [$fileData['size']];

        $uploaded = [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];
        $maxSize = 15 * 1024 * 1024; // 15 MB
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/applicants';

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        foreach ($names as $idx => $name) {
            if (empty($name) || $errors[$idx] !== UPLOAD_ERR_OK) {
                continue;
            }

            if ($sizes[$idx] > $maxSize) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                continue;
            }

            $filename = 'doc_' . time() . '_' . substr(bin2hex(random_bytes(6)), 0, 8) . '.' . $ext;
            $dest = $uploadDir . '/' . $filename;

            if (move_uploaded_file($tmpNames[$idx], $dest)) {
                $uploaded[] = '/uploads/applicants/' . $filename;
            }
        }

        return $uploaded;
    }

    /**
     * Standardize all phone numbers in the input data to XXX-XXX-XXXX
     */
    private function formatPhoneFields(array $data): array {
        $phoneKeys = [
            'home_phone',
            'cell_phone',
            'current_employer_phone',
            'previous_employer_phone',
            'current_landlord_phone',
            'previous_landlord_phone',
            'emergency_contact_1_phone',
            'emergency_contact_2_phone'
        ];

        foreach ($phoneKeys as $key) {
            if (!empty($data[$key])) {
                $data[$key] = self::cleanPhone($data[$key]);
            }
        }

        return $data;
    }

    public static function cleanPhone(?string $phone): string {
        if (!$phone) return '';
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 11 && $digits[0] === '1') {
            $digits = substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            return substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6, 4);
        }
        return $phone;
    }

    /**
     * Resolve first_name, middle_name, last_name, and composite full_name
     */
    private function resolveNameFields(array $data): array {
        $first = trim($data['first_name'] ?? '');
        $middle = trim($data['middle_name'] ?? '');
        $last = trim($data['last_name'] ?? '');

        if (!empty($first) || !empty($last)) {
            $parts = array_filter([$first, $middle, $last], function($p) { return $p !== ''; });
            $data['full_name'] = implode(' ', $parts);
            $data['first_name'] = $first;
            $data['middle_name'] = $middle ?: null;
            $data['last_name'] = $last;
        } elseif (!empty($data['full_name'])) {
            $parts = preg_split('/\s+/', trim($data['full_name']));
            if (count($parts) === 1) {
                $data['first_name'] = $parts[0];
                $data['middle_name'] = null;
                $data['last_name'] = '—';
            } elseif (count($parts) === 2) {
                $data['first_name'] = $parts[0];
                $data['middle_name'] = null;
                $data['last_name'] = $parts[1];
            } else {
                $data['first_name'] = array_shift($parts);
                $data['last_name'] = array_pop($parts);
                $data['middle_name'] = implode(' ', $parts);
            }
        }

        return $data;
    }
}
