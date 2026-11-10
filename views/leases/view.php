<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/leases') ?>">&larr; Back to Leases</a>
</div>

<div class="page-header">
    <div>
        <div class="badge badge-success"><?= strtoupper($lease['status']) ?> LEASE</div>
        <h1 class="page-title" style="margin-top: 5px;">Lease #<?= $lease['id'] ?> — <?= htmlspecialchars($lease['property_name']) ?> (Unit <?= htmlspecialchars($lease['unit_number']) ?>)</h1>
        <p class="page-subtitle">Resident: <?= htmlspecialchars($lease['tenant_name']) ?> &bull; <?= htmlspecialchars($lease['tenant_email']) ?></p>
    </div>

    <div class="action-buttons">
        <form action="<?= Router::url('/leases/' . $lease['id'] . '/status') ?>" method="POST" style="display: flex; gap: 8px;">
            <select name="status" class="form-control" style="width: auto;">
                <option value="active" <?= $lease['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="draft" <?= $lease['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="terminated" <?= $lease['status'] === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                <option value="expired" <?= $lease['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
            </select>
            <button type="submit" class="btn btn-outline">Update Status</button>
        </form>
    </div>
</div>

<div class="dashboard-columns">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Agreement Terms</h2>
        </div>
        <div class="card-body">
            <div class="stat-row">
                <span class="text-muted">Monthly Rent:</span>
                <strong class="text-success font-bold" style="font-size: 18px;">$<?= number_format((float)$lease['rent_amount'], 2) ?></strong>
            </div>
            <div class="stat-row">
                <span class="text-muted">Security Deposit:</span>
                <strong>$<?= number_format((float)$lease['deposit_held'], 2) ?></strong>
            </div>
            <div class="stat-row">
                <span class="text-muted">Lease Duration:</span>
                <span><?= htmlspecialchars($lease['start_date']) ?> to <?= htmlspecialchars($lease['end_date']) ?></span>
            </div>
            <div class="stat-row">
                <span class="text-muted">Payment Frequency:</span>
                <span><?= ucfirst($lease['payment_frequency']) ?></span>
            </div>

            <?php if (!empty($lease['terms'])): ?>
                <hr style="border:0; border-top: 1px solid var(--border); margin: 15px 0;">
                <h4 style="margin: 0 0 5px; font-size: 13px; color: var(--text-muted); text-transform: uppercase;">Special Provisions</h4>
                <p style="margin: 0;"><?= nl2br(htmlspecialchars($lease['terms'])) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Protected Local Document Vault Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Attached Lease Contracts</h2>
            <span class="badge badge-subtle">Local Disk Vault</span>
        </div>
        <div class="card-body">
            <?php if (empty($documents)): ?>
                <div class="text-center text-muted" style="padding: 25px;">
                    <p>No document attached to this lease agreement.</p>
                </div>
            <?php else: ?>
                <div class="document-list">
                    <?php foreach ($documents as $doc): ?>
                        <div class="document-item">
                            <div class="document-icon">
                                <?= (strpos($doc['mime_type'], 'pdf') !== false) ? '📄' : '🖼️' ?>
                            </div>
                            <div class="document-info">
                                <div class="document-name"><?= htmlspecialchars($doc['original_name']) ?></div>
                                <div class="document-meta">
                                    <?= round($doc['file_size'] / 1024, 1) ?> KB &bull; Uploaded <?= date('M d, Y', strtotime($doc['created_at'])) ?>
                                </div>
                            </div>
                            <div class="document-actions">
                                <a href="<?= Router::url('/documents/' . $doc['id']) ?>" target="_blank" class="btn btn-outline btn-xs">View</a>
                                <a href="<?= Router::url('/documents/' . $doc['id'] . '/download') ?>" class="btn btn-primary btn-xs">Download</a>
                                <form action="<?= Router::url('/documents/' . $doc['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this stored document?');" style="display:inline;">
                                    <button type="submit" class="btn btn-danger btn-outline btn-xs">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
