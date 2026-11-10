<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/tenants') ?>">&larr; Back to Tenant Directory</a>
</div>

<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <h1 class="page-title" style="margin: 0;"><?= htmlspecialchars($tenant['first_name'] . ' ' . $tenant['last_name']) ?></h1>
            <?php if (!empty($tenant['active_lease_id'])): ?>
                <span class="badge badge-success">Active Tenant</span>
            <?php else: ?>
                <span class="badge badge-subtle">Inactive</span>
            <?php endif; ?>
        </div>
        <p class="page-subtitle" style="margin-top: 4px;">Resident Profile & Contact File</p>
    </div>
    <div class="action-buttons">
        <?php if (!empty($tenant['active_lease_id'])): ?>
            <a href="<?= Router::url('/leases/' . $tenant['active_lease_id']) ?>" class="btn btn-primary">View Active Lease</a>
        <?php else: ?>
            <a href="<?= Router::url('/leases/create') ?>" class="btn btn-outline">+ Assign to Lease</a>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-columns">
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="card-title">Contact Information</h2>
            <a href="<?= Router::url('/tenants/' . $tenant['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit Profile</a>
        </div>
        <div class="card-body">
            <p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($tenant['email']) ?>"><?= htmlspecialchars($tenant['email']) ?></a></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($tenant['phone']) ?></p>
            <hr style="border:0; border-top: 1px solid var(--border); margin: 15px 0;">
            <p><strong>Emergency Contact:</strong> <?= htmlspecialchars($tenant['emergency_contact'] ?? 'None registered') ?></p>
            <p><strong>Emergency Phone:</strong> <?= htmlspecialchars($tenant['emergency_phone'] ?? '—') ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2 class="card-title">Current Unit & Property</h2>
            <?php if (!empty($tenant['property_id'])): ?>
                <a href="<?= Router::url('/properties/' . $tenant['property_id']) ?>" class="btn btn-outline btn-sm">View Property</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (!empty($tenant['property_name'])): ?>
                <p>
                    <strong>Property:</strong> 
                    <a href="<?= Router::url('/properties/' . $tenant['property_id']) ?>" class="font-medium text-primary">
                        <?= htmlspecialchars($tenant['property_name']) ?> &rarr;
                    </a>
                </p>
                <p>
                    <strong>Unit:</strong> 
                    <?php if (!empty($tenant['unit_id'])): ?>
                        <a href="<?= Router::url('/units/' . $tenant['unit_id'] . '/edit') ?>" class="font-medium text-primary">
                            Unit <?= htmlspecialchars($tenant['unit_number']) ?> (Edit Unit) &rarr;
                        </a>
                    <?php else: ?>
                        Unit <?= htmlspecialchars($tenant['unit_number']) ?>
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <p class="text-muted">Tenant is currently not assigned to any active lease agreement.</p>
                <a href="<?= Router::url('/leases/create') ?>" class="btn btn-primary btn-sm">Draft New Lease</a>
            <?php endif; ?>

            <?php if (!empty($tenant['notes'])): ?>
                <hr style="border:0; border-top: 1px solid var(--border); margin: 15px 0;">
                <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($tenant['notes'])) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
