<?php
use App\Core\Router;
use App\Core\AppDate;

// Calculate days remaining on the lease agreement
$today = AppDate::today();
$endDate = !empty($lease['end_date']) ? new DateTime($lease['end_date']) : null;
$daysLeft = null;
if ($endDate) {
    $diff = $today->diff($endDate);
    $daysLeft = $diff->invert ? -$diff->days : $diff->days;
}

// Expiring soon criteria: 45 days or less left on the lease agreement
$isExpiringSoon = ($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 45);

// Effective status for display (if active and within 45 days, or explicitly marked expiring_soon)
$effStatus = $lease['status'];
if ($effStatus === 'active' && $isExpiringSoon) {
    $effStatus = 'expiring_soon';
}

$statusBadgeMap = [
    'active'        => ['class' => 'badge-success', 'label' => 'ACTIVE LEASE'],
    'expiring_soon' => ['class' => 'badge-warning', 'label' => 'EXPIRING SOON' . ($daysLeft !== null ? ' (' . ($daysLeft === 0 ? 'TODAY' : ($daysLeft === 1 ? '1 DAY LEFT' : $daysLeft . ' DAYS LEFT')) . ')' : '')],
    'draft'         => ['class' => 'badge-info', 'label' => 'DRAFT LEASE'],
    'expired'       => ['class' => 'badge-warning', 'label' => 'EXPIRED LEASE'],
    'terminated'    => ['class' => 'badge-danger', 'label' => 'TERMINATED LEASE'],
];
$badge = $statusBadgeMap[$effStatus] ?? ['class' => 'badge-subtle', 'label' => strtoupper(str_replace('_', ' ', $effStatus)) . ' LEASE'];

$from = $_GET['from'] ?? '';
$applicantId = !empty($_GET['applicant_id']) ? (int)$_GET['applicant_id'] : 0;
if (empty($from) && !empty($_SERVER['HTTP_REFERER'])) {
    if (strpos($_SERVER['HTTP_REFERER'], '/applicants/history') !== false) {
        $from = 'history';
    } elseif (strpos($_SERVER['HTTP_REFERER'], '/applicants/') !== false) {
        $from = 'applicant';
        if (preg_match('/\/applicants\/(\d+)/', $_SERVER['HTTP_REFERER'], $m)) {
            $applicantId = (int)$m[1];
        }
    }
}
$fromHistory = ($from === 'history');
$fromApplicant = ($from === 'applicant');

// Filter placeholders that require manual entry because data was not found in the application/unit/property
$missingPlaceholders = [];
if (!empty($placeholders)) {
    foreach ($placeholders as $ph) {
        if (!empty($ph['is_missing'])) {
            $missingPlaceholders[] = $ph;
        }
    }
}
?>

<div class="breadcrumb">
    <?php if ($fromApplicant && $applicantId > 0): ?>
        <a href="<?= Router::url('/applicants/' . $applicantId) ?>">&larr; Back to Application &amp; Lease Workflow</a>
    <?php elseif ($fromHistory): ?>
        <a href="<?= Router::url('/applicants/history') ?>">&larr; Back to Historical Applications</a>
    <?php else: ?>
        <a href="<?= Router::url('/leases') ?>">&larr; Back to Leases</a>
    <?php endif; ?>
</div>

<div class="page-header">
    <div>
        <div class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></div>
        <h1 class="page-title" style="margin-top: 5px;">Lease #<?= $lease['id'] ?> — <?= htmlspecialchars($lease['property_name']) ?> (Unit <?= htmlspecialchars($lease['unit_number']) ?>)</h1>
        <p class="page-subtitle">
            <?= !empty($lease['tenant_id']) ? 'Resident: ' : 'Prospective Applicant(s): ' ?>
            <?= htmlspecialchars($lease['tenant_name'] ?: 'Unassigned') ?>
            <?= !empty($lease['tenant_email']) ? ' &bull; ' . htmlspecialchars($lease['tenant_email']) : '' ?>
        </p>
    </div>

    <div class="action-buttons">
        <form action="<?= Router::url('/leases/' . $lease['id'] . '/status') ?>" method="POST" style="display: flex; gap: 8px;">
            <?php if ($fromHistory): ?>
                <input type="hidden" name="from" value="history">
            <?php elseif ($fromApplicant && $applicantId > 0): ?>
                <input type="hidden" name="from" value="applicant">
                <input type="hidden" name="applicant_id" value="<?= $applicantId ?>">
            <?php endif; ?>
            <select name="status" class="form-control" style="width: auto;">
                <option value="active" <?= $lease['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="draft" <?= $lease['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                <?php if ($isExpiringSoon || $lease['status'] === 'expiring_soon'): ?>
                    <option value="expiring_soon" <?= ($lease['status'] === 'expiring_soon' || ($lease['status'] === 'active' && $isExpiringSoon)) ? 'selected' : '' ?>>Expiring Soon</option>
                <?php endif; ?>
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
                <span>
                    <?= htmlspecialchars($lease['start_date']) ?> to <?= htmlspecialchars($lease['end_date']) ?>
                    <?php if ($isExpiringSoon): ?>
                        <span class="badge badge-warning" style="margin-left: 6px; font-size: 11px;">
                            <?= $daysLeft === 0 ? 'Expires Today' : ($daysLeft === 1 ? '1 day left' : $daysLeft . ' days left') ?>
                        </span>
                    <?php endif; ?>
                </span>
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
                                <?php if (strpos($doc['mime_type'], 'pdf') !== false): ?>
                                    📄
                                <?php elseif (strpos($doc['mime_type'], 'word') !== false || strpos($doc['original_name'], '.docx') !== false): ?>
                                    📝
                                <?php else: ?>
                                    🖼️
                                <?php endif; ?>
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

<!-- Streamlined Lease Document Generation from Configured Template -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 class="card-title" style="margin: 0;">Generate Lease Agreement from Template (.docx)</h2>
            <p style="margin: 4px 0 0; font-size: 13px; color: var(--text-muted);">
                Pick a lease template below. Mapped fields are automatically extracted from the applicant's application, unit, and property data.
            </p>
        </div>
        <a href="<?= Router::url('/leases/templates?lease_id=' . $lease['id'] . (!empty($selectedTemplate['id']) ? '&template_id=' . $selectedTemplate['id'] : '')) ?>" class="btn btn-outline btn-sm">
            ⚙️ Configure Templates &amp; Mappings
        </a>
    </div>

    <div class="card-body">
        <?php if (!empty($templateError)): ?>
            <div class="alert alert-danger" style="margin-bottom: 16px;">
                <?= htmlspecialchars($templateError) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($templates)): ?>
            <div class="text-center text-muted" style="padding: 30px;">
                <p>No lease templates have been uploaded yet.</p>
                <a href="<?= Router::url('/leases/templates?lease_id=' . $lease['id']) ?>" class="btn btn-primary btn-sm">Upload &amp; Configure Template</a>
            </div>
        <?php else: ?>
            <!-- Template Picker -->
            <form method="GET" action="<?= Router::url('/leases/' . $lease['id']) ?>" id="formPickTemplate" style="margin-bottom: 20px;">
                <?php if ($fromApplicant && $applicantId > 0): ?>
                    <input type="hidden" name="from" value="applicant">
                    <input type="hidden" name="applicant_id" value="<?= $applicantId ?>">
                <?php elseif ($fromHistory): ?>
                    <input type="hidden" name="from" value="history">
                <?php endif; ?>
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <label style="font-weight: 600; font-size: 14px; white-space: nowrap;">Select Lease Template:</label>
                    <select name="template_id" class="form-control" onchange="document.getElementById('formPickTemplate').submit();" style="max-width: 460px;">
                        <?php foreach ($templates as $tmpl): ?>
                            <option value="<?= $tmpl['id'] ?>" <?= (!empty($selectedTemplate['id']) && $selectedTemplate['id'] == $tmpl['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tmpl['name']) ?> (<?= htmlspecialchars($tmpl['original_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="badge badge-subtle">
                        <?= count($placeholders) ?> mapped placeholder<?= count($placeholders) === 1 ? '' : 's' ?>
                    </span>
                </div>
            </form>

            <?php if (!empty($selectedTemplate)): ?>
                <form action="<?= Router::url('/leases/' . $lease['id'] . '/generate-document') ?>" method="POST">
                    <input type="hidden" name="template_id" value="<?= $selectedTemplate['id'] ?>">
                    <input type="hidden" name="remove_red_color" value="1">
                    <?php if ($fromApplicant && $applicantId > 0): ?>
                        <input type="hidden" name="from" value="applicant">
                        <input type="hidden" name="applicant_id" value="<?= $applicantId ?>">
                    <?php elseif ($fromHistory): ?>
                        <input type="hidden" name="from" value="history">
                    <?php endif; ?>

                    <?php if (!empty($missingPlaceholders)): ?>
                        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                            <div style="font-weight: 600; font-size: 13px; color: #92400e; margin-bottom: 10px;">
                                The following placeholder(s) were not found in the application/unit/property data. Please fill them in manually if needed:
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
                                <?php foreach ($missingPlaceholders as $mIdx => $mph): ?>
                                    <div>
                                        <label class="form-label" style="font-size: 12px; font-weight: 600; margin-bottom: 4px; display: block;">
                                            <span style="font-family: monospace; color: #dc2626;"><?= htmlspecialchars($mph['original_text']) ?></span>
                                            <span class="text-muted" style="font-weight: 400;">(<?= htmlspecialchars($mph['field_label']) ?>)</span>
                                        </label>
                                        <input type="hidden" name="placeholder_keys[<?= $mIdx ?>]" value="<?= htmlspecialchars($mph['original_text']) ?>">
                                        <input type="text" name="placeholder_values[<?= $mIdx ?>]" value="" class="form-control" placeholder="Enter value manually...">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div style="background: var(--bg-subtle, #f9fafb); border: 1px solid var(--border, #e5e7eb); border-radius: 8px; padding: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <div style="font-size: 13px; color: var(--text-muted);">
                            Ready to generate lease agreement for <strong><?= htmlspecialchars($lease['tenant_name'] ?: 'Prospective Applicant(s)') ?></strong> at <strong><?= htmlspecialchars($lease['property_name']) ?> (Unit <?= htmlspecialchars($lease['unit_number']) ?>)</strong>.
                        </div>
                        <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 6px;">
                            <span>📥</span> Generate &amp; Download Lease Agreement (.docx)
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
