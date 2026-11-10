<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/maintenance') ?>">&larr; Back to Maintenance</a>
</div>

<div class="card" style="max-width: 750px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Submit Maintenance Ticket</h2>
        <p class="text-muted text-xs">Record property defects, repair needs, and attach local photo evidence</p>
    </div>
    <div class="card-body">
        <form action="<?= Router::url('/maintenance') ?>" method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="unit_id">Impacted Unit *</label>
                    <select id="unit_id" name="unit_id" class="form-control" required>
                        <option value="">-- Select Property & Unit --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= htmlspecialchars($u['property_name']) ?> &bull; Unit <?= htmlspecialchars($u['unit_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="tenant_id">Reported By Resident</label>
                    <select id="tenant_id" name="tenant_id" class="form-control">
                        <option value="">-- In-House Staff / Not Tenant --</option>
                        <?php foreach ($tenants as $t): ?>
                            <option value="<?= $t['id'] ?>">
                                <?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="category">Issue Category</label>
                    <select id="category" name="category" class="form-control">
                        <option value="plumbing">Plumbing & Drains</option>
                        <option value="electrical">Electrical & Lighting</option>
                        <option value="hvac">HVAC & Heating/Air Conditioning</option>
                        <option value="appliance">Kitchen / Laundry Appliances</option>
                        <option value="structural">Doors, Windows & Structural</option>
                        <option value="pest_control">Pest Control</option>
                        <option value="other" selected>General / Other</option>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="priority">Priority Level</label>
                    <select id="priority" name="priority" class="form-control">
                        <option value="low">Low (Standard routine maintenance)</option>
                        <option value="medium" selected>Medium (Fix within 48-72 hours)</option>
                        <option value="high">High (Urgent tenant comfort)</option>
                        <option value="emergency">Emergency (Active water leak, gas, electrical hazard)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="title">Ticket Subject / Summary *</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Master bathroom faucet won't stop leaking" required>
            </div>

            <div class="form-group">
                <label for="description">Detailed Description *</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Describe what happened, where the issue is located, and any troubleshooting already performed..." required></textarea>
            </div>

            <div class="form-group">
                <label for="photo">
                    Attach Photo / Invoice (JPG, PNG, WebP, or PDF)
                    <span class="badge badge-subtle">Saved to Local Vault</span>
                </label>
                <input type="file" id="photo" name="photo" class="form-control" accept="image/*,.pdf">
                <small class="text-muted">Files are stored in protected local directory <code>/storage/photos/maintenance/</code>.</small>
            </div>

            <div class="form-group">
                <label for="assigned_to">Assign Contractor / Technician (Optional)</label>
                <input type="text" id="assigned_to" name="assigned_to" class="form-control" placeholder="e.g. QuickFix Plumbing Inc.">
            </div>

            <div class="form-actions">
                <a href="<?= Router::url('/maintenance') ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Submit Ticket</button>
            </div>
        </form>
    </div>
</div>
