<?php
use App\Core\Router;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Property Portfolios</h1>
        <p class="page-subtitle">Manage buildings, residential complexes, and commercial rental facilities</p>
    </div>
    <div>
        <a href="<?= Router::url('/properties/create') ?>" class="btn btn-primary">+ Add New Property</a>
    </div>
</div>

<div class="card-grid">
    <?php if (empty($properties)): ?>
        <div class="card text-center" style="grid-column: 1 / -1; padding: 50px;">
            <p class="text-muted">No properties found in your database.</p>
            <a href="<?= Router::url('/properties/create') ?>" class="btn btn-primary" style="margin-top: 15px;">Create Your First Property</a>
        </div>
    <?php else: ?>
        <?php foreach ($properties as $prop): ?>
            <div class="card property-card">
                <div class="property-card-body">
                    <div class="property-card-top">
                        <div class="property-card-info">
                            <div class="property-type-tag"><?= strtoupper(str_replace('_', ' ', $prop['property_type'])) ?></div>
                            <h2 class="property-title">
                                <a href="<?= Router::url('/properties/' . $prop['id']) ?>"><?= htmlspecialchars($prop['name']) ?></a>
                            </h2>
                            <p class="property-address">
                                📍 <?= htmlspecialchars($prop['address_line1']) ?><?= $prop['address_line2'] ? ', ' . htmlspecialchars($prop['address_line2']) : '' ?><br>
                                <?= htmlspecialchars($prop['city']) ?>, <?= htmlspecialchars($prop['state']) ?> <?= htmlspecialchars($prop['zip_code']) ?>
                            </p>
                        </div>
                        <div class="property-card-photo">
                            <?php if (!empty($prop['photo_path'])): ?>
                                <img src="<?= Router::url($prop['photo_path']) ?>" alt="<?= htmlspecialchars($prop['name']) ?>" class="property-thumbnail">
                            <?php else: ?>
                                <div class="property-thumbnail-placeholder">📷</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="property-stats">
                        <div class="prop-stat-item">
                            <span class="stat-num"><?= (int)$prop['total_units'] ?></span>
                            <span class="stat-label">Units</span>
                        </div>
                        <div class="prop-stat-item">
                            <span class="stat-num text-success"><?= (int)$prop['occupied_units'] ?></span>
                            <span class="stat-label">Occupied</span>
                        </div>
                        <div class="prop-stat-item">
                            <span class="stat-num text-warning"><?= (int)$prop['vacant_units'] ?></span>
                            <span class="stat-label">Vacant</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <span class="text-muted text-xs">Manager: <?= htmlspecialchars($prop['manager_name'] ?? 'Unassigned') ?></span>
                    <a href="<?= Router::url('/properties/' . $prop['id']) ?>" class="btn btn-outline btn-sm">Manage &rarr;</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
