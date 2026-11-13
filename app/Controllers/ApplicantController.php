<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\PdfApplicationParser;
use App\Core\AppDate;
use App\Models\Applicant;
use App\Models\Tenant;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Lease;

class ApplicantController extends Controller {
    public function index(): void {
        $all = Applicant::all();
        $today = AppDate::todayString();

        $activeApplicants = [];
        $historicalApplicants = [];

        foreach ($all as &$app) {
            $requestedTermDate = !empty($app['term_start_date']) ? $app['term_start_date'] : (!empty($app['date_requested']) ? $app['date_requested'] : null);
            $isTermPassed = (!empty($requestedTermDate) && $today > $requestedTermDate);

            // Automatically reject applications whose requested term date has passed (unless already in lease workflow or converted)
            if (!in_array($app['status'], ['converted', 'lease_created', 'lease_sent', 'rejected'], true) && $isTermPassed) {
                $app['status'] = 'rejected';
                $app['status_date'] = $requestedTermDate . ' 23:59:59';
                $app['auto_rejected'] = true;
                if (!AppDate::isSimulated()) {
                    Applicant::updateStatus((int)$app['id'], 'rejected', $app['status_date']);
                }
            }

            $isHistorical = false;

            if ($app['status'] === 'converted') {
                $leaseStart = !empty($app['lease_start_date']) ? $app['lease_start_date'] : (!empty($app['term_start_date']) ? $app['term_start_date'] : null);
                if (!empty($leaseStart) && $leaseStart <= $today) {
                    $isHistorical = true;
                }
            } elseif ($app['status'] === 'rejected') {
                $rejectionTimestamp = !empty($app['status_date']) ? strtotime($app['status_date']) : strtotime($app['updated_at'] ?? $app['created_at']);
                $rejectionDate = date('Y-m-d', $rejectionTimestamp);
                $daysSinceRejection = (strtotime($today) - strtotime($rejectionDate)) / 86400;
                $nowTimestamp = strtotime($today . ' ' . date('H:i:s'));
                $elapsedSeconds = $nowTimestamp - $rejectionTimestamp;

                if ($daysSinceRejection >= 1 || $elapsedSeconds >= 86400 || $isTermPassed) {
                    $isHistorical = true;
                }
            }

            if ($isHistorical) {
                $historicalApplicants[] = $app;
            } else {
                $activeApplicants[] = $app;
            }
        }
        unset($app);

        $viewMode = $_GET['view'] ?? 'active';
        if ($viewMode === 'history') {
            $this->render('applicants.history', [
                'title'                => 'Historical Applications',
                'activeApplicants'     => $activeApplicants,
                'historicalApplicants' => $historicalApplicants,
                'isHistory'            => true,
            ]);
            return;
        }

        $this->render('applicants.index', [
            'title'                => 'Rental Applicants',
            'applicants'           => $activeApplicants,
            'activeApplicants'     => $activeApplicants,
            'historicalApplicants' => $historicalApplicants,
            'isHistory'            => false,
        ]);
    }

    public function history(): void {
        $_GET['view'] = 'history';
        $this->index();
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

        $today = AppDate::todayString();
        $requestedTermDate = !empty($applicant['term_start_date']) ? $applicant['term_start_date'] : (!empty($applicant['date_requested']) ? $applicant['date_requested'] : null);
        $isTermPassed = (!empty($requestedTermDate) && $today > $requestedTermDate);

        if (!in_array($applicant['status'], ['converted', 'lease_created', 'lease_sent', 'rejected'], true) && $isTermPassed) {
            $applicant['status'] = 'rejected';
            $applicant['status_date'] = $requestedTermDate . ' 23:59:59';
            $applicant['auto_rejected'] = true;
            if (!AppDate::isSimulated()) {
                Applicant::updateStatus((int)$id, 'rejected', $applicant['status_date']);
            }
        }

        $properties = Property::all();
        $allUnits = Unit::all();
        $otherApplicants = Applicant::allPendingAndApproved();
        $lease = !empty($applicant['lease_id']) ? Lease::findById((int)$applicant['lease_id']) : null;
        $leaseApplicants = !empty($applicant['lease_id']) ? Applicant::getByLeaseId((int)$applicant['lease_id']) : [];

        $this->render('applicants.view', [
            'title'            => 'Application: ' . $applicant['full_name'],
            'applicant'        => $applicant,
            'properties'       => $properties,
            'allUnits'         => $allUnits,
            'otherApplicants'  => $otherApplicants,
            'lease'            => $lease,
            'leaseApplicants'  => $leaseApplicants
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

        if (!empty($data['status']) && $data['status'] !== $applicant['status']) {
            $data['status_date'] = AppDate::todayString() . ' ' . date('H:i:s');
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
        $allowedStatuses = ['pending', 'approved', 'lease_created', 'lease_sent', 'rejected', 'converted'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        $statusLabels = [
            'pending'       => 'Pending Review',
            'approved'      => 'Approved',
            'lease_created' => 'Review Lease',
            'lease_sent'    => 'Lease Sent',
            'converted'     => 'Converted to Tenant',
            'rejected'      => 'Rejected'
        ];

        Applicant::updateStatus((int)$id, $status);
        $this->setFlash('success', 'Application status updated to ' . ($statusLabels[$status] ?? ucfirst($status)) . '.');
        $this->redirect('/applicants/' . $id);
    }

    /**
     * Step 2 of workflow (after Approved):
     * Create a Draft Lease Agreement with the prospective applicant(s) on it,
     * transitioning their application status to 'lease_created'.
     */
    public function createLease(string $id): void {
        $primaryApplicant = Applicant::findById((int)$id);
        if (!$primaryApplicant) {
            $this->setFlash('error', 'Applicant not found.');
            $this->redirect('/applicants');
        }

        $unitId = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null;
        if (!$unitId) {
            $this->setFlash('error', 'Please select an apartment/unit for the lease agreement.');
            $this->redirect('/applicants/' . $id);
        }

        $unit = Unit::findById($unitId);
        if (!$unit) {
            $this->setFlash('error', 'Selected unit could not be found.');
            $this->redirect('/applicants/' . $id);
        }

        // Collect all prospective applicant IDs to include on this lease agreement
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

        $startDate = !empty($_POST['lease_start_date']) ? $_POST['lease_start_date'] : (!empty($primaryApplicant['term_start_date']) ? $primaryApplicant['term_start_date'] : AppDate::todayString());
        $endDate = !empty($_POST['lease_end_date']) ? $_POST['lease_end_date'] : (!empty($primaryApplicant['term_end_date']) ? $primaryApplicant['term_end_date'] : date('Y-m-d', strtotime($startDate . ' +1 year')));
        $rentAmount = isset($_POST['rent_amount']) && $_POST['rent_amount'] !== '' ? (float)$_POST['rent_amount'] : (float)$unit['target_rent'];
        $securityDeposit = isset($_POST['security_deposit']) && $_POST['security_deposit'] !== '' ? (float)$_POST['security_deposit'] : (float)$unit['security_deposit'];
        $paymentFrequency = !empty($_POST['payment_frequency']) && in_array($_POST['payment_frequency'], ['monthly', 'quarterly', 'yearly'], true) ? $_POST['payment_frequency'] : 'monthly';

        $prospectiveNames = [];
        foreach ($applicantIds as $appId) {
            $appRow = Applicant::findById($appId);
            if ($appRow) {
                $prospectiveNames[] = $appRow['full_name'];
            }
        }

        $customTerms = trim($_POST['terms'] ?? '');
        if ($customTerms === '') {
            $customTerms = count($prospectiveNames) > 1
                ? 'Residential lease agreement prepared for prospective co-applicants: ' . implode(', ', $prospectiveNames) . '.'
                : 'Standard residential lease agreement prepared for prospective applicant: ' . ($prospectiveNames[0] ?? $primaryApplicant['full_name']) . '.';
        }

        // Create Draft Lease Agreement (tenant_id is NULL until all prospective applicants sign)
        $leaseId = Lease::create([
            'unit_id'           => $unitId,
            'tenant_id'         => null,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'rent_amount'       => $rentAmount,
            'deposit_held'      => $securityDeposit,
            'payment_frequency' => $paymentFrequency,
            'status'            => 'draft',
            'terms'             => $customTerms
        ]);

        // Link all selected prospective applicants to this draft lease and transition status to 'lease_created'
        foreach ($applicantIds as $appId) {
            Applicant::assignToDraftLease($appId, $leaseId, $unitId, (int)$unit['property_id']);
        }

        $namesList = implode(' & ', array_map('htmlspecialchars', $prospectiveNames));
        $this->setFlash('success', 'Lease Agreement #' . $leaseId . ' created with prospective applicant(s) ' . $namesList . ' for Unit #' . htmlspecialchars($unit['unit_number']) . '.');
        $this->redirect('/leases/' . $leaseId . '?from=applicant&applicant_id=' . $id);
    }

    /**
     * Remove the created draft lease agreement and return the application(s)
     * back to the 'approved' status.
     */
    public function cancelLease(string $id): void {
        $applicant = Applicant::findById((int)$id);
        if (!$applicant) {
            $this->setFlash('error', 'Applicant not found.');
            $this->redirect('/applicants');
        }

        $leaseId = (int)($applicant['lease_id'] ?? 0);
        if ($leaseId > 0) {
            // Revert all prospective applicants associated with this draft lease back to 'approved'
            Applicant::removeDraftLeaseByLeaseId($leaseId);

            // Delete the draft lease
            Lease::delete($leaseId);

            $this->setFlash('success', 'Draft Lease #' . $leaseId . ' was removed and the application returned to Approved status.');
        } else {
            Applicant::updateStatus((int)$id, 'approved');
            $this->setFlash('success', 'Application status returned to Approved.');
        }

        $this->redirect('/applicants/' . $id);
    }

    /**
     * Step 3 of workflow (after Lease Created):
     * Send the Lease Agreement to the prospective applicant(s) for signature,
     * transitioning their application status to 'lease_sent'.
     */
    public function sendLease(string $id): void {
        $applicant = Applicant::findById((int)$id);
        if (!$applicant) {
            $this->setFlash('error', 'Applicant not found.');
            $this->redirect('/applicants');
        }

        $leaseId = (int)($applicant['lease_id'] ?? 0);
        if ($leaseId > 0) {
            Applicant::markLeaseSentByLeaseId($leaseId);
            $leaseApplicants = Applicant::getByLeaseId($leaseId);
            $names = array_map(function($a) { return htmlspecialchars($a['full_name']); }, $leaseApplicants);
            $namesList = implode(' & ', $names);
            $this->setFlash('success', 'Lease Agreement #' . $leaseId . ' has been sent to ' . $namesList . '. Awaiting applicant signature(s).');
        } else {
            Applicant::updateStatus((int)$id, 'lease_sent');
            $this->setFlash('success', 'Lease Agreement has been sent to ' . htmlspecialchars($applicant['full_name']) . '. Awaiting signature.');
        }

        if (!empty($_POST['from']) && $_POST['from'] === 'lease' && $leaseId > 0) {
            $this->redirect('/leases/' . $leaseId);
        }

        $this->redirect('/applicants/' . $id);
    }

    /**
     * Recall a sent lease agreement and return the application(s)
     * from Step 4 (lease_sent) back to Step 3 (lease_created).
     */
    public function recallLease(string $id): void {
        $applicant = Applicant::findById((int)$id);
        if (!$applicant) {
            $this->setFlash('error', 'Applicant not found.');
            $this->redirect('/applicants');
        }

        $leaseId = (int)($applicant['lease_id'] ?? 0);
        if ($leaseId > 0) {
            Applicant::recallLeaseToCreatedByLeaseId($leaseId);
            $this->setFlash('success', 'Lease agreement was recalled. Application workflow returned to Step 2 (Review Lease).');
        } else {
            Applicant::recallLeaseToCreated((int)$id);
            $this->setFlash('success', 'Application workflow returned to Step 2 (Review Lease).');
        }

        if (!empty($_POST['from']) && $_POST['from'] === 'lease' && $leaseId > 0) {
            $this->redirect('/leases/' . $leaseId);
        }

        $this->redirect('/applicants/' . $id);
    }

    /**
     * Step 4 of workflow (after Lease Sent):
     * Record signature for one or all prospective applicants on the lease.
     * Once ALL prospective applicants on the lease have signed, automatically convert
     * them into Tenants and activate the Lease Agreement.
     */
    public function signLease(string $id): void {
        $applicant = Applicant::findById((int)$id);
        if (!$applicant) {
            $this->setFlash('error', 'Applicant not found.');
            $this->redirect('/applicants');
        }

        $leaseId = (int)($applicant['lease_id'] ?? 0);
        $signAll = !empty($_POST['sign_all']);
        $targetApplicantId = !empty($_POST['target_applicant_id']) ? (int)$_POST['target_applicant_id'] : (int)$id;

        if ($leaseId > 0) {
            if ($signAll) {
                Applicant::markAllLeaseSignedByLeaseId($leaseId);
            } else {
                Applicant::markLeaseSigned($targetApplicantId);
            }

            $leaseApplicants = Applicant::getByLeaseId($leaseId);
            $totalCount = count($leaseApplicants);
            $signedCount = 0;
            $signedApplicantName = $applicant['full_name'];

            foreach ($leaseApplicants as $la) {
                if ((int)$la['id'] === $targetApplicantId) {
                    $signedApplicantName = $la['full_name'];
                }
                if (!empty($la['lease_signed'])) {
                    $signedCount++;
                }
            }

            if ($signedCount < $totalCount) {
                $this->setFlash('success', 'Recorded lease signature for ' . htmlspecialchars($signedApplicantName) . ' (' . $signedCount . ' of ' . $totalCount . ' signed). Once all prospective applicants sign, they will automatically become tenants.');
                if (!empty($_POST['from']) && $_POST['from'] === 'lease' && $leaseId > 0) {
                    $this->redirect('/leases/' . $leaseId);
                }
                $this->redirect('/applicants/' . $id);
            }

            // All prospective applicants on the lease have signed -> convert to Tenants!
            $this->finalizeLeaseAndConvertApplicants($leaseId, (int)$id);
            return;
        }

        // Fallback if no lease_id was linked
        Applicant::markLeaseSigned((int)$id);
        $this->redirect('/applicants/' . $id);
    }

    /**
     * Finalize a fully-signed draft lease: create Tenant records for all prospective applicants
     * on the lease, activate the Lease Agreement, mark the Unit as occupied, and transition
     * all applicants on the lease to 'converted'.
     */
    private function finalizeLeaseAndConvertApplicants(int $leaseId, int $redirectApplicantId): void {
        $lease = Lease::findById($leaseId);
        $leaseApplicants = Applicant::getByLeaseId($leaseId);

        if (!$lease || empty($leaseApplicants)) {
            $this->setFlash('error', 'Could not locate lease agreement or prospective applicants to finalize.');
            $this->redirect('/applicants/' . $redirectApplicantId);
        }

        $unitId = (int)$lease['unit_id'];
        $convertedTenantNames = [];
        $primaryLeaseActivated = false;

        foreach ($leaseApplicants as $app) {
            $appId = (int)$app['id'];
            $tenantId = !empty($app['tenant_id']) ? (int)$app['tenant_id'] : null;

            if (!$tenantId) {
                $nameParts = preg_split('/\s+/', trim($app['full_name']));
                $firstName = array_shift($nameParts);
                $lastName = !empty($nameParts) ? implode(' ', $nameParts) : '—';

                $phone = !empty($app['cell_phone']) ? $app['cell_phone'] : (!empty($app['home_phone']) ? $app['home_phone'] : '—');
                $email = !empty($app['email']) ? $app['email'] : strtolower($firstName . '.' . time() . rand(10, 99) . '@noemail.local');

                $tenantId = Tenant::create([
                    'first_name'        => $firstName,
                    'last_name'         => $lastName,
                    'email'             => $email,
                    'phone'             => $phone,
                    'emergency_contact' => $app['emergency_contact_1_name'] ?: null,
                    'emergency_phone'   => $app['emergency_contact_1_phone'] ?: null,
                    'notes'             => 'Converted from Rental Application #' . $appId . ' upon signing Lease #' . $leaseId . ' on ' . AppDate::todayString() . '.'
                ]);
            }

            if (!$primaryLeaseActivated) {
                Lease::activateWithTenant($leaseId, $tenantId);
                $primaryLeaseActivated = true;
            } else {
                // Create active co-tenant lease record so all co-tenants appear in unit/tenant queries
                Lease::create([
                    'unit_id'           => $unitId,
                    'tenant_id'         => $tenantId,
                    'start_date'        => $lease['start_date'],
                    'end_date'          => $lease['end_date'],
                    'rent_amount'       => $lease['rent_amount'],
                    'deposit_held'      => 0.00,
                    'payment_frequency' => $lease['payment_frequency'] ?? 'monthly',
                    'status'            => 'active',
                    'terms'             => $lease['terms']
                ]);
            }

            Applicant::markConverted($appId, $tenantId, $unitId, $leaseId);
            $convertedTenantNames[] = htmlspecialchars($app['full_name']);
        }

        $namesList = implode(' & ', $convertedTenantNames);
        $this->setFlash('success', 'All prospective applicants (' . $namesList . ') have signed the lease! They are now official Tenants in Unit #' . htmlspecialchars($lease['unit_number'] ?? '') . '.');
        if (!empty($_POST['from']) && $_POST['from'] === 'lease' && $leaseId > 0) {
            $this->redirect('/leases/' . $leaseId);
        }
        $this->redirect('/applicants/' . $redirectApplicantId);
    }

    public function convert(string $id): void {
        // Legacy / direct route delegates to createLease if not yet created
        $this->createLease($id);
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
