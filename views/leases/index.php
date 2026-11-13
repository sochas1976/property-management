<?php
use App\Core\Router;
use App\Core\AppDate;

$inProgressLeases = $inProgressLeases ?? [];
$activeLeases = $activeLeases ?? [];
$activeTab = $activeTab ?? 'all';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Lease Agreements</h1>
        <p class="page-subtitle">Manage contract terms, track in-progress lease workflows, and configure Word (.docx) lease templates</p>
    </div>
    <div>
        <a href="<?= Router::url('/leases/create') ?>" class="btn btn-primary">+ Create New Lease</a>
    </div>
</div>

<!-- View Segmented Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 22px; align-items: center; flex-wrap: wrap;">
    <a href="<?= Router::url('/leases') ?>" class="btn <?= $activeTab === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        All Leases (<?= count($leases) ?>)
    </a>
    <a href="<?= Router::url('/leases?tab=in_progress') ?>" class="btn <?= $activeTab === 'in_progress' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Workflows in Progress (<?= count($inProgressLeases) ?>)
    </a>
    <a href="<?= Router::url('/leases?tab=active') ?>" class="btn <?= $activeTab === 'active' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Active Leases (<?= count($activeLeases) ?>)
    </a>
    <a href="<?= Router::url('/leases/templates') ?>" class="btn btn-outline btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Lease Templates
    </a>
</div>

<!-- TABLE 1: In-Progress Lease Workflows (Stages 1 - 3) -->
<?php if ($activeTab === 'all' || $activeTab === 'in_progress'): ?>
    <?php if (!empty($inProgressLeases)): ?>
        <div class="card" style="margin-bottom: 28px; border: 1.5px solid #fde68a; box-shadow: 0 4px 14px rgba(245, 166, 35, 0.08); border-radius: 12px; overflow: hidden;">
            <div class="card-header" style="background: #fffdf5; border-bottom: 1.5px solid #fde68a; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: #92400e;">
                            Lease Workflows in Progress
                        </h2>
                        <span class="badge badge-warning" style="font-weight: 700; font-size: 11px; padding: 3px 9px; border-radius: 9999px;">
                            <?= count($inProgressLeases) ?> In Progress
                        </span>
                    </div>
                    <p class="text-muted text-xs" style="margin: 4px 0 0 0;">
                        Lease agreements currently moving through drafting, terms review, and awaiting signatures. Pick up where you left off at any time.
                    </p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Workflow Stage</th>
                            <th>Property &amp; Unit</th>
                            <th>Prospective Applicant(s)</th>
                            <th>Lease Period</th>
                            <th>Monthly Rent &amp; Deposit</th>
                            <th>Attached Contracts</th>
                            <th style="text-align: right;">Workflow Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inProgressLeases as $wLease): 
                            $wStage = $wLease['applicant_status'] ?? 'draft';
                            $isSent = ($wStage === 'lease_sent');
                            $signedCount = (int)($wLease['signed_count'] ?? 0);
                            $appCount = max(1, (int)($wLease['applicant_count'] ?? 1));
                        ?>
                            <tr>
                                <td>
                                    <?php if ($isSent): ?>
                                        <span class="badge" style="background: rgba(245, 166, 35, 0.15); color: #b45309; border: 1.5px solid #f59e0b; font-weight: 700; font-size: 11.5px; padding: 4px 10px; border-radius: 9999px; display: inline-block;">
                                            Step 3: Awaiting Signature(s)
                                        </span>
                                        <div class="text-muted text-xs" style="margin-top: 4px; font-weight: 600;">
                                            <?= $signedCount ?> of <?= $appCount ?> Signed
                                        </div>
                                    <?php else: ?>
                                        <span class="badge badge-info" style="font-weight: 700; font-size: 11.5px; padding: 4px 10px; border-radius: 9999px; display: inline-block;">
                                            Step 2: Review Lease Draft
                                        </span>
                                        <div class="text-muted text-xs" style="margin-top: 4px;">
                                            Draft Prepared &bull; Ready to Send
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($wLease['property_name']) ?></strong>
                                    <div class="text-muted text-xs">Unit <?= htmlspecialchars($wLease['unit_number']) ?></div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($wLease['tenant_name'] ?: 'Prospective Applicant') ?></strong>
                                    <div class="text-muted text-xs"><?= htmlspecialchars($wLease['tenant_email'] ?? '') ?></div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($wLease['start_date']) ?> &rarr; <?= htmlspecialchars($wLease['end_date']) ?>
                                    <div class="text-muted text-xs"><?= ucfirst($wLease['payment_frequency']) ?></div>
                                </td>
                                <td>
                                    <div class="font-bold text-success">
                                        $<?= number_format((float)$wLease['rent_amount'], 2) ?>
                                    </div>
                                    <div class="text-muted text-xs">
                                        Deposit: $<?= number_format((float)($wLease['deposit_held'] ?? $wLease['deposit_amount'] ?? 0), 2) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($wLease['document_count']) && $wLease['document_count'] > 0): ?>
                                        <a href="<?= Router::url('/leases/' . $wLease['id']) ?>" class="badge badge-subtle" title="Stored locally in private vault">
                                            📄 <?= $wLease['document_count'] ?> Attached
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted text-xs">No file attached</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($isSent): ?>
                                        <a href="<?= Router::url('/leases/' . $wLease['id']) ?>" class="btn btn-primary btn-sm" style="font-weight: 600; padding: 6px 14px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                                            Resume Step 3 &rarr;
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= Router::url('/leases/' . $wLease['id']) ?>" class="btn btn-primary btn-sm" style="font-weight: 600; padding: 6px 14px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                                            Resume Step 2 &rarr;
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($activeTab === 'in_progress'): ?>
        <div class="card" style="margin-bottom: 28px; border-radius: 12px; padding: 40px; text-align: center;">
            <p class="text-muted" style="margin: 0; font-size: 14.5px;">No lease agreements currently in progress.</p>
            <div style="margin-top: 14px;">
                <a href="<?= Router::url('/applicants') ?>" class="btn btn-primary btn-sm">Review Applicants</a>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- TABLE 2: Executed & Active Lease Agreements -->
<?php if ($activeTab === 'all' || $activeTab === 'active'): ?>
    <div class="card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <?php if ($activeTab === 'all'): ?>
            <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border); padding: 16px 20px;">
                <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700;">
                    Executed &amp; Active Lease Agreements
                </h2>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Property &amp; Unit</th>
                        <th>Resident Tenant</th>
                        <th>Lease Period</th>
                        <th>Monthly Rent</th>
                        <th>Document</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activeLeases)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted" style="padding: 40px;">
                                No active or completed lease agreements found.
                                <br><br>
                                <a href="<?= Router::url('/leases/create') ?>" class="btn btn-primary btn-sm">Draft First Lease</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activeLeases as $lease): ?>
                            <tr>
                                <td>
                                    <?php
                                    $endDate = !empty($lease['end_date']) ? new DateTime($lease['end_date']) : null;
                                    $daysLeft = null;
                                    if ($endDate) {
                                        $diff = AppDate::today()->diff($endDate);
                                        $daysLeft = $diff->invert ? -$diff->days : $diff->days;
                                    }
                                    $isExpiringSoon = ($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 45);

                                    $effStatus = $lease['status'];
                                    if ($effStatus === 'active' && $isExpiringSoon) {
                                        $effStatus = 'expiring_soon';
                                    }

                                    $leaseStatusMap = [
                                        'active'        => 'badge-success',
                                        'expiring_soon' => 'badge-warning',
                                        'draft'         => 'badge-info',
                                        'expired'       => 'badge-warning',
                                        'terminated'    => 'badge-danger',
                                    ];
                                    $statusClass = $leaseStatusMap[$effStatus] ?? 'badge-subtle';
                                    $statusLabel = ($effStatus === 'expiring_soon') ? 'Expiring Soon' : ucfirst(str_replace('_', ' ', $effStatus));
                                    ?>
                                    <span class="badge <?= $statusClass ?>" <?= $isExpiringSoon ? 'title="' . ($daysLeft === 0 ? 'Expires today' : ($daysLeft === 1 ? '1 day left' : $daysLeft . ' days left')) . '"' : '' ?>><?= $statusLabel ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($lease['property_name']) ?></strong>
                                    <div class="text-muted text-xs">Unit <?= htmlspecialchars($lease['unit_number']) ?></div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($lease['tenant_name'] ?: 'Resident') ?></strong>
                                    <div class="text-muted text-xs"><?= htmlspecialchars($lease['tenant_email'] ?? '') ?></div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($lease['start_date']) ?> &rarr; <?= htmlspecialchars($lease['end_date']) ?>
                                </td>
                                <td class="font-bold text-success">
                                    $<?= number_format((float)$lease['rent_amount'], 2) ?>
                                </td>
                                <td>
                                    <?php if (!empty($lease['document_id'])): ?>
                                        <a href="<?= Router::url('/documents/' . $lease['document_id']) ?>" target="_blank" class="badge badge-subtle" title="Stored locally in private vault">
                                            📄 <?= htmlspecialchars(substr($lease['document_name'], 0, 15)) ?>...
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted text-xs">No file</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="<?= Router::url('/leases/' . $lease['id']) ?>" class="btn btn-outline btn-xs" style="font-weight: 600; padding: 5px 12px; border-radius: 6px;">View Terms</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
