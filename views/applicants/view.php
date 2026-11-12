<?php
use App\Core\Router;
use App\Core\AppDate;

$applicantDocs = [];
if (!empty($applicant['documents'])) {
    $decoded = json_decode($applicant['documents'], true);
    if (is_array($decoded)) {
        $applicantDocs = $decoded;
    }
}

$from = $_GET['from'] ?? '';
if (empty($from) && !empty($_SERVER['HTTP_REFERER'])) {
    if (strpos($_SERVER['HTTP_REFERER'], '/applicants/history') !== false) {
        $from = 'history';
    }
}
$fromHistory = ($from === 'history');
?>

<div class="breadcrumb">
    <?php if ($fromHistory): ?>
        <a href="<?= Router::url('/applicants/history') ?>">&larr; Back to Historical Applications</a>
    <?php else: ?>
        <a href="<?= Router::url('/applicants') ?>">&larr; Back to Active Applicants</a>
    <?php endif; ?>
</div>

<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <h1 class="page-title" style="margin: 0;"><?= htmlspecialchars($applicant['full_name']) ?></h1>
            <?php if ($applicant['status'] === 'approved'): ?>
                <span class="badge badge-success">Approved</span>
            <?php elseif ($applicant['status'] === 'lease_created'): ?>
                <span class="badge badge-info">Lease Created</span>
            <?php elseif ($applicant['status'] === 'lease_sent'): ?>
                <span class="badge badge-info">Lease Sent</span>
                <?php
                $totalOnLease = (int)($applicant['lease_total_applicants'] ?? count($leaseApplicants ?? []));
                $signedOnLease = (int)($applicant['lease_signed_applicants'] ?? 0);
                ?>
                <?php if (!empty($applicant['lease_signed'])): ?>
                    <span class="badge badge-success">Signed by Applicant<?= $totalOnLease > 1 ? ' (' . $signedOnLease . ' of ' . $totalOnLease . ' Signed)' : '' ?></span>
                <?php else: ?>
                    <span class="badge badge-warning">Awaiting Signature<?= $totalOnLease > 1 ? ' (' . $signedOnLease . ' of ' . $totalOnLease . ' Signed)' : '' ?></span>
                <?php endif; ?>
            <?php elseif ($applicant['status'] === 'converted'): ?>
                <span class="badge badge-success">Converted to Tenant</span>
                <?php 
                $leaseStart = !empty($applicant['lease_start_date']) ? $applicant['lease_start_date'] : $applicant['term_start_date'];
                $hasCommenced = (!empty($leaseStart) && $leaseStart <= AppDate::todayString());
                ?>
                <?php if ($hasCommenced): ?>
                    <span class="badge badge-subtle" title="Lease period has commenced; application archived in History">Archived in History</span>
                <?php endif; ?>
            <?php elseif ($applicant['status'] === 'rejected'): ?>
                <span class="badge badge-danger">Rejected</span>
                <?php 
                $rejTimestamp = !empty($applicant['status_date']) ? strtotime($applicant['status_date']) : strtotime($applicant['updated_at'] ?? $applicant['created_at']);
                $rejDays = (strtotime(AppDate::todayString()) - strtotime(date('Y-m-d', $rejTimestamp))) / 86400;
                if ($rejDays >= 1 || $fromHistory): ?>
                    <span class="badge badge-subtle" title="Application archived in History">Archived in History</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="badge badge-warning">Pending Review</span>
            <?php endif; ?>
        </div>
        <p class="page-subtitle" style="margin-top: 4px;">Rental Application Details &amp; Decision Center</p>
    </div>
    
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <?php if (!$fromHistory): ?>
            <a href="<?= Router::url('/applicants/' . $applicant['id'] . '/edit') ?>" class="btn btn-outline">Edit Application</a>
        <?php endif; ?>

        <?php if ($applicant['status'] === 'converted'): ?>
            <?php if (!empty($applicant['lease_id'])): ?>
                <a href="<?= Router::url('/leases/' . $applicant['lease_id'] . ($fromHistory ? '?from=history' : '')) ?>" class="btn btn-outline">
                    View Lease Agreement
                </a>
            <?php endif; ?>
            <?php if (!empty($applicant['tenant_id'])): ?>
                <a href="<?= Router::url('/tenants/' . $applicant['tenant_id'] . ($fromHistory ? '?from=history' : '')) ?>" class="btn btn-primary">
                    View Tenant Profile &rarr;
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Workflow stage setup
$stageMap = [
    'pending'       => 0,
    'approved'      => 1,
    'lease_created' => 2,
    'lease_sent'    => 3,
    'converted'     => 4,
    'rejected'      => -1,
];
$currentStage = $stageMap[$applicant['status']] ?? 0;
$workflowSteps = [
    1 => '1. Approved',
    2 => '2. Lease Created',
    3 => '3. Lease Sent',
    4 => '4. Signed & Tenant',
];
$prospectiveList = !empty($leaseApplicants) ? $leaseApplicants : (!empty($applicant['lease_id']) ? [$applicant] : []);
$signedTotal = 0;
foreach ($prospectiveList as $pa) {
    if (!empty($pa['lease_signed']) || $pa['status'] === 'converted') {
        $signedTotal++;
    }
}
?>

<!-- Full-Width Application & Lease Workflow Banner Card -->
<?php if ($applicant['status'] !== 'rejected'): ?>
<div class="card" style="margin-bottom: 24px; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 18px;">📋</span>
            <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700;">Application &amp; Lease Workflow</h2>
        </div>
        <div>
            <?php if ($applicant['status'] === 'pending'): ?>
                <span class="badge badge-warning">Awaiting Application Review</span>
            <?php elseif ($applicant['status'] === 'approved'): ?>
                <span class="badge badge-success">Approved — Ready for Lease</span>
            <?php elseif ($applicant['status'] === 'lease_created'): ?>
                <span class="badge badge-info">Draft Lease Created</span>
            <?php elseif ($applicant['status'] === 'lease_sent'): ?>
                <span class="badge badge-info">Lease Sent (<?= $signedTotal ?> of <?= count($prospectiveList) ?> Signed)</span>
            <?php elseif ($applicant['status'] === 'converted'): ?>
                <span class="badge badge-success">&#10003; Lease Active &amp; Tenancy Established</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body" style="padding: 20px 24px;">
        <!-- Visual Progress Stepper -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px;">
            <?php foreach ($workflowSteps as $stepIdx => $stepLabel):
                $isCompleted = ($currentStage >= $stepIdx);
                $isCurrent = ($currentStage + 1 === $stepIdx || ($stepIdx === 4 && $currentStage === 4));
                $bgStyle = $isCompleted ? 'rgba(22, 163, 74, 0.08)' : ($isCurrent ? 'var(--ws-surface-subtle)' : 'transparent');
                $borderStyle = $isCompleted ? '#16a34a' : ($isCurrent ? 'var(--ws-accent)' : 'var(--ws-border-subtle)');
                $textStyle = $isCompleted ? '#16a34a' : ($isCurrent ? 'var(--ws-text)' : 'var(--ws-text-muted)');
            ?>
                <div style="padding: 10px 14px; border-radius: 10px; border: 1.5px solid <?= $borderStyle ?>; background: <?= $bgStyle ?>; text-align: center; font-size: 13px; font-weight: <?= ($isCompleted || $isCurrent) ? '700' : '500' ?>; color: <?= $textStyle ?>; transition: all 0.2s ease;">
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8; margin-bottom: 2px;">
                        <?= $isCompleted ? 'Completed' : ($isCurrent ? 'Current Stage' : 'Upcoming') ?>
                    </div>
                    <div><?= $isCompleted ? '&#10003; ' : '' ?><?= htmlspecialchars($stepLabel) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Stage Actions & Details Panel -->
        <div style="background: var(--ws-surface-subtle); padding: 16px 20px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
            <?php if ($applicant['status'] === 'pending'): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div>
                        <div style="font-weight: 700; font-size: 14.5px;">Step 1: Application Review</div>
                        <div class="text-muted text-xs" style="margin-top: 3px;">
                            Review the applicant's income, background check, and references below. Approve the application to proceed with drafting the lease agreement.
                        </div>
                    </div>
                    <?php if (!$fromHistory): ?>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <form action="<?= Router::url('/applicants/' . $applicant['id'] . '/status') ?>" method="POST" style="margin: 0;">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-primary btn-sm" style="background: #16a34a; border-color: #16a34a;">Approve Application</button>
                            </form>
                            <form action="<?= Router::url('/applicants/' . $applicant['id'] . '/status') ?>" method="POST" style="margin: 0;">
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn-outline btn-danger btn-sm">Reject Application</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($applicant['status'] === 'approved'): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div>
                        <div style="font-weight: 700; font-size: 14.5px; color: #16a34a;">&#10003; Application Approved — Ready to Draft Lease</div>
                        <div class="text-muted text-xs" style="margin-top: 3px;">
                            Create a residential tenancy lease agreement with <?= htmlspecialchars($applicant['full_name']) ?> and any prospective co-applicants on it.
                        </div>
                    </div>
                    <?php if (!$fromHistory): ?>
                        <div style="display: flex; gap: 8px; align-items: stretch; flex-wrap: wrap;">
                            <form action="<?= Router::url('/applicants/' . $applicant['id'] . '/status') ?>" method="POST" style="margin: 0; display: flex;">
                                <input type="hidden" name="status" value="pending">
                                <button type="submit" class="btn btn-outline" title="Revert to Pending Review">
                                    &larr; Revert to Pending
                                </button>
                            </form>
                            <button type="button" class="btn btn-primary" onclick="openConvertModal()">
                                Create Lease Agreement &rarr;
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($applicant['status'] === 'lease_created'): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 14px;">
                    <div>
                        <div style="font-weight: 700; font-size: 14.5px;">Step 3: Send Lease to Prospective Applicant(s)</div>
                        <div class="text-muted text-xs" style="margin-top: 3px;">
                            Draft Lease #<?= (int)$applicant['lease_id'] ?> is prepared. Send the contract to the prospective applicant(s) for digital signing.
                        </div>
                    </div>
                    <?php if (!$fromHistory): ?>
                        <div style="display: flex; gap: 8px; align-items: stretch; flex-wrap: wrap;">
                            <form action="<?= Router::url('/applicants/' . $applicant['id'] . '/cancel-lease') ?>" method="POST" style="margin: 0; display: flex;">
                                <button type="submit" class="btn btn-outline" title="Cancel this draft lease and return to Approved stage">
                                    &larr; Cancel Lease &amp; Go Back
                                </button>
                            </form>
                            <?php if (!empty($applicant['lease_id'])): ?>
                                <a href="<?= Router::url('/leases/' . $applicant['lease_id']) ?>" class="btn btn-outline" style="display: inline-flex; align-items: center;">View Draft Lease</a>
                            <?php endif; ?>
                            <form action="<?= Router::url('/applicants/' . $applicant['id'] . '/send-lease') ?>" method="POST" style="margin: 0; display: flex;">
                                <button type="submit" class="btn btn-primary">Send Lease to Applicant(s) &rarr;</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Prospective Applicants on this Draft Lease -->
                <?php if (!empty($prospectiveList)): ?>
                    <div style="border-top: 1px solid var(--ws-border-subtle); padding-top: 12px; margin-top: 12px;">
                        <span class="text-muted text-xs" style="display: block; margin-bottom: 8px;">Prospective Applicants Included on Lease:</span>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px;">
                            <?php foreach ($prospectiveList as $pa): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: var(--ws-surface); border-radius: 8px; border: 1px solid var(--ws-border-subtle);">
                                    <span style="font-weight: 600; font-size: 13.5px;"><?= htmlspecialchars($pa['full_name']) ?></span>
                                    <span class="badge badge-info" style="font-size: 11.5px; padding: 3px 10px; font-weight: 600;">Ready to Send</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif ($applicant['status'] === 'lease_sent'): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 14px;">
                    <div>
                        <div style="font-weight: 700; font-size: 14.5px;">Step 4: Lease Sent — Awaiting Signature(s)</div>
                        <div class="text-muted text-xs" style="margin-top: 3px;">
                            Once all prospective applicants sign the lease, they will automatically become official tenants and the lease will activate.
                        </div>
                    </div>
                    <?php if (!$fromHistory): ?>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <?php if (!empty($applicant['lease_id'])): ?>
                                <a href="<?= Router::url('/leases/' . $applicant['lease_id']) ?>" class="btn btn-outline btn-sm">View Lease Agreement</a>
                            <?php endif; ?>
                            <?php if (count($prospectiveList) > 1): ?>
                                <form action="<?= Router::url('/applicants/' . $applicant['id'] . '/sign-lease') ?>" method="POST" style="margin: 0;">
                                    <input type="hidden" name="sign_all" value="1">
                                    <button type="submit" class="btn btn-outline btn-sm" style="color: #16a34a; border-color: #16a34a;">Mark All Signed</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Prospective Applicants Signing Tracker -->
                <?php if (!empty($prospectiveList)): ?>
                    <div style="border-top: 1px solid var(--ws-border-subtle); padding-top: 12px; margin-top: 12px;">
                        <span class="text-muted text-xs" style="display: block; margin-bottom: 8px;">
                            Signature Progress (<?= $signedTotal ?> of <?= count($prospectiveList) ?> Signed):
                        </span>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 10px;">
                            <?php foreach ($prospectiveList as $pa):
                                $paSigned = !empty($pa['lease_signed']) || $pa['status'] === 'converted';
                            ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--ws-surface); border-radius: 8px; border: 1px solid var(--ws-border-subtle);">
                                    <div>
                                        <div style="font-weight: 600; font-size: 13.5px;"><?= htmlspecialchars($pa['full_name']) ?></div>
                                        <?php if ($paSigned && !empty($pa['lease_signed_at'])): ?>
                                            <div class="text-muted text-xs">Signed <?= date('M j, Y g:i A', strtotime($pa['lease_signed_at'])) ?></div>
                                        <?php else: ?>
                                            <div class="text-muted text-xs">Awaiting Signature</div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($paSigned): ?>
                                            <span class="badge badge-success">&#10003; Signed</span>
                                        <?php elseif (!$fromHistory): ?>
                                            <form action="<?= Router::url('/applicants/' . $applicant['id'] . '/sign-lease') ?>" method="POST" style="margin: 0;">
                                                <input type="hidden" name="target_applicant_id" value="<?= (int)$pa['id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-xs">Record Signature</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif ($applicant['status'] === 'converted'): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div>
                        <div style="font-weight: 700; font-size: 14.5px; color: #16a34a;">&#10003; Workflow Complete — Official Tenant Profiles Created</div>
                        <div class="text-muted text-xs" style="margin-top: 3px;">
                            All prospective applicants signed the lease agreement. The lease is active and unit occupancy is confirmed.
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <?php if (!empty($applicant['lease_id'])): ?>
                            <a href="<?= Router::url('/leases/' . $applicant['lease_id'] . ($fromHistory ? '?from=history' : '')) ?>" class="btn btn-outline btn-sm">
                                View Lease #<?= (int)$applicant['lease_id'] ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($applicant['tenant_id'])): ?>
                            <a href="<?= Router::url('/tenants/' . $applicant['tenant_id'] . ($fromHistory ? '?from=history' : '')) ?>" class="btn btn-primary btn-sm">
                                View Tenant Profile &rarr;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="dashboard-columns">
    <!-- Left Column: Personal, Accommodation, Tenancy & Emergency -->
    <div>
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border);">
                <h2 class="card-title">Accommodation & Term Information</h2>
            </div>
            <div class="card-body">
                <div style="background: var(--ws-surface-subtle); padding: 14px 16px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div>
                            <span class="text-muted text-xs">Date Requested</span>
                            <div><strong><?= !empty($applicant['date_requested']) ? date('M j, Y', strtotime($applicant['date_requested'])) : '—' ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Tenancy Type</span>
                            <div><strong><?= ucfirst(htmlspecialchars($applicant['tenancy_type'])) ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Term Beginning</span>
                            <div><strong><?= !empty($applicant['term_start_date']) ? date('M j, Y', strtotime($applicant['term_start_date'])) : '—' ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Term Ending</span>
                            <div><strong><?= !empty($applicant['term_end_date']) ? date('M j, Y', strtotime($applicant['term_end_date'])) : '—' ?></strong></div>
                        </div>
                        <div style="grid-column: span 2;">
                            <span class="text-muted text-xs">Adults Occupying</span>
                            <div><strong><?= nl2br(htmlspecialchars($applicant['occupants_adults'] ?: 'None specified')) ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Children Occupying</span>
                            <div><strong><?= htmlspecialchars($applicant['occupants_children'] ?: 'None specified') ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border);">
                <h2 class="card-title">Personal Information</h2>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 14px; background: var(--ws-surface-subtle); padding: 12px 14px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                    <div>
                        <span class="text-muted text-xs">First Name</span>
                        <div style="font-weight: 700; font-size: 15px; margin-top: 2px;">
                            <?= htmlspecialchars($applicant['first_name'] ?: ($applicant['full_name'] ?: '—')) ?>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Middle Name</span>
                        <div style="font-weight: 700; font-size: 15px; margin-top: 2px;">
                            <?= htmlspecialchars($applicant['middle_name'] ?: '—') ?>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Last Name</span>
                        <div style="font-weight: 700; font-size: 15px; margin-top: 2px;">
                            <?= htmlspecialchars($applicant['last_name'] ?: '—') ?>
                        </div>
                    </div>
                </div>

                <div style="background: var(--ws-surface-subtle); padding: 14px 16px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                        <div>
                            <span class="text-muted text-xs">Birthday</span>
                            <div><strong><?= !empty($applicant['birthday']) ? date('M j, Y', strtotime($applicant['birthday'])) : '—' ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Driver License #</span>
                            <div><strong><?= htmlspecialchars($applicant['driver_license'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Cell Phone</span>
                            <div><strong><?= htmlspecialchars($applicant['cell_phone'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Home Phone</span>
                            <div><strong><?= htmlspecialchars($applicant['home_phone'] ?: '—') ?></strong></div>
                        </div>
                        <div style="grid-column: span 2;">
                            <span class="text-muted text-xs">Email Address</span>
                            <div><strong><?= htmlspecialchars($applicant['email'] ?: '—') ?></strong></div>
                        </div>
                    </div>

                    <hr style="border:0; border-top: 1px solid var(--ws-border-subtle); margin: 14px 0;">

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <span class="text-muted text-xs">Present Address</span>
                            <div><strong><?= htmlspecialchars($applicant['present_address'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">How long?</span>
                            <div><strong><?= htmlspecialchars($applicant['present_address_duration'] ?: '—') ?></strong></div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                        <div>
                            <span class="text-muted text-xs">Previous Address</span>
                            <div><strong><?= htmlspecialchars($applicant['previous_address'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">How long?</span>
                            <div><strong><?= htmlspecialchars($applicant['previous_address_duration'] ?: '—') ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border); display: flex; align-items: center; justify-content: space-between;">
                <h2 class="card-title" style="margin: 0;">Documents & Identification</h2>
                <label for="applicant_documents" title="Add Documents (Driver's License, ID, Paystubs, etc.)" style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: transparent; border: 2px solid var(--ws-accent); color: var(--ws-accent); cursor: pointer; transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease; padding: 0;"
                       onmouseover="this.style.transform='scale(1.12)'; this.style.background='var(--ws-accent-subtle)';"
                       onmouseout="this.style.transform='scale(1)'; this.style.background='transparent';">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </label>
            </div>
            <div class="card-body">
                <input type="file" id="applicant_documents" name="applicant_documents[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" onchange="uploadApplicantDocuments(this)" style="display: none;">

                <div style="background: var(--ws-surface-subtle); border: 1px solid var(--ws-border-subtle); padding: 16px; border-radius: 12px;">
                <div id="applicant-documents-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 14px; margin-bottom: 0; <?= empty($applicantDocs) ? 'display: none;' : '' ?>">
                    <?php if (!empty($applicantDocs)): ?>
                        <?php foreach ($applicantDocs as $idx => $doc): 
                            $isPdf = strtolower(pathinfo($doc, PATHINFO_EXTENSION)) === 'pdf';
                        ?>
                            <div class="applicant-doc-card" 
                                 data-doc="<?= htmlspecialchars($doc) ?>" 
                                 style="position: relative; border-radius: 12px; overflow: hidden; border: 1px solid var(--ws-border-subtle); background: var(--ws-surface-subtle); padding: 6px; text-align: center; user-select: none; transition: opacity 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;">
                                
                                <?php if ($isPdf): ?>
                                    <a href="<?= Router::url($doc) ?>" target="_blank" title="Open PDF Document" 
                                       style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 95px; background: var(--ws-accent-subtle); border-radius: 8px; text-decoration: none; color: var(--ws-accent); transition: transform 0.15s ease;"
                                       onmouseover="this.style.transform='scale(1.03)'"
                                       onmouseout="this.style.transform='scale(1)'">
                                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <line x1="16" y1="13" x2="8" y2="13"></line>
                                            <line x1="16" y1="17" x2="8" y2="17"></line>
                                            <polyline points="10 9 9 9 8 9"></polyline>
                                        </svg>
                                        <span style="font-size: 11px; font-weight: 700; margin-top: 4px; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= htmlspecialchars(basename($doc)) ?>
                                        </span>
                                    </a>
                                <?php else: ?>
                                    <img src="<?= Router::url($doc) ?>" 
                                         alt="Applicant Document" 
                                         onclick="openDocModal('<?= Router::url($doc) ?>')" 
                                         style="width: 100%; height: 95px; object-fit: cover; border-radius: 8px; cursor: pointer; transition: transform 0.15s ease; pointer-events: auto;"
                                         draggable="false"
                                         onmouseover="this.style.transform='scale(1.03)'"
                                         onmouseout="this.style.transform='scale(1)'">
                                <?php endif; ?>
                                
                                <button type="button" 
                                        title="Delete document" 
                                        onclick="deleteApplicantDocumentDynamically(<?= (int)$applicant['id'] ?>, '<?= htmlspecialchars($doc) ?>', this)" 
                                        style="position: absolute; top: 10px; right: 10px; width: 26px; height: 26px; border-radius: 50%; background: rgba(0,0,0,0.65); border: none; color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.15s ease, transform 0.15s ease; z-index: 5;"
                                        onmouseover="this.style.background='#dc2626'; this.style.transform='scale(1.1)';"
                                        onmouseout="this.style.background='rgba(0,0,0,0.65)'; this.style.transform='scale(1)';">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="applicant-documents-empty" style="<?= empty($applicantDocs) ? '' : 'display: none;' ?> text-align: center; padding: 20px 14px; color: var(--ws-text-muted); font-size: 13.5px;">
                    Click the <span style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; border: 1.5px solid var(--ws-accent); color: var(--ws-accent); font-size: 12px; font-weight: 700; line-height: 1; vertical-align: middle;">+</span> button above to upload photo ID, driver's license, or paystubs.
                </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border);">
                <h2 class="card-title">Emergency Contacts</h2>
            </div>
            <div class="card-body">
                <!-- Contact #1 -->
                <div style="background: var(--ws-surface-subtle); padding: 14px 16px; border-radius: 10px; border: 1px solid var(--ws-border-subtle); margin-bottom: 14px;">
                    <h4 style="font-size: 13.5px; font-weight: 700; margin: 0 0 12px 0; border-bottom: 1.5px solid var(--ws-border-subtle); padding-bottom: 8px;">Contact #1</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 10px;">
                        <div>
                            <span class="text-muted text-xs">Name</span>
                            <div><strong><?= htmlspecialchars($applicant['emergency_contact_1_name'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Relationship</span>
                            <div><strong><?= htmlspecialchars($applicant['emergency_contact_1_relationship'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Phone</span>
                            <div><strong style="white-space: nowrap;"><?= htmlspecialchars($applicant['emergency_contact_1_phone'] ?: '—') ?></strong></div>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Address</span>
                        <div><strong><?= htmlspecialchars($applicant['emergency_contact_1_address'] ?: '—') ?></strong></div>
                    </div>
                </div>

                <!-- Contact #2 -->
                <div style="background: var(--ws-surface-subtle); padding: 14px 16px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                    <h4 style="font-size: 13.5px; font-weight: 700; margin: 0 0 12px 0; border-bottom: 1.5px solid var(--ws-border-subtle); padding-bottom: 8px;">Contact #2</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 10px;">
                        <div>
                            <span class="text-muted text-xs">Name</span>
                            <div><strong><?= htmlspecialchars($applicant['emergency_contact_2_name'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Relationship</span>
                            <div><strong><?= htmlspecialchars($applicant['emergency_contact_2_relationship'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Phone</span>
                            <div><strong style="white-space: nowrap;"><?= htmlspecialchars($applicant['emergency_contact_2_phone'] ?: '—') ?></strong></div>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Address</span>
                        <div><strong><?= htmlspecialchars($applicant['emergency_contact_2_address'] ?: '—') ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Employment, Income, Rental History & Status -->
    <div>
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border);">
                <h2 class="card-title">Property &amp; Unit Assignment</h2>
            </div>
            <div class="card-body">
                <div style="background: var(--ws-surface-subtle); padding: 14px 16px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div>
                            <span class="text-muted text-xs">Property</span>
                            <div><strong><?= htmlspecialchars($applicant['target_property_name'] ?: 'Unassigned') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Assigned Unit</span>
                            <div><strong><?= htmlspecialchars($applicant['target_unit_number'] ? 'Unit ' . $applicant['target_unit_number'] : 'Unspecified') ?></strong></div>
                        </div>
                    </div>

                    <?php if ($applicant['status'] === 'converted' && !empty($applicant['tenant_id'])): ?>
                        <div style="padding-top: 12px; border-top: 1px solid var(--ws-border-subtle); margin-top: 14px;">
                            <span class="text-muted text-xs">Tenant Assignment</span>
                            <div style="font-weight: 600; margin-top: 2px;">
                                Active Tenant Record #<?= (int)$applicant['tenant_id'] ?>: <?= htmlspecialchars($applicant['converted_tenant_first_name'] . ' ' . $applicant['converted_tenant_last_name']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border);">
                <h2 class="card-title">Employment & Income</h2>
            </div>
            <div class="card-body">
                <div style="background: var(--ws-surface-subtle); padding: 10px 14px; border-radius: 8px; border: 1px solid var(--ws-border-subtle); margin-bottom: 14px;">
                    <span class="text-muted text-xs">Monthly Income</span>
                    <div style="font-size: 20px; font-weight: 700; color: var(--ws-accent);">
                        <?= $applicant['monthly_income'] !== null ? '$' . number_format($applicant['monthly_income'], 2) . ' / month' : '—' ?>
                    </div>
                </div>

                <!-- Current Employment -->
                <div style="background: var(--ws-surface-subtle); padding: 14px 16px; border-radius: 10px; border: 1px solid var(--ws-border-subtle); margin-bottom: 14px;">
                    <h4 style="font-size: 13.5px; font-weight: 700; margin: 0 0 12px 0; border-bottom: 1.5px solid var(--ws-border-subtle); padding-bottom: 8px;">Current Employment</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <span class="text-muted text-xs">Employer</span>
                            <div><strong><?= htmlspecialchars($applicant['current_employer'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Occupation</span>
                            <div><strong><?= htmlspecialchars($applicant['current_occupation'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Supervisor</span>
                            <div><strong><?= htmlspecialchars($applicant['current_supervisor'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Phone</span>
                            <div><strong><?= htmlspecialchars($applicant['current_employer_phone'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Duration</span>
                            <div><strong><?= htmlspecialchars($applicant['current_employment_duration'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Address</span>
                            <div><strong><?= htmlspecialchars($applicant['current_employer_address'] ?: '—') ?></strong></div>
                        </div>
                    </div>
                </div>

                <!-- Previous Employment -->
                <div style="background: var(--ws-surface-subtle); padding: 14px 16px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                    <h4 style="font-size: 13.5px; font-weight: 700; margin: 0 0 12px 0; border-bottom: 1.5px solid var(--ws-border-subtle); padding-bottom: 8px;">Previous Employment</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <span class="text-muted text-xs">Employer</span>
                            <div><strong><?= htmlspecialchars($applicant['previous_employer'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Occupation</span>
                            <div><strong><?= htmlspecialchars($applicant['previous_occupation'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Supervisor</span>
                            <div><strong><?= htmlspecialchars($applicant['previous_supervisor'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Phone</span>
                            <div><strong><?= htmlspecialchars($applicant['previous_employer_phone'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Duration</span>
                            <div><strong><?= htmlspecialchars($applicant['previous_employment_duration'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Address</span>
                            <div><strong><?= htmlspecialchars($applicant['previous_employer_address'] ?: '—') ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border);">
                <h2 class="card-title">Rental History & Landlord References</h2>
            </div>
            <div class="card-body">
                <!-- Current Landlord -->
                <div style="background: var(--ws-surface-subtle); padding: 14px 16px; border-radius: 10px; border: 1px solid var(--ws-border-subtle); margin-bottom: 14px;">
                    <h4 style="font-size: 13.5px; font-weight: 700; margin: 0 0 12px 0; border-bottom: 1.5px solid var(--ws-border-subtle); padding-bottom: 8px;">Current Landlord</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <span class="text-muted text-xs">Current Landlord</span>
                            <div><strong><?= htmlspecialchars($applicant['current_landlord_name'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Current Landlord Phone</span>
                            <div><strong><?= htmlspecialchars($applicant['current_landlord_phone'] ?: '—') ?></strong></div>
                        </div>
                    </div>
                </div>

                <!-- Previous Landlord -->
                <div style="background: var(--ws-surface-subtle); padding: 14px 16px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                    <h4 style="font-size: 13.5px; font-weight: 700; margin: 0 0 12px 0; border-bottom: 1.5px solid var(--ws-border-subtle); padding-bottom: 8px;">Previous Landlord</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <span class="text-muted text-xs">Previous Landlord</span>
                            <div><strong><?= htmlspecialchars($applicant['previous_landlord_name'] ?: '—') ?></strong></div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Previous Landlord Phone</span>
                            <div><strong><?= htmlspecialchars($applicant['previous_landlord_phone'] ?: '—') ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border);">
                <h2 class="card-title">Declaration & Signature</h2>
            </div>
            <div class="card-body">
                <div style="background: var(--ws-surface-subtle); padding: 14px 16px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div>
                            <span class="text-muted text-xs">Credit Check Authorized</span>
                            <div style="margin-top: 4px;">
                                <?php if (!empty($applicant['credit_check_authorized'])): ?>
                                    <span class="badge badge-success">Yes (Authorized)</span>
                                <?php else: ?>
                                    <span class="badge badge-subtle">No</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Application Date</span>
                            <div><strong><?= !empty($applicant['application_date']) ? date('M j, Y', strtotime($applicant['application_date'])) : '—' ?></strong></div>
                        </div>
                        <div style="grid-column: span 2;">
                            <span class="text-muted text-xs">Applicant Signature</span>
                            <?php if (!empty($applicant['applicant_signature']) && str_starts_with($applicant['applicant_signature'], 'data:image/')): ?>
                                <div style="margin-top: 6px; background: var(--ws-surface-subtle); border: 1px solid var(--ws-border-subtle); border-radius: 8px; padding: 10px 14px; display: inline-flex; align-items: center; justify-content: center; min-width: 240px; max-width: 100%;">
                                    <img src="<?= htmlspecialchars($applicant['applicant_signature']) ?>" alt="Applicant e-Signature" style="max-height: 75px; max-width: 100%; object-fit: contain; display: block;">
                                </div>
                            <?php else: ?>
                                <div style="font-style: italic; font-size: 15px; margin-top: 2px;"><strong><?= htmlspecialchars($applicant['applicant_signature'] ?: '—') ?></strong></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($applicant['notes'])): ?>
                        <hr style="border:0; border-top: 1px solid var(--ws-border-subtle); margin: 14px 0;">
                        <span class="text-muted text-xs">Internal Notes</span>
                        <div style="margin-top: 4px;"><?= nl2br(htmlspecialchars($applicant['notes'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Lease Agreement Modal -->
<div id="convert-modal" class="modal-backdrop" onclick="if(event.target.id==='convert-modal') closeConvertModal();">
    <div class="modal-content" style="max-width: 620px; background: var(--ws-surface); border-radius: 14px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 700;">Create Lease Agreement</h3>
            <button type="button" onclick="closeConvertModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>

        <p class="text-muted text-xs" style="margin-top: 0; margin-bottom: 16px;">
            Create a lease agreement with the prospective applicant(s) on it. Once created, the lease can be sent to the prospective applicant(s) for signature. When all applicants sign the lease, they automatically become tenants.
        </p>

        <form action="<?= Router::url('/applicants/' . $applicant['id'] . '/create-lease') ?>" method="POST">
            
            <!-- Step 1: Unit Selection -->
            <div class="form-group">
                <label for="modal_unit_id">Assigned Unit *</label>
                <select id="modal_unit_id" name="unit_id" class="form-control" required onchange="onModalUnitChange(this)">
                    <option value="">-- Choose Unit --</option>
                    <?php foreach ($allUnits as $u): ?>
                        <option value="<?= $u['id'] ?>" 
                                data-rent="<?= $u['target_rent'] ?>" 
                                data-deposit="<?= $u['security_deposit'] ?>"
                                data-status="<?= htmlspecialchars($u['status']) ?>"
                                <?= $applicant['target_unit_id'] == $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['property_name']) ?> — <?= htmlspecialchars($u['unit_number']) ?> (<?= ucfirst(htmlspecialchars($u['status'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Existing Lease Turnover Notice (Conditional) -->
            <div id="existing-lease-warning" style="display: none; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; color: #92400e; font-size: 13px;">
                <div style="font-weight: 700; margin-bottom: 4px;">⚠️ Unit has an active/existing lease:</div>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; margin: 4px 0 0 0;">
                    <input type="checkbox" name="terminate_previous_lease" value="1" style="width: 16px; height: 16px; accent-color: var(--ws-accent);">
                    <span><strong>Turnover:</strong> Mark the previous expiring lease as Expired and replace with this new tenancy</span>
                </label>
            </div>

            <!-- Step 2: Choose All Prospective Applicants for this Lease -->
            <div style="background: var(--ws-surface-subtle); border: 1px solid var(--ws-border-subtle); padding: 14px 16px; border-radius: 10px; margin-bottom: 16px;">
                <label style="display: block; font-size: 13.5px; font-weight: 700; margin-bottom: 6px; color: var(--ws-text);">
                    Select Prospective Applicants on this Lease Agreement
                </label>
                <p class="text-muted text-xs" style="margin-top: 0; margin-bottom: 10px;">
                    Select all prospective applicants (couples, roommates, co-signers) to include on this lease agreement. Once all selected applicants sign the lease, they will become tenants.
                </p>

                <!-- Primary applicant (always included) -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: var(--ws-surface); border: 1.5px solid var(--ws-accent); border-radius: 8px; margin-bottom: 8px;">
                    <label style="display: flex; align-items: center; gap: 10px; margin: 0; cursor: default; font-weight: 600; font-size: 13.5px;">
                        <input type="checkbox" checked disabled style="width: 16px; height: 16px; accent-color: var(--ws-accent);">
                        <span><?= htmlspecialchars($applicant['full_name']) ?> <span class="badge badge-primary" style="margin-left: 6px;">Primary Applicant</span></span>
                    </label>
                    <span class="text-muted text-xs"><?= htmlspecialchars($applicant['cell_phone'] ?: ($applicant['email'] ?: '')) ?></span>
                </div>

                <!-- Co-applicants list (filtered by unit or selectable) -->
                <div id="co-applicants-container">
                    <?php 
                    $hasCo = false;
                    foreach ($otherApplicants as $oa): 
                        if ((int)$oa['id'] === (int)$applicant['id']) continue;
                        $hasCo = true;
                    ?>
                        <div class="co-applicant-row" 
                             data-unit-id="<?= htmlspecialchars($oa['target_unit_id'] ?? '') ?>"
                             style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: var(--ws-surface); border: 1px solid var(--ws-border-subtle); border-radius: 8px; margin-bottom: 6px;">
                            <label style="display: flex; align-items: center; gap: 10px; margin: 0; cursor: pointer; font-size: 13.5px;">
                                <input type="checkbox" name="co_applicant_ids[]" value="<?= (int)$oa['id'] ?>" style="width: 16px; height: 16px; accent-color: var(--ws-accent);">
                                <span>
                                    <strong><?= htmlspecialchars($oa['full_name']) ?></strong>
                                    <?php if (!empty($oa['target_unit_number'])): ?>
                                        <span class="badge badge-subtle" style="margin-left: 4px;">Applied: Unit <?= htmlspecialchars($oa['target_unit_number']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </label>
                            <span class="text-muted text-xs"><?= htmlspecialchars($oa['cell_phone'] ?: ($oa['email'] ?: '')) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <?php if (!$hasCo): ?>
                        <div class="text-muted text-xs" style="padding: 6px 0;">No other pending/approved applications found.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Step 3: Lease Terms -->
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="lease_start_date">Lease Start Date *</label>
                    <input type="date" id="lease_start_date" name="lease_start_date" class="form-control" value="<?= !empty($applicant['term_start_date']) ? $applicant['term_start_date'] : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group col-6">
                    <label for="lease_end_date">Lease End Date</label>
                    <input type="date" id="lease_end_date" name="lease_end_date" class="form-control" value="<?= !empty($applicant['term_end_date']) ? $applicant['term_end_date'] : date('Y-m-d', strtotime('+1 year')) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="rent_amount">Monthly Rent ($) *</label>
                    <input type="number" id="rent_amount" name="rent_amount" class="form-control" step="0.01" required value="0.00">
                </div>
                <div class="form-group col-6">
                    <label for="security_deposit">Security Deposit ($)</label>
                    <input type="number" id="security_deposit" name="security_deposit" class="form-control" step="0.01" value="0.00">
                </div>
            </div>

            <div class="form-group">
                <label for="lease_terms">Lease Terms / Special Provisions (Optional)</label>
                <textarea id="lease_terms" name="terms" class="form-control" rows="2" placeholder="Optional lease terms, conditions, or notes for the prospective applicant(s)..."></textarea>
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="closeConvertModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Lease Agreement</button>
            </div>
        </form>
    </div>
</div>

<script>
function openConvertModal() {
    document.getElementById('convert-modal').classList.add('active');
    const unitSelect = document.getElementById('modal_unit_id');
    if (unitSelect) onModalUnitChange(unitSelect);
}

function closeConvertModal() {
    document.getElementById('convert-modal').classList.remove('active');
}

function onModalUnitChange(selectElem) {
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    if (selectedOption && selectedOption.dataset.rent) {
        document.getElementById('rent_amount').value = selectedOption.dataset.rent;
        document.getElementById('security_deposit').value = selectedOption.dataset.deposit || '0.00';
    }

    // Check if unit is currently occupied (expiring lease turnover option)
    const warning = document.getElementById('existing-lease-warning');
    if (selectedOption && selectedOption.dataset.status === 'occupied') {
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }

    // Filter co-applicant suggestions to highlight those matching this unit
    const unitId = selectElem.value;
    document.querySelectorAll('.co-applicant-row').forEach(row => {
        const rowUnitId = row.getAttribute('data-unit-id');
        const checkbox = row.querySelector('input[type="checkbox"]');
        if (unitId && rowUnitId === unitId) {
            row.style.background = 'var(--ws-accent-subtle)';
            row.style.borderColor = 'var(--ws-accent)';
            if (!checkbox.dataset.manuallyChanged) {
                checkbox.checked = true;
            }
        } else {
            row.style.background = 'var(--ws-surface)';
            row.style.borderColor = 'var(--ws-border-subtle)';
        }
        checkbox.addEventListener('change', () => {
            checkbox.dataset.manuallyChanged = 'true';
        });
    });
}

// Document Management & Lightbox
let currentDocIndex = 0;

function getAllDocSources() {
    const images = document.querySelectorAll('#applicant-documents-grid .applicant-doc-card img');
    return Array.from(images).map(img => img.getAttribute('src'));
}

function openDocModal(src) {
    const docs = getAllDocSources();
    currentDocIndex = docs.indexOf(src);
    if (currentDocIndex === -1) currentDocIndex = 0;
    updateDocModalView();
    document.getElementById('applicant-doc-modal').classList.add('active');
}

function updateDocModalView() {
    const docs = getAllDocSources();
    if (docs.length === 0) return;
    
    if (currentDocIndex >= docs.length) currentDocIndex = 0;
    if (currentDocIndex < 0) currentDocIndex = docs.length - 1;

    const img = document.getElementById('modal-doc-img');
    img.src = docs[currentDocIndex];

    const counter = document.getElementById('modal-doc-counter');
    if (counter) {
        counter.textContent = (currentDocIndex + 1) + ' / ' + docs.length;
    }
}

function navigateDocModal(direction, e) {
    if (e) e.stopPropagation();
    currentDocIndex += direction;
    updateDocModalView();
}

function closeDocModal(e) {
    if (e.target.id === 'applicant-doc-modal') {
        document.getElementById('applicant-doc-modal').classList.remove('active');
    }
}

document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('applicant-doc-modal');
    if (!modal || !modal.classList.contains('active')) return;
    if (e.key === 'ArrowRight') {
        navigateDocModal(1);
    } else if (e.key === 'ArrowLeft') {
        navigateDocModal(-1);
    } else if (e.key === 'Escape') {
        modal.classList.remove('active');
    }
});

function uploadApplicantDocuments(inputElem) {
    if (!inputElem.files || inputElem.files.length === 0) return;

    const formData = new FormData();
    for (let i = 0; i < inputElem.files.length; i++) {
        formData.append('applicant_documents[]', inputElem.files[i]);
    }
    formData.append('ajax', '1');

    const emptyNotice = document.getElementById('applicant-documents-empty');
    const grid = document.getElementById('applicant-documents-grid');
    if (emptyNotice) {
        emptyNotice.innerHTML = '<span style="display: inline-flex; align-items: center; gap: 8px;"><svg style="animation: spin 1s linear infinite;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path></svg> Uploading document(s)...</span>';
        emptyNotice.style.display = 'block';
    }

    fetch('<?= Router::url('/applicants/' . $applicant['id'] . '/upload-document') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.uploaded && data.uploaded.length > 0) {
            if (emptyNotice) emptyNotice.style.display = 'none';
            if (grid) grid.style.display = 'grid';

            data.uploaded.forEach(doc => {
                const isPdf = doc.toLowerCase().endsWith('.pdf');
                const card = document.createElement('div');
                card.className = 'applicant-doc-card';
                card.setAttribute('data-doc', doc);
                card.style.cssText = 'position: relative; border-radius: 12px; overflow: hidden; border: 1px solid var(--ws-border-subtle); background: var(--ws-surface-subtle); padding: 6px; text-align: center; user-select: none; transition: opacity 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;';

                const fullUrl = '<?= Router::url('') ?>' + (doc.startsWith('/') ? doc : '/' + doc);
                const filename = doc.split('/').pop();

                let inner = '';
                if (isPdf) {
                    inner += `
                        <a href="${fullUrl}" target="_blank" title="Open PDF Document" 
                           style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 95px; background: var(--ws-accent-subtle); border-radius: 8px; text-decoration: none; color: var(--ws-accent); transition: transform 0.15s ease;"
                           onmouseover="this.style.transform='scale(1.03)'"
                           onmouseout="this.style.transform='scale(1)'">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <span style="font-size: 11px; font-weight: 700; margin-top: 4px; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                ${filename}
                            </span>
                        </a>`;
                } else {
                    inner += `
                        <img src="${fullUrl}" 
                             alt="Applicant Document" 
                             onclick="openDocModal('${fullUrl}')" 
                             style="width: 100%; height: 95px; object-fit: cover; border-radius: 8px; cursor: pointer; transition: transform 0.15s ease; pointer-events: auto;"
                             draggable="false"
                             onmouseover="this.style.transform='scale(1.03)'"
                             onmouseout="this.style.transform='scale(1)'">`;
                }

                inner += `
                    <button type="button" 
                            title="Delete document" 
                            onclick="deleteApplicantDocumentDynamically(<?= (int)$applicant['id'] ?>, '${doc}', this)" 
                            style="position: absolute; top: 10px; right: 10px; width: 26px; height: 26px; border-radius: 50%; background: rgba(0,0,0,0.65); border: none; color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.15s ease, transform 0.15s ease; z-index: 5;"
                            onmouseover="this.style.background='#dc2626'; this.style.transform='scale(1.1)';"
                            onmouseout="this.style.background='rgba(0,0,0,0.65)'; this.style.transform='scale(1)';">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </button>`;

                card.innerHTML = inner;
                grid.appendChild(card);
            });
        } else {
            alert(data.message || 'Error uploading documents.');
            if (emptyNotice && (!grid || grid.querySelectorAll('.applicant-doc-card').length === 0)) {
                emptyNotice.style.display = 'block';
                emptyNotice.innerHTML = 'Click the + button above to upload photo ID, driver\'s license, or paystubs.';
            }
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error while uploading documents.');
        if (emptyNotice && (!grid || grid.querySelectorAll('.applicant-doc-card').length === 0)) {
            emptyNotice.style.display = 'block';
        }
    })
    .finally(() => {
        inputElem.value = '';
    });
}

function deleteApplicantDocumentDynamically(applicantId, docPath, buttonElem) {
    const card = buttonElem.closest('.applicant-doc-card');
    card.style.opacity = '0.4';
    card.style.pointerEvents = 'none';

    const formData = new FormData();
    formData.append('document', docPath);

    fetch('<?= Router::url('/applicants/') ?>' + applicantId + '/delete-document', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            card.style.transform = 'scale(0.8)';
            card.style.opacity = '0';
            setTimeout(() => {
                card.remove();
                const grid = document.getElementById('applicant-documents-grid');
                if (grid && grid.querySelectorAll('.applicant-doc-card').length === 0) {
                    grid.style.display = 'none';
                    const emptyNotice = document.getElementById('applicant-documents-empty');
                    if (emptyNotice) emptyNotice.style.display = 'block';
                }
            }, 250);
        } else {
            alert(data.message || 'Error deleting document.');
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error while deleting document.');
        card.style.opacity = '1';
        card.style.pointerEvents = 'auto';
    });
}
</script>

<!-- Lightbox Modal for Document Preview -->
<div id="applicant-doc-modal" class="modal-backdrop" onclick="closeDocModal(event)">
    <div style="position: relative; max-width: 90vw; max-height: 90vh; display: flex; align-items: center; justify-content: center;">
        <button type="button" onclick="document.getElementById('applicant-doc-modal').classList.remove('active')" 
                style="position: absolute; top: -45px; right: 0; background: none; border: none; color: #fff; font-size: 34px; cursor: pointer; line-height: 1;">&times;</button>
        
        <!-- Previous Arrow -->
        <button type="button" onclick="navigateDocModal(-1, event)" 
                title="Previous document"
                style="position: absolute; left: -50px; background: rgba(0,0,0,0.6); border: 1.5px solid rgba(255,255,255,0.3); color: #fff; width: 44px; height: 44px; border-radius: 50%; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s ease, transform 0.15s ease; z-index: 10;"
                onmouseover="this.style.background='var(--ws-accent)'; this.style.transform='scale(1.08)';"
                onmouseout="this.style.background='rgba(0,0,0,0.6)'; this.style.transform='scale(1)';">
            &#10094;
        </button>

        <img id="modal-doc-img" src="" alt="Document Preview" style="max-width: 82vw; max-height: 82vh; border-radius: 14px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); object-fit: contain; transition: opacity 0.15s ease;">

        <!-- Next Arrow -->
        <button type="button" onclick="navigateDocModal(1, event)" 
                title="Next document"
                style="position: absolute; right: -50px; background: rgba(0,0,0,0.6); border: 1.5px solid rgba(255,255,255,0.3); color: #fff; width: 44px; height: 44px; border-radius: 50%; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s ease, transform 0.15s ease; z-index: 10;"
                onmouseover="this.style.background='var(--ws-accent)'; this.style.transform='scale(1.08)';"
                onmouseout="this.style.background='rgba(0,0,0,0.6)'; this.style.transform='scale(1)';">
            &#10095;
        </button>

        <!-- Document Counter Badge -->
        <div id="modal-doc-counter" style="position: absolute; bottom: -36px; color: #fff; font-size: 13px; font-weight: 500; background: rgba(0,0,0,0.5); padding: 4px 12px; border-radius: 20px;"></div>
    </div>
</div>
