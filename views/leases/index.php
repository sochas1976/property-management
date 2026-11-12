<?php
use App\Core\Router;
use App\Core\AppDate;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Lease Agreements</h1>
        <p class="page-subtitle">Manage contract terms, security deposits, and Word (.docx) lease templates</p>
    </div>
    <div>
        <a href="<?= Router::url('/leases/create') ?>" class="btn btn-primary">+ Create New Lease</a>
    </div>
</div>

<!-- View Segmented Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; align-items: center;">
    <a href="<?= Router::url('/leases') ?>" class="btn btn-primary btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Lease Agreements
    </a>
    <a href="<?= Router::url('/leases/templates') ?>" class="btn btn-outline btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Lease Templates
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Property & Unit</th>
                    <th>Tenant</th>
                    <th>Lease Period</th>
                    <th>Monthly Rent</th>
                    <th>Document</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leases)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 40px;">
                            No lease contracts recorded yet.
                            <br><br>
                            <a href="<?= Router::url('/leases/create') ?>" class="btn btn-primary btn-sm">Draft First Lease</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leases as $lease): ?>
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
                                <strong><?= htmlspecialchars($lease['tenant_name']) ?></strong>
                                <div class="text-muted text-xs"><?= htmlspecialchars($lease['tenant_email']) ?></div>
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
                            <td>
                                <a href="<?= Router::url('/leases/' . $lease['id']) ?>" class="btn btn-outline btn-xs">View Terms</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
