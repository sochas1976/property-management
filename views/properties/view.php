<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/properties') ?>">&larr; Back to Properties</a>
</div>

<div class="page-header">
    <div style="display: flex; align-items: flex-start; gap: 20px; flex: 1;">
        <?php if (!empty($property['photo_path'])): ?>
            <img src="<?= Router::url($property['photo_path']) ?>" alt="<?= htmlspecialchars($property['name']) ?>" class="property-detail-photo">
        <?php endif; ?>
        <div>
            <div class="property-type-tag" style="margin-bottom: 4px;"><?= strtoupper(str_replace('_', ' ', $property['property_type'])) ?></div>
            <h1 class="page-title" style="margin-top: 0;"><?= htmlspecialchars($property['name']) ?></h1>
            <p class="page-subtitle">
                <?= htmlspecialchars($property['address_line1']) ?><?= $property['address_line2'] ? ', ' . htmlspecialchars($property['address_line2']) : '' ?>,
                <?= htmlspecialchars($property['city']) ?>, <?= htmlspecialchars($property['state']) ?> <?= htmlspecialchars($property['zip_code']) ?>
            </p>
        </div>
    </div>
    <div class="action-buttons">
        <a href="<?= Router::url('/properties/' . $property['id'] . '/edit') ?>" class="btn btn-outline">Edit Property</a>
        <a href="<?= Router::url('/properties/' . $property['id'] . '/units/create') ?>" class="btn btn-outline">Add Unit</a>
        <form action="<?= Router::url('/properties/' . $property['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this property and all its units?');" style="display:inline;">
            <button type="submit" class="btn btn-outline">Delete Property</button>
        </form>
    </div>
</div>

<?php
$unitFilter = $_GET['status'] ?? 'all';
$totalUnitsCount = count($units ?? []);
$occupiedUnitsCount = 0;
$vacantUnitsCount = 0;
$maintenanceUnitsCount = 0;
$filteredUnits = [];

if (!empty($units)) {
    foreach ($units as $uItem) {
        $uSt = $uItem['status'] ?? 'vacant';
        if ($uSt === 'occupied') {
            $occupiedUnitsCount++;
        } elseif ($uSt === 'maintenance') {
            $maintenanceUnitsCount++;
        } else {
            $vacantUnitsCount++;
        }

        if ($unitFilter === 'all' || $uSt === $unitFilter) {
            $filteredUnits[] = $uItem;
        }
    }
}

// Drag-to-reorder is only available on the unfiltered "All Units" view
$canReorderUnits = ($unitFilter === 'all' && count($filteredUnits) > 1);
$unitTableColspan = $canReorderUnits ? 8 : 7;
?>

<!-- View Segmented Filter Pill Tabs (Option 3) -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;">
    <a href="<?= Router::url('/properties/' . $property['id'] . '?status=all') ?>" 
       class="btn <?= $unitFilter === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm" 
       style="border-radius: 9999px; padding: 6px 16px;">
        All Units
    </a>
    <a href="<?= Router::url('/properties/' . $property['id'] . '?status=occupied') ?>" 
       class="btn <?= $unitFilter === 'occupied' ? 'btn-primary' : 'btn-outline' ?> btn-sm" 
       style="border-radius: 9999px; padding: 6px 16px;">
        Occupied
    </a>
    <a href="<?= Router::url('/properties/' . $property['id'] . '?status=vacant') ?>" 
       class="btn <?= $unitFilter === 'vacant' ? 'btn-primary' : 'btn-outline' ?> btn-sm" 
       style="border-radius: 9999px; padding: 6px 16px;">
        Vacant
    </a>
    <?php if ($maintenanceUnitsCount > 0 || $unitFilter === 'maintenance'): ?>
        <a href="<?= Router::url('/properties/' . $property['id'] . '?status=maintenance') ?>" 
           class="btn <?= $unitFilter === 'maintenance' ? 'btn-primary' : 'btn-outline' ?> btn-sm" 
           style="border-radius: 9999px; padding: 6px 16px;">
            Maintenance
        </a>
    <?php endif; ?>
</div>

<!-- Units Section -->
<div class="card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <div class="card-header">
        <h2 class="card-title">Units &amp; Floorplans</h2>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <?php if ($canReorderUnits): ?>
                        <th style="width: 36px; padding-right: 0;" aria-label="Reorder"></th>
                    <?php endif; ?>
                    <th>Unit</th>
                    <th>Bed / Bath</th>
                    <th>Size</th>
                    <th>Monthly Rent</th>
                    <th>Status</th>
                    <th>Current Tenant</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="units-table-body" data-reorder-url="<?= Router::url('/properties/' . $property['id'] . '/units/reorder') ?>">
                <?php if (empty($filteredUnits)): ?>
                    <tr>
                        <td colspan="<?= $unitTableColspan ?>" class="text-center text-muted" style="padding: 40px;">
                            <?php if ($unitFilter !== 'all' && $totalUnitsCount > 0): ?>
                                No <?= htmlspecialchars($unitFilter) ?> units found at this property.
                                <br><br>
                                <a href="<?= Router::url('/properties/' . $property['id']) ?>" class="btn btn-outline btn-sm">Show All Units</a>
                            <?php else: ?>
                                No units added to this property yet.
                                <br><br>
                                <a href="<?= Router::url('/properties/' . $property['id'] . '/units/create') ?>" class="btn btn-primary btn-sm">Add First Unit</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filteredUnits as $unit): ?>
                        <tr class="unit-row" data-unit-id="<?= (int)$unit['id'] ?>" onclick="window.location.href='<?= Router::url('/units/' . $unit['id']) ?>'" style="cursor: pointer;" title="Click to view unit details">
                            <?php if ($canReorderUnits): ?>
                                <td class="unit-drag-cell" onclick="event.stopPropagation();" style="width: 36px; padding-right: 0; cursor: default;">
                                    <span class="unit-drag-handle" title="Drag to reorder" aria-label="Drag to reorder">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <circle cx="9" cy="6" r="1.8"></circle><circle cx="15" cy="6" r="1.8"></circle>
                                            <circle cx="9" cy="12" r="1.8"></circle><circle cx="15" cy="12" r="1.8"></circle>
                                            <circle cx="9" cy="18" r="1.8"></circle><circle cx="15" cy="18" r="1.8"></circle>
                                        </svg>
                                    </span>
                                </td>
                            <?php endif; ?>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php
                                    $uPhotos = !empty($unit['photos']) ? json_decode($unit['photos'], true) : [];
                                    ?>
                                    <?php if (!empty($uPhotos) && is_array($uPhotos)): ?>
                                        <img src="<?= Router::url($uPhotos[0]) ?>" alt="Unit photo" style="width: 38px; height: 38px; border-radius: 8px; object-fit: cover; border: 1px solid var(--ws-border-subtle); flex-shrink: 0;">
                                    <?php endif; ?>
                                    <div>
                                        <a href="<?= Router::url('/units/' . $unit['id']) ?>" style="font-weight: 500; color: var(--ws-text); text-decoration: none;">Unit <?= htmlspecialchars($unit['unit_number']) ?></a>
                                        <?php if (!empty($uPhotos) && count($uPhotos) > 1): ?>
                                            <div class="text-muted text-xs"><?= count($uPhotos) ?> photos</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= (int)$unit['bedrooms'] ?> bd &bull; <?= htmlspecialchars($unit['bathrooms']) ?> ba</td>
                            <td><?= $unit['square_feet'] ? number_format($unit['square_feet']) . ' sq ft' : '—' ?></td>
                            <td class="font-bold">$<?= number_format((float)$unit['target_rent'], 2) ?>/mo</td>
                            <td>
                                <?php
                                $unitStatusMap = [
                                    'occupied'    => 'badge-success',
                                    'vacant'      => 'badge-warning',
                                    'maintenance' => 'badge-danger',
                                ];
                                $statusClass = $unitStatusMap[$unit['status']] ?? 'badge-subtle';
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= ucfirst($unit['status']) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($unit['current_tenant_name'])): ?>
                                    <span style="font-weight: 500; color: var(--ws-text);">
                                        <?= htmlspecialchars($unit['current_tenant_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted text-xs">No active lease</span>
                                <?php endif; ?>
                            </td>
                            <td onclick="event.stopPropagation();">
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <a href="<?= Router::url('/units/' . $unit['id']) ?>" 
                                       class="btn btn-outline btn-xs" 
                                       title="View Unit Details"
                                       aria-label="View Unit Details"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <a href="<?= Router::url('/units/' . $unit['id'] . '/edit') ?>" 
                                       class="btn btn-outline btn-xs" 
                                       title="Edit Unit"
                                       aria-label="Edit Unit"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </a>
                                    <form action="<?= Router::url('/units/' . $unit['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete Unit <?= htmlspecialchars($unit['unit_number']) ?>?');" style="display:inline; margin:0;">
                                        <button type="submit" 
                                                class="btn btn-outline btn-danger btn-xs" 
                                                title="Delete Unit"
                                                aria-label="Delete Unit"
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer" style="padding: 12px 22px; background-color: var(--card-footer-bg, #faf9f6); border-top: 1px solid var(--ws-border-subtle, #ebe7df); display: flex; align-items: center; justify-content: space-between; font-size: 13px; color: var(--ws-text-muted, #737373);">
        <span>Total Units: <strong style="color: var(--ws-text, #111111);"><?= $totalUnitsCount ?> <?= $totalUnitsCount === 1 ? 'Unit' : 'Units' ?></strong></span>
        <span>
            <strong class="text-success"><?= $occupiedUnitsCount ?> Occupied</strong>
            &bull;
            <strong class="text-warning"><?= $vacantUnitsCount ?> Vacant</strong>
            <?php if ($maintenanceUnitsCount > 0): ?>
                &bull;
                <strong class="text-danger"><?= $maintenanceUnitsCount ?> Maintenance</strong>
            <?php endif; ?>
        </span>
    </div>
</div>


<?php if ($canReorderUnits): ?>
<style>
    .unit-drag-handle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 30px;
        border-radius: 6px;
        color: var(--ws-text-muted, #8c867b);
        cursor: grab;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .unit-drag-handle:hover {
        background: var(--table-th-bg, #ece8df);
        color: var(--ws-text, #111111);
    }
    .unit-drag-handle:active {
        cursor: grabbing;
    }
    .unit-row.is-dragging {
        opacity: 0.45;
    }
    .unit-row.is-drop-target td {
        box-shadow: inset 0 2px 0 var(--ws-accent, #f5a623);
    }
    .unit-row.is-drop-target-after td {
        box-shadow: inset 0 -2px 0 var(--ws-accent, #f5a623);
    }
    #unit-order-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        padding: 10px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        background: var(--ws-text, #111111);
        color: var(--ws-surface, #ffffff);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.18);
        opacity: 0;
        transform: translateY(8px);
        transition: opacity 0.2s ease, transform 0.2s ease;
        pointer-events: none;
        z-index: 1000;
    }
    #unit-order-toast.is-visible {
        opacity: 1;
        transform: translateY(0);
    }
    #unit-order-toast.is-error {
        background: #dc2626;
        color: #ffffff;
    }
</style>

<div id="unit-order-toast" role="status" aria-live="polite"></div>

<script>
(function() {
    const tbody = document.getElementById('units-table-body');
    if (!tbody) return;

    const reorderUrl = tbody.dataset.reorderUrl;
    let draggedRow = null;
    let initialOrder = '';
    let toastTimer = null;

    function getRows() {
        return Array.from(tbody.querySelectorAll('tr.unit-row'));
    }

    function currentOrder() {
        return getRows().map(r => r.dataset.unitId);
    }

    function clearDropMarkers() {
        getRows().forEach(r => r.classList.remove('is-drop-target', 'is-drop-target-after'));
    }

    function showToast(message, isError) {
        const toast = document.getElementById('unit-order-toast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.toggle('is-error', !!isError);
        toast.classList.add('is-visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('is-visible'), 2200);
    }

    function saveOrder(previousOrder) {
        const formData = new FormData();
        currentOrder().forEach(id => formData.append('order[]', id));

        fetch(reorderUrl, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    showToast('Unit order saved');
                } else {
                    throw new Error((data && data.message) || 'Could not save unit order.');
                }
            })
            .catch(err => {
                // Restore previous order on failure
                previousOrder.forEach(id => {
                    const row = tbody.querySelector('tr.unit-row[data-unit-id="' + id + '"]');
                    if (row) tbody.appendChild(row);
                });
                showToast(err.message || 'Could not save unit order.', true);
            });
    }

    // Only allow dragging when the grip handle is pressed, so normal row clicks still open Unit Details
    tbody.addEventListener('mousedown', function(e) {
        const handle = e.target.closest('.unit-drag-handle');
        const row = e.target.closest('tr.unit-row');
        if (!row) return;
        row.draggable = !!handle;
    });

    tbody.addEventListener('dragstart', function(e) {
        const row = e.target.closest('tr.unit-row');
        if (!row || !row.draggable) {
            e.preventDefault();
            return;
        }
        draggedRow = row;
        initialOrder = currentOrder().join(',');
        row.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', row.dataset.unitId);
    });

    tbody.addEventListener('dragover', function(e) {
        if (!draggedRow) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const target = e.target.closest('tr.unit-row');
        clearDropMarkers();
        if (!target || target === draggedRow) return;

        const rect = target.getBoundingClientRect();
        const after = (e.clientY - rect.top) > rect.height / 2;
        target.classList.add(after ? 'is-drop-target-after' : 'is-drop-target');
    });

    tbody.addEventListener('drop', function(e) {
        if (!draggedRow) return;
        e.preventDefault();

        const target = e.target.closest('tr.unit-row');
        if (target && target !== draggedRow) {
            const rect = target.getBoundingClientRect();
            const after = (e.clientY - rect.top) > rect.height / 2;
            tbody.insertBefore(draggedRow, after ? target.nextSibling : target);
        }
        clearDropMarkers();
    });

    tbody.addEventListener('dragend', function() {
        if (!draggedRow) return;
        draggedRow.classList.remove('is-dragging');
        draggedRow.draggable = false;
        clearDropMarkers();

        const newOrder = currentOrder().join(',');
        if (newOrder !== initialOrder) {
            saveOrder(initialOrder.split(','));
        }
        draggedRow = null;
    });
})();
</script>
<?php endif; ?>
