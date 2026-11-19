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
                        <?php else: 
                            $allPropUnits = $prop['units'];
                            $previewUnits = array_slice($allPropUnits, 0, 2);
                            $remainingUnitsCount = count($allPropUnits) - count($previewUnits);
                        ?>
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <?php foreach ($previewUnits as $u): 
                                    $uStatus = $u['status'] ?? 'vacant';
                                    $uBadgeClass = ($uStatus === 'occupied') ? 'badge-success' : (($uStatus === 'maintenance') ? 'badge-danger' : 'badge-warning');
                                    $uBadgeLabel = ucfirst($uStatus);
                                    $uDisplayName = is_numeric($u['unit_number']) ? 'Unit ' . $u['unit_number'] : $u['unit_number'];
                                    $uBaths = rtrim(rtrim(number_format((float)$u['bathrooms'], 1), '0'), '.');
                                ?>
                                    <a href="<?= Router::url('/units/' . $u['id'] . '/edit') ?>" 
                                       style="display: grid; grid-template-columns: minmax(68px, 1fr) 68px 78px 88px; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 10px; background: var(--ws-surface-subtle, #faf9f6); border: 1px solid var(--ws-border, #ebe7df); text-decoration: none; transition: background-color 0.15s ease;"
                                       onmouseover="this.style.backgroundColor='var(--ws-raised, #f1efe9)'"
                                       onmouseout="this.style.backgroundColor='var(--ws-surface-subtle, #faf9f6)'">
                                        <span style="font-size: 13px; font-weight: 700; color: var(--ws-text, #111111); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: left;">
                                            <?= htmlspecialchars($uDisplayName) ?>
                                        </span>
                                        <span class="text-muted text-xs" style="white-space: nowrap; text-align: left;">
                                            <?= (int)$u['bedrooms'] ?> bd &bull; <?= htmlspecialchars($uBaths) ?> ba
                                        </span>
                                        <span style="font-size: 13px; font-weight: 700; color: var(--ws-text, #111111); white-space: nowrap; text-align: left; font-variant-numeric: tabular-nums;">
                                            $<?= number_format((float)$u['target_rent'], 0) ?>/mo
                                        </span>
                                        <span style="display: flex; justify-content: flex-start;">
                                            <span class="badge <?= $uBadgeClass ?>" style="font-size: 10.5px; padding: 2px 9px;">
                                                <?= htmlspecialchars($uBadgeLabel) ?>
                                            </span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>

                                <?php if ($remainingUnitsCount > 0): ?>
                                    <a href="<?= Router::url('/properties/' . $prop['id']) ?>"
                                       style="display: flex; align-items: center; justify-content: center; padding: 7px 12px; border-radius: 10px; background: var(--ws-surface-subtle, #faf9f6); border: 1px dashed var(--ws-border-strong, #d8d3c7); text-decoration: none; font-size: 12px; font-weight: 600; color: var(--ws-text-secondary, #4a4a4a); transition: background-color 0.15s ease, color 0.15s ease;"
                                       onmouseover="this.style.backgroundColor='var(--ws-raised, #f1efe9)'; this.style.color='var(--ws-text, #111111)'"
                                       onmouseout="this.style.backgroundColor='var(--ws-surface-subtle, #faf9f6)'; this.style.color='var(--ws-text-secondary, #4a4a4a)'">
                                        ... +<?= $remainingUnitsCount ?> more <?= $remainingUnitsCount === 1 ? 'unit' : 'units' ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <span class="text-muted text-xs">Manager: <?= htmlspecialchars($prop['manager_name'] ?? 'Unassigned') ?></span>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <a href="<?= Router::url('/properties/' . $prop['id']) ?>" 
                           class="btn btn-outline btn-xs" 
                           title="View Property"
                           aria-label="View Property"
                           style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </a>
                        <form action="<?= Router::url('/properties/' . $prop['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this property and all its units?');" style="display:inline; margin:0;">
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
    <?php endif; ?>
</div>
