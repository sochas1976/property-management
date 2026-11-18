<?php
use App\Core\Router;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Tenant Directory</h1>
        <p class="page-subtitle">Roster of current residents, emergency contacts, and active lease assignments</p>
    </div>
    <div>
        <a href="<?= Router::url('/tenants/create') ?>" class="btn btn-outline">Register Tenant</a>
    </div>
</div>

<!-- View Segmented Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; align-items: center;">
    <a href="<?= Router::url('/tenants') ?>" class="btn btn-primary btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Active Tenants
    </a>
    <a href="<?= Router::url('/tenants/history') ?>" class="btn btn-outline btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Historical Tenants
    </a>
</div>

<div class="card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <div class="card-header" style="background-color: var(--table-th-dark, #d8d3c7); border-bottom: 1.5px solid var(--table-th-dark-border, #c4bcae); padding: 16px 20px;">
        <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--table-th-dark-text, var(--ws-text));">
            Active Tenants
        </h2>
    </div>
    <div class="table-responsive" style="background-color: var(--ws-bg);">
        <table class="table">
            <thead>
                <tr>
                    <th>Tenant Name</th>
                    <th>Status</th>
                    <th>Contact Info</th>
                    <th>Current Residence</th>
                    <th>Emergency Contact</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tenants)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted" style="padding: 40px;">
                            No active tenants with an assigned residence and lease agreement.
                            <br><br>
                            <a href="<?= Router::url('/tenants/history') ?>" class="btn btn-outline btn-sm">View Historical Tenants</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tenants as $tenant): ?>
                        <tr>
                            <td>
                                <div>
                                    <a href="<?= Router::url('/tenants/' . $tenant['id']) ?>" style="font-weight: 500; color: var(--ws-text); text-decoration: none;">
                                        <?= htmlspecialchars($tenant['first_name'] . ' ' . $tenant['last_name']) ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-success">Active Tenant</span>
                            </td>
                            <td>
                                <div>
                                    <a href="mailto:<?= htmlspecialchars($tenant['email']) ?>" style="color: var(--ws-text); text-decoration: none; font-weight: 500;">
                                        <?= htmlspecialchars($tenant['email']) ?>
                                    </a>
                                </div>
                                <div class="text-muted text-xs"><?= htmlspecialchars($tenant['phone']) ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 500;">
                                    <?php if (!empty($tenant['property_id'])): ?>
                                        <a href="<?= Router::url('/properties/' . $tenant['property_id']) ?>" style="color: var(--ws-text); text-decoration: none; font-weight: 500;">
                                            <?= htmlspecialchars($tenant['property_name']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--ws-text);"><?= htmlspecialchars($tenant['property_name'] ?? '—') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted text-xs">
                                    <?php if (!empty($tenant['unit_id'])): ?>
                                        <a href="<?= Router::url('/units/' . $tenant['unit_id'] . '/edit') ?>" class="text-muted" style="text-decoration: none;">
                                            Unit #<?= htmlspecialchars($tenant['unit_number']) ?>
                                        </a>
                                    <?php else: ?>
                                        Unit #<?= htmlspecialchars($tenant['unit_number'] ?? '—') ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($tenant['emergency_contact'])): ?>
                                    <div style="font-weight: 500; color: var(--ws-text);"><?= htmlspecialchars($tenant['emergency_contact']) ?></div>
                                    <div class="text-muted text-xs"><?= htmlspecialchars($tenant['emergency_phone'] ?? '—') ?></div>
                                <?php else: ?>
                                    <div class="text-muted">—</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <a href="<?= Router::url('/tenants/' . $tenant['id']) ?>" 
                                       class="btn btn-outline btn-xs" 
                                       title="View Tenant Profile"
                                       aria-label="View Tenant Profile"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <?php if (!empty($tenant['active_lease_id'])): ?>
                                        <a href="<?= Router::url('/leases/' . $tenant['active_lease_id']) ?>" 
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
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
