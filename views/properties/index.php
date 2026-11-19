<?php
use App\Core\Router;

$typeFilter = $_GET['type'] ?? 'all';
$viewMode = $_GET['view'] ?? 'cards';

$filteredProperties = [];
if (!empty($properties)) {
    foreach ($properties as $p) {
        if ($typeFilter === 'all' || ($p['property_type'] ?? '') === $typeFilter) {
            $filteredProperties[] = $p;
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Property Portfolios</h1>
        <p class="page-subtitle">Manage buildings, residential complexes, and commercial rental facilities</p>
    </div>
    <div>
        <a href="<?= Router::url('/properties/create') ?>" class="btn btn-outline">Add New Property</a>
    </div>
</div>

<!-- View Segmented Tabs -->
<div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <a href="<?= Router::url('/properties?type=all&view=' . urlencode($viewMode)) ?>" 
           class="btn <?= $typeFilter === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm" 
           style="border-radius: 9999px; padding: 6px 16px;">
            All Properties (<?= count($properties ?? []) ?>)
        </a>
        <a href="<?= Router::url('/properties?type=multi_family&view=' . urlencode($viewMode)) ?>" 
           class="btn <?= $typeFilter === 'multi_family' ? 'btn-primary' : 'btn-outline' ?> btn-sm" 
           style="border-radius: 9999px; padding: 6px 16px;">
            Multi-Family
        </a>
        <a href="<?= Router::url('/properties?type=single_family&view=' . urlencode($viewMode)) ?>" 
           class="btn <?= $typeFilter === 'single_family' ? 'btn-primary' : 'btn-outline' ?> btn-sm" 
           style="border-radius: 9999px; padding: 6px 16px;">
            Single Family
        </a>
        <a href="<?= Router::url('/properties?type=apartment&view=' . urlencode($viewMode)) ?>" 
           class="btn <?= $typeFilter === 'apartment' ? 'btn-primary' : 'btn-outline' ?> btn-sm" 
           style="border-radius: 9999px; padding: 6px 16px;">
            Apartment
        </a>
        <a href="<?= Router::url('/properties?type=commercial&view=' . urlencode($viewMode)) ?>" 
           class="btn <?= $typeFilter === 'commercial' ? 'btn-primary' : 'btn-outline' ?> btn-sm" 
           style="border-radius: 9999px; padding: 6px 16px;">
            Commercial
        </a>
    </div>

    <div style="display: flex; gap: 6px; align-items: center;">
        <a href="<?= Router::url('/properties?type=' . urlencode($typeFilter) . '&view=cards') ?>" 
           class="btn <?= $viewMode !== 'table' ? 'btn-primary' : 'btn-outline' ?> btn-sm" 
           style="border-radius: 9999px; padding: 6px 14px;">
            Portfolio Cards
        </a>
        <a href="<?= Router::url('/properties?type=' . urlencode($typeFilter) . '&view=table') ?>" 
           class="btn <?= $viewMode === 'table' ? 'btn-primary' : 'btn-outline' ?> btn-sm" 
           style="border-radius: 9999px; padding: 6px 14px;">
            Table View
        </a>
    </div>
</div>

<?php if (empty($filteredProperties)): ?>
    <div class="card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <div class="card-header" style="background-color: var(--table-th-dark, #d8d3c7); border-bottom: 1.5px solid var(--table-th-dark-border, #c4bcae); padding: 16px 20px;">
            <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--table-th-dark-text, var(--ws-text));">
                Property Portfolios
            </h2>
        </div>
        <div class="card-body text-center text-muted" style="padding: 44px 20px;">
            No properties match the selected filter.
            <br><br>
            <?php if ($typeFilter !== 'all'): ?>
                <a href="<?= Router::url('/properties') ?>" class="btn btn-outline btn-sm">Show All Properties</a>
            <?php else: ?>
                <a href="<?= Router::url('/properties/create') ?>" class="btn btn-primary btn-sm">Add New Property</a>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($viewMode === 'table'): ?>
    <!-- Standardized Data Table View -->
    <div class="card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <div class="card-header" style="background-color: var(--table-th-dark, #d8d3c7); border-bottom: 1.5px solid var(--table-th-dark-border, #c4bcae); padding: 16px 20px;">
            <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--table-th-dark-text, var(--ws-text));">
                Property Portfolios
            </h2>
        </div>
        <div class="table-responsive" style="background-color: var(--ws-bg);">
            <table class="table">
                <thead>
                    <tr>
                        <th>Property Name</th>
                        <th>Type</th>
                        <th>Address</th>
                        <th>Units</th>
                        <th>Occupancy</th>
                        <th>Manager</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredProperties as $prop): ?>
                        <tr>
                            <td>
                                <a href="<?= Router::url('/properties/' . $prop['id']) ?>" style="font-weight: 500; color: var(--ws-text); text-decoration: none;">
                                    <?= htmlspecialchars($prop['name']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-subtle">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $prop['property_type']))) ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 500; color: var(--ws-text);">
                                    <?= htmlspecialchars($prop['address_line1']) ?><?= $prop['address_line2'] ? ', ' . htmlspecialchars($prop['address_line2']) : '' ?>
                                </div>
                                <div class="text-muted text-xs">
                                    <?= htmlspecialchars($prop['city']) ?>, <?= htmlspecialchars($prop['state']) ?> <?= htmlspecialchars($prop['zip_code']) ?>
                                </div>
                            </td>
                            <td style="font-weight: 500; color: var(--ws-text);">
                                <?= (int)$prop['total_units'] ?> Total
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                    <span class="badge badge-success"><?= (int)$prop['occupied_units'] ?> Occupied</span>
                                    <?php if ((int)$prop['vacant_units'] > 0): ?>
                                        <span class="badge badge-warning"><?= (int)$prop['vacant_units'] ?> Vacant</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 500; color: var(--ws-text);">
                                    <?= htmlspecialchars($prop['manager_name'] ?? 'Unassigned') ?>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <a href="<?= Router::url('/properties/' . $prop['id']) ?>" 
                                       class="btn btn-outline btn-xs" 
                                       title="View Property & Units"
                                       aria-label="View Property & Units"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                    <a href="<?= Router::url('/properties/' . $prop['id'] . '/edit') ?>" 
                                       class="btn btn-outline btn-xs" 
                                       title="Edit Property"
                                       aria-label="Edit Property"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </a>
                                    <form action="<?= Router::url('/properties/' . $prop['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete property <?= htmlspecialchars(addslashes($prop['name'])) ?> and all its units?');" style="display:inline; margin:0;">
                                        <button type="submit" 
                                                class="btn btn-outline btn-danger btn-xs" 
                                                title="Delete Property"
                                                aria-label="Delete Property"
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
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <!-- Standardized Portfolio Cards Grid -->
    <div class="card-grid">
        <?php foreach ($filteredProperties as $prop): ?>
            <div class="card property-card" style="border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; flex-direction: column;">
                
                <!-- 1. Distinguished Card Header (--table-th-dark #D8D3C7) -->
                <div class="card-header" style="background-color: var(--table-th-dark, #d8d3c7); border-bottom: 1.5px solid var(--table-th-dark-border, #c4bcae); padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                    <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--table-th-dark-text, var(--ws-text)); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <a href="<?= Router::url('/properties/' . $prop['id']) ?>" style="color: inherit; text-decoration: none;">
                            <?= htmlspecialchars($prop['name']) ?>
                        </a>
                    </h2>
                    <span class="badge badge-subtle" style="background: var(--ws-surface); border: 1px solid var(--ws-border-strong); color: var(--ws-text); font-size: 11px;">
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $prop['property_type']))) ?>
                    </span>
                </div>

                <!-- 2. Secondary Column Header Metrics Strip (--table-th-bg #ECE8DF) -->
                <div style="background-color: var(--table-th-bg, #ece8df); border-bottom: 1.5px solid var(--table-th-border, #ded8cd); padding: 10px 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                    <div>
                        <div style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: var(--table-th-text, #423e37);">Total Units</div>
                        <div style="font-size: 15px; font-weight: 700; color: var(--ws-text); margin-top: 1px;"><?= (int)$prop['total_units'] ?></div>
                    </div>
                    <div>
                        <div style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: var(--table-th-text, #423e37);">Occupied</div>
                        <div class="text-success" style="font-size: 15px; font-weight: 700; margin-top: 1px;"><?= (int)$prop['occupied_units'] ?></div>
                    </div>
                    <div>
                        <div style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: var(--table-th-text, #423e37);">Vacant</div>
                        <div class="text-warning" style="font-size: 15px; font-weight: 700; margin-top: 1px;"><?= (int)$prop['vacant_units'] ?></div>
                    </div>
                </div>

                <!-- 3. Card Body (Address, Manager & Thumbnail) -->
                <div class="property-card-body" style="padding: 18px 20px; flex: 1; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                    <div style="min-width: 0; flex: 1;">
                        <div class="text-muted text-xs" style="text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-bottom: 3px;">Property Address</div>
                        <div style="font-weight: 500; font-size: 14.5px; color: var(--ws-text); line-height: 1.4;">
                            <?= htmlspecialchars($prop['address_line1']) ?><?= $prop['address_line2'] ? ', ' . htmlspecialchars($prop['address_line2']) : '' ?>
                        </div>
                        <div class="text-muted text-xs" style="margin-top: 2px;">
                            <?= htmlspecialchars($prop['city']) ?>, <?= htmlspecialchars($prop['state']) ?> <?= htmlspecialchars($prop['zip_code']) ?>
                        </div>

                        <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--ws-border-subtle); display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                            <span class="text-muted text-xs">Assigned Manager</span>
                            <span style="font-size: 13px; font-weight: 500; color: var(--ws-text);"><?= htmlspecialchars($prop['manager_name'] ?? 'Unassigned') ?></span>
                        </div>
                    </div>

                    <div class="property-card-photo" style="flex-shrink: 0;">
                        <?php if (!empty($prop['photo_path'])): ?>
                            <img src="<?= Router::url($prop['photo_path']) ?>" alt="<?= htmlspecialchars($prop['name']) ?>" class="property-thumbnail" style="width: 72px; height: 72px; border-radius: 12px; object-fit: cover; border: 1.5px solid var(--ws-border);">
                        <?php else: ?>
                            <div style="width: 72px; height: 72px; border-radius: 12px; background: var(--ws-surface-subtle); border: 1.5px solid var(--ws-border); display: flex; align-items: center; justify-content: center; color: var(--ws-text-muted);">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                                    <path d="M9 22v-4h6v4"></path>
                                    <path d="M8 6h.01"></path><path d="M16 6h.01"></path>
                                    <path d="M12 6h.01"></path><path d="M12 10h.01"></path>
                                    <path d="M12 14h.01"></path><path d="M16 10h.01"></path>
                                    <path d="M16 14h.01"></path><path d="M8 10h.01"></path>
                                    <path d="M8 14h.01"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4. Card Footer (Manage Units Button & 30x30px Action Icon Controls) -->
                <div class="card-footer" style="padding: 12px 20px; background-color: var(--card-footer-bg); border-top: 1px solid var(--ws-border-subtle); display: flex; align-items: center; justify-content: space-between;">
                    <a href="<?= Router::url('/properties/' . $prop['id']) ?>" class="btn btn-outline btn-xs">
                        Manage Units
                    </a>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <a href="<?= Router::url('/properties/' . $prop['id']) ?>" 
                           class="btn btn-outline btn-xs" 
                           title="View Property Details"
                           aria-label="View Property Details"
                           style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </a>
                        <a href="<?= Router::url('/properties/' . $prop['id'] . '/edit') ?>" 
                           class="btn btn-outline btn-xs" 
                           title="Edit Property"
                           aria-label="Edit Property"
                           style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                        <form action="<?= Router::url('/properties/' . $prop['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete property <?= htmlspecialchars(addslashes($prop['name'])) ?> and all its units?');" style="display:inline; margin:0;">
                            <button type="submit" 
                                    class="btn btn-outline btn-danger btn-xs" 
                                    title="Delete Property"
                                    aria-label="Delete Property"
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
                </div>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
