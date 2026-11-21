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
        <div style="padding-bottom: 20px; margin-bottom: 22px; border-bottom: 1.5px solid var(--ws-border, #ebe7df);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 14px 16px; border-radius: 12px; background: var(--ws-surface-subtle, #faf9f6); border: 1.5px solid var(--ws-border, #ebe7df);">
                <div style="display: flex; align-items: center; gap: 14px; min-width: 0; flex: 1;">
                    <div class="property-card-photo" style="flex-shrink: 0;">
                        <?php if (!empty($property['photo_path'])): ?>
                            <img src="<?= Router::url($property['photo_path']) ?>" alt="<?= htmlspecialchars($property['name']) ?>" class="property-thumbnail" style="width: 68px; height: 68px; border-radius: 10px; border: 1.5px solid var(--ws-border-strong, #d8d3c7); object-fit: cover; display: block;">
                        <?php else: ?>
                            <div class="property-thumbnail-placeholder" style="width: 68px; height: 68px; border-radius: 10px;">📷</div>
                        <?php endif; ?>
                    </div>
                    <div style="min-width: 0;">
                        <div class="property-type-tag" style="margin-bottom: 2px; font-size: 10.5px;"><?= strtoupper(str_replace('_', ' ', $property['property_type'])) ?></div>
                        <div style="font-size: 15.5px; font-weight: 700; color: var(--ws-text); line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($property['name']) ?></div>
                        <div style="font-size: 13px; color: var(--ws-text-muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?= htmlspecialchars($property['address_line1']) ?><?= !empty($property['address_line2']) ? ', ' . htmlspecialchars($property['address_line2']) : '' ?>,
                            <?= htmlspecialchars($property['city']) ?>, <?= htmlspecialchars($property['state']) ?> <?= htmlspecialchars($property['zip_code']) ?>
                        </div>
                    </div>
                </div>
                <span class="badge badge-subtle" style="flex-shrink: 0;">New Unit</span>
            </div>
        </div>

        <form action="<?= Router::url('/units') ?>" method="POST" enctype="multipart/form-data">
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

            <div class="form-group">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <label style="margin: 0; font-size: 14.5px; font-weight: 600;">Unit Pictures</label>
                    <label for="unit_photos_picker" title="Add Pictures" style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: transparent; border: 2px solid var(--ws-accent); color: var(--ws-accent); cursor: pointer; transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease; padding: 0;"
                           onmouseover="this.style.transform='scale(1.12)'; this.style.background='var(--ws-accent-subtle)';"
                           onmouseout="this.style.transform='scale(1)'; this.style.background='transparent';">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </label>
                </div>
                <input type="file" id="unit_photos_picker" multiple accept="image/jpeg,image/png,image/webp,image/gif" onchange="addStagedUnitPhotos(this)" style="display: none;">
                <input type="file" id="unit_photos" name="unit_photos[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" style="display: none;">

                <div id="unit-photos-grid" style="display: none; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 14px; margin-bottom: 16px;"></div>
            </div>

            <div class="form-actions">
                <a href="<?= Router::url('/properties/' . $property['id']) ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Unit</button>
            </div>
        </form>
    </div>
</div>

<!-- Lightbox Modal for Unit Photo Preview -->
<div id="unit-photo-modal" class="modal-backdrop" onclick="closeUnitPhotoModal(event)">
    <div style="position: relative; max-width: 90vw; max-height: 90vh; display: flex; align-items: center; justify-content: center;">
        <button type="button" onclick="document.getElementById('unit-photo-modal').classList.remove('active')" 
                style="position: absolute; top: -45px; right: 0; background: none; border: none; color: #fff; font-size: 34px; cursor: pointer; line-height: 1;">&times;</button>
        
        <!-- Previous Arrow -->
        <button type="button" onclick="navigatePhotoModal(-1, event)" 
                title="Previous photo"
                style="position: absolute; left: -50px; background: rgba(0,0,0,0.6); border: 1.5px solid rgba(255,255,255,0.3); color: #fff; width: 44px; height: 44px; border-radius: 50%; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s ease, transform 0.15s ease; z-index: 10;"
                onmouseover="this.style.background='var(--ws-accent)'; this.style.transform='scale(1.08)';"
                onmouseout="this.style.background='rgba(0,0,0,0.6)'; this.style.transform='scale(1)';">
            &#10094;
        </button>

        <img id="modal-photo-img" src="" alt="Unit Photo Preview" style="max-width: 82vw; max-height: 82vh; border-radius: 14px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); object-fit: contain; transition: opacity 0.15s ease;">

        <!-- Next Arrow -->
        <button type="button" onclick="navigatePhotoModal(1, event)" 
                title="Next photo"
                style="position: absolute; right: -50px; background: rgba(0,0,0,0.6); border: 1.5px solid rgba(255,255,255,0.3); color: #fff; width: 44px; height: 44px; border-radius: 50%; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s ease, transform 0.15s ease; z-index: 10;"
                onmouseover="this.style.background='var(--ws-accent)'; this.style.transform='scale(1.08)';"
                onmouseout="this.style.background='rgba(0,0,0,0.6)'; this.style.transform='scale(1)';">
            &#10095;
        </button>

        <!-- Photo Counter Badge -->
        <div id="modal-photo-counter" style="position: absolute; bottom: -36px; color: #fff; font-size: 13px; font-weight: 500; background: rgba(0,0,0,0.5); padding: 4px 12px; border-radius: 20px;"></div>
    </div>
</div>

<script>
let stagedUnitFiles = [];
let currentPhotoIndex = 0;

function syncStagedUnitFilesToInput() {
    const dt = new DataTransfer();
    stagedUnitFiles.forEach(item => dt.items.add(item.file));
    const hiddenInput = document.getElementById('unit_photos');
    if (hiddenInput) {
        hiddenInput.files = dt.files;
    }
}

function addStagedUnitPhotos(pickerInput) {
    if (!pickerInput.files || pickerInput.files.length === 0) return;
    const grid = document.getElementById('unit-photos-grid');

    Array.from(pickerInput.files).forEach(file => {
        const id = 'staged_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);
        const reader = new FileReader();
        reader.onload = function(e) {
            const dataUrl = e.target.result;
            stagedUnitFiles.push({ id: id, file: file, dataUrl: dataUrl });
            syncStagedUnitFilesToInput();

            const card = document.createElement('div');
            card.className = 'unit-photo-card';
            card.draggable = true;
            card.dataset.stagedId = id;
            card.style.cssText = 'position: relative; border-radius: 12px; overflow: hidden; border: 1px solid var(--ws-border-subtle); background: var(--ws-surface-subtle); padding: 6px; text-align: center; cursor: grab; user-select: none; transition: opacity 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;';
            card.innerHTML = `
                <img src="${dataUrl}" 
                     alt="Unit photo" 
                     onclick="openUnitPhotoModal(this.src)" 
                     style="width: 100%; height: 95px; object-fit: cover; border-radius: 8px; cursor: pointer; transition: transform 0.15s ease; pointer-events: auto;"
                     draggable="false"
                     onmouseover="this.style.transform='scale(1.03)'"
                     onmouseout="this.style.transform='scale(1)'">
                <button type="button" 
                        title="Delete photo" 
                        onclick="removeStagedUnitPhoto('${id}', this)" 
                        style="position: absolute; top: 10px; right: 10px; width: 26px; height: 26px; border-radius: 50%; background: rgba(0,0,0,0.65); border: none; color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.15s ease, transform 0.15s ease; z-index: 5;"
                        onmouseover="this.style.background='#dc2626'; this.style.transform='scale(1.1)';"
                        onmouseout="this.style.background='rgba(0,0,0,0.65)'; this.style.transform='scale(1)';">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </button>
            `;
            grid.appendChild(card);
            grid.style.display = 'grid';
        };
        reader.readAsDataURL(file);
    });

    pickerInput.value = '';
}

function removeStagedUnitPhoto(stagedId, buttonElem) {
    const card = buttonElem.closest('.unit-photo-card');
    if (!card) return;
    stagedUnitFiles = stagedUnitFiles.filter(item => item.id !== stagedId);
    syncStagedUnitFilesToInput();

    card.style.transform = 'scale(0.8)';
    card.style.opacity = '0';
    setTimeout(() => {
        card.remove();
        const grid = document.getElementById('unit-photos-grid');
        if (grid && grid.querySelectorAll('.unit-photo-card').length === 0) {
            grid.style.display = 'none';
        }
    }, 220);
}

function getAllPhotoSources() {
    const images = document.querySelectorAll('#unit-photos-grid .unit-photo-card img');
    return Array.from(images).map(img => img.getAttribute('src'));
}

function openUnitPhotoModal(src) {
    const photos = getAllPhotoSources();
    currentPhotoIndex = photos.indexOf(src);
    if (currentPhotoIndex === -1) currentPhotoIndex = 0;
    updatePhotoModalView();
    document.getElementById('unit-photo-modal').classList.add('active');
}

function updatePhotoModalView() {
    const photos = getAllPhotoSources();
    if (photos.length === 0) return;

    if (currentPhotoIndex >= photos.length) currentPhotoIndex = 0;
    if (currentPhotoIndex < 0) currentPhotoIndex = photos.length - 1;

    const img = document.getElementById('modal-photo-img');
    img.src = photos[currentPhotoIndex];

    const counter = document.getElementById('modal-photo-counter');
    if (counter) {
        counter.textContent = (currentPhotoIndex + 1) + ' / ' + photos.length;
    }
}

function navigatePhotoModal(direction, e) {
    if (e) e.stopPropagation();
    currentPhotoIndex += direction;
    updatePhotoModalView();
}

function closeUnitPhotoModal(e) {
    if (e.target.id === 'unit-photo-modal') {
        document.getElementById('unit-photo-modal').classList.remove('active');
    }
}

document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('unit-photo-modal');
    if (!modal || !modal.classList.contains('active')) return;
    if (e.key === 'ArrowRight') {
        navigatePhotoModal(1);
    } else if (e.key === 'ArrowLeft') {
        navigatePhotoModal(-1);
    } else if (e.key === 'Escape') {
        modal.classList.remove('active');
    }
});

(function() {
    const modal = document.getElementById('unit-photo-modal');
    if (!modal) return;
    let wheelLock = false;
    modal.addEventListener('wheel', (e) => {
        if (!modal.classList.contains('active')) return;
        e.preventDefault();
        if (wheelLock) return;
        wheelLock = true;
        setTimeout(() => { wheelLock = false; }, 160);
        if (e.deltaY > 0 || e.deltaX > 0) {
            navigatePhotoModal(1);
        } else if (e.deltaY < 0 || e.deltaX < 0) {
            navigatePhotoModal(-1);
        }
    }, { passive: false });
})();

// Drag-and-drop sorting for staged unit photos
(function() {
    let dragSrcCard = null;
    document.addEventListener('DOMContentLoaded', function() {
        const grid = document.getElementById('unit-photos-grid');
        if (!grid) return;

        grid.addEventListener('dragstart', function(e) {
            const card = e.target.closest('.unit-photo-card');
            if (!card) return;
            dragSrcCard = card;
            card.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', '');
        });

        grid.addEventListener('dragover', function(e) {
            e.preventDefault();
            const card = e.target.closest('.unit-photo-card');
            if (!card || card === dragSrcCard) return;
            grid.querySelectorAll('.unit-photo-card').forEach(c => {
                c.style.borderColor = '';
                c.style.boxShadow = '';
            });
            card.style.borderColor = 'var(--ws-accent)';
            card.style.boxShadow = '0 0 0 2px var(--ws-accent-subtle)';
        });

        grid.addEventListener('dragleave', function(e) {
            const card = e.target.closest('.unit-photo-card');
            if (card && card !== dragSrcCard) {
                card.style.borderColor = '';
                card.style.boxShadow = '';
            }
        });

        grid.addEventListener('drop', function(e) {
            e.preventDefault();
            const dropTarget = e.target.closest('.unit-photo-card');
            if (!dropTarget || !dragSrcCard || dropTarget === dragSrcCard) return;

            const cards = Array.from(grid.querySelectorAll('.unit-photo-card'));
            const srcIdx = cards.indexOf(dragSrcCard);
            const dropIdx = cards.indexOf(dropTarget);

            if (srcIdx < dropIdx) {
                grid.insertBefore(dragSrcCard, dropTarget.nextSibling);
            } else {
                grid.insertBefore(dragSrcCard, dropTarget);
            }

            // Reorder stagedUnitFiles to match DOM order
            const orderedIds = Array.from(grid.querySelectorAll('.unit-photo-card')).map(c => c.dataset.stagedId);
            stagedUnitFiles.sort((a, b) => orderedIds.indexOf(a.id) - orderedIds.indexOf(b.id));
            syncStagedUnitFilesToInput();
        });

        grid.addEventListener('dragend', function() {
            grid.querySelectorAll('.unit-photo-card').forEach(card => {
                card.style.opacity = '1';
                card.style.borderColor = '';
                card.style.boxShadow = '';
            });
            dragSrcCard = null;
        });
    });
})();

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
