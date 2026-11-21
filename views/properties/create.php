<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/properties') ?>">&larr; Back to Properties</a>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Add New Property</h2>
    </div>
    <div class="card-body">
        <form action="<?= Router::url('/properties') ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Property Name *</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Pacific Coast Apartments" required>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="property_type">Property Type *</label>
                    <select id="property_type" name="property_type" class="form-control">
                        <option value="multi_family">Multi-Family Residential</option>
                        <option value="single_family">Single Family Home</option>
                        <option value="apartment">Apartment Complex</option>
                        <option value="commercial">Commercial / Office</option>
                        <option value="condo">Condominium</option>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="manager_id">Assigned Property Manager</label>
                    <select id="manager_id" name="manager_id" class="form-control">
                        <option value="">-- Select Manager --</option>
                        <?php foreach ($managers as $mgr): ?>
                            <option value="<?= $mgr['id'] ?>"><?= htmlspecialchars($mgr['full_name']) ?> (<?= htmlspecialchars($mgr['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="address_line1">Street Address *</label>
                <input type="text" id="address_line1" name="address_line1" class="form-control" placeholder="1234 Main St" required>
            </div>

            <div class="form-group">
                <label for="address_line2">Address Line 2</label>
                <input type="text" id="address_line2" name="address_line2" class="form-control" placeholder="Building B, Suite 100">
            </div>

            <div class="form-row">
                <div class="form-group col-4">
                    <label for="city">City *</label>
                    <input type="text" id="city" name="city" class="form-control" placeholder="City" required>
                </div>
                <div class="form-group col-4">
                    <label for="state">State / Province *</label>
                    <input type="text" id="state" name="state" class="form-control" placeholder="CA" required>
                </div>
                <div class="form-group col-4">
                    <label for="zip_code">ZIP / Postal Code *</label>
                    <input type="text" id="zip_code" name="zip_code" class="form-control" placeholder="90210" required>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Property Description / Notes</label>
                <textarea id="description" name="description" class="form-control auto-expand" rows="2" oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px';" style="overflow: hidden; resize: none; line-height: 1.6;" placeholder="Key amenities, parking regulations, access gate codes..."></textarea>
            </div>

            <div class="form-group">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <label style="margin: 0; font-size: 14.5px; font-weight: 600;">Property Pictures</label>
                    <label for="property_photos_picker" title="Add Pictures" style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: transparent; border: 2px solid var(--ws-accent); color: var(--ws-accent); cursor: pointer; transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease; padding: 0;"
                           onmouseover="this.style.transform='scale(1.12)'; this.style.background='var(--ws-accent-subtle)';"
                           onmouseout="this.style.transform='scale(1)'; this.style.background='transparent';">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </label>
                </div>
                <input type="file" id="property_photos_picker" multiple accept="image/jpeg,image/png,image/webp,image/gif" onchange="addStagedPropertyPhotos(this)" style="display: none;">
                <input type="file" id="property_photos" name="property_photos[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" style="display: none;">

                <div id="unit-photos-grid" style="display: none; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 14px; margin-bottom: 16px;"></div>
            </div>

            <div class="form-actions">
                <a href="<?= Router::url('/properties') ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Property</button>
            </div>
        </form>
    </div>
</div>

<!-- Lightbox Modal for Property Photo Preview -->
<div id="unit-photo-modal" class="modal-backdrop" onclick="closePropertyPhotoModal(event)">
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

        <img id="modal-photo-img" src="" alt="Property Photo Preview" style="max-width: 82vw; max-height: 82vh; border-radius: 14px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); object-fit: contain; transition: opacity 0.15s ease;">

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
let stagedPropertyFiles = [];
let currentPhotoIndex = 0;

function syncStagedPropertyFilesToInput() {
    const dt = new DataTransfer();
    stagedPropertyFiles.forEach(item => dt.items.add(item.file));
    const hiddenInput = document.getElementById('property_photos');
    if (hiddenInput) {
        hiddenInput.files = dt.files;
    }
}

function addStagedPropertyPhotos(pickerInput) {
    if (!pickerInput.files || pickerInput.files.length === 0) return;
    const grid = document.getElementById('unit-photos-grid');

    Array.from(pickerInput.files).forEach(file => {
        const id = 'staged_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);
        const reader = new FileReader();
        reader.onload = function(e) {
            const dataUrl = e.target.result;
            stagedPropertyFiles.push({ id: id, file: file, dataUrl: dataUrl });
            syncStagedPropertyFilesToInput();

            const card = document.createElement('div');
            card.className = 'unit-photo-card';
            card.draggable = true;
            card.dataset.stagedId = id;
            card.style.cssText = 'position: relative; border-radius: 12px; overflow: hidden; border: 1px solid var(--ws-border-subtle); background: var(--ws-surface-subtle); padding: 6px; text-align: center; cursor: grab; user-select: none; transition: opacity 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;';
            card.innerHTML = `
                <img src="${dataUrl}" 
                     alt="Property photo" 
                     onclick="openPropertyPhotoModal(this.src)" 
                     style="width: 100%; height: 95px; object-fit: cover; border-radius: 8px; cursor: pointer; transition: transform 0.15s ease; pointer-events: auto;"
                     draggable="false"
                     onmouseover="this.style.transform='scale(1.03)'"
                     onmouseout="this.style.transform='scale(1)'">
                <button type="button" 
                        title="Delete photo" 
                        onclick="removeStagedPropertyPhoto('${id}', this)" 
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

function removeStagedPropertyPhoto(stagedId, buttonElem) {
    const card = buttonElem.closest('.unit-photo-card');
    if (!card) return;
    stagedPropertyFiles = stagedPropertyFiles.filter(item => item.id !== stagedId);
    syncStagedPropertyFilesToInput();

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

function openPropertyPhotoModal(src) {
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

function closePropertyPhotoModal(e) {
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

            const orderedIds = Array.from(grid.querySelectorAll('.unit-photo-card')).map(c => c.dataset.stagedId);
            stagedPropertyFiles.sort((a, b) => orderedIds.indexOf(a.id) - orderedIds.indexOf(b.id));
            syncStagedPropertyFilesToInput();
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
</script>
