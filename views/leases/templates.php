<?php
use App\Core\Router;
use App\Core\AppDate;

$returnLeaseId = !empty($_GET['lease_id']) ? (int)$_GET['lease_id'] : 0;
$todayFormatted = AppDate::today()->format('F j, Y');
$todayDay = AppDate::today()->format('j');
?>

<?php if ($returnLeaseId > 0): ?>
    <div class="breadcrumb">
        <a href="<?= Router::url('/leases/' . $returnLeaseId) ?>">&larr; Back to Lease #<?= $returnLeaseId ?></a>
    </div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Lease Templates</h1>
        <p class="page-subtitle">Upload Word (.docx) templates and map <code>[ ]</code> bracket placeholders to Application, Unit, Property, and Calendar data</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="toggleUploadBox();" id="btnToggleUpload">
            + Upload New Template (.docx)
        </button>
    </div>
</div>

<!-- View Segmented Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; align-items: center;">
    <a href="<?= Router::url('/leases') ?>" class="btn btn-outline btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Lease Agreements
    </a>
    <a href="<?= Router::url('/leases/templates') ?>" class="btn btn-primary btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Lease Templates
    </a>
</div>

<!-- Collapsible Upload Box -->
<div id="uploadTemplateBox" class="card" style="display: none; margin-bottom: 24px; border: 1px dashed var(--primary, #2563eb);">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 class="card-title" style="margin: 0;">Upload New Lease Agreement Template (.docx)</h2>
            <p style="margin: 4px 0 0; font-size: 13px; color: var(--text-muted);">
                Placeholders in your Word document should be enclosed in <code>[ ]</code> brackets (e.g., <code>[LANDLORD NAME / COMPANY]</code>, <code>[Tenant Full Name 1]</code>, <code>[AGREEMENT DATE, Day]</code>).
            </p>
        </div>
        <button type="button" class="btn btn-outline btn-xs" onclick="toggleUploadBox();">&times; Close</button>
    </div>
    <div class="card-body">
        <form action="<?= Router::url('/leases/templates/upload') ?>" method="POST" enctype="multipart/form-data">
            <?php if ($returnLeaseId > 0): ?>
                <input type="hidden" name="lease_id" value="<?= $returnLeaseId ?>">
            <?php endif; ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 16px;">
                <div>
                    <label class="form-label" style="font-size: 12px; font-weight: 600;">Template Display Name</label>
                    <input type="text" name="template_name" class="form-control" placeholder="e.g. Standard Residential Tenancy Agreement">
                </div>
                <div>
                    <label class="form-label" style="font-size: 12px; font-weight: 600;">Select .docx File <span class="text-danger">*</span></label>
                    <input type="file" name="template_file" class="form-control" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label class="form-label" style="font-size: 12px; font-weight: 600;">Description / Notes (Optional)</label>
                <input type="text" name="template_description" class="form-control" placeholder="e.g. Standard residential lease with bracketed placeholders for landlord, co-applicants, unit, and rent.">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-outline" onclick="toggleUploadBox();">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload &amp; Extract Placeholders</button>
            </div>
        </form>
    </div>
</div>

<!-- Main Template Mapping Engine Card -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 280px;">
            <label style="font-weight: 600; font-size: 14px; white-space: nowrap;">Select Template:</label>
            <form method="GET" action="<?= Router::url('/leases/templates') ?>" id="formSelectTemplate" style="display: flex; gap: 8px; flex: 1; margin: 0;">
                <?php if ($returnLeaseId > 0): ?>
                    <input type="hidden" name="lease_id" value="<?= $returnLeaseId ?>">
                <?php endif; ?>
                <select name="template_id" class="form-control" onchange="document.getElementById('formSelectTemplate').submit();" style="max-width: 460px;">
                    <?php if (empty($templates)): ?>
                        <option value="">No templates uploaded yet</option>
                    <?php else: ?>
                        <?php foreach ($templates as $tmpl): ?>
                            <option value="<?= $tmpl['id'] ?>" <?= (!empty($selectedTemplate['id']) && $selectedTemplate['id'] == $tmpl['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tmpl['name']) ?> (<?= htmlspecialchars($tmpl['original_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>
        </div>

        <?php if (!empty($selectedTemplate)): ?>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="badge badge-subtle">
                    <?= round(($selectedTemplate['file_size'] ?? 0) / 1024, 1) ?> KB &bull; <?= htmlspecialchars($selectedTemplate['original_name']) ?>
                </span>
                <?php if (!empty($selectedTemplate['placeholder_mappings'])): ?>
                    <span class="badge badge-success">Mappings Saved</span>
                <?php endif; ?>
                <form action="<?= Router::url('/leases/templates/' . $selectedTemplate['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this template from the library?');" style="display:inline; margin:0;">
                    <button type="submit" class="btn btn-outline btn-danger btn-xs" title="Delete Template">&times; Delete Template</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <?php if (!empty($templateError)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <?= htmlspecialchars($templateError) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($selectedTemplate)): ?>
            <div class="text-center text-muted" style="padding: 40px 20px;">
                <p style="font-size: 15px; margin-bottom: 12px;">No lease template available.</p>
                <button type="button" class="btn btn-primary" onclick="toggleUploadBox();">Upload Your First .docx Template</button>
            </div>
        <?php elseif (empty($placeholders)): ?>
            <div style="background: var(--bg-subtle, #f9fafb); border-radius: 8px; padding: 28px; text-align: center; color: var(--text-muted);">
                <p style="margin: 0 0 8px; font-weight: 600; color: var(--text-main, #111827);">No <code>[ ]</code> bracket placeholders found in this template.</p>
                <p style="margin: 0; font-size: 13px;">
                    Place any dynamic variables inside square brackets in your Word document (for example: <code>[LANDLORD NAME / COMPANY]</code>, <code>[Tenant Full Name 1]</code>, <code>[AGREEMENT DATE, Day]</code>) and upload the updated <code>.docx</code> file.
                </p>
            </div>
        <?php else: ?>
            <form action="<?= Router::url('/leases/templates/' . $selectedTemplate['id'] . '/save-mapping') ?>" method="POST">
                <?php if ($returnLeaseId > 0): ?>
                    <input type="hidden" name="lease_id" value="<?= $returnLeaseId ?>">
                <?php endif; ?>

                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <span class="badge badge-info" style="font-size: 12px;">
                            <?= count($placeholders) ?> Bracket Placeholder<?= count($placeholders) > 1 ? 's' : '' ?> Found
                        </span>
                        <span style="font-size: 13px; color: var(--text-muted); margin-left: 8px;">
                            Map each <code>[ ]</code> placeholder on the left to a data field on the right. Any value entered in <strong>Manual / Override Value</strong> will take precedence and override the source field. Calendar Day currently resolves to <strong><?= htmlspecialchars($todayDay) ?></strong> (<?= htmlspecialchars($todayFormatted) ?>).
                        </span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        💾 Save Placeholder Mapping
                    </button>
                </div>

                <div class="table-responsive" style="margin-bottom: 20px;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border, #e5e7eb); text-align: left; font-size: 12px; text-transform: uppercase; color: var(--text-muted);">
                                <th style="padding: 10px 12px; width: 38%;">Placeholder in DOCX Template (Left)</th>
                                <th style="padding: 10px 12px; width: 36%;">Data Source Field (Application / Unit / Property / Calendar)</th>
                                <th style="padding: 10px 12px; width: 26%;">Manual / Override Value (Overrides Source)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($placeholders as $phIdx => $ph): ?>
                                <tr style="border-bottom: 1px solid var(--border, #f3f4f6);">
                                    <td style="padding: 12px; vertical-align: middle;">
                                        <input type="hidden" name="placeholder_keys[<?= $phIdx ?>]" value="<?= htmlspecialchars($ph['original_text']) ?>">
                                        <div style="font-family: monospace; font-weight: 700; color: #dc2626; font-size: 13px; margin-bottom: 4px;">
                                            <?= htmlspecialchars($ph['original_text']) ?>
                                            <?php if ($ph['count'] > 1): ?>
                                                <span class="badge badge-subtle" style="font-size: 10px; margin-left: 4px;">&times;<?= $ph['count'] ?> occurrences</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 11px; color: var(--text-muted); font-style: italic; line-height: 1.4;">
                                            <?= htmlspecialchars($ph['context']) ?>
                                        </div>
                                    </td>
                                    <td style="padding: 12px; vertical-align: middle;">
                                        <select name="mapped_fields[<?= $phIdx ?>]" class="form-control" style="width: 100%; font-size: 13px;">
                                            <?php foreach ($fieldCatalog as $groupName => $fields): ?>
                                                <optgroup label="<?= htmlspecialchars($groupName) ?>">
                                                    <?php foreach ($fields as $fieldKey => $fieldLabel): ?>
                                                        <option value="<?= htmlspecialchars($fieldKey) ?>" <?= ($ph['mapped_field'] === $fieldKey) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($fieldLabel) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td style="padding: 12px; vertical-align: middle;">
                                        <input type="text"
                                               name="manual_values[<?= $phIdx ?>]"
                                               value="<?= htmlspecialchars($ph['manual_value']) ?>"
                                               class="form-control"
                                               placeholder="Optional override value (takes priority)..."
                                               style="width: 100%; font-size: 13px;">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="background: var(--bg-subtle, #f9fafb); border: 1px solid var(--border, #e5e7eb); border-radius: 8px; padding: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <span style="font-size: 13px; color: var(--text-muted);">
                        Once saved, selecting this template in any Lease Agreement view will automatically extract the mapped fields from the applicant's application, unit, and property.
                    </span>
                    <button type="submit" class="btn btn-primary">
                        💾 Save Placeholder Mapping
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleUploadBox() {
    var box = document.getElementById('uploadTemplateBox');
    var btn = document.getElementById('btnToggleUpload');
    if (box.style.display === 'none' || box.style.display === '') {
        box.style.display = 'block';
        btn.textContent = 'Hide Upload Box';
    } else {
        box.style.display = 'none';
        btn.textContent = '+ Upload New Template (.docx)';
    }
}
</script>
