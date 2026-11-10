<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/properties/' . $property['id']) ?>">&larr; Back to <?= htmlspecialchars($property['name']) ?></a>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Add Unit to <?= htmlspecialchars($property['name']) ?></h2>
    </div>
    <div class="card-body">
        <form action="<?= Router::url('/units') ?>" method="POST">
            <input type="hidden" name="property_id" value="<?= $property['id'] ?>">

            <?php if ($property['property_type'] === 'single_family'): ?>
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="unit_number">Unit Type *</label>
                    <select id="unit_number" name="unit_number" class="form-control" required>
                        <option value="">-- Select Unit Type --</option>
                        <option value="Main Floor">Main Floor</option>
                        <option value="Basement">Basement</option>
                        <option value="Garage Suite">Garage Suite</option>
                        <option value="House">House</option>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="floor">Floor Level</label>
                    <input type="number" id="floor" name="floor" class="form-control" value="1" min="-2" max="100">
                </div>
            </div>
            <?php else: ?>
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="unit_number">Unit / Suite Number *</label>
                    <input type="text" id="unit_number" name="unit_number" class="form-control" placeholder="e.g. 101, 2B, Penthouse" required>
                </div>
                <div class="form-group col-6">
                    <label for="floor">Floor Level</label>
                    <input type="number" id="floor" name="floor" class="form-control" value="1" min="-2" max="100">
                </div>
            </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group col-4">
                    <label for="bedrooms">Bedrooms</label>
                    <input type="number" id="bedrooms" name="bedrooms" class="form-control" value="1" min="0" max="20">
                </div>
                <div class="form-group col-4">
                    <label for="bathrooms">Bathrooms</label>
                    <input type="number" id="bathrooms" name="bathrooms" class="form-control" value="1.0" step="0.5" min="0.5" max="20">
                </div>
                <div class="form-group col-4">
                    <label for="square_feet">Square Footage</label>
                    <input type="number" id="square_feet" name="square_feet" class="form-control" placeholder="e.g. 850">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="target_rent">Monthly Rent ($) *</label>
                    <input type="number" id="target_rent" name="target_rent" class="form-control" step="0.01" placeholder="2200.00" required>
                </div>
                <div class="form-group col-6">
                    <label for="security_deposit">Security Deposit ($)</label>
                    <input type="number" id="security_deposit" name="security_deposit" class="form-control" step="0.01" placeholder="2200.00">
                </div>
            </div>

            <div class="form-group">
                <label for="status">Initial Occupancy Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="vacant" selected>Vacant (Available for Lease)</option>
                    <option value="maintenance">Under Maintenance / Turnaround</option>
                    <option value="occupied">Occupied</option>
                </select>
            </div>

            <?php
            $availableAppliances = [
                'fridge'     => 'Fridge',
                'stove'      => 'Stove',
                'microwave'  => 'Microwave',
                'dishwasher' => 'Dishwasher',
                'washer'     => 'Washer',
                'dryer'      => 'Dryer'
            ];
            ?>
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px;">Available Appliances</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; background: var(--ws-surface-subtle); padding: 14px 16px; border-radius: 12px; border: 1px solid var(--ws-border-subtle);">
                    <?php foreach ($availableAppliances as $key => $label): ?>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 500; color: var(--ws-text); margin: 0;">
                            <input type="checkbox" name="appliances[]" value="<?= $key ?>" style="width: 16px; height: 16px; accent-color: var(--ws-accent); cursor: pointer;">
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Unit Description</label>
                <textarea id="notes" name="notes" class="form-control auto-expand" rows="1" oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px';" style="overflow: hidden; resize: none; line-height: 1.6;" placeholder="Washer/dryer hookups, southern exposure, carpet condition..."></textarea>
            </div>

            <div class="form-actions">
                <a href="<?= Router::url('/properties/' . $property['id']) ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Unit</button>
            </div>
        </form>
    </div>
</div>
