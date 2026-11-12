<?php
use App\Core\Router;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Historical Applications</h1>
        <p class="page-subtitle">Archived applications, converted tenancies, and past applications</p>
    </div>
    <div>
        <a href="<?= Router::url('/applicants/create') ?>" class="btn btn-primary">+ Add Application</a>
    </div>
</div>

<!-- View Segmented Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; align-items: center;">
    <a href="<?= Router::url('/applicants') ?>" class="btn btn-outline btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Active Applications
    </a>
    <a href="<?= Router::url('/applicants/history') ?>" class="btn btn-primary btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Historical Applications
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Assigned Apartment</th>
                    <th>Lease Commencement</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historicalApplicants)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted" style="padding: 40px;">
                            No historical application records yet.
                            <br><small class="text-muted">Converted tenants whose lease has commenced and rejected applications after 1 day will appear here.</small>
                            <br><br>
                            <a href="<?= Router::url('/applicants') ?>" class="btn btn-outline btn-sm">&larr; Return to Active Applications</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historicalApplicants as $app): ?>
                        <tr>
                            <td>
                                <strong><a href="<?= Router::url('/applicants/' . $app['id'] . '?from=history') ?>"><?= htmlspecialchars($app['full_name']) ?></a></strong>
                            </td>
                            <td>
                                <?php if (!empty($app['target_property_name'])): ?>
                                    <div><?= htmlspecialchars($app['target_property_name']) ?></div>
                                    <?php if (!empty($app['target_unit_number'])): ?>
                                        <div class="text-muted text-xs">Unit #<?= htmlspecialchars($app['target_unit_number']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php if ($app['status'] === 'converted'): ?>
                                    <?php 
                                    $start = !empty($app['lease_start_date']) ? $app['lease_start_date'] : $app['term_start_date'];
                                    $end = !empty($app['lease_end_date']) ? $app['lease_end_date'] : $app['term_end_date'];
                                    ?>
                                    <?php if (!empty($start)): ?>
                                        <strong><?= date('M j, Y', strtotime($start)) ?></strong>
                                        <?php if (!empty($end)): ?>
                                            <div class="text-muted text-xs">thru <?= date('M j, Y', strtotime($end)) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                <?php elseif ($app['status'] === 'rejected'): ?>
                                    <?php 
                                    $requestedTerm = !empty($app['term_start_date']) ? $app['term_start_date'] : $app['date_requested'];
                                    ?>
                                    <?php if (!empty($requestedTerm)): ?>
                                        <span class="text-muted text-xs">Term: <?= date('M j, Y', strtotime($requestedTerm)) ?></span>
                                    <?php elseif (!empty($app['status_date'])): ?>
                                        <span class="text-muted text-xs">Rejected <?= date('M j, Y', strtotime($app['status_date'])) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($app['status'] === 'converted'): ?>
                                    <span class="badge badge-success" style="font-size: 14px; padding: 3px 10px; font-weight: 500;">Converted to Tenant</span>
                                    <div class="text-muted text-xs" style="margin-top: 2px;">Lease Commenced</div>
                                <?php elseif ($app['status'] === 'rejected'): ?>
                                    <span class="badge badge-danger" style="font-size: 14px; padding: 3px 10px; font-weight: 500;">Rejected</span>
                                    <div class="text-muted text-xs" style="margin-top: 2px;"><?= !empty($app['auto_rejected']) ? 'Term Expired' : 'Application Closed' ?></div>
                                <?php else: ?>
                                    <span class="badge badge-subtle"><?= ucfirst(htmlspecialchars($app['status'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <?= date('M j, Y', strtotime($app['created_at'])) ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <a href="<?= Router::url('/applicants/' . $app['id'] . '?from=history') ?>" 
                                       class="btn btn-outline btn-xs" 
                                       title="View Application Details"
                                       aria-label="View Application Details"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <?php if (!empty($app['tenant_id'])): ?>
                                        <a href="<?= Router::url('/tenants/' . $app['tenant_id'] . '?from=history') ?>" 
                                           class="btn btn-outline btn-xs" 
                                           title="View Active Tenant Profile"
                                           aria-label="View Active Tenant Profile"
                                           style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($app['lease_id'])): ?>
                                        <a href="<?= Router::url('/leases/' . $app['lease_id'] . '?from=history') ?>" 
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
