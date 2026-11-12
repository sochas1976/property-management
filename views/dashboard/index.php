<?php
use App\Core\Router;
use App\Core\AppDate;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Operations Dashboard</h1>
        <p class="page-subtitle">Real-time overview of your real estate portfolio, occupancy, and cash flow</p>
    </div>
    <div class="action-buttons">
        <a href="<?= Router::url('/properties/create') ?>" class="btn btn-outline">+ Add Property</a>
        <a href="<?= Router::url('/leases/create') ?>" class="btn btn-primary">+ New Lease</a>
    </div>
</div>

<!-- KPI Cards Grid -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Properties</span>
            <span class="metric-icon">🏘️</span>
        </div>
        <div class="metric-value"><?= (int)$propertiesCount ?></div>
        <div class="metric-footer">Active across all portfolios</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Occupancy Rate</span>
            <span class="metric-icon">📊</span>
        </div>
        <div class="metric-value"><?= $occupancyRate ?>%</div>
        <div class="metric-footer">
            <span class="text-success font-medium"><?= (int)$occupiedUnits ?> Occupied</span> of <?= (int)$totalUnits ?> Total Units
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Rent Collected</span>
            <span class="metric-icon">💵</span>
        </div>
        <div class="metric-value text-success">$<?= number_format($financialTotals['total_collected'], 2) ?></div>
        <div class="metric-footer">
            $<?= number_format($financialTotals['total_pending'], 2) ?> pending invoices
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Open Tickets</span>
            <span class="metric-icon">🔧</span>
        </div>
        <div class="metric-value <?= $openTicketsCount > 0 ? 'text-warning' : 'text-success' ?>">
            <?= (int)$openTicketsCount ?>
        </div>
        <div class="metric-footer">Maintenance requests in queue</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Applicants</span>
            <span class="metric-icon">📋</span>
        </div>
        <div class="metric-value <?= $pendingApplicants > 0 ? 'text-warning' : '' ?>">
            <?= (int)$pendingApplicants ?>
        </div>
        <div class="metric-footer"><a href="<?= Router::url('/applicants') ?>" style="color: inherit; text-decoration: underline;">Pending applications</a></div>
    </div>
</div>

<div class="dashboard-columns">
    <!-- Lease Expiry Dates Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Lease Expiry Dates</h2>
            <a href="<?= Router::url('/leases') ?>" class="btn btn-link">View All Leases &rarr;</a>
        </div>
        <div class="table-responsive" style="overflow-x: hidden;">
            <table class="table" style="table-layout: auto; width: 100%; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="padding: 12px 10px 12px 18px; white-space: nowrap; width: 1%;">Status</th>
                        <th style="padding: 12px 10px; white-space: nowrap; width: 1%;">Expiry Date</th>
                        <th style="padding: 12px 10px; white-space: nowrap;">Tenant</th>
                        <th style="padding: 12px 18px 12px 10px;">Property & Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expiringLeases)): ?>
                        <tr><td colspan="4" class="text-center text-muted" style="padding: 25px;">No active leases recorded yet.</td></tr>
                    <?php else: ?>
                        <?php 
                        $today = AppDate::today();
                        foreach ($expiringLeases as $l): 
                            $endDate = !empty($l['end_date']) ? new DateTime($l['end_date']) : null;
                            $daysLeft = null;
                            if ($endDate) {
                                $diff = $today->diff($endDate);
                                $daysLeft = $diff->invert ? -$diff->days : $diff->days;
                            }
                            $isExpiringSoon = ($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 45);
                            $isExpired = ($daysLeft !== null && $daysLeft < 0);
                        ?>
                            <tr onclick="window.location.href='<?= Router::url('/leases/' . $l['id']) ?>'" style="cursor: pointer;" title="Click to view lease agreement">
                                <td style="padding: 12px 10px 12px 18px; white-space: nowrap;">
                                    <?php if ($isExpired): ?>
                                        <span class="badge badge-warning">Expired</span>
                                    <?php elseif ($isExpiringSoon || $l['status'] === 'expiring_soon'): ?>
                                        <span class="badge badge-warning">Expiring Soon</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 10px; white-space: nowrap;">
                                    <strong><?= date('M j, Y', strtotime($l['end_date'])) ?></strong>
                                    <?php if ($daysLeft !== null): ?>
                                        <div class="text-muted text-xs" style="white-space: nowrap;">
                                            <?= $daysLeft === 0 ? 'Expires today' : ($daysLeft === 1 ? '1 day left' : ($daysLeft > 1 ? $daysLeft . ' days left' : abs($daysLeft) . ' days ago')) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 10px; white-space: nowrap;">
                                    <strong><?= htmlspecialchars($l['tenant_name']) ?></strong>
                                </td>
                                <td style="padding: 12px 18px 12px 10px;">
                                    <div style="font-weight: 600; line-height: 1.25;"><?= htmlspecialchars($l['property_name']) ?></div>
                                    <div class="text-muted text-xs">Unit <?= htmlspecialchars($l['unit_number']) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Maintenance Tickets Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Urgent Maintenance Tickets</h2>
            <a href="<?= Router::url('/maintenance') ?>" class="btn btn-link">Manage Work Orders &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Priority</th>
                        <th>Issue</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTickets)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No open maintenance tickets. Everything in order!</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentTickets as $ticket): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $pClassMap = ['emergency' => 'badge-danger', 'high' => 'badge-warning', 'medium' => 'badge-info'];
                                    $pClass = $pClassMap[$ticket['priority']] ?? 'badge-subtle';
                                    ?>
                                    <span class="badge <?= $pClass ?>"><?= ucfirst($ticket['priority']) ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($ticket['title']) ?></strong>
                                    <div class="text-muted text-xs"><?= htmlspecialchars(substr($ticket['description'], 0, 50)) ?>...</div>
                                </td>
                                <td><?= htmlspecialchars($ticket['property_name']) ?> #<?= htmlspecialchars($ticket['unit_number']) ?></td>
                                <td><span class="badge badge-subtle"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span></td>
                                <td>
                                    <a href="<?= Router::url('/maintenance/' . $ticket['id']) ?>" class="btn btn-outline btn-xs">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
