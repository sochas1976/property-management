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

            <hr style="border:0; border-top: 1px solid var(--ws-border-subtle); margin: 20px 0;">

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
                <div style="margin-top: 20px; padding: 15px; background: var(--ws-success-bg); border: 1px solid var(--ws-success-border); border-radius: 10px;">
                    <h4 style="margin: 0 0 5px; color: var(--ws-success); font-size: 14px;">Resolution / Work Notes:</h4>
                    <p style="margin: 0; color: var(--ws-text);"><?= nl2br(htmlspecialchars($request['resolution_notes'])) ?></p>
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
                            <div style="border: 1px solid var(--ws-border-subtle); border-radius: 10px; overflow: hidden;">
                                <?php if (strpos($doc['mime_type'], 'image/') === 0): ?>
                                    <div style="max-height: 220px; overflow: hidden; background: #000; text-align: center;">
                                        <img src="<?= Router::url('/documents/' . $doc['id']) ?>" alt="<?= htmlspecialchars($doc['original_name']) ?>" style="max-width: 100%; max-height: 220px; object-fit: contain;">
                                    </div>
                                <?php endif; ?>
                                <div style="padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; background: var(--ws-surface-subtle);">
                                    <span style="font-size: 13px; font-weight: 500;"><?= htmlspecialchars($doc['original_name']) ?></span>
                                    <div style="display: flex; gap: 6px; align-items: center;">
                                        <a href="<?= Router::url('/documents/' . $doc['id'] . '/download') ?>" class="btn btn-outline btn-xs">Download</a>
                                        <form action="<?= Router::url('/documents/' . $doc['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this file?');" style="display:inline; margin: 0;">
                                            <button type="submit" class="btn btn-danger btn-outline btn-xs" title="Delete Attachment" aria-label="Delete Attachment"
                                                    style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
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
