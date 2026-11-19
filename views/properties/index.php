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

                    <div style="padding-top: 14px; border-top: 1px solid var(--ws-border-subtle);">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--ws-text-muted, #737373); margin-bottom: 8px;">
                            Available Unit Types
                        </div>
                        <?php if (empty($prop['units'])): ?>
                            <div style="padding: 10px 12px; border-radius: 10px; background: var(--ws-surface-subtle, #faf9f6); border: 1px solid var(--ws-border, #ebe7df); display: flex; align-items: center; justify-content: space-between;">
                                <span class="text-muted text-xs">No units registered yet</span>
                                <a href="<?= Router::url('/properties/' . $prop['id'] . '/units/create') ?>" class="btn btn-outline btn-xs">Add Unit</a>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <?php foreach ($prop['units'] as $u): 
                                    $uStatus = $u['status'] ?? 'vacant';
                                    $uBadgeClass = ($uStatus === 'occupied') ? 'badge-success' : (($uStatus === 'maintenance') ? 'badge-danger' : 'badge-warning');
                                    $uBadgeLabel = ucfirst($uStatus);
                                    $uDisplayName = is_numeric($u['unit_number']) ? 'Unit ' . $u['unit_number'] : $u['unit_number'];
                                    $uBaths = rtrim(rtrim(number_format((float)$u['bathrooms'], 1), '0'), '.');
                                ?>
                                    <a href="<?= Router::url('/units/' . $u['id'] . '/edit') ?>" 
                                       style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 8px 12px; border-radius: 10px; background: var(--ws-surface-subtle, #faf9f6); border: 1px solid var(--ws-border, #ebe7df); text-decoration: none; transition: background-color 0.15s ease;"
                                       onmouseover="this.style.backgroundColor='var(--ws-raised, #f1efe9)'"
                                       onmouseout="this.style.backgroundColor='var(--ws-surface-subtle, #faf9f6)'">
                                        <div style="min-width: 0;">
                                            <span style="font-size: 13px; font-weight: 700; color: var(--ws-text, #111111);">
                                                <?= htmlspecialchars($uDisplayName) ?>
                                            </span>
                                            <span class="text-muted text-xs" style="margin-left: 6px;">
                                                <?= (int)$u['bedrooms'] ?> bd &bull; <?= htmlspecialchars($uBaths) ?> ba
                                            </span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                            <span style="font-size: 13px; font-weight: 700; color: var(--ws-text, #111111);">
                                                $<?= number_format((float)$u['target_rent'], 0) ?>/mo
                                            </span>
                                            <span class="badge <?= $uBadgeClass ?>" style="font-size: 10.5px; padding: 2px 9px;">
                                                <?= htmlspecialchars($uBadgeLabel) ?>
                                            </span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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
