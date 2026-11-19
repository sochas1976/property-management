<?php
use App\Core\Router;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Maintenance & Work Orders</h1>
        <p class="page-subtitle">Track resident maintenance requests, vendor dispatches, and repair workflows</p>
    </div>
    <div>
        <a href="<?= Router::url('/maintenance/create') ?>" class="btn btn-outline">Submit Ticket</a>
    </div>
</div>

<!-- View Segmented Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;">
    <a href="<?= Router::url('/maintenance?status=all') ?>" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 9999px; padding: 6px 16px;">All Tickets</a>
    <a href="<?= Router::url('/maintenance?status=new') ?>" class="btn <?= $statusFilter === 'new' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 9999px; padding: 6px 16px;">New</a>
    <a href="<?= Router::url('/maintenance?status=assigned') ?>" class="btn <?= $statusFilter === 'assigned' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 9999px; padding: 6px 16px;">Assigned</a>
    <a href="<?= Router::url('/maintenance?status=in_progress') ?>" class="btn <?= $statusFilter === 'in_progress' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 9999px; padding: 6px 16px;">In Progress</a>
    <a href="<?= Router::url('/maintenance?status=resolved') ?>" class="btn <?= $statusFilter === 'resolved' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 9999px; padding: 6px 16px;">Resolved</a>
</div>

<div class="card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <div class="card-header" style="background-color: var(--table-th-dark, #d8d3c7); border-bottom: 1.5px solid var(--table-th-dark-border, #c4bcae); padding: 16px 20px;">
        <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--table-th-dark-text, var(--ws-text));">
            Maintenance Work Orders
        </h2>
    </div>
    <div class="table-responsive" style="background-color: var(--ws-bg);">
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
                                <div>
                                    <a href="<?= Router::url('/maintenance/' . $req['id']) ?>" style="font-weight: 500; color: var(--ws-text); text-decoration: none;">
                                        <?= htmlspecialchars($req['title']) ?>
                                    </a>
                                </div>
                                <?php if (!empty($req['document_id'])): ?>
                                    <div class="text-muted text-xs">Photo attached</div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-subtle"><?= ucfirst(str_replace('_', ' ', $req['category'])) ?></span></td>
                            <td>
                                <div style="font-weight: 500; color: var(--ws-text);"><?= htmlspecialchars($req['property_name']) ?></div>
                                <div class="text-muted text-xs">Unit #<?= htmlspecialchars($req['unit_number']) ?></div>
                            </td>
                            <td style="font-weight: 500; color: var(--ws-text);"><?= htmlspecialchars($req['tenant_name'] ?? 'Staff') ?></td>
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
                                <a href="<?= Router::url('/maintenance/' . $req['id']) ?>" 
                                   class="btn btn-outline btn-xs" 
                                   title="View & Manage Work Order"
                                   aria-label="View & Manage Work Order"
                                   style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
