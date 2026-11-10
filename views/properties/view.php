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
            <div class="badge badge-subtle"><?= strtoupper(str_replace('_', ' ', $property['property_type'])) ?></div>
            <h1 class="page-title" style="margin-top: 5px;"><?= htmlspecialchars($property['name']) ?></h1>
            <p class="page-subtitle">
                📍 <?= htmlspecialchars($property['address_line1']) ?><?= $property['address_line2'] ? ', ' . htmlspecialchars($property['address_line2']) : '' ?>,
                <?= htmlspecialchars($property['city']) ?>, <?= htmlspecialchars($property['state']) ?> <?= htmlspecialchars($property['zip_code']) ?>
            </p>
        </div>
    </div>
    <div class="action-buttons">
        <a href="<?= Router::url('/properties/' . $property['id'] . '/edit') ?>" class="btn btn-outline">Edit Property</a>
        <a href="<?= Router::url('/properties/' . $property['id'] . '/units/create') ?>" class="btn btn-primary">+ Add Unit</a>
        <form action="<?= Router::url('/properties/' . $property['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this property and all its units?');" style="display:inline;">
            <button type="submit" class="btn btn-danger btn-outline">Delete Property</button>
        </form>
    </div>
</div>

<?php if (!empty($property['description'])): ?>
    <div class="card" style="margin-bottom: 25px;">
        <div class="card-body">
            <h4 style="margin-top: 0; color: var(--text-muted); font-size: 13px; text-transform: uppercase;">Overview & Notes</h4>
            <p style="margin: 0;"><?= nl2br(htmlspecialchars($property['description'])) ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Units Section -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Units & Floorplans (<?= count($units) ?>)</h2>
            <p class="text-muted text-xs">Manage individual apartment doors, tenant occupancy, and rental rates</p>
        </div>
        <a href="<?= Router::url('/properties/' . $property['id'] . '/units/create') ?>" class="btn btn-outline btn-sm">+ New Unit</a>
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
                <?php if (empty($units)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 40px;">
                            No units added to this property yet.
                            <br><br>
                            <a href="<?= Router::url('/properties/' . $property['id'] . '/units/create') ?>" class="btn btn-primary btn-sm">Add First Unit</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($units as $unit): ?>
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
                                        <strong>Unit <?= htmlspecialchars($unit['unit_number']) ?></strong>
                                        <?php if (!empty($uPhotos) && count($uPhotos) > 1): ?>
                                            <div class="text-muted text-xs">📷 <?= count($uPhotos) ?> photos</div>
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
                                    <span class="font-medium text-primary">
                                        👥 <?= htmlspecialchars($unit['current_tenant_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted text-xs">No active lease</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <a href="<?= Router::url('/units/' . $unit['id'] . '/edit') ?>" class="btn btn-outline btn-xs">Edit</a>
                                    <form action="<?= Router::url('/units/' . $unit['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete Unit <?= htmlspecialchars($unit['unit_number']) ?>?');" style="display:inline;">
                                        <button type="submit" class="btn btn-outline btn-danger btn-xs">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
