<?php
use App\Core\Router;
use App\Core\AppDate;

// Calculate days remaining on the lease agreement
$today = AppDate::today();
$endDate = !empty($lease['end_date']) ? new DateTime($lease['end_date']) : null;
$daysLeft = null;
if ($endDate) {
    $diff = $today->diff($endDate);
    $daysLeft = $diff->invert ? -$diff->days : $diff->days;
}

// Expiring soon criteria: 45 days or less left on the lease agreement
$isExpiringSoon = ($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 45);

// Effective status for display (if active and within 45 days, or explicitly marked expiring_soon)
$effStatus = $lease['status'];
if ($effStatus === 'active' && $isExpiringSoon) {
    $effStatus = 'expiring_soon';
}

$statusBadgeMap = [
    'active'        => ['class' => 'badge-success', 'label' => 'ACTIVE LEASE'],
    'expiring_soon' => ['class' => 'badge-warning', 'label' => 'EXPIRING SOON' . ($daysLeft !== null ? ' (' . ($daysLeft === 0 ? 'TODAY' : ($daysLeft === 1 ? '1 DAY LEFT' : $daysLeft . ' DAYS LEFT')) . ')' : '')],
    'draft'         => ['class' => 'badge-info', 'label' => 'DRAFT LEASE'],
    'expired'       => ['class' => 'badge-warning', 'label' => 'EXPIRED LEASE'],
    'terminated'    => ['class' => 'badge-danger', 'label' => 'TERMINATED LEASE'],
];
$badge = $statusBadgeMap[$effStatus] ?? ['class' => 'badge-subtle', 'label' => strtoupper(str_replace('_', ' ', $effStatus)) . ' LEASE'];

// Workflow / Applicant linkage
$leaseApplicants = $lease['applicants'] ?? [];
$primaryApplicant = !empty($leaseApplicants) ? $leaseApplicants[0] : null;

$from = $_GET['from'] ?? '';
$applicantId = !empty($_GET['applicant_id']) ? (int)$_GET['applicant_id'] : ($primaryApplicant ? (int)$primaryApplicant['id'] : 0);
if (empty($from) && !empty($_SERVER['HTTP_REFERER'])) {
    if (strpos($_SERVER['HTTP_REFERER'], '/applicants/history') !== false) {
        $from = 'history';
    } elseif (strpos($_SERVER['HTTP_REFERER'], '/applicants/') !== false) {
        $from = 'applicant';
        if (preg_match('/\/applicants\/(\d+)/', $_SERVER['HTTP_REFERER'], $m)) {
            $applicantId = (int)$m[1];
        }
    }
}
$fromHistory = ($from === 'history');
$fromApplicant = ($from === 'applicant') || ($applicantId > 0 && !empty($leaseApplicants));

// Workflow stage setup (matching Application & Lease Workflow)
$applicantStatus = $primaryApplicant['status'] ?? ($lease['status'] === 'draft' ? 'lease_created' : ($lease['status'] === 'active' ? 'converted' : ''));
$stageMap = [
    'pending'       => 0,
    'approved'      => 1,
    'lease_created' => 2,
    'lease_sent'    => 3,
    'converted'     => 4,
    'rejected'      => -1,
];
$currentStage = $stageMap[$applicantStatus] ?? ($lease['status'] === 'draft' ? 2 : ($lease['status'] === 'active' ? 4 : 0));
$workflowSteps = [
    1 => '1. Approved',
    2 => '2. Review Lease',
    3 => '3. Lease Sent',
    4 => '4. Signed & Tenant',
];

$signedTotal = 0;
foreach ($leaseApplicants as $pa) {
    if (!empty($pa['lease_signed']) || $pa['status'] === 'converted') {
        $signedTotal++;
    }
}

// Filter placeholders that require manual entry because data was not found
$missingPlaceholders = [];
if (!empty($placeholders)) {
    foreach ($placeholders as $ph) {
        if (!empty($ph['is_missing'])) {
            $missingPlaceholders[] = $ph;
        }
    }
}
?>

<div class="breadcrumb" style="margin-bottom: 16px; font-size: 13px; font-weight: 500;">
    <?php if ($applicantId > 0): ?>
        <?php if (!($lease['status'] === 'draft' || !empty($leaseApplicants))): ?>
            <a href="<?= Router::url('/applicants/' . $applicantId . ($fromHistory ? '?from=history' : '')) ?>" style="display: inline-flex; align-items: center; gap: 6px;">
                &larr; Back to Application &amp; Lease Workflow
            </a>
        <?php endif; ?>
    <?php elseif ($fromHistory): ?>
        <a href="<?= Router::url('/applicants/history') ?>" style="display: inline-flex; align-items: center; gap: 6px;">
            &larr; Back to Historical Applications
        </a>
    <?php else: ?>
        <a href="<?= Router::url('/leases') ?>" style="display: inline-flex; align-items: center; gap: 6px;">
            &larr; Back to Leases
        </a>
    <?php endif; ?>
</div>

<div class="page-header" style="margin-bottom: 24px;">
    <div>
        <?php if ($lease['status'] !== 'draft'): ?>
            <div class="badge <?= $badge['class'] ?>" style="font-weight: 600; padding: 4px 10px; border-radius: 6px;">
                <?= $badge['label'] ?>
            </div>
        <?php endif; ?>
        <h1 class="page-title" style="margin-top: 6px; font-size: 24px; font-weight: 700;">
            Residential Lease Agreement<?= $lease['status'] === 'draft' ? '' : ' #' . $lease['id'] ?>
        </h1>
        <p class="page-subtitle" style="margin-top: 4px; color: var(--text-muted); font-size: 14px;">
            <?= $lease['status'] === 'draft' 
                ? 'Generate and review agreement terms.'
                : 'Residential tenancy agreement terms, attached contracts, and execution records.' ?>
        </p>
    </div>

    <?php if ($lease['status'] !== 'draft'): ?>
    <div class="action-buttons">
        <form action="<?= Router::url('/leases/' . $lease['id'] . '/status') ?>" method="POST" style="display: flex; gap: 8px; align-items: center;">
            <?php if ($fromHistory): ?>
                <input type="hidden" name="from" value="history">
            <?php elseif ($applicantId > 0): ?>
                <input type="hidden" name="from" value="applicant">
                <input type="hidden" name="applicant_id" value="<?= $applicantId ?>">
            <?php endif; ?>
            <select name="status" class="form-control" style="width: auto; border-radius: 8px; border: 1.5px solid var(--ws-border-strong, #d8d3c7); padding: 8px 12px; font-size: 13.5px;">
                <option value="active" <?= $lease['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="draft" <?= $lease['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                <?php if ($isExpiringSoon || $lease['status'] === 'expiring_soon'): ?>
                    <option value="expiring_soon" <?= ($lease['status'] === 'expiring_soon' || ($lease['status'] === 'active' && $isExpiringSoon)) ? 'selected' : '' ?>>Expiring Soon</option>
                <?php endif; ?>
                <option value="terminated" <?= $lease['status'] === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                <option value="expired" <?= $lease['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
            </select>
            <button type="submit" class="btn btn-outline" style="border: 1.5px solid var(--ws-border-strong, #d8d3c7); font-weight: 600; padding: 8px 16px; border-radius: 8px; background: #fff; cursor: pointer;">
                Update Status
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<!-- Full-Width Application & Lease Workflow Banner Card (Includes Workflow Stepper, Stage Actions, and Property, Unit & Prospective Tenants) -->
<?php if ($lease['status'] === 'draft' || !empty($leaseApplicants)): ?>
<div class="card" style="margin-bottom: 24px; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-radius: 12px; overflow: hidden;">
    <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border); padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700;">Steps for Completion</h2>
        </div>
        <div>
            <?php if ($applicantStatus === 'lease_created' || $lease['status'] === 'draft'): ?>
                <!-- Hidden during review as step buttons drive the workflow -->
            <?php elseif ($applicantStatus === 'lease_sent'): ?>
                <span class="badge badge-info" style="font-weight: 600; padding: 4px 10px; border-radius: 6px;">Lease Sent (<?= $signedTotal ?> of <?= count($leaseApplicants) ?> Signed)</span>
            <?php elseif ($applicantStatus === 'converted' || $lease['status'] === 'active'): ?>
                <span class="badge badge-success" style="font-weight: 600; padding: 4px 10px; border-radius: 6px;">Lease Active &amp; Tenancy Established</span>
            <?php else: ?>
                <span class="badge badge-subtle" style="font-weight: 600; padding: 4px 10px; border-radius: 6px;"><?= strtoupper(htmlspecialchars($applicantStatus ?: 'In Progress')) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body" style="padding: 20px 24px;">
        <!-- Stages of Completeness & Step Actions Unified Section -->
        <div style="background: var(--ws-surface-subtle); padding: 18px 20px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
            <!-- Visual Progress Stepper (4 Stages of Completeness) -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px;">
                <?php foreach ($workflowSteps as $stepIdx => $stepLabel):
                    $isPending = ($applicantStatus === 'pending');
                    $isPendingStep1 = ($isPending && $stepIdx === 1);
                    $isCompleted = (!$isPending && ($stepIdx < $currentStage || ($currentStage === 4 && $stepIdx === 4)));
                    $isCurrent = $isPendingStep1 || (!$isPending && $stepIdx === $currentStage && $currentStage !== 4);

                    if ($isPendingStep1) {
                        $bgStyle = '#fffdf7';
                        $borderStyle = 'var(--ws-accent, #f5a623)';
                        $textStyle = 'var(--ws-text, #111)';
                        $stageText = 'Current Stage';
                    } elseif ($isCompleted) {
                        $bgStyle = 'rgba(22, 163, 74, 0.08)';
                        $borderStyle = '#16a34a';
                        $textStyle = '#16a34a';
                        $stageText = 'Completed';
                    } elseif ($isCurrent) {
                        $bgStyle = '#ffffff';
                        $borderStyle = 'var(--ws-accent)';
                        $textStyle = 'var(--ws-text)';
                        $stageText = 'Current Stage';
                    } else {
                        $bgStyle = 'transparent';
                        $borderStyle = 'var(--ws-border-subtle)';
                        $textStyle = 'var(--ws-text-muted)';
                        $stageText = 'Upcoming';
                    }
                ?>
                    <div style="padding: 10px 14px; border-radius: 10px; border: 1.5px solid <?= $borderStyle ?>; background: <?= $bgStyle ?>; text-align: center; font-size: 13px; font-weight: <?= ($isCompleted || $isCurrent) ? '700' : '500' ?>; color: <?= $textStyle ?>; transition: all 0.2s ease;">
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8; margin-bottom: 2px;">
                            <?= $stageText ?>
                        </div>
                        <div><?= $isCompleted ? '&#10003; ' : '' ?><?= htmlspecialchars($stepLabel) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Step Actions & Details -->
            <div style="border-top: 1px solid var(--ws-border-subtle); padding-top: 14px;">
                <?php if ($applicantStatus === 'lease_created' || ($lease['status'] === 'draft' && $applicantStatus !== 'lease_sent' && $applicantStatus !== 'converted')): ?>
                    <div>
                        <div>
                            <div style="font-weight: 700; font-size: 14.5px;">Step 2: Review Lease Agreement:</div>
                            <div class="text-muted text-xs" style="margin-top: 3px;">
                                The draft lease agreement is prepared. Review terms below and finalize the agreement.
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center; justify-content: flex-end; margin-top: 14px; flex-wrap: wrap;">
                            <?php if ($primaryApplicant): ?>
                                <form action="<?= Router::url('/applicants/' . $primaryApplicant['id'] . '/cancel-lease') ?>" method="POST" style="margin: 0; display: inline-flex;">
                                    <button type="submit" class="btn btn-outline" style="border: 1.5px solid var(--ws-border-strong, #d8d3c7); font-weight: 600; padding: 9px 18px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; background: #fff; color: var(--ws-text);">
                                        &larr; Revert Back
                                    </button>
                                </form>
                                <a href="<?= Router::url('/leases?paused=' . $lease['id']) ?>" class="btn btn-outline" style="border: 1.5px solid var(--ws-border-strong, #d8d3c7); font-weight: 600; padding: 9px 18px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; background: #fff; color: var(--ws-text);" title="Pause workflow and return to the Leases table">
                                    Pause &amp; Save
                                </a>
                                <form action="<?= Router::url('/applicants/' . $primaryApplicant['id'] . '/send-lease') ?>" method="POST" style="margin: 0; display: inline-flex;">
                                    <input type="hidden" name="from" value="lease">
                                    <button type="submit" class="btn btn-primary" style="font-weight: 600; padding: 9px 18px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                                        Send Lease &rarr;
                                    </button>
                                </form>
                            <?php else: ?>
                                <form action="<?= Router::url('/leases/' . $lease['id'] . '/status') ?>" method="POST" style="margin: 0; display: inline-flex;">
                                    <input type="hidden" name="status" value="active">
                                    <button type="submit" class="btn btn-primary" style="font-weight: 600; padding: 9px 18px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                                        Activate Lease &rarr;
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php elseif ($applicantStatus === 'lease_sent'): ?>
                        <div>
                            <div>
                                <div style="font-weight: 700; font-size: 14.5px;">Step 3: Lease Sent — Awaiting Signature(s):</div>
                                <div class="text-muted text-xs" style="margin-top: 3px; line-height: 1.5;">
                                    Once all prospective applicants sign the lease, they will automatically become official tenants and the lease will activate.
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center; justify-content: flex-end; margin-top: 14px; flex-wrap: wrap;">
                                <?php if ($primaryApplicant): ?>
                                    <form action="<?= Router::url('/applicants/' . $primaryApplicant['id'] . '/recall-lease') ?>" method="POST" style="margin: 0; display: inline-flex;">
                                        <input type="hidden" name="from" value="lease">
                                        <button type="submit" class="btn btn-outline" style="border: 1.5px solid var(--ws-border-strong, #d8d3c7); font-weight: 600; padding: 9px 18px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; background: #fff;" onclick="return confirm('Recall this sent lease and return to Step 2 (Review Lease)?');">
                                            &larr; Revert Back
                                        </button>
                                    </form>
                                    <a href="<?= Router::url('/leases?paused=' . $lease['id']) ?>" class="btn btn-outline" style="border: 1.5px solid var(--ws-border-strong, #d8d3c7); font-weight: 600; padding: 9px 18px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; background: #fff; color: var(--ws-text);" title="Pause workflow and return to the Leases in-progress table">
                                        Pause &amp; Save Workflow
                                    </a>
                                    <form action="<?= Router::url('/applicants/' . $primaryApplicant['id'] . '/sign-lease') ?>" method="POST" style="margin: 0; display: inline-flex;">
                                        <input type="hidden" name="from" value="lease">
                                        <input type="hidden" name="sign_all" value="1">
                                        <button type="submit" class="btn btn-primary" style="font-weight: 600; padding: 9px 18px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                                            <?= count($leaseApplicants) > 1 ? 'Mark All Signed &rarr;' : 'Mark Signed &rarr;' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($applicantStatus === 'converted' || $lease['status'] === 'active'): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                            <div>
                                <div class="text-muted text-xs" style="line-height: 1.5;">
                                    All prospective applicants signed the lease agreement. The lease is active and unit occupancy is confirmed.
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <?php if (!empty($lease['tenant_id'])): ?>
                                    <a href="<?= Router::url('/tenants/' . $lease['tenant_id']) ?>" class="btn btn-primary" style="font-weight: 600; padding: 9px 18px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                                        View Tenant Profile &rarr;
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Card 2: Comprehensive Lease Agreement Details -->
<div class="card" style="margin-bottom: 24px; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-radius: 12px; overflow: hidden;">
    <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border); padding: 16px 20px;">
        <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700;">Lease Agreement Details</h2>
    </div>
    <div class="card-body" style="padding: 20px 24px;">
        <!-- Prospective Tenants / Resident Information (First) -->
        <div style="margin-bottom: 20px;">
            <div style="margin-bottom: 10px;">
                <span class="text-muted text-xs" style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    <?= !empty($lease['tenant_id']) ? 'Current Resident(s)' : 'Prospective Applicants Included on Lease' ?>:
                </span>
            </div>

            <div style="background: var(--ws-surface-subtle); padding: 16px 18px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                <?php if (!empty($leaseApplicants)): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px;">
                        <?php foreach ($leaseApplicants as $idx => $pa): 
                            $paSigned = !empty($pa['lease_signed']) || $pa['status'] === 'converted';
                        ?>
                            <div>
                                <span class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 6px;">
                                    Applicant <?= $idx + 1 ?>
                                </span>
                                <div style="background: #f4f1ea; padding: 12px 16px; border-radius: 8px; border: 1px solid #dfd9cb; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <a href="<?= Router::url('/applicants/' . $pa['id']) ?>" style="font-weight: 700; font-size: 15px; color: var(--ws-text); text-decoration: none;">
                                            <?= htmlspecialchars($pa['full_name']) ?>
                                        </a>
                                        <?php if ($paSigned): ?>
                                            <span class="badge badge-success" style="font-size: 11px; padding: 2px 9px; font-weight: 600; border-radius: 9999px;">Signed</span>
                                        <?php elseif ($applicantStatus === 'lease_sent'): ?>
                                            <span class="badge badge-info" style="font-size: 11px; padding: 2px 9px; font-weight: 600; border-radius: 9999px;">Awaiting Signature</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted text-xs" style="margin-top: 5px; line-height: 1.4;">
                                        <?php
                                        $contactParts = [];
                                        if (!empty($pa['email'])) $contactParts[] = htmlspecialchars($pa['email']);
                                        $phone = !empty($pa['cell_phone']) ? $pa['cell_phone'] : (!empty($pa['home_phone']) ? $pa['home_phone'] : '');
                                        if (!empty($phone)) $contactParts[] = htmlspecialchars($phone);
                                        echo !empty($contactParts) ? implode(' • ', $contactParts) : 'Applicant Profile';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                        <div style="background: #f4f1ea; padding: 12px 16px; border-radius: 8px; border: 1px solid #dfd9cb; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                            <span class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 4px;">
                                Current Resident
                            </span>
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <strong style="font-weight: 700; font-size: 15px; color: var(--ws-text);">
                                    <?= htmlspecialchars($lease['tenant_name'] ?: 'Unassigned') ?>
                                </strong>
                                <?php if (!empty($lease['tenant_id'])): ?>
                                    <a href="<?= Router::url('/tenants/' . $lease['tenant_id']) ?>" class="btn btn-outline btn-xs" style="border: 1.5px solid var(--ws-border-strong, #d8d3c7); font-weight: 600; padding: 3px 8px; border-radius: 6px; background: #fff;">
                                        View Profile &rarr;
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted text-xs" style="margin-top: 5px; line-height: 1.4;">
                                <?php
                                $tenantParts = [];
                                if (!empty($lease['tenant_email'])) $tenantParts[] = htmlspecialchars($lease['tenant_email']);
                                if (!empty($lease['tenant_phone'])) $tenantParts[] = htmlspecialchars($lease['tenant_phone']);
                                echo !empty($tenantParts) ? implode(' • ', $tenantParts) : 'Resident Record';
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Property & Unit Overview Section (Second) -->
        <div style="margin-bottom: 20px;">
            <div style="margin-bottom: 10px;">
                <span class="text-muted text-xs" style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    Property &amp; Unit Premises:
                </span>
            </div>
            <div style="background: var(--ws-surface-subtle); padding: 16px 18px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <span class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 2px;">Property</span>
                        <div style="font-weight: 700; font-size: 15px; color: var(--ws-text);">
                            <?= htmlspecialchars($lease['property_name']) ?>
                        </div>
                        <?php 
                        $addressParts = array_filter([
                            $lease['address_line1'] ?? '',
                            $lease['address_line2'] ?? '',
                            $lease['city'] ?? '',
                            $lease['state'] ?? '',
                            $lease['zip_code'] ?? ''
                        ]);
                        if (!empty($addressParts)): ?>
                            <div class="text-muted text-xs" style="margin-top: 3px; line-height: 1.4;">
                                <?= htmlspecialchars(implode(', ', $addressParts)) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <span class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 2px;">Unit Specification</span>
                        <div style="font-weight: 700; font-size: 15px; color: var(--ws-text);">
                            Unit <?= htmlspecialchars($lease['unit_number']) ?>
                        </div>
                        <div class="text-muted text-xs" style="margin-top: 3px; line-height: 1.4;">
                            <?php
                            $unitMeta = [];
                            if (!empty($lease['bedrooms'])) $unitMeta[] = $lease['bedrooms'] . ' Bed' . ($lease['bedrooms'] > 1 ? 's' : '');
                            if (!empty($lease['bathrooms'])) $unitMeta[] = $lease['bathrooms'] . ' Bath' . ($lease['bathrooms'] > 1 ? 's' : '');
                            if (!empty($lease['square_feet'])) $unitMeta[] = number_format($lease['square_feet']) . ' sq ft';
                            echo !empty($unitMeta) ? htmlspecialchars(implode(' • ', $unitMeta)) : 'Residential Suite';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agreement Terms Section (Third / Last) -->
        <div>
            <div style="margin-bottom: 10px;">
                <span class="text-muted text-xs" style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    Agreement Terms:
                </span>
            </div>
            <?php
            $leaseType = 'Fixed';
            if (!empty($primaryApplicant['tenancy_type'])) {
                $leaseType = ucfirst($primaryApplicant['tenancy_type']);
            } elseif (!empty($lease['tenancy_type'])) {
                $leaseType = ucfirst($lease['tenancy_type']);
            } elseif (!empty($lease['terms']) && preg_match('/periodic/i', $lease['terms'])) {
                $leaseType = 'Periodic';
            } elseif (empty($lease['end_date'])) {
                $leaseType = 'Periodic';
            }
            ?>
            <div style="background: var(--ws-surface-subtle); padding: 16px 18px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px;">
                    <div>
                        <span class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 2px;">Monthly Rent</span>
                        <div class="text-success font-bold" style="font-size: 17px;">
                            $<?= number_format((float)$lease['rent_amount'], 2) ?>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 2px;">Security Deposit</span>
                        <div style="font-weight: 700; font-size: 16px; color: var(--ws-text);">
                            $<?= number_format((float)$lease['deposit_held'], 2) ?>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 4px;">Lease Type</span>
                        <div>
                            <span class="badge badge-subtle" style="font-size: 13.5px; padding: 3px 12px; font-weight: 600; background: #e5e2da; border: 1px solid #c8c3b7; color: var(--ws-text);">
                                <?= htmlspecialchars($leaseType) ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 2px;">Start Date</span>
                        <div style="font-weight: 700; font-size: 15px; color: var(--ws-text);">
                            <?= htmlspecialchars($lease['start_date']) ?>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 2px;">End Date</span>
                        <div style="font-weight: 700; font-size: 15px; color: var(--ws-text); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <?php if (!empty($lease['end_date'])): ?>
                                <?= htmlspecialchars($lease['end_date']) ?>
                                <?php if ($isExpiringSoon): ?>
                                    <span class="badge badge-warning" style="font-size: 11px;">
                                        <?= $daysLeft === 0 ? 'Expires Today' : ($daysLeft === 1 ? '1 day left' : $daysLeft . ' days left') ?>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">&mdash;</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; display: block; margin-bottom: 2px;">Payment Frequency</span>
                        <div style="font-weight: 700; font-size: 15px; color: var(--ws-text);">
                            <?= ucfirst($lease['payment_frequency']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attached Lease Contracts Card -->
<div class="card" style="margin-bottom: 24px; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-radius: 12px; overflow: hidden;">
    <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border); padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700;">Attached Lease Contracts</h2>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <button type="button" onclick="document.getElementById('leaseUploadInput').click();" title="Attach signed contract or document" style="width: 28px; height: 28px; border-radius: 50%; background: transparent; border: 1.5px solid var(--ws-accent, #f5a623); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: var(--ws-accent, #f5a623); font-size: 16px; font-weight: 700; line-height: 1; transition: all 0.15s ease;" onmouseover="this.style.background='rgba(245, 166, 35, 0.1)'; this.style.borderColor='var(--ws-accent-hover, #e69512)'; this.style.color='var(--ws-accent-hover, #e69512)';" onmouseout="this.style.background='transparent'; this.style.borderColor='var(--ws-accent, #f5a623)'; this.style.color='var(--ws-accent, #f5a623)';">
                +
            </button>
            <form id="formLeaseUpload" action="<?= Router::url('/leases/' . $lease['id'] . '/upload-document') ?>" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="file" id="leaseUploadInput" name="document" onchange="document.getElementById('formLeaseUpload').submit();" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            </form>
        </div>
    </div>
    <div class="card-body" style="padding: 20px 24px;">
        <?php if (empty($documents)): ?>
            <div class="text-center text-muted" style="padding: 20px;">
                <p style="margin: 0; font-size: 13.5px;">No document attached to this lease agreement yet.</p>
            </div>
        <?php else: ?>
            <div class="document-list">
                <?php foreach ($documents as $doc): ?>
                    <div class="document-item">
                        <div class="document-info">
                            <div class="document-name" style="font-weight: 600;"><?= htmlspecialchars($doc['original_name']) ?></div>
                            <div class="document-meta">
                                <?= round($doc['file_size'] / 1024, 1) ?> KB &bull; Uploaded <?= date('M d, Y', strtotime($doc['created_at'])) ?>
                            </div>
                        </div>
                        <div class="document-actions">
                            <a href="<?= Router::url('/documents/' . $doc['id']) ?>" target="_blank" class="btn btn-outline btn-xs" style="border: 1.5px solid var(--ws-border-strong, #d8d3c7); font-weight: 600; padding: 4px 10px; border-radius: 6px; background: #fff;">View</a>
                            <a href="<?= Router::url('/documents/' . $doc['id'] . '/download') ?>" class="btn btn-primary btn-xs" style="font-weight: 600; padding: 4px 10px; border-radius: 6px;">Download</a>
                            <form action="<?= Router::url('/documents/' . $doc['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this stored document?');" style="display:inline;">
                                <button type="submit" class="btn btn-danger btn-outline btn-xs" style="font-weight: 600; padding: 4px 10px; border-radius: 6px;">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Streamlined Lease Document Generation from Configured Template (Only in Draft Review / Step 2) -->
<?php if ($applicantStatus === 'lease_created' || ($lease['status'] === 'draft' && $applicantStatus !== 'lease_sent' && $applicantStatus !== 'converted')): ?>
<div id="generateLeaseSection" class="card" style="margin-bottom: 24px; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-radius: 12px; overflow: hidden;">
    <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border); padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700;">Generate Lease Agreement</h2>
        </div>
        <a href="<?= Router::url('/leases/templates?lease_id=' . $lease['id'] . (!empty($selectedTemplate['id']) ? '&template_id=' . $selectedTemplate['id'] : '')) ?>" class="btn btn-outline" style="border: 1.5px solid var(--ws-border-strong, #d8d3c7); font-weight: 600; padding: 7px 14px; border-radius: 8px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; background: var(--ws-surface-subtle, #faf9f6); color: var(--ws-text, #111); transition: all 0.15s ease;">
            Configure Lease Templates
        </a>
    </div>

    <div class="card-body" style="padding: 20px 24px;">
        <?php if (!empty($templateError)): ?>
            <div class="alert alert-danger" style="margin-bottom: 18px; border-radius: 8px;">
                <?= htmlspecialchars($templateError) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($templates)): ?>
            <div class="text-center text-muted" style="padding: 30px;">
                <p style="margin-bottom: 14px; font-size: 14px;">No lease templates have been uploaded yet.</p>
                <a href="<?= Router::url('/leases/templates?lease_id=' . $lease['id']) ?>" class="btn btn-primary" style="font-weight: 600; padding: 9px 18px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                    Upload &amp; Configure Template
                </a>
            </div>
        <?php else: ?>
            <form action="<?= Router::url('/leases/' . $lease['id'] . '/generate-document') ?>" method="POST" id="formGenerateLease">
                <input type="hidden" name="remove_red_color" value="1">
                <?php if ($applicantId > 0): ?>
                    <input type="hidden" name="from" value="applicant">
                    <input type="hidden" name="applicant_id" value="<?= $applicantId ?>">
                <?php elseif ($fromHistory): ?>
                    <input type="hidden" name="from" value="history">
                <?php endif; ?>

                <?php if (!empty($selectedTemplate)): ?>
                    <input type="hidden" name="template_id" value="<?= $selectedTemplate['id'] ?>">
                <?php endif; ?>

                <!-- 1. Available Lease Templates Cards (Always Visible First) -->
                <div style="margin-bottom: 20px;">
                    <div style="margin-bottom: 10px;">
                        <span class="text-muted text-xs" style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                            Available Lease Templates:
                        </span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
                        <?php foreach ($templates as $tmpl): 
                            $isSelected = (!empty($selectedTemplate['id']) && $selectedTemplate['id'] == $tmpl['id']);
                            $tmplUrl = Router::url('/leases/' . $lease['id'] . '?template_id=' . $tmpl['id'] . ($applicantId > 0 ? '&from=applicant&applicant_id=' . $applicantId : ($fromHistory ? '&from=history' : '')) . '#generateLeaseSection');
                        ?>
                            <div onclick="window.location.href='<?= $tmplUrl ?>';" 
                                 style="cursor: pointer; padding: 14px 16px; border-radius: 10px; border: 2px solid <?= $isSelected ? 'var(--ws-accent, #f5a623)' : 'var(--ws-border, #ebe7df)' ?>; background: <?= $isSelected ? '#fffdf7' : 'var(--ws-surface-subtle, #faf9f6)' ?>; box-shadow: <?= $isSelected ? '0 3px 12px rgba(245, 166, 35, 0.16)' : 'none' ?>; display: flex; align-items: center; justify-content: space-between; gap: 12px; transition: all 0.15s ease;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 22px; height: 22px; border-radius: 50%; border: 2px solid <?= $isSelected ? 'var(--ws-accent, #f5a623)' : 'var(--ws-border-strong, #d8d3c7)' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #fff;">
                                        <?php if ($isSelected): ?>
                                            <div style="width: 10px; height: 10px; border-radius: 50%; background: var(--ws-accent, #f5a623);"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="width: 32px; height: 40px; background: #ffffff; border: 1.5px solid <?= $isSelected ? 'var(--ws-accent, #f5a623)' : 'var(--ws-border-strong, #d8d3c7)' ?>; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); padding: 4px 3px; display: flex; flex-direction: column; justify-content: space-between; flex-shrink: 0;">
                                        <div style="display: flex; flex-direction: column; gap: 2.5px;">
                                            <div style="height: 3px; width: 60%; background: var(--ws-text, #111); border-radius: 1px;"></div>
                                            <div style="height: 2px; width: 100%; background: #e5e2da; border-radius: 1px;"></div>
                                            <div style="height: 2px; width: 80%; background: #e5e2da; border-radius: 1px;"></div>
                                            <div style="height: 2px; width: 90%; background: #e5e2da; border-radius: 1px;"></div>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                                            <span style="font-size: 6.5px; font-weight: 800; color: var(--ws-accent, #f5a623); letter-spacing: 0.2px; line-height: 1;">DOCX</span>
                                            <?php if ($isSelected): ?>
                                                <div style="width: 8px; height: 8px; border-radius: 50%; background: rgba(245, 166, 35, 0.2); border: 1px solid rgba(245, 166, 35, 0.7); display: flex; align-items: center; justify-content: center; font-size: 5.5px; color: var(--ws-accent, #f5a623); font-weight: 900; line-height: 1;">&#10003;</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; font-size: 14px; color: var(--ws-text);">
                                            <?= htmlspecialchars($tmpl['name']) ?>
                                        </div>
                                        <div class="text-muted text-xs" style="margin-top: 2px;">
                                            <?= htmlspecialchars($tmpl['original_name']) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($isSelected): ?>
                                    <span style="font-size: 11px; padding: 3px 10px; font-weight: 700; border-radius: 9999px; background: rgba(245, 166, 35, 0.15); color: #b45309; border: 1px solid rgba(245, 166, 35, 0.35);">
                                        Active
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. Selected Template Details & Generate Lease Action Row -->
                <?php if (!empty($selectedTemplate)): ?>
                    <div style="background: var(--ws-surface-subtle); padding: 18px 20px; border-radius: 10px; border: 1px solid var(--ws-border-subtle); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                        <!-- Document Preview Sheet Tile + Meta Details -->
                        <div style="display: flex; align-items: center; gap: 18px; min-width: 280px;">
                            <!-- Mini Page Tile -->
                            <div style="width: 58px; height: 76px; background: #ffffff; border: 1.5px solid var(--ws-accent, #f5a623); border-radius: 6px; box-shadow: 0 2px 8px rgba(245, 166, 35, 0.12); padding: 7px 6px; display: flex; flex-direction: column; justify-content: space-between; flex-shrink: 0; position: relative;">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <div style="height: 5px; width: 65%; background: var(--ws-text, #111); border-radius: 2px;"></div>
                                    <div style="height: 3px; width: 100%; background: #e5e2da; border-radius: 2px;"></div>
                                    <div style="height: 3px; width: 85%; background: #e5e2da; border-radius: 2px;"></div>
                                    <div style="height: 3px; width: 90%; background: #e5e2da; border-radius: 2px;"></div>
                                    <div style="height: 3px; width: 70%; background: #e5e2da; border-radius: 2px;"></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                                    <span style="font-size: 8.5px; font-weight: 800; color: var(--ws-accent, #f5a623); letter-spacing: 0.3px;">DOCX</span>
                                    <div style="width: 12px; height: 12px; border-radius: 50%; background: rgba(245, 166, 35, 0.2); border: 1.5px solid rgba(245, 166, 35, 0.7); display: flex; align-items: center; justify-content: center; font-size: 7.5px; color: var(--ws-accent, #f5a623); font-weight: 900;">&#10003;</div>
                                </div>
                            </div>

                            <!-- Template Description -->
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span style="font-weight: 700; font-size: 15.5px; color: var(--ws-text);">
                                        <?= htmlspecialchars($selectedTemplate['name']) ?>
                                    </span>
                                    <span class="badge badge-success" style="font-size: 10.5px; padding: 2px 8px; font-weight: 600; border-radius: 9999px;">
                                        Ready to Generate
                                    </span>
                                </div>
                                <div class="text-muted text-xs" style="margin-top: 4px; line-height: 1.4;">
                                    File: <span style="font-weight: 600; color: var(--ws-text);"><?= htmlspecialchars($selectedTemplate['original_name'] ?? 'template.docx') ?></span>
                                    &bull; <?= count($placeholders) ?> auto-mapped fields
                                </div>
                            </div>
                        </div>

                        <!-- Primary Generate Action Button on Right -->
                        <div>
                            <button type="submit" class="btn btn-primary" style="font-weight: 600; padding: 11px 24px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); cursor: pointer; font-size: 14.5px;">
                                Generate Lease Agreement
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($missingPlaceholders)): ?>
                        <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 10px; padding: 16px 20px; margin-top: 20px;">
                            <div style="font-weight: 700; font-size: 13.5px; color: #92400e; margin-bottom: 10px;">
                                The following placeholder(s) were not found in the application/unit/property data. Please fill them in manually if needed:
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px;">
                                <?php foreach ($missingPlaceholders as $mIdx => $mph): ?>
                                    <div>
                                        <label class="form-label" style="font-size: 12px; font-weight: 600; margin-bottom: 4px; display: block;">
                                            <span style="font-family: monospace; color: #dc2626;"><?= htmlspecialchars($mph['original_text']) ?></span>
                                            <span class="text-muted" style="font-weight: 400;">(<?= htmlspecialchars($mph['field_label']) ?>)</span>
                                        </label>
                                        <input type="hidden" name="placeholder_keys[<?= $mIdx ?>]" value="<?= htmlspecialchars($mph['original_text']) ?>">
                                        <input type="text" name="placeholder_values[<?= $mIdx ?>]" value="" class="form-control" placeholder="Enter value manually..." style="border-radius: 8px; border: 1.5px solid #fde68a; padding: 8px 12px; font-size: 13.5px;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
