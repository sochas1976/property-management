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

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="status">Initial Occupancy Status</label>
                    <select id="status" name="status" class="form-control" onchange="onUnitStatusChange(this.value)">
                        <option value="vacant" selected>Vacant (Available for Lease)</option>
                        <option value="maintenance">Under Maintenance / Turnaround</option>
                        <option value="occupied">Occupied</option>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="available_date">Available Date</label>
                    <input type="date" id="available_date" name="available_date" class="form-control" value="<?= \App\Core\AppDate::todayString() ?>">
                    <small class="text-muted" id="available_date_hint" style="display: block; margin-top: 4px; font-size: 12px;">Available immediately (effective current date)</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="adults_only">Adults Only</label>
                    <select id="adults_only" name="adults_only" class="form-control">
                        <option value="no" selected>No</option>
                        <option value="yes">Yes</option>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="furnished">Furnished</label>
                    <select id="furnished" name="furnished" class="form-control">
                        <option value="no" selected>No</option>
                        <option value="yes">Yes</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-4">
                    <label for="pets">Pets</label>
                    <select id="pets" name="pets" class="form-control">
                        <option value="not_allowed" selected>Not Allowed</option>
                        <option value="allowed">Allowed</option>
                    </select>
                </div>
                <div class="form-group col-4">
                    <label for="smoking">Smoking</label>
                    <select id="smoking" name="smoking" class="form-control">
                        <option value="not_allowed" selected>Not Allowed</option>
                        <option value="allowed">Allowed</option>
                    </select>
                </div>
                <div class="form-group col-4">
                    <label for="parking">Parking</label>
                    <select id="parking" name="parking" class="form-control">
                        <option value="street" selected>Street</option>
                        <option value="driveway">Driveway</option>
                        <option value="garage">Garage</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="tenant_insurance">Tenant Insurance</label>
                <select id="tenant_insurance" name="tenant_insurance" class="form-control">
                    <option value="not_required" selected>Not Required</option>
                    <option value="required">Required</option>
                </select>
            </div>

            <?php
            $utilityTypes = [
                'electricity' => ['label' => 'Electricity'],
                'heat'        => ['label' => 'Heat'],
                'water'       => ['label' => 'Water'],
                'internet'    => ['label' => 'Internet']
            ];
            ?>
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Utilities Coverage & Responsibility</label>

                <div style="background: var(--ws-surface-subtle); padding: 16px 18px; border-radius: 12px; border: 1px solid var(--ws-border-subtle);">
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <?php foreach ($utilityTypes as $uKey => $uInfo): ?>
                            <div class="utility-row" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--ws-border-subtle);">
                                <div style="display: flex; align-items: center; min-width: 140px;">
                                    <strong style="font-size: 14.5px;"><?= $uInfo['label'] ?></strong>
                                </div>

                                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                    <select class="form-control" style="width: auto; min-width: 180px; padding: 6px 12px; font-size: 13.5px;" onchange="onUtilityModeChange('<?= $uKey ?>', this.value)">
                                        <option value="not_included" selected>Not Included (Tenant 100%)</option>
                                        <option value="included">Included (100% Covered)</option>
                                        <option value="shared">Shared Percentage Split</option>
                                    </select>

                                    <input type="checkbox" id="util_inc_<?= $uKey ?>" name="utilities[included][]" value="<?= $uKey ?>" style="display: none;">

                                    <div id="util_split_box_<?= $uKey ?>" style="display: none; align-items: center; gap: 6px;">
                                        <span class="text-muted text-xs">Tenant pays:</span>
                                        <input type="number" 
                                               id="util_pct_<?= $uKey ?>" 
                                               name="utilities[shared_pct][<?= $uKey ?>]" 
                                               class="form-control" 
                                               style="width: 75px; padding: 6px 8px; text-align: center; font-weight: 700;" 
                                               min="1" max="99" step="1" 
                                               value="60"
                                               placeholder="60"
                                               disabled>
                                        <span style="font-weight: 700; font-size: 13px;">%</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
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

<script>
function onUnitStatusChange(statusVal) {
    const availInput = document.getElementById('available_date');
    const hint = document.getElementById('available_date_hint');
    if (!availInput) return;

    const todayVal = '<?= \App\Core\AppDate::todayString() ?>';
    availInput.value = todayVal;
    if (hint) {
        if (statusVal === 'occupied') {
            hint.innerHTML = 'Occupied';
        } else {
            hint.innerHTML = 'Available immediately (effective current date)';
        }
    }
}

function onUtilityModeChange(uKey, mode) {
    const incCheckbox = document.getElementById('util_inc_' + uKey);
    const splitBox = document.getElementById('util_split_box_' + uKey);
    const pctInput = document.getElementById('util_pct_' + uKey);

    if (mode === 'included') {
        if (incCheckbox) incCheckbox.checked = true;
        if (splitBox) splitBox.style.display = 'none';
        if (pctInput) pctInput.disabled = true;
    } else if (mode === 'shared') {
        if (incCheckbox) incCheckbox.checked = false;
        if (splitBox) splitBox.style.display = 'flex';
        if (pctInput) {
            pctInput.disabled = false;
            if (!pctInput.value) pctInput.value = '60';
        }
    } else { // not_included
        if (incCheckbox) incCheckbox.checked = false;
        if (splitBox) splitBox.style.display = 'none';
        if (pctInput) pctInput.disabled = true;
    }
}
</script>
