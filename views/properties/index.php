<?php
use App\Core\Router;
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

<div class="card-grid">
    <?php if (empty($properties)): ?>
        <div class="card text-center" style="grid-column: 1 / -1; padding: 50px;">
            <p class="text-muted">No properties found in your database.</p>
            <a href="<?= Router::url('/properties/create') ?>" class="btn btn-primary" style="margin-top: 15px;">Create Your First Property</a>
        </div>
    <?php else: ?>
        <?php foreach ($properties as $prop): ?>
            <div class="card property-card">
                <div class="card-header" style="background-color: var(--table-th-dark, #d8d3c7); border-bottom: 1.5px solid var(--table-th-dark-border, #c4bcae); padding: 16px 20px;">
                    <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--table-th-dark-text, var(--ws-text));">
                        <a href="<?= Router::url('/properties/' . $prop['id']) ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($prop['name']) ?></a>
                    </h2>
                </div>
                <div class="property-card-body">
                    <div class="property-card-top">
                        <div class="property-card-info">
                            <div class="property-type-tag"><?= strtoupper(str_replace('_', ' ', $prop['property_type'])) ?></div>
                            <p class="property-address">
                                <?= htmlspecialchars($prop['address_line1']) ?><?= $prop['address_line2'] ? ', ' . htmlspecialchars($prop['address_line2']) : '' ?><br>
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

                    <div class="property-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding-top: 16px; border-top: 1px solid var(--ws-border-subtle);">
                        <div style="padding: 10px 8px; border-radius: 10px; text-align: center; background: var(--ws-surface-subtle, #faf9f6); border: 1px solid var(--ws-border, #ebe7df);">
                            <div style="font-size: 18px; font-weight: 800; line-height: 1.2; color: var(--ws-text, #111111);"><?= (int)$prop['total_units'] ?></div>
                            <div style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--ws-text-muted, #737373); margin-top: 2px;">Units</div>
                        </div>
                        <div style="padding: 10px 8px; border-radius: 10px; text-align: center; background: var(--ws-success-bg, #eaf7ee); border: 1px solid var(--ws-success-border, #c6edd1);">
                            <div style="font-size: 18px; font-weight: 800; line-height: 1.2; color: var(--ws-success, #15803d);"><?= (int)$prop['occupied_units'] ?></div>
                            <div style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--ws-success, #15803d); margin-top: 2px;">Occupied</div>
                        </div>
                        <div style="padding: 10px 8px; border-radius: 10px; text-align: center; background: var(--ws-warning-bg, #fff6e5); border: 1px solid var(--ws-warning-border, #fed7aa);">
                            <div style="font-size: 18px; font-weight: 800; line-height: 1.2; color: var(--ws-warning, #b45309);"><?= (int)$prop['vacant_units'] ?></div>
                            <div style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--ws-warning, #b45309); margin-top: 2px;">Vacant</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <span class="text-muted text-xs">Manager: <?= htmlspecialchars($prop['manager_name'] ?? 'Unassigned') ?></span>
                    <a href="<?= Router::url('/properties/' . $prop['id']) ?>" class="btn btn-outline btn-sm">Manage</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
