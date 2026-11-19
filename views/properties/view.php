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
                    <th>Unit #</th>
                    <th>Bed / Bath</th>
                    <th>Size</th>
                    <th>Monthly Rent</th>
                    <th>Status</th>
                    <th>Current Tenant</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filteredUnits)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 40px;">
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
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php
                                    $uPhotos = !empty($unit['photos']) ? json_decode($unit['photos'], true) : [];
                                    ?>
                                    <?php if (!empty($uPhotos) && is_array($uPhotos)): ?>
                                        <img src="<?= Router::url($uPhotos[0]) ?>" alt="Unit photo" style="width: 38px; height: 38px; border-radius: 8px; object-fit: cover; border: 1px solid var(--ws-border-subtle); flex-shrink: 0;">
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 500; color: var(--ws-text);">Unit <?= htmlspecialchars($unit['unit_number']) ?></div>
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
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
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
