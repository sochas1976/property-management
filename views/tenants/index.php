<?php
use App\Core\Router;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Tenant Directory</h1>
        <p class="page-subtitle">Roster of current residents, emergency contacts, and active lease assignments</p>
    </div>
    <div>
        <a href="<?= Router::url('/tenants/create') ?>" class="btn btn-primary">+ Register Tenant</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
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
                            No tenants registered yet.
                            <br><br>
                            <a href="<?= Router::url('/tenants/create') ?>" class="btn btn-primary btn-sm">Register Tenant</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tenants as $tenant): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($tenant['first_name'] . ' ' . $tenant['last_name']) ?></strong>
                            </td>
                            <td>
                                <?php if (!empty($tenant['active_lease_id'])): ?>
                                    <span class="badge badge-success">Active Tenant</span>
                                <?php else: ?>
                                    <span class="badge badge-subtle">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>📧 <a href="mailto:<?= htmlspecialchars($tenant['email']) ?>"><?= htmlspecialchars($tenant['email']) ?></a></div>
                                <div class="text-muted text-xs">📞 <?= htmlspecialchars($tenant['phone']) ?></div>
                            </td>
                            <td>
                                <?php if (!empty($tenant['property_name'])): ?>
                                    <strong>
                                        <?php if (!empty($tenant['property_id'])): ?>
                                            <a href="<?= Router::url('/properties/' . $tenant['property_id']) ?>" class="font-medium text-primary">
                                                <?= htmlspecialchars($tenant['property_name']) ?>
                                            </a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($tenant['property_name']) ?>
                                        <?php endif; ?>
                                    </strong>
                                    <div class="text-muted text-xs">
                                        <?php if (!empty($tenant['unit_id'])): ?>
                                            <a href="<?= Router::url('/units/' . $tenant['unit_id'] . '/edit') ?>" class="text-muted">
                                                Unit #<?= htmlspecialchars($tenant['unit_number']) ?>
                                            </a>
                                        <?php else: ?>
                                            Unit #<?= htmlspecialchars($tenant['unit_number']) ?>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge badge-subtle">No Active Unit</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($tenant['emergency_contact'])): ?>
                                    <div><?= htmlspecialchars($tenant['emergency_contact']) ?></div>
                                    <div class="text-muted text-xs"><?= htmlspecialchars($tenant['emergency_phone'] ?? '—') ?></div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= Router::url('/tenants/' . $tenant['id']) ?>" class="btn btn-outline btn-xs">View</a>
                                <form action="<?= Router::url('/tenants/' . $tenant['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete tenant profile?');" style="display:inline;">
                                    <button type="submit" class="btn btn-outline btn-danger btn-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
