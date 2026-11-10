<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/properties/' . $property['id']) ?>">&larr; Back to <?= htmlspecialchars($property['name']) ?></a>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Edit Unit <?= htmlspecialchars($unit['unit_number']) ?></h2>
    </div>
    <div class="card-body">
        <form id="unit-edit-form" action="<?= Router::url('/units/' . $unit['id'] . '/update') ?>" method="POST" enctype="multipart/form-data">
            <?php if ($property['property_type'] === 'single_family'): ?>
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="unit_number">Unit Type *</label>
                    <select id="unit_number" name="unit_number" class="form-control" required>
                        <option value="">-- Select Unit Type --</option>
                        <option value="Main Floor" <?= $unit['unit_number'] === 'Main Floor' ? 'selected' : '' ?>>Main Floor</option>
                        <option value="Basement" <?= $unit['unit_number'] === 'Basement' ? 'selected' : '' ?>>Basement</option>
                        <option value="Garage Suite" <?= $unit['unit_number'] === 'Garage Suite' ? 'selected' : '' ?>>Garage Suite</option>
                        <option value="House" <?= $unit['unit_number'] === 'House' ? 'selected' : '' ?>>House</option>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="floor">Floor Level</label>
                    <input type="number" id="floor" name="floor" class="form-control" value="<?= (int)$unit['floor'] ?>" min="-2" max="100">
                </div>
            </div>
            <?php else: ?>
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="unit_number">Unit / Suite Number *</label>
                    <input type="text" id="unit_number" name="unit_number" class="form-control" value="<?= htmlspecialchars($unit['unit_number']) ?>" required>
                </div>
                <div class="form-group col-6">
                    <label for="floor">Floor Level</label>
                    <input type="number" id="floor" name="floor" class="form-control" value="<?= (int)$unit['floor'] ?>" min="-2" max="100">
                </div>
            </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group col-4">
                    <label for="bedrooms">Bedrooms</label>
                    <input type="number" id="bedrooms" name="bedrooms" class="form-control" value="<?= (int)$unit['bedrooms'] ?>" min="0" max="20">
                </div>
                <div class="form-group col-4">
                    <label for="bathrooms">Bathrooms</label>
                    <input type="number" id="bathrooms" name="bathrooms" class="form-control" value="<?= htmlspecialchars($unit['bathrooms']) ?>" step="0.5" min="0.5" max="20">
                </div>
                <div class="form-group col-4">
                    <label for="square_feet">Square Footage</label>
                    <input type="number" id="square_feet" name="square_feet" class="form-control" value="<?= $unit['square_feet'] ? (int)$unit['square_feet'] : '' ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="target_rent">Monthly Rent ($) *</label>
                    <input type="number" id="target_rent" name="target_rent" class="form-control" step="0.01" value="<?= htmlspecialchars($unit['target_rent']) ?>" required>
                </div>
                <div class="form-group col-6">
                    <label for="security_deposit">Security Deposit ($)</label>
                    <input type="number" id="security_deposit" name="security_deposit" class="form-control" step="0.01" value="<?= htmlspecialchars($unit['security_deposit']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="status">Occupancy Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="vacant" <?= $unit['status'] === 'vacant' ? 'selected' : '' ?>>Vacant (Available for Lease)</option>
                    <option value="maintenance" <?= $unit['status'] === 'maintenance' ? 'selected' : '' ?>>Under Maintenance / Turnaround</option>
                    <option value="occupied" <?= $unit['status'] === 'occupied' ? 'selected' : '' ?>>Occupied</option>
                </select>
            </div>

            <?php
            $currentAppliances = [];
            if (!empty($unit['appliances'])) {
                $decoded = json_decode($unit['appliances'], true);
                if (is_array($decoded)) {
                    $currentAppliances = $decoded;
                }
            }
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
                            <input type="checkbox" name="appliances[]" value="<?= $key ?>" <?= in_array($key, $currentAppliances, true) ? 'checked' : '' ?> style="width: 16px; height: 16px; accent-color: var(--ws-accent); cursor: pointer;">
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Unit Description</label>
                <textarea id="notes" name="notes" class="form-control auto-expand" rows="1" oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px';" style="overflow: hidden; resize: none; line-height: 1.6;"><?= htmlspecialchars($unit['notes'] ?? '') ?></textarea>
            </div>

            <?php
            $unitPhotos = [];
            if (!empty($unit['photos'])) {
                $decoded = json_decode($unit['photos'], true);
                if (is_array($decoded)) {
                    $unitPhotos = $decoded;
                }
            }
            ?>
            <div class="form-group">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <label style="margin: 0; font-size: 14.5px; font-weight: 600;">Unit Pictures</label>
                    <label for="unit_photos" title="Add Pictures" style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: transparent; border: 2px solid var(--ws-accent); color: var(--ws-accent); cursor: pointer; transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease; padding: 0;"
                           onmouseover="this.style.transform='scale(1.12)'; this.style.background='var(--ws-accent-subtle)';"
                           onmouseout="this.style.transform='scale(1)'; this.style.background='transparent';">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </label>
                </div>
                <input type="file" id="unit_photos" name="unit_photos[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewNewPhotos(this)" style="display: none;">
                
                <div id="unit-photos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 14px; margin-bottom: 16px; <?= empty($unitPhotos) ? 'display: none;' : '' ?>">
                    <?php if (!empty($unitPhotos)): ?>
                        <?php foreach ($unitPhotos as $idx => $photo): ?>
                            <div class="unit-photo-card" 
                                 draggable="true" 
                                 data-photo="<?= htmlspecialchars($photo) ?>" 
                                 style="position: relative; border-radius: 12px; overflow: hidden; border: 1px solid var(--ws-border-subtle); background: var(--ws-surface-subtle); padding: 6px; text-align: center; cursor: grab; user-select: none; transition: opacity 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;">
                                
                                <input type="hidden" name="existing_photos[]" value="<?= htmlspecialchars($photo) ?>">
                                
                                <img src="<?= Router::url($photo) ?>" 
                                     alt="Unit photo" 
                                     onclick="openUnitPhotoModal('<?= Router::url($photo) ?>')" 
                                     style="width: 100%; height: 95px; object-fit: cover; border-radius: 8px; cursor: pointer; transition: transform 0.15s ease; pointer-events: auto;"
                                     draggable="false"
                                     onmouseover="this.style.transform='scale(1.03)'"
                                     onmouseout="this.style.transform='scale(1)'">
                                
                                <button type="button" 
                                        title="Delete photo" 
                                        onclick="deleteUnitPhotoDynamically(<?= (int)$unit['id'] ?>, '<?= htmlspecialchars($photo) ?>', this)" 
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
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="new-photos-preview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 12px;"></div>
            </div>

            <div id="unit-form-actions" class="form-actions" style="display: none; opacity: 0; transition: opacity 0.25s ease;">
                <a href="<?= Router::url('/properties/' . $property['id']) ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
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
let currentPhotoIndex = 0;

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
    
    // Circular wrap-around logic
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

// Keyboard arrow navigation for lightbox
document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('unit-photo-modal');
    if (!modal || !modal.classList.contains('active')) return;
    if (e.key === 'ArrowRight') {
        navigatePhotoModal(1);
    } else if (e.key === 'ArrowLeft') {
        navigatePhotoModal(-1);
    }
});

function deleteUnitPhotoDynamically(unitId, photoPath, buttonElem) {
    const card = buttonElem.closest('.unit-photo-card');
    card.style.opacity = '0.4';
    card.style.pointerEvents = 'none';

    const formData = new FormData();
    formData.append('photo', photoPath);

    fetch('<?= Router::url('/units/') ?>' + unitId + '/delete-photo', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            card.style.transform = 'scale(0.8)';
            card.style.opacity = '0';
            setTimeout(() => {
                card.remove();
                const grid = document.getElementById('unit-photos-grid');
                if (grid && grid.querySelectorAll('.unit-photo-card').length === 0) {
                    grid.style.display = 'none';
                }
            }, 250);
        } else {
            alert(data.message || 'Error deleting picture.');
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
        }
    })
    .catch(err => {
        alert('Error deleting picture.');
        card.style.opacity = '1';
        card.style.pointerEvents = 'auto';
    });
}

function previewNewPhotos(input) {
    const previewContainer = document.getElementById('new-photos-preview');
    previewContainer.innerHTML = '';
    if (!input.files || input.files.length === 0) {
        checkFormChanges();
        return;
    }

    Array.from(input.files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const card = document.createElement('div');
            card.style.cssText = 'position: relative; border-radius: 8px; overflow: hidden; border: 1px solid var(--ws-border-subtle); background: var(--ws-surface); padding: 4px;';
            card.innerHTML = `
                <img src="${e.target.result}" style="width: 100%; height: 75px; object-fit: cover; border-radius: 6px;">
                <div style="font-size: 11px; color: var(--ws-text-muted); margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${file.name}</div>
            `;
            previewContainer.appendChild(card);
        };
        reader.readAsDataURL(file);
    });

    checkFormChanges();
}

// Track initial form values and only show Cancel & Save Changes when changes exist
let initialFormData = '';

function serializeFormState(form) {
    const ordered = [];   // order-sensitive entries (existing_photos[])
    const sortable = [];  // everything else (safe to sort for stable comparison)
    Array.from(form.elements).forEach(elem => {
        if (!elem.name || elem.disabled) return;
        if (elem.type === 'file') {
            sortable.push(elem.name + '=' + (elem.files ? elem.files.length : 0));
        } else if (elem.type === 'checkbox' || elem.type === 'radio') {
            if (elem.checked) sortable.push(elem.name + '=' + elem.value);
        } else if (elem.name === 'existing_photos[]') {
            // Preserve DOM order so drag-and-drop reorder counts as a change
            ordered.push(elem.name + '=' + elem.value);
        } else {
            sortable.push(elem.name + '=' + elem.value);
        }
    });
    return sortable.sort().join('&') + '||' + ordered.join('&');
}

function checkFormChanges() {
    const form = document.getElementById('unit-edit-form');
    const actions = document.getElementById('unit-form-actions');
    if (!form || !actions) return;

    const currentData = serializeFormState(form);
    const hasChanges = currentData !== initialFormData;

    if (hasChanges) {
        actions.style.display = 'flex';
        requestAnimationFrame(() => {
            actions.style.opacity = '1';
        });
    } else {
        actions.style.opacity = '0';
        setTimeout(() => {
            if (serializeFormState(form) === initialFormData) {
                actions.style.display = 'none';
            }
        }, 250);
    }
}

// ── Drag-and-Drop Photo Sorting ──
(function() {
    let dragSrcCard = null;

    function initDragAndDrop() {
        const grid = document.getElementById('unit-photos-grid');
        if (!grid) return;

        grid.addEventListener('dragstart', function(e) {
            const card = e.target.closest('.unit-photo-card');
            if (!card) return;
            dragSrcCard = card;
            card.style.opacity = '0.4';
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', ''); // required for Firefox
        });

        grid.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const card = e.target.closest('.unit-photo-card');
            if (!card || card === dragSrcCard) return;

            // Clear previous drop indicators
            grid.querySelectorAll('.unit-photo-card').forEach(c => {
                c.style.borderColor = '';
                c.style.boxShadow = '';
            });

            // Highlight the drop target
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

            // Determine insertion order based on position
            const cards = Array.from(grid.querySelectorAll('.unit-photo-card'));
            const srcIdx = cards.indexOf(dragSrcCard);
            const dropIdx = cards.indexOf(dropTarget);

            if (srcIdx < dropIdx) {
                // Moving forward: insert after the drop target
                grid.insertBefore(dragSrcCard, dropTarget.nextSibling);
            } else {
                // Moving backward: insert before the drop target
                grid.insertBefore(dragSrcCard, dropTarget);
            }

            // Update hidden input order to match new DOM order
            updatePhotoInputOrder();

            // Trigger change detection
            checkFormChanges();
        });

        grid.addEventListener('dragend', function(e) {
            // Clean up all visual states
            grid.querySelectorAll('.unit-photo-card').forEach(card => {
                card.style.opacity = '1';
                card.style.borderColor = '';
                card.style.boxShadow = '';
                card.classList.remove('dragging');
            });
            dragSrcCard = null;
        });
    }

    function updatePhotoInputOrder() {
        const grid = document.getElementById('unit-photos-grid');
        if (!grid) return;
        const cards = grid.querySelectorAll('.unit-photo-card');
        cards.forEach(card => {
            const input = card.querySelector('input[name="existing_photos[]"]');
            if (input) {
                // Re-set value from card data attribute to maintain correct order
                input.value = card.dataset.photo;
            }
        });
    }

    // Also support touch devices via pointer events
    let touchSrcCard = null;
    let touchClone = null;
    let touchOffsetX = 0;
    let touchOffsetY = 0;

    function initTouchDragAndDrop() {
        const grid = document.getElementById('unit-photos-grid');
        if (!grid) return;

        grid.addEventListener('touchstart', function(e) {
            const card = e.target.closest('.unit-photo-card');
            if (!card) return;
            // Ignore if touch starts on delete button
            if (e.target.closest('button')) return;

            touchSrcCard = card;
            const touch = e.touches[0];
            const rect = card.getBoundingClientRect();
            touchOffsetX = touch.clientX - rect.left;
            touchOffsetY = touch.clientY - rect.top;

            // Create floating clone after a slight delay (prevent conflict with taps)
            card._touchTimeout = setTimeout(function() {
                card.style.opacity = '0.4';
                touchClone = card.cloneNode(true);
                touchClone.style.position = 'fixed';
                touchClone.style.width = rect.width + 'px';
                touchClone.style.height = rect.height + 'px';
                touchClone.style.left = (touch.clientX - touchOffsetX) + 'px';
                touchClone.style.top = (touch.clientY - touchOffsetY) + 'px';
                touchClone.style.opacity = '0.85';
                touchClone.style.zIndex = '9999';
                touchClone.style.pointerEvents = 'none';
                touchClone.style.boxShadow = '0 8px 24px rgba(0,0,0,0.25)';
                touchClone.style.transform = 'scale(1.05)';
                document.body.appendChild(touchClone);
            }, 150);
        }, { passive: true });

        grid.addEventListener('touchmove', function(e) {
            if (!touchSrcCard || !touchClone) return;
            e.preventDefault();
            const touch = e.touches[0];
            touchClone.style.left = (touch.clientX - touchOffsetX) + 'px';
            touchClone.style.top = (touch.clientY - touchOffsetY) + 'px';

            // Highlight card under finger
            const elem = document.elementFromPoint(touch.clientX, touch.clientY);
            const hoverCard = elem ? elem.closest('.unit-photo-card') : null;
            grid.querySelectorAll('.unit-photo-card').forEach(c => {
                c.style.borderColor = '';
                c.style.boxShadow = '';
            });
            if (hoverCard && hoverCard !== touchSrcCard) {
                hoverCard.style.borderColor = 'var(--ws-accent)';
                hoverCard.style.boxShadow = '0 0 0 2px var(--ws-accent-subtle)';
            }
        }, { passive: false });

        grid.addEventListener('touchend', function(e) {
            if (touchSrcCard && touchSrcCard._touchTimeout) {
                clearTimeout(touchSrcCard._touchTimeout);
            }

            if (!touchSrcCard || !touchClone) {
                touchSrcCard = null;
                return;
            }

            const touch = e.changedTouches[0];
            const elem = document.elementFromPoint(touch.clientX, touch.clientY);
            const dropTarget = elem ? elem.closest('.unit-photo-card') : null;

            if (dropTarget && dropTarget !== touchSrcCard) {
                const cards = Array.from(grid.querySelectorAll('.unit-photo-card'));
                const srcIdx = cards.indexOf(touchSrcCard);
                const dropIdx = cards.indexOf(dropTarget);

                if (srcIdx < dropIdx) {
                    grid.insertBefore(touchSrcCard, dropTarget.nextSibling);
                } else {
                    grid.insertBefore(touchSrcCard, dropTarget);
                }
                updatePhotoInputOrder();
                checkFormChanges();
            }

            // Clean up
            grid.querySelectorAll('.unit-photo-card').forEach(c => {
                c.style.opacity = '1';
                c.style.borderColor = '';
                c.style.boxShadow = '';
            });
            if (touchClone && touchClone.parentNode) {
                touchClone.parentNode.removeChild(touchClone);
            }
            touchSrcCard = null;
            touchClone = null;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initDragAndDrop();
        initTouchDragAndDrop();
    });
})();

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('unit-edit-form');
    if (form) {
        initialFormData = serializeFormState(form);
        form.addEventListener('input', checkFormChanges);
        form.addEventListener('change', checkFormChanges);
    }
});
</script>
