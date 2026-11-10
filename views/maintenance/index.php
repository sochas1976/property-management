<?php
use App\Core\Router;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Maintenance & Work Orders</h1>
        <p class="page-subtitle">Track resident maintenance requests, vendor dispatches, and repair workflows</p>
    </div>
    <div>
        <a href="<?= Router::url('/maintenance/create') ?>" class="btn btn-primary">+ Submit Ticket</a>
    </div>
</div>

<!-- Status Filter Tabs -->
<div class="filter-tabs">
    <a href="<?= Router::url('/maintenance?status=all') ?>" class="tab-link <?= $statusFilter === 'all' ? 'active' : '' ?>">All Tickets</a>
    <a href="<?= Router::url('/maintenance?status=new') ?>" class="tab-link <?= $statusFilter === 'new' ? 'active' : '' ?>">New</a>
    <a href="<?= Router::url('/maintenance?status=assigned') ?>" class="tab-link <?= $statusFilter === 'assigned' ? 'active' : '' ?>">Assigned</a>
    <a href="<?= Router::url('/maintenance?status=in_progress') ?>" class="tab-link <?= $statusFilter === 'in_progress' ? 'active' : '' ?>">In Progress</a>
    <a href="<?= Router::url('/maintenance?status=resolved') ?>" class="tab-link <?= $statusFilter === 'resolved' ? 'active' : '' ?>">Resolved</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Priority</th>
                    <th>Ticket Title</th>
                    <th>Category</th>
                    <th>Property & Unit</th>
                    <th>Reported By</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted" style="padding: 40px;">
                            No maintenance requests match this filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>
                                <?php
                                $reqPriorityMap = ['emergency' => 'badge-danger', 'high' => 'badge-warning', 'medium' => 'badge-info'];
                                $priorityClass = $reqPriorityMap[$req['priority']] ?? 'badge-subtle';
                                ?>
                                <span class="badge <?= $priorityClass ?>"><?= ucfirst($req['priority']) ?></span>
                            </td>
                            <td>
                                <strong><a href="<?= Router::url('/maintenance/' . $req['id']) ?>"><?= htmlspecialchars($req['title']) ?></a></strong>
                                <?php if (!empty($req['document_id'])): ?>
                                    <span title="Photo attached">📷</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-subtle"><?= ucfirst(str_replace('_', ' ', $req['category'])) ?></span></td>
                            <td><?= htmlspecialchars($req['property_name']) ?> (#<?= htmlspecialchars($req['unit_number']) ?>)</td>
                            <td><?= htmlspecialchars($req['tenant_name'] ?? 'Staff') ?></td>
                            <td>
                                <?php
                                $reqStatusMap = [
                                    'new'         => 'badge-warning',
                                    'assigned'    => 'badge-info',
                                    'in_progress' => 'badge-primary',
                                    'resolved'    => 'badge-success',
                                ];
                                $sClass = $reqStatusMap[$req['status']] ?? 'badge-subtle';
                                ?>
                                <span class="badge <?= $sClass ?>"><?= ucfirst(str_replace('_', ' ', $req['status'])) ?></span>
                            </td>
                            <td><?= htmlspecialchars($req['assigned_to'] ?? 'Unassigned') ?></td>
                            <td>
                                <a href="<?= Router::url('/maintenance/' . $req['id']) ?>" class="btn btn-outline btn-xs">Manage</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
