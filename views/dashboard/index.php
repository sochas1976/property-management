<?php
use App\Core\Router;
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
    <!-- Recent Payments Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Rent Payments</h2>
            <a href="<?= Router::url('/finances') ?>" class="btn btn-link">View All Finances &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Tenant</th>
                        <th>Property & Unit</th>
                        <th>Amount</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentPayments)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No payments recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentPayments as $pm): ?>
                            <tr>
                                <td><?= htmlspecialchars($pm['payment_date']) ?></td>
                                <td><strong><?= htmlspecialchars($pm['tenant_name']) ?></strong></td>
                                <td><?= htmlspecialchars($pm['property_name']) ?> (#<?= htmlspecialchars($pm['unit_number']) ?>)</td>
                                <td class="text-success font-bold">$<?= number_format((float)$pm['amount_paid'], 2) ?></td>
                                <td><span class="badge badge-subtle"><?= ucfirst(str_replace('_', ' ', $pm['payment_method'])) ?></span></td>
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
