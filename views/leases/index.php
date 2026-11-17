<?php
use App\Core\Router;
use App\Core\AppDate;

$inProgressLeases = $inProgressLeases ?? [];
$activeLeases = $activeLeases ?? [];
$expiredLeases = $expiredLeases ?? [];
$activeTab = $activeTab ?? 'all';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Lease Agreements</h1>
    </div>
    <div>
        <a href="<?= Router::url('/leases/create') ?>" class="btn btn-primary">+ Create New Lease</a>
    </div>
</div>

<!-- View Segmented Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 22px; align-items: center; flex-wrap: wrap;">
    <a href="<?= Router::url('/leases') ?>" class="btn <?= $activeTab === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        All Leases
    </a>
    <a href="<?= Router::url('/leases?tab=in_progress') ?>" class="btn <?= $activeTab === 'in_progress' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Workflows in Progress (<?= count($inProgressLeases) ?>)
    </a>
    <a href="<?= Router::url('/leases?tab=active') ?>" class="btn <?= $activeTab === 'active' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Active Leases
    </a>
    <a href="<?= Router::url('/leases?tab=expired') ?>" class="btn <?= $activeTab === 'expired' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Expired Leases
    </a>
    <a href="<?= Router::url('/leases/templates') ?>" class="btn btn-outline btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Lease Templates
    </a>
</div>

<!-- TABLE 1: In-Progress Lease Workflows (Stages 1 - 3) -->
<?php if ($activeTab === 'all' || $activeTab === 'in_progress'): ?>
    <?php if (!empty($inProgressLeases)): ?>
        <div class="card" style="margin-bottom: 28px; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-radius: 12px; overflow: hidden;">
            <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border); padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700;">
                        Lease Workflows in Progress
                    </h2>
                </div>
            </div>
            <div class="table-responsive" style="background-color: var(--ws-bg);">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="border-top-left-radius: calc(var(--radius-lg) - 1px);">Prospective Applicant(s)</th>
                            <th>Property &amp; Unit</th>
                            <th style="white-space: nowrap;">Lease Period</th>
                            <th>Monthly Rent</th>
                            <th style="border-top-right-radius: calc(var(--radius-lg) - 1px);">Workflow Stage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inProgressLeases as $wLease): 
                            $wStage = $wLease['applicant_status'] ?? 'draft';
                            $isSent = ($wStage === 'lease_sent');
                            $isRenewal = (!empty($wLease['terms']) && stripos($wLease['terms'], 'renew') !== false) || (!empty($wLease['tenant_id']) && $wLease['status'] === 'draft');
                            $leaseUrl = Router::url('/leases/' . $wLease['id']);
                        ?>
                            <tr onclick="window.location.href='<?= $leaseUrl ?>';" style="cursor: pointer;" title="Click to view lease workflow">
                                <td>
                                    <?php
                                    $applicantNames = array_filter(array_map('trim', explode(' & ', $wLease['tenant_name'] ?: 'Prospective Applicant')));
                                    foreach ($applicantNames as $aName):
                                    ?>
                                        <div><strong><a href="<?= $leaseUrl ?>" style="color: #000000;"><?= htmlspecialchars($aName) ?></a></strong></div>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($wLease['property_name']) ?></strong>
                                    <div class="text-muted text-xs">Unit <?= htmlspecialchars($wLease['unit_number']) ?></div>
                                </td>
                                <td style="white-space: nowrap;">
                                    <div><?= htmlspecialchars($wLease['start_date']) ?> &rarr; <?= htmlspecialchars($wLease['end_date']) ?></div>
                                    <div class="text-muted text-xs"><?= ucfirst($wLease['payment_frequency']) ?></div>
                                </td>
                                <td style="white-space: nowrap;">
                                    <div class="font-bold text-success">
                                        $<?= number_format((float)$wLease['rent_amount'], 2) ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?= $leaseUrl ?>" style="text-decoration: none; color: inherit; display: inline-block;">
                                        <?php if ($isSent): ?>
                                            <span class="badge" style="background: rgba(245, 166, 35, 0.15); color: #b45309; border: 1.5px solid #f59e0b; font-weight: 700; font-size: 11.5px; padding: 4px 10px; border-radius: 9999px; display: inline-block; white-space: nowrap; cursor: pointer;">
                                                Awaiting Signature(s)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-info" style="font-weight: 700; font-size: 11.5px; padding: 4px 10px; border-radius: 9999px; display: inline-block; white-space: nowrap; cursor: pointer;">
                                                <?= $isRenewal ? 'Review Renewal Draft' : 'Review Lease Draft' ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
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

<!-- TABLE 2: Active Lease Agreements -->
<?php if ($activeTab === 'all' || $activeTab === 'active'): ?>
    <div class="card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <?php if ($activeTab === 'all'): ?>
            <div class="card-header" style="background-color: var(--table-th-bg); border-bottom: 1.5px solid var(--table-th-border); padding: 16px 20px;">
                <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700;">
                    Active Lease Agreements
                </h2>
            </div>
        <?php endif; ?>

        <div class="table-responsive" style="background-color: var(--ws-bg);">
            <table class="table">
                <thead>
                    <tr>
                        <th style="border-top-left-radius: calc(var(--radius-lg) - 1px);">Status</th>
                        <th>Property &amp; Unit</th>
                        <th>Resident Tenant</th>
                        <th>Lease Period</th>
                        <th>Monthly Rent</th>
                        <th style="border-top-right-radius: calc(var(--radius-lg) - 1px);">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activeLeases)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted" style="padding: 40px;">
                                No active lease agreements found.
                                <br><br>
                                <a href="<?= Router::url('/leases/create') ?>" class="btn btn-primary btn-sm">Draft First Lease</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activeLeases as $lease): 
                            $leaseUrl = Router::url('/leases/' . $lease['id']);
                        ?>
                            <tr onclick="window.location.href='<?= $leaseUrl ?>';" style="cursor: pointer;" title="Click to view lease terms">
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
                                    ];
                                    $statusClass = $leaseStatusMap[$effStatus] ?? 'badge-success';
                                    $statusLabel = ($effStatus === 'expiring_soon') ? 'Expiring Soon' : ucfirst(str_replace('_', ' ', $effStatus));
                                    ?>
                                    <span class="badge <?= $statusClass ?>" <?= $isExpiringSoon ? 'title="' . ($daysLeft === 0 ? 'Expires today' : ($daysLeft === 1 ? '1 day left' : $daysLeft . ' days left')) . '"' : '' ?>><?= $statusLabel ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($lease['property_name']) ?></strong>
                                    <div class="text-muted text-xs">Unit <?= htmlspecialchars($lease['unit_number']) ?></div>
                                </td>
                                <td>
                                    <?php
                                    $tenantNames = array_filter(array_map('trim', preg_split('/\s*(&|,)\s*/', $lease['tenant_name'] ?: 'Resident')));
                                    foreach ($tenantNames as $tName):
                                    ?>
                                        <div><strong><?= htmlspecialchars($tName) ?></strong></div>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($lease['start_date']) ?> &rarr; <?= htmlspecialchars($lease['end_date']) ?>
                                </td>
                                <td class="font-bold text-success">
                                    $<?= number_format((float)$lease['rent_amount'], 2) ?>
                                </td>
                                <td>
                                    <a href="<?= $leaseUrl ?>" class="btn btn-primary btn-xs" style="font-weight: 600; padding: 6px 14px; border-radius: 8px; box-shadow: 0 1px 3px rgba(245, 166, 35, 0.25); display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">View Terms</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- TABLE 3: Expired Lease Agreements (Separate View) -->
<?php if ($activeTab === 'expired'): ?>
    <div class="card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <div class="table-responsive" style="background-color: var(--ws-bg);">
            <table class="table">
                <thead>
                    <tr>
                        <th style="border-top-left-radius: calc(var(--radius-lg) - 1px);">Status</th>
                        <th>Property &amp; Unit</th>
                        <th>Resident Tenant</th>
                        <th>Lease Period</th>
                        <th>Monthly Rent</th>
                        <th style="border-top-right-radius: calc(var(--radius-lg) - 1px);">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expiredLeases)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted" style="padding: 40px;">
                                No expired lease agreements found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($expiredLeases as $lease): 
                            $leaseUrl = Router::url('/leases/' . $lease['id']);
                        ?>
                            <tr onclick="window.location.href='<?= $leaseUrl ?>';" style="cursor: pointer;" title="Click to view lease terms">
                                <td>
                                    <?php
                                    $effStatus = $lease['status'];
                                    $leaseStatusMap = [
                                        'expired'    => 'badge-warning',
                                        'terminated' => 'badge-danger',
                                    ];
                                    $statusClass = $leaseStatusMap[$effStatus] ?? 'badge-warning';
                                    $statusLabel = ucfirst(str_replace('_', ' ', $effStatus));
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($lease['property_name']) ?></strong>
                                    <div class="text-muted text-xs">Unit <?= htmlspecialchars($lease['unit_number']) ?></div>
                                </td>
                                <td>
                                    <?php
                                    $tenantNames = array_filter(array_map('trim', preg_split('/\s*(&|,)\s*/', $lease['tenant_name'] ?: 'Resident')));
                                    foreach ($tenantNames as $tName):
                                    ?>
                                        <div><strong><?= htmlspecialchars($tName) ?></strong></div>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($lease['start_date']) ?> &rarr; <?= htmlspecialchars($lease['end_date']) ?>
                                </td>
                                <td class="font-bold text-success">
                                    $<?= number_format((float)$lease['rent_amount'], 2) ?>
                                </td>
                                <td>
                                    <a href="<?= $leaseUrl ?>" class="btn btn-outline btn-xs" style="border: 1.5px solid var(--ws-border-strong, #d8d3c7); font-weight: 600; padding: 6px 14px; border-radius: 8px; background: #ffffff; color: var(--ws-text); box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: inline-flex; align-items: center; justify-content: center; text-decoration: none;">View Terms</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
