<?php
use App\Core\Router;

$from = $_GET['from'] ?? '';
if (empty($from) && !empty($_SERVER['HTTP_REFERER'])) {
    if (strpos($_SERVER['HTTP_REFERER'], '/tenants/history') !== false) {
        $from = 'tenants_history';
    } elseif (strpos($_SERVER['HTTP_REFERER'], '/applicants/history') !== false) {
        $from = 'applicants_history';
    }
}
$fromTenantsHistory = ($from === 'history' || $from === 'tenants_history');
$fromApplicantsHistory = ($from === 'applicants_history');
?>

<div class="breadcrumb">
    <?php if ($fromTenantsHistory): ?>
        <a href="<?= Router::url('/tenants/history') ?>">&larr; Back to Historical Tenants</a>
    <?php elseif ($fromApplicantsHistory): ?>
        <a href="<?= Router::url('/applicants/history') ?>">&larr; Back to Historical Applications</a>
    <?php else: ?>
        <a href="<?= Router::url('/tenants') ?>">&larr; Back to Active Tenants</a>
    <?php endif; ?>
</div>

<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <h1 class="page-title" style="margin: 0;"><?= htmlspecialchars($tenant['first_name'] . ' ' . $tenant['last_name']) ?></h1>
            <?php if (!empty($tenant['active_lease_id'])): ?>
                <span class="badge badge-success">Active Tenant</span>
            <?php else: ?>
                <span class="badge badge-subtle">Past Tenant</span>
            <?php endif; ?>
        </div>
        <p class="page-subtitle" style="margin-top: 4px;">Resident Profile & Contact File</p>
    </div>
    <div class="action-buttons">
        <?php if (!empty($tenant['active_lease_id'])): ?>
            <a href="<?= Router::url('/leases/' . $tenant['active_lease_id'] . ($fromTenantsHistory ? '?from=history' : '')) ?>" class="btn btn-primary">View Active Lease</a>
        <?php else: ?>
            <a href="<?= Router::url('/leases/create') ?>" class="btn btn-outline">Assign to Lease</a>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-columns">
    <div class="card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <div class="card-header" style="background-color: var(--table-th-dark, #d8d3c7); border-bottom: 1.5px solid var(--table-th-dark-border, #c4bcae); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--table-th-dark-text, var(--ws-text));">Contact Information</h2>
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

    <div class="card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <div class="card-header" style="background-color: var(--table-th-dark, #d8d3c7); border-bottom: 1.5px solid var(--table-th-dark-border, #c4bcae); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--table-th-dark-text, var(--ws-text));">Current Unit & Property</h2>
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
                <p class="text-muted">Tenant is currently not assigned to an active lease agreement.</p>
                <a href="<?= Router::url('/leases/create') ?>" class="btn btn-outline btn-sm">Draft New Lease</a>
            <?php endif; ?>

            <?php if (!empty($tenant['notes'])): ?>
                <hr style="border:0; border-top: 1px solid var(--border); margin: 15px 0;">
                <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($tenant['notes'])) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($leases)): ?>
    <div class="card" style="margin-top: 24px; border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <div class="card-header" style="background-color: var(--table-th-dark, #d8d3c7); border-bottom: 1.5px solid var(--table-th-dark-border, #c4bcae); padding: 16px 20px;">
            <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--table-th-dark-text, var(--ws-text));">
                Lease History
            </h2>
        </div>
        <div class="table-responsive" style="background-color: var(--ws-bg);">
            <table class="table">
                <thead>
                    <tr>
                        <th>Lease ID</th>
                        <th>Property & Unit</th>
                        <th>Status</th>
                        <th>Term</th>
                        <th>Rent</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leases as $l): ?>
                        <tr>
                            <td style="font-weight: 500; color: var(--ws-text);">
                                #<?= htmlspecialchars($l['id']) ?>
                            </td>
                            <td>
                                <div style="font-weight: 500; color: var(--ws-text);"><?= htmlspecialchars($l['property_name'] ?? '—') ?></div>
                                <div class="text-muted text-xs">Unit #<?= htmlspecialchars($l['unit_number'] ?? '—') ?></div>
                            </td>
                            <td>
                                <?php if ($l['status'] === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php elseif ($l['status'] === 'terminated'): ?>
                                    <span class="badge badge-danger">Terminated</span>
                                <?php elseif ($l['status'] === 'expired'): ?>
                                    <span class="badge badge-subtle">Expired</span>
                                <?php else: ?>
                                    <span class="badge badge-subtle"><?= htmlspecialchars(ucfirst($l['status'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= date('M d, Y', strtotime($l['start_date'])) ?> – <?= date('M d, Y', strtotime($l['end_date'])) ?>
                            </td>
                            <td class="font-bold text-success">
                                $<?= number_format((float)$l['rent_amount'], 2) ?>/mo
                            </td>
                            <td>
                                <a href="<?= Router::url('/leases/' . $l['id']) ?>" 
                                   class="btn btn-outline btn-xs" 
                                   title="View Lease Agreement"
                                   aria-label="View Lease Agreement"
                                   style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
