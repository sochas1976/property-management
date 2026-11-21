<?php
use App\Core\Router;

$uStatus = $unit['status'] ?? 'vacant';
$uBadgeClass = ($uStatus === 'occupied') ? 'badge-success' : (($uStatus === 'maintenance') ? 'badge-danger' : 'badge-warning');
$uBadgeLabel = ucfirst($uStatus);
$uDisplayTitle = 'Unit ' . $unit['unit_number'];
$uBaths = rtrim(rtrim(number_format((float)$unit['bathrooms'], 1), '0'), '.');

$unitPhotos = [];
if (!empty($unit['photos'])) {
    $decodedPhotos = json_decode($unit['photos'], true);
    if (is_array($decodedPhotos)) {
        $unitPhotos = $decodedPhotos;
    }
}

$currentUtilities = [
    'included'   => [],
    'shared_pct' => []
];
if (!empty($unit['utilities'])) {
    $decodedUtils = json_decode($unit['utilities'], true);
    if (is_array($decodedUtils)) {
        $currentUtilities = array_merge($currentUtilities, $decodedUtils);
    }
}
$utilityTypes = [
    'electricity' => 'Electricity',
    'heat'        => 'Heat',
    'water'       => 'Water',
    'internet'    => 'Internet',
    'cable'       => 'Cable'
];

$currentAppliances = [];
if (!empty($unit['appliances'])) {
    $decodedApps = json_decode($unit['appliances'], true);
    if (is_array($decodedApps)) {
        $currentAppliances = $decodedApps;
    }
}
$applianceLabels = [
    'fridge'             => 'Fridge',
    'stove'              => 'Stove',
    'microwave'          => 'Microwave',
    'dishwasher'         => 'Dishwasher',
    'washer'             => 'Washer',
    'dryer'              => 'Dryer',
    'washer_dryer_combo' => 'Washer/Dryer Combo'
];
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/properties/' . $property['id']) ?>">&larr; Back to <?= htmlspecialchars($property['name']) ?></a>
</div>

<div class="page-header">
    <div>
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <h1 class="page-title" style="margin: 0;"><?= htmlspecialchars($uDisplayTitle) ?></h1>
            <span class="badge <?= $uBadgeClass ?>"><?= htmlspecialchars($uBadgeLabel) ?></span>
        </div>
        <p class="page-subtitle" style="margin-top: 4px;">
            <?= htmlspecialchars($property['name']) ?> &bull; Unit Specifications, Utilities &amp; Tenancy File
        </p>
    </div>
    <div class="action-buttons">
        <a href="<?= Router::url('/units/' . $unit['id'] . '/edit') ?>" class="btn btn-outline">Edit Unit</a>
        <?php if (!empty($activeLease['id'])): ?>
            <a href="<?= Router::url('/leases/' . $activeLease['id'] . '?from=unit&unit_id=' . (int)$unit['id']) ?>" class="btn btn-outline">View Active Lease</a>
        <?php endif; ?>
        <form action="<?= Router::url('/units/' . $unit['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($uDisplayTitle)) ?>?');" style="display:inline;">
            <button type="submit" class="btn btn-outline">Delete Unit</button>
        </form>
    </div>
</div>

<!-- Single-Column Stacked Layout -->
<div style="display: flex; flex-direction: column; gap: 22px;">

    <!-- Card 1: Property & Unit -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Property &amp; Unit</h2>
        </div>
        <div class="card-body">
            <!-- Property Summary & Unit Specifications Banner -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; padding: 16px 20px; border-radius: 12px; background: var(--ws-surface-subtle, #faf9f6); border: 1.5px solid var(--ws-border, #ebe7df);">
                <!-- Left: Property Photo & Address -->
                <div style="display: flex; align-items: center; gap: 14px; min-width: 260px; flex: 1 1 320px;">
                    <div class="property-card-photo" style="flex-shrink: 0;">
                        <?php if (!empty($property['photo_path'])): ?>
                            <img src="<?= Router::url($property['photo_path']) ?>" alt="<?= htmlspecialchars($property['name']) ?>" class="property-thumbnail" style="width: 64px; height: 64px; border-radius: 10px; border: 1.5px solid var(--ws-border-strong, #d8d3c7); object-fit: cover; display: block;">
                        <?php else: ?>
                            <div class="property-thumbnail-placeholder" style="width: 64px; height: 64px; border-radius: 10px;">📷</div>
                        <?php endif; ?>
                    </div>
                    <div style="min-width: 0;">
                        <div class="property-type-tag" style="margin-bottom: 2px; font-size: 10.5px;"><?= strtoupper(str_replace('_', ' ', $property['property_type'])) ?></div>
                        <div style="font-size: 15.5px; font-weight: 700; color: var(--ws-text); line-height: 1.25;">
                            <a href="<?= Router::url('/properties/' . $property['id']) ?>" style="color: inherit; text-decoration: none;">
                                <?= htmlspecialchars($property['name']) ?>
                            </a>
                        </div>
                        <div style="font-size: 13px; color: var(--ws-text-muted); margin-top: 2px;">
                            <?= htmlspecialchars($property['address_line1']) ?><?= !empty($property['address_line2']) ? ', ' . htmlspecialchars($property['address_line2']) : '' ?>,
                            <?= htmlspecialchars($property['city']) ?>, <?= htmlspecialchars($property['state']) ?> <?= htmlspecialchars($property['zip_code']) ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Suite, Floor Level, Bedrooms & Bathrooms, Square Footage -->
                <div style="display: grid; grid-template-columns: repeat(4, minmax(110px, auto)); gap: 24px; align-items: center; padding-left: 20px; border-left: 1.5px solid var(--ws-border, #ebe7df); flex: 1 1 460px;">
                    <div>
                        <span class="text-muted text-xs">Suite</span>
                        <div style="font-weight: 700; color: var(--ws-text); margin-top: 2px;"><?= htmlspecialchars($unit['unit_number']) ?></div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Floor Level</span>
                        <div style="font-weight: 700; color: var(--ws-text); margin-top: 2px;">Floor <?= (int)($unit['floor'] ?? 1) ?></div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Bedrooms &amp; Bathrooms</span>
                        <div style="font-weight: 700; color: var(--ws-text); margin-top: 2px;">
                            <?= (int)$unit['bedrooms'] ?> Bed &bull; <?= htmlspecialchars($uBaths) ?> Bath
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Square Footage</span>
                        <div style="font-weight: 700; color: var(--ws-text); margin-top: 2px;">
                            <?= !empty($unit['square_feet']) ? number_format((int)$unit['square_feet']) . ' sq ft' : '—' ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($unit['notes'])): ?>
                <!-- Unit Description & Notes -->
                <div style="background: var(--ws-surface-subtle); padding: 16px 18px; border-radius: 10px; border: 1px solid var(--ws-border-subtle); margin-top: 16px;">
                    <span class="text-muted text-xs" style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Unit Description &amp; Notes</span>
                    <div style="margin-top: 6px; font-size: 14px; color: var(--ws-text); line-height: 1.55;">
                        <?= nl2br(htmlspecialchars($unit['notes'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Unit Pictures (Inside Property & Unit Card) -->
            <div style="background: var(--ws-surface-subtle); padding: 16px 18px; border-radius: 10px; border: 1px solid var(--ws-border-subtle); margin-top: 16px;">
                <div class="text-muted text-xs" style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">
                    Unit Pictures
                </div>
                <?php if (empty($unitPhotos)): ?>
                    <div style="text-align: center; padding: 14px 10px;">
                        <p class="text-muted" style="margin: 0 0 10px; font-size: 13.5px;">No pictures uploaded for this unit yet.</p>
                        <a href="<?= Router::url('/units/' . $unit['id'] . '/edit') ?>" class="btn btn-outline btn-xs">Upload Unit Pictures</a>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;">
                        <?php foreach ($unitPhotos as $pIdx => $photoPath): ?>
                            <div style="border-radius: 10px; overflow: hidden; border: 1px solid var(--ws-border); background: var(--ws-surface); padding: 4px;">
                                <img src="<?= Router::url($photoPath) ?>" 
                                     alt="Unit photo <?= $pIdx + 1 ?>" 
                                     onclick="openUnitViewPhotoModal(<?= $pIdx ?>)"
                                     style="width: 100%; height: 105px; object-fit: cover; border-radius: 7px; cursor: pointer; display: block; transition: transform 0.15s ease;"
                                     onmouseover="this.style.transform='scale(1.03)'"
                                     onmouseout="this.style.transform='scale(1)'">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Card 2: Current Tenancy & Lease -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Current Tenancy &amp; Lease</h2>
        </div>
        <div class="card-body">
            <div style="background: var(--ws-surface-subtle); padding: 16px 18px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                <?php if (!empty($activeLease)): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
                        <div>
                            <span class="text-muted text-xs">Current Resident</span>
                            <div style="font-weight: 700; font-size: 15px; margin-top: 2px;">
                                <?php if (!empty($activeLease['tenant_id'])): ?>
                                    <a href="<?= Router::url('/tenants/' . $activeLease['tenant_id']) ?>" style="color: var(--ws-text); text-decoration: none;">
                                        <?= htmlspecialchars($activeLease['tenant_name'] ?? 'Resident') ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($activeLease['tenant_name'] ?? 'Resident') ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Lease Status</span>
                            <div style="margin-top: 4px;">
                                <a href="<?= Router::url('/leases/' . $activeLease['id'] . '?from=unit&unit_id=' . (int)$unit['id']) ?>" style="text-decoration: none;">
                                    <span class="badge badge-success">Active Lease</span>
                                </a>
                            </div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Lease Term</span>
                            <div style="font-weight: 700; color: var(--ws-text); margin-top: 2px;">
                                <?= !empty($activeLease['start_date']) ? date('M j, Y', strtotime($activeLease['start_date'])) : '—' ?>
                                &ndash;
                                <?= !empty($activeLease['end_date']) ? date('M j, Y', strtotime($activeLease['end_date'])) : 'Periodic' ?>
                            </div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Monthly Rent</span>
                            <div class="text-success font-bold" style="font-size: 15.5px; margin-top: 2px;">
                                $<?= number_format((float)($activeLease['rent_amount'] ?? $unit['target_rent']), 2) ?> / mo
                            </div>
                        </div>
                        <div>
                            <span class="text-muted text-xs">Security Deposit</span>
                            <div style="font-weight: 700; color: var(--ws-text); font-size: 15px; margin-top: 2px;">
                                $<?= number_format((float)($unit['security_deposit'] ?? 0), 2) ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; flex: 1;">
                            <div>
                                <span class="text-muted text-xs">Occupancy Status</span>
                                <div style="font-weight: 700; color: var(--ws-text); margin-top: 2px;">
                                    <?= $uStatus === 'maintenance' ? 'Under Maintenance / Turnaround' : 'Vacant — Available Immediately' ?>
                                </div>
                            </div>
                            <div>
                                <span class="text-muted text-xs">Monthly Rent</span>
                                <div class="text-success font-bold" style="font-size: 15.5px; margin-top: 2px;">
                                    $<?= number_format((float)$unit['target_rent'], 2) ?> / mo
                                </div>
                            </div>
                            <div>
                                <span class="text-muted text-xs">Security Deposit</span>
                                <div style="font-weight: 700; color: var(--ws-text); font-size: 15px; margin-top: 2px;">
                                    $<?= number_format((float)($unit['security_deposit'] ?? 0), 2) ?>
                                </div>
                            </div>
                        </div>
                        <a href="<?= Router::url('/applicants') ?>" class="btn btn-outline btn-xs">View Applicants</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Card 3: Rules, Utilities & Appliances -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Rules, Utilities &amp; Appliances</h2>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; gap: 16px;">
            <!-- House Rules Grid -->
            <div style="background: var(--ws-surface-subtle); padding: 16px 18px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                <div class="text-muted text-xs" style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">
                    Policies &amp; House Rules
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 14px;">
                    <div>
                        <span class="text-muted text-xs">Adults Only</span>
                        <div style="font-weight: 700; color: var(--ws-text); margin-top: 2px;">
                            <?= ($unit['adults_only'] ?? 'no') === 'yes' ? 'Yes' : 'No' ?>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Furnished</span>
                        <div style="font-weight: 700; color: var(--ws-text); margin-top: 2px;">
                            <?= ($unit['furnished'] ?? 'no') === 'yes' ? 'Yes' : 'No' ?>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Parking</span>
                        <div style="font-weight: 700; color: var(--ws-text); margin-top: 2px;">
                            <?= ucfirst(htmlspecialchars($unit['parking'] ?? 'Street')) ?>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Pets</span>
                        <div style="font-weight: 700; color: var(--ws-text); margin-top: 2px;">
                            <?= ($unit['pets'] ?? 'not_allowed') === 'allowed' ? 'Allowed' : 'Not Allowed' ?>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Smoking</span>
                        <div style="font-weight: 700; color: var(--ws-text); margin-top: 2px;">
                            <?= ($unit['smoking'] ?? 'not_allowed') === 'allowed' ? 'Allowed' : 'Not Allowed' ?>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted text-xs">Tenant Insurance</span>
                        <div style="font-weight: 700; color: var(--ws-text); margin-top: 2px;">
                            <?= ($unit['tenant_insurance'] ?? 'not_required') === 'required' ? 'Required' : 'Not Required' ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2-Column Side-by-Side Cards: Utilities Coverage (Col 1) & Included Appliances (Col 2) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; align-items: stretch;">
                <!-- Column 1 Card: Utilities Coverage -->
                <div style="background: var(--ws-surface-subtle); padding: 16px 18px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                    <div class="text-muted text-xs" style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">
                        Utilities Coverage
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach ($utilityTypes as $uKey => $uLabel): 
                            $isInc = in_array($uKey, $currentUtilities['included'] ?? [], true);
                            $sharedPct = isset($currentUtilities['shared_pct'][$uKey]) && $currentUtilities['shared_pct'][$uKey] !== '' ? (int)$currentUtilities['shared_pct'][$uKey] : null;
                            if ($isInc) {
                                $uStatusText = 'Included (100%)';
                                $uClass = 'badge-success';
                            } elseif ($sharedPct !== null && $sharedPct > 0 && $sharedPct < 100) {
                                $uStatusText = 'Shared (' . $sharedPct . '% Tenant)';
                                $uClass = 'badge-warning';
                            } else {
                                $uStatusText = 'Tenant (100%)';
                                $uClass = 'badge-subtle';
                            }
                        ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 8px 12px; border-radius: 8px; background: var(--ws-surface); border: 1px solid var(--ws-border-subtle);">
                                <span style="font-size: 13.5px; font-weight: 600; color: var(--ws-text);"><?= htmlspecialchars($uLabel) ?></span>
                                <span class="badge <?= $uClass ?>" style="font-size: 10.5px; padding: 2px 8px;"><?= htmlspecialchars($uStatusText) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Column 2 Card: Included Appliances -->
                <div style="background: var(--ws-surface-subtle); padding: 16px 18px; border-radius: 10px; border: 1px solid var(--ws-border-subtle);">
                    <div class="text-muted text-xs" style="font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">
                        Included Appliances
                    </div>
                    <?php if (empty($currentAppliances)): ?>
                        <span class="text-muted text-xs">No appliances specified.</span>
                    <?php else: ?>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                            <?php foreach ($currentAppliances as $appKey): 
                                $appName = $applianceLabels[$appKey] ?? ucfirst($appKey);
                            ?>
                                <div style="display: flex; align-items: center; padding: 8px 12px; border-radius: 8px; background: var(--ws-surface); border: 1px solid var(--ws-border-subtle); font-size: 13.5px; font-weight: 600; color: var(--ws-text);">
                                    <span><?= htmlspecialchars($appName) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php if (!empty($unitPhotos)): ?>
<!-- Lightbox Modal for Unit Photo Preview -->
<div id="unit-view-photo-modal" class="modal-backdrop" onclick="if(event.target === this) this.classList.remove('active')">
    <div style="position: relative; max-width: 90vw; max-height: 90vh; display: flex; align-items: center; justify-content: center;">
        <button type="button" onclick="document.getElementById('unit-view-photo-modal').classList.remove('active')" 
                style="position: absolute; top: -45px; right: 0; background: none; border: none; color: #fff; font-size: 34px; cursor: pointer; line-height: 1;">&times;</button>

        <!-- Previous Arrow -->
        <button type="button" onclick="navigateUnitViewPhoto(-1, event)" 
                title="Previous photo"
                style="position: absolute; left: -50px; background: rgba(0,0,0,0.6); border: 1.5px solid rgba(255,255,255,0.3); color: #fff; width: 44px; height: 44px; border-radius: 50%; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s ease, transform 0.15s ease; z-index: 10;"
                onmouseover="this.style.background='var(--ws-accent)'; this.style.transform='scale(1.08)';"
                onmouseout="this.style.background='rgba(0,0,0,0.6)'; this.style.transform='scale(1)';">
            &#10094;
        </button>

        <img id="unit-view-modal-img" src="" alt="Unit Photo Preview" style="max-width: 82vw; max-height: 82vh; border-radius: 14px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); object-fit: contain; transition: opacity 0.15s ease;">

        <!-- Next Arrow -->
        <button type="button" onclick="navigateUnitViewPhoto(1, event)" 
                title="Next photo"
                style="position: absolute; right: -50px; background: rgba(0,0,0,0.6); border: 1.5px solid rgba(255,255,255,0.3); color: #fff; width: 44px; height: 44px; border-radius: 50%; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s ease, transform 0.15s ease; z-index: 10;"
                onmouseover="this.style.background='var(--ws-accent)'; this.style.transform='scale(1.08)';"
                onmouseout="this.style.background='rgba(0,0,0,0.6)'; this.style.transform='scale(1)';">
            &#10095;
        </button>

        <!-- Photo Counter Badge -->
        <div id="unit-view-photo-counter" style="position: absolute; bottom: -36px; color: #fff; font-size: 13px; font-weight: 500; background: rgba(0,0,0,0.5); padding: 4px 12px; border-radius: 20px;"></div>
    </div>
</div>
<script>
var unitViewPhotosList = <?= json_encode(array_map(function($p) { return Router::url($p); }, $unitPhotos)) ?>;
var currentUnitViewPhotoIdx = 0;

function openUnitViewPhotoModal(idx) {
    currentUnitViewPhotoIdx = typeof idx === 'number' ? idx : 0;
    updateUnitViewPhotoModal();
    var modal = document.getElementById('unit-view-photo-modal');
    if (modal) {
        modal.classList.add('active');
    }
}

function updateUnitViewPhotoModal() {
    if (!unitViewPhotosList || unitViewPhotosList.length === 0) return;

    // Circular wrap-around
    if (currentUnitViewPhotoIdx >= unitViewPhotosList.length) currentUnitViewPhotoIdx = 0;
    if (currentUnitViewPhotoIdx < 0) currentUnitViewPhotoIdx = unitViewPhotosList.length - 1;

    var img = document.getElementById('unit-view-modal-img');
    if (img) {
        img.src = unitViewPhotosList[currentUnitViewPhotoIdx];
    }

    var counter = document.getElementById('unit-view-photo-counter');
    if (counter) {
        counter.textContent = (currentUnitViewPhotoIdx + 1) + ' / ' + unitViewPhotosList.length;
    }
}

function navigateUnitViewPhoto(direction, e) {
    if (e) e.stopPropagation();
    currentUnitViewPhotoIdx += direction;
    updateUnitViewPhotoModal();
}

// Keyboard arrow navigation & Escape
document.addEventListener('keydown', function(e) {
    var modal = document.getElementById('unit-view-photo-modal');
    if (!modal || !modal.classList.contains('active')) return;
    if (e.key === 'ArrowRight') {
        navigateUnitViewPhoto(1);
    } else if (e.key === 'ArrowLeft') {
        navigateUnitViewPhoto(-1);
    } else if (e.key === 'Escape') {
        modal.classList.remove('active');
    }
});

// Mouse wheel scrolling inside the photo modal
(function() {
    var modal = document.getElementById('unit-view-photo-modal');
    if (!modal) return;
    var wheelLock = false;
    modal.addEventListener('wheel', function(e) {
        if (!modal.classList.contains('active')) return;
        e.preventDefault();
        if (wheelLock) return;
        wheelLock = true;
        setTimeout(function() { wheelLock = false; }, 160);
        if (e.deltaY > 0 || e.deltaX > 0) {
            navigateUnitViewPhoto(1);
        } else if (e.deltaY < 0 || e.deltaX < 0) {
            navigateUnitViewPhoto(-1);
        }
    }, { passive: false });
})();
</script>
<?php endif; ?>
