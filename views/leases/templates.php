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
        <h1 class="page-title">Lease Agreements</h1>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="toggleUploadBox();" id="btnToggleUpload">
            Upload New Template (.docx)
        </button>
    </div>
</div>

<!-- View Segmented Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 22px; align-items: center; flex-wrap: wrap;">
    <a href="<?= Router::url('/leases') ?>" class="btn btn-outline btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        All Leases
    </a>
    <a href="<?= Router::url('/leases?tab=in_progress') ?>" class="btn btn-outline btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Workflows in Progress (<?= (int)($inProgressCount ?? 0) ?>)
    </a>
    <a href="<?= Router::url('/leases?tab=active') ?>" class="btn btn-outline btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Active Leases
    </a>
    <a href="<?= Router::url('/leases?tab=expired') ?>" class="btn btn-outline btn-sm" style="border-radius: 9999px; padding: 6px 16px;">
        Expired Leases
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
        <button type="button" class="btn btn-outline btn-xs" onclick="toggleUploadBox();">Close</button>
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
<div class="card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <div class="card-header" style="background-color: var(--table-th-dark, #d8d3c7); border-bottom: 1.5px solid var(--table-th-dark-border, #c4bcae); padding: 16px 20px;">
        <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--table-th-dark-text, var(--ws-text));">
            Lease Templates
        </h2>
    </div>

    <div class="card-body">
        <!-- Available Lease Templates Cards (Matching Workflow Style) -->
        <?php if (!empty($templates)): ?>
            <div style="margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--ws-border-subtle);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                    <span class="text-muted text-xs" style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                        Available Lease Templates:
                    </span>
                    <?php if (!empty($selectedTemplate) && !empty($selectedTemplate['placeholder_mappings'])): ?>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="badge badge-success">Mappings Saved</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px;">
                    <?php foreach ($templates as $tmpl): 
                        $isSelected = (!empty($selectedTemplate['id']) && $selectedTemplate['id'] == $tmpl['id']);
                        $tmplUrl = Router::url('/leases/templates?template_id=' . $tmpl['id'] . ($returnLeaseId > 0 ? '&lease_id=' . $returnLeaseId : ''));
                    ?>
                        <div onclick="window.location.href='<?= $tmplUrl ?>';" 
                             style="cursor: pointer; padding: 14px 16px; border-radius: 10px; border: 2px solid <?= $isSelected ? 'var(--ws-accent, #f5a623)' : 'var(--ws-border, #ebe7df)' ?>; background: <?= $isSelected ? '#fffdf7' : 'var(--ws-surface-subtle, #faf9f6)' ?>; box-shadow: <?= $isSelected ? '0 3px 12px rgba(245, 166, 35, 0.16)' : 'none' ?>; display: flex; align-items: center; justify-content: space-between; gap: 12px; transition: all 0.15s ease;">
                            <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                                <div style="width: 22px; height: 22px; border-radius: 50%; border: 2px solid <?= $isSelected ? 'var(--ws-accent, #f5a623)' : 'var(--ws-border-strong, #d8d3c7)' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #fff;">
                                    <?php if ($isSelected): ?>
                                        <div style="width: 10px; height: 10px; border-radius: 50%; background: var(--ws-accent, #f5a623);"></div>
                                    <?php endif; ?>
                                </div>
                                <div style="width: 32px; height: 40px; background: #ffffff; border: 1.5px solid <?= $isSelected ? 'var(--ws-accent, #f5a623)' : 'var(--ws-border-strong, #d8d3c7)' ?>; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); padding: 4px 3px; display: flex; flex-direction: column; justify-content: space-between; flex-shrink: 0;">
                                    <div style="display: flex; flex-direction: column; gap: 2.5px;">
                                        <div style="height: 3px; width: 60%; background: var(--ws-text, #111); border-radius: 1px;"></div>
                                        <div style="height: 2px; width: 100%; background: #e5e2da; border-radius: 1px;"></div>
                                        <div style="height: 2px; width: 80%; background: #e5e2da; border-radius: 1px;"></div>
                                        <div style="height: 2px; width: 90%; background: #e5e2da; border-radius: 1px;"></div>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                                        <span style="font-size: 6.5px; font-weight: 800; color: var(--ws-accent, #f5a623); letter-spacing: 0.2px; line-height: 1;">DOCX</span>
                                        <?php if ($isSelected): ?>
                                            <div style="width: 8px; height: 8px; border-radius: 50%; background: rgba(245, 166, 35, 0.2); border: 1px solid rgba(245, 166, 35, 0.7); display: flex; align-items: center; justify-content: center; font-size: 5.5px; color: var(--ws-accent, #f5a623); font-weight: 900; line-height: 1;">&#10003;</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="min-width: 0;">
                                    <div style="font-weight: 700; font-size: 14px; color: var(--ws-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($tmpl['name']) ?>
                                    </div>
                                    <div class="text-muted text-xs" style="margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($tmpl['original_name']) ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($isSelected): ?>
                                <form action="<?= Router::url('/leases/templates/' . $tmpl['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this template from the library?');" style="display:inline; margin:0;" onclick="event.stopPropagation();">
                                    <button type="submit" class="btn btn-outline btn-danger btn-xs" style="padding: 3px 10px; font-size: 11px; font-weight: 700; border-radius: 6px; flex-shrink: 0;" title="Delete Template">
                                        Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
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

                <div class="table-responsive" style="margin-bottom: 20px;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border, #e5e7eb); text-align: left; font-size: 12px; text-transform: uppercase; color: var(--text-muted);">
                                <th style="padding: 10px 12px; width: 38%;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span>Placeholder In Document</span>
                                        <span class="badge badge-info" style="font-size: 11px; font-weight: 700; text-transform: none; letter-spacing: normal;">
                                            <?= count($placeholders) ?> Bracket Placeholder<?= count($placeholders) > 1 ? 's' : '' ?> Found
                                        </span>
                                    </div>
                                </th>
                                <th style="padding: 10px 12px; width: 36%;">Data Source Field</th>
                                <th style="padding: 10px 12px; width: 26%;">Manual / Override Value</th>
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

                <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
                    <button type="submit" class="btn btn-primary">
                        Save Placeholder Mapping
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
        btn.textContent = 'Upload New Template (.docx)';
    }
}
</script>
