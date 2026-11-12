<?php
use App\Core\Router;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Rental Applicants</h1>
        <p class="page-subtitle">Prospective tenants, rental application forms, review pipeline, and approval workflow</p>
    </div>
    <div>
        <a href="<?= Router::url('/applicants/create') ?>" class="btn btn-primary">+ Add Application</a>
    </div>
</div>

<!-- View Segmented Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; align-items: center;">
    <a href="<?= Router::url('/applicants') ?>" class="btn btn-primary btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Active Applications
    </a>
    <a href="<?= Router::url('/applicants/history') ?>" class="btn btn-outline btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Historical Applications
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Applicant Name</th>
                    <th>Target Apartment</th>
                    <th>Rental Type</th>
                    <th style="white-space: nowrap;">Requested Term</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applicants)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 40px;">
                            No active rental applications in the pipeline.
                            <?php if (!empty($historicalApplicants)): ?>
                                <br><br>
                                <a href="<?= Router::url('/applicants/history') ?>" class="btn btn-outline btn-sm">View Historical Applications</a>
                            <?php else: ?>
                                <br><br>
                                <a href="<?= Router::url('/applicants/create') ?>" class="btn btn-primary btn-sm">Add New Application</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicants as $app): ?>
                        <tr>
                            <td>
                                <strong><a href="<?= Router::url('/applicants/' . $app['id']) ?>"><?= htmlspecialchars($app['full_name']) ?></a></strong>
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
                            <td>
                                <span class="badge badge-subtle" style="font-size: 14px; padding: 3px 10px; font-weight: 500;">
                                    <?= ucfirst(htmlspecialchars($app['tenancy_type'] ?: 'Fixed')) ?>
                                </span>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php if (!empty($app['term_start_date'])): ?>
                                    <?= date('M j, Y', strtotime($app['term_start_date'])) ?>
                                    <?php if (!empty($app['term_end_date'])): ?>
                                        <span class="text-muted" style="margin: 0 4px;">&rarr;</span>
                                        <?= date('M j, Y', strtotime($app['term_end_date'])) ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted"><?= !empty($app['date_requested']) ? 'Req: ' . date('M j, Y', strtotime($app['date_requested'])) : '—' ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <?= date('M j, Y', strtotime($app['created_at'])) ?>
                            </td>
                            <td>
                                <?php if ($app['status'] === 'approved'): ?>
                                    <span class="badge badge-success" style="font-size: 14px; padding: 3px 10px; font-weight: 500;">Approved</span>
                                <?php elseif ($app['status'] === 'lease_created'): ?>
                                    <span class="badge badge-info" style="font-size: 14px; padding: 3px 10px; font-weight: 500;">Lease Created</span>
                                <?php elseif ($app['status'] === 'lease_sent'): ?>
                                    <span class="badge badge-info" style="font-size: 14px; padding: 3px 10px; font-weight: 500;">Lease Sent</span>
                                    <?php
                                    $totApp = (int)($app['lease_total_applicants'] ?? 1);
                                    $sigApp = (int)($app['lease_signed_applicants'] ?? (!empty($app['lease_signed']) ? 1 : 0));
                                    ?>
                                    <div class="text-muted text-xs" style="margin-top: 2px;">
                                        <?= !empty($app['lease_signed']) ? 'Signed (' . $sigApp . '/' . $totApp . ')' : 'Awaiting Signature (' . $sigApp . '/' . $totApp . ')' ?>
                                    </div>
                                <?php elseif ($app['status'] === 'converted'): ?>
                                    <span class="badge badge-success" style="font-size: 14px; padding: 3px 10px; font-weight: 500;">Converted to Tenant</span>
                                <?php elseif ($app['status'] === 'rejected'): ?>
                                    <span class="badge badge-danger" style="font-size: 14px; padding: 3px 10px; font-weight: 500;">Rejected</span>
                                <?php else: ?>
                                    <span class="badge badge-warning" style="font-size: 14px; padding: 3px 10px; font-weight: 500;">Pending Review</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <a href="<?= Router::url('/applicants/' . $app['id']) ?>" 
                                       class="btn btn-outline btn-xs" 
                                       title="View Application"
                                       aria-label="View Application"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <a href="<?= Router::url('/applicants/' . $app['id'] . '/edit') ?>" 
                                       class="btn btn-outline btn-xs" 
                                       title="Edit Application"
                                       aria-label="Edit Application"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </a>
                                    <form action="<?= Router::url('/applicants/' . $app['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this application record?');" style="display:inline; margin:0;">
                                        <button type="submit" 
                                                class="btn btn-outline btn-danger btn-xs" 
                                                title="Delete Application"
                                                aria-label="Delete Application"
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
