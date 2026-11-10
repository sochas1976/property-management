<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/maintenance') ?>">&larr; Back to Maintenance</a>
</div>

<div class="page-header">
    <div>
        <?php
        $pClassMap = ['emergency' => 'badge-danger', 'high' => 'badge-warning', 'medium' => 'badge-info'];
        $pClass = $pClassMap[$request['priority']] ?? 'badge-subtle';
        ?>
        <div class="badge <?= $pClass ?>"><?= strtoupper($request['priority']) ?> PRIORITY</div>
        <h1 class="page-title" style="margin-top: 5px;"><?= htmlspecialchars($request['title']) ?></h1>
        <p class="page-subtitle">
            Ticket #<?= $request['id'] ?> &bull; <?= htmlspecialchars($request['property_name']) ?> (Unit <?= htmlspecialchars($request['unit_number']) ?>) &bull; Reported <?= date('M d, Y h:i A', strtotime($request['created_at'])) ?>
        </p>
    </div>
</div>

<div class="dashboard-columns">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Issue Description</h2>
            <span class="badge badge-subtle"><?= ucfirst(str_replace('_', ' ', $request['category'])) ?></span>
        </div>
        <div class="card-body">
            <p style="font-size: 15px; line-height: 1.6;"><?= nl2br(htmlspecialchars($request['description'])) ?></p>

            <hr style="border:0; border-top: 1px solid var(--border); margin: 20px 0;">

            <div class="stat-row">
                <span class="text-muted">Reported By:</span>
                <strong><?= htmlspecialchars($request['tenant_name'] ?? 'In-House Staff') ?></strong>
            </div>
            <?php if (!empty($request['tenant_phone'])): ?>
                <div class="stat-row">
                    <span class="text-muted">Resident Phone:</span>
                    <span><?= htmlspecialchars($request['tenant_phone']) ?></span>
                </div>
            <?php endif; ?>
            <div class="stat-row">
                <span class="text-muted">Currently Assigned To:</span>
                <strong><?= htmlspecialchars($request['assigned_to'] ?? 'Unassigned') ?></strong>
            </div>

            <?php if (!empty($request['resolution_notes'])): ?>
                <div style="margin-top: 20px; padding: 15px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px;">
                    <h4 style="margin: 0 0 5px; color: #166534; font-size: 14px;">Resolution / Work Notes:</h4>
                    <p style="margin: 0; color: #14532d;"><?= nl2br(htmlspecialchars($request['resolution_notes'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Management & Photo Attachments -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Status & Dispatch Form -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Update Work Order Status</h2>
            </div>
            <div class="card-body">
                <form action="<?= Router::url('/maintenance/' . $request['id'] . '/status') ?>" method="POST">
                    <div class="form-group">
                        <label for="status">Work Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="new" <?= $request['status'] === 'new' ? 'selected' : '' ?>>New / Unacknowledged</option>
                            <option value="assigned" <?= $request['status'] === 'assigned' ? 'selected' : '' ?>>Assigned to Vendor</option>
                            <option value="in_progress" <?= $request['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress / Parts Ordered</option>
                            <option value="resolved" <?= $request['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="closed" <?= $request['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="assigned_to">Assigned Contractor / Tech</label>
                        <input type="text" id="assigned_to" name="assigned_to" class="form-control" value="<?= htmlspecialchars($request['assigned_to'] ?? '') ?>" placeholder="e.g. Master Plumbers LLC">
                    </div>

                    <div class="form-group">
                        <label for="resolution_notes">Resolution / Completion Notes</label>
                        <textarea id="resolution_notes" name="resolution_notes" class="form-control" rows="3" placeholder="Replaced valve stem, tested pressure, tenant signed off..."><?= htmlspecialchars($request['resolution_notes'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Save Work Order Changes</button>
                </form>
            </div>
        </div>

        <!-- Local Photo Vault -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Attached Photos & Invoices</h2>
                <span class="badge badge-subtle">Local Storage</span>
            </div>
            <div class="card-body">
                <?php if (empty($documents)): ?>
                    <p class="text-muted text-center" style="margin: 0; padding: 15px;">No images or receipts attached.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($documents as $doc): ?>
                            <div style="border: 1px solid var(--border); border-radius: 8px; overflow: hidden;">
                                <?php if (strpos($doc['mime_type'], 'image/') === 0): ?>
                                    <div style="max-height: 220px; overflow: hidden; background: #000; text-align: center;">
                                        <img src="<?= Router::url('/documents/' . $doc['id']) ?>" alt="<?= htmlspecialchars($doc['original_name']) ?>" style="max-width: 100%; max-height: 220px; object-fit: contain;">
                                    </div>
                                <?php endif; ?>
                                <div style="padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; background: var(--bg-card);">
                                    <span style="font-size: 13px; font-weight: 500;"><?= htmlspecialchars($doc['original_name']) ?></span>
                                    <div>
                                        <a href="<?= Router::url('/documents/' . $doc['id'] . '/download') ?>" class="btn btn-outline btn-xs">Download</a>
                                        <form action="<?= Router::url('/documents/' . $doc['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this file?');" style="display:inline;">
                                            <button type="submit" class="btn btn-danger btn-outline btn-xs">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
