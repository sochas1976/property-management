<?php
use App\Core\Router;
use App\Core\AppDate;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Operations Dashboard</h1>
        <p class="page-subtitle">Real-time overview of your real estate portfolio, occupancy, and cash flow</p>
    </div>
    <div class="action-buttons">
        <button type="button" class="btn btn-outline" onclick="togglePaletteModal(true)">Design Palette</button>
        <a href="<?= Router::url('/properties/create') ?>" class="btn btn-outline">Add Property</a>
        <a href="<?= Router::url('/leases/create') ?>" class="btn btn-primary">New Lease</a>
    </div>
</div>

<!-- KPI Cards Grid -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Properties</span>
            <span class="metric-icon">🏘️</span>
        </div>
        <div class="metric-value"><?= (int)$propertiesCount ?></div>
        <div class="metric-footer">Active across all portfolios</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Occupancy Rate</span>
            <span class="metric-icon">📊</span>
        </div>
        <div class="metric-value"><?= $occupancyRate ?>%</div>
        <div class="metric-footer">
            <span class="text-success font-medium"><?= (int)$occupiedUnits ?> Occupied</span> of <?= (int)$totalUnits ?> Total Units
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Rent Collected</span>
            <span class="metric-icon">💵</span>
        </div>
        <div class="metric-value text-success">$<?= number_format($financialTotals['total_collected'], 2) ?></div>
        <div class="metric-footer">
            $<?= number_format($financialTotals['total_pending'], 2) ?> pending invoices
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Open Tickets</span>
            <span class="metric-icon">🔧</span>
        </div>
        <div class="metric-value <?= $openTicketsCount > 0 ? 'text-warning' : 'text-success' ?>">
            <?= (int)$openTicketsCount ?>
        </div>
        <div class="metric-footer">Maintenance requests in queue</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Applicants</span>
            <span class="metric-icon">📋</span>
        </div>
        <div class="metric-value <?= $pendingApplicants > 0 ? 'text-warning' : '' ?>">
            <?= (int)$pendingApplicants ?>
        </div>
        <div class="metric-footer"><a href="<?= Router::url('/applicants') ?>" style="color: inherit; text-decoration: underline;">Pending applications</a></div>
    </div>
</div>

<div class="dashboard-columns">
    <!-- Lease Expiry Dates Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Lease Expiry Dates</h2>
            <a href="<?= Router::url('/leases') ?>" class="btn btn-outline btn-xs">View All Leases</a>
        </div>
        <div class="table-responsive" style="overflow-x: hidden;">
            <table class="table" style="table-layout: auto; width: 100%; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="padding: 12px 10px 12px 18px; white-space: nowrap; width: 1%;">Status</th>
                        <th style="padding: 12px 10px; white-space: nowrap; width: 1%;">Expiry Date</th>
                        <th style="padding: 12px 10px; white-space: nowrap;">Tenant</th>
                        <th style="padding: 12px 18px 12px 10px;">Property & Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expiringLeases)): ?>
                        <tr><td colspan="4" class="text-center text-muted" style="padding: 25px;">No active leases recorded yet.</td></tr>
                    <?php else: ?>
                        <?php 
                        $today = AppDate::today();
                        foreach ($expiringLeases as $l): 
                            $endDate = !empty($l['end_date']) ? new DateTime($l['end_date']) : null;
                            $daysLeft = null;
                            if ($endDate) {
                                $diff = $today->diff($endDate);
                                $daysLeft = $diff->invert ? -$diff->days : $diff->days;
                            }
                            $isExpiringSoon = ($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 45);
                            $isExpired = ($daysLeft !== null && $daysLeft < 0);
                        ?>
                            <tr onclick="window.location.href='<?= Router::url('/leases/' . $l['id']) ?>'" style="cursor: pointer;" title="Click to view lease agreement">
                                <td style="padding: 12px 10px 12px 18px; white-space: nowrap;">
                                    <?php if ($isExpired): ?>
                                        <span class="badge badge-warning">Expired</span>
                                    <?php elseif ($isExpiringSoon || $l['status'] === 'expiring_soon'): ?>
                                        <span class="badge badge-warning">Expiring Soon</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 10px; white-space: nowrap;">
                                    <strong><?= date('M j, Y', strtotime($l['end_date'])) ?></strong>
                                    <?php if ($daysLeft !== null): ?>
                                        <div class="text-muted text-xs" style="white-space: nowrap;">
                                            <?= $daysLeft === 0 ? 'Expires today' : ($daysLeft === 1 ? '1 day left' : ($daysLeft > 1 ? $daysLeft . ' days left' : abs($daysLeft) . ' days ago')) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 10px; white-space: nowrap;">
                                    <strong><?= htmlspecialchars($l['tenant_name']) ?></strong>
                                </td>
                                <td style="padding: 12px 18px 12px 10px;">
                                    <div style="font-weight: 600; line-height: 1.25;"><?= htmlspecialchars($l['property_name']) ?></div>
                                    <div class="text-muted text-xs">Unit <?= htmlspecialchars($l['unit_number']) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Maintenance Tickets Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Urgent Maintenance Tickets</h2>
            <a href="<?= Router::url('/maintenance') ?>" class="btn btn-outline btn-xs">Manage Work Orders</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Priority</th>
                        <th>Issue</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTickets)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No open maintenance tickets. Everything in order!</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentTickets as $ticket): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $pClassMap = ['emergency' => 'badge-danger', 'high' => 'badge-warning', 'medium' => 'badge-info'];
                                    $pClass = $pClassMap[$ticket['priority']] ?? 'badge-subtle';
                                    ?>
                                    <span class="badge <?= $pClass ?>"><?= ucfirst($ticket['priority']) ?></span>
                                </td>
                                <td>
                                    <div style="font-weight: 500; color: var(--ws-text);"><?= htmlspecialchars($ticket['title']) ?></div>
                                    <div class="text-muted text-xs"><?= htmlspecialchars(substr($ticket['description'], 0, 50)) ?>...</div>
                                </td>
                                <td>
                                    <div style="font-weight: 500; color: var(--ws-text);"><?= htmlspecialchars($ticket['property_name']) ?></div>
                                    <div class="text-muted text-xs">Unit #<?= htmlspecialchars($ticket['unit_number']) ?></div>
                                </td>
                                <td><span class="badge badge-subtle"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span></td>
                                <td>
                                    <a href="<?= Router::url('/maintenance/' . $ticket['id']) ?>" 
                                       class="btn btn-outline btn-xs" 
                                       title="View Maintenance Ticket"
                                       aria-label="View Maintenance Ticket"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; border-radius: 8px;">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Interactive Design System Palette Modal -->
<style>
#designPaletteModal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background-color: var(--modal-overlay, rgba(18, 18, 18, 0.55));
    backdrop-filter: blur(5px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
#designPaletteModal.open {
    display: flex;
}
.pp-palette-modal-card {
    width: 100%;
    max-width: 1080px;
    max-height: 92vh;
    overflow-y: auto;
    border-radius: 22px;
    padding: 20px 22px 22px;
    background-color: var(--ws-surface, #ffffff);
    border: 1.5px solid var(--ws-border-strong, #d8d3c7);
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
}
.pp-board-light {
    --pb-bg: #f7f6f2;
    --pb-border: #d8d3c7;
    --pb-tile-bg: #ffffff;
    --pb-tile-border: #ebe7df;
    --pb-tile-raised: #f1efe9;
    --pb-text-main: #111111;
    --pb-text-sec: #4a4a4a;
    --pb-text-muted: #737373;
    --pb-th-dark: #d8d3c7;
    --pb-th-dark-border: #c4bcae;
    --pb-th-bg: #ece8df;
    --pb-th-border: #ded8cd;
    --pb-th-text: #423e37;
    --pb-input-bg: #ffffff;
    --pb-input-border: #dcd8d0;
    --pb-charcoal: #191919;
    --pb-charcoal-text: #ffffff;
}
.pp-board-dark {
    --pb-bg: #0e0f12;
    --pb-border: #282b33;
    --pb-tile-bg: #191b1f;
    --pb-tile-border: #2c3039;
    --pb-tile-raised: #282b33;
    --pb-text-main: #f5f6f8;
    --pb-text-sec: #a0a6b2;
    --pb-text-muted: #717784;
    --pb-th-dark: #181a20;
    --pb-th-dark-border: #282b33;
    --pb-th-bg: #21242b;
    --pb-th-border: #2c3039;
    --pb-th-text: #cfd3da;
    --pb-input-bg: #131417;
    --pb-input-border: #2e323b;
    --pb-charcoal: #f5f6f8;
    --pb-charcoal-text: #111111;
}
.pp-bento-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}
@media (max-width: 900px) {
    .pp-bento-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 560px) {
    .pp-bento-grid {
        grid-template-columns: 1fr;
    }
}
.pp-col {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 10px;
}
.pp-card-group {
    display: flex;
    flex-direction: column;
}
.pp-card-group.flex-1 {
    flex: 1;
}
.pp-outside-header {
    font-size: 11px;
    font-weight: 700;
    color: var(--pb-text-sec);
    margin-bottom: 4px;
    margin-left: 2px;
    line-height: 1.2;
    letter-spacing: 0.15px;
}
.pp-bento-tile {
    background-color: var(--pb-tile-bg);
    border: 1.5px solid var(--pb-tile-border);
    border-radius: 16px;
    padding: 14px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.2s ease;
}
.pp-swatch-card {
    border-radius: 16px;
    height: 82px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border: 1.5px solid rgba(0, 0, 0, 0.08);
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.pp-swatch-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}
.pp-ramp-bar {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    height: 24px;
    width: 100%;
}
</style>

<div id="designPaletteModal" onclick="if(event.target === this) togglePaletteModal(false);">
    <div class="pp-palette-modal-card">
        <div id="ppPaletteRoot" class="pp-board-light">
            <!-- Top Header Bar & Theme Switcher -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 10px; font-size: 12.5px; font-weight: 700; background: var(--pb-tile-raised); color: var(--pb-text-main); border: 1.5px solid var(--pb-border);">
                    <span id="ppBoardTitle">PropPulse PMS — Stylized Design Palette (Warm Light)</span>
                    <span id="ppCopyToast" style="display: none; margin-left: 6px; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; background: #f5a623; color: #ffffff;">Copied!</span>
                </div>

                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="display: inline-flex; align-items: center; gap: 4px; padding: 3px; border-radius: 999px; background: var(--pb-tile-raised); border: 1.5px solid var(--pb-border);">
                        <button type="button" id="ppBtnLight" onclick="setDashboardPaletteTheme('light')"
                                style="border: none; cursor: pointer; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #f5a623; color: #ffffff;">
                            Warm Light
                        </button>
                        <button type="button" id="ppBtnDark" onclick="setDashboardPaletteTheme('dark')"
                                style="border: none; cursor: pointer; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; background: transparent; color: var(--pb-text-sec);">
                            Obsidian Dark
                        </button>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="togglePaletteModal(false)">Close</button>
                </div>
            </div>

            <!-- Main Bento Board -->
            <div style="padding: 16px; border-radius: 20px; background-color: var(--pb-bg); border: 2.5px solid var(--pb-border);">
                <div class="pp-bento-grid">
                    
                    <!-- COLUMN 1: 4 Stacked Color Swatch Cards -->
                    <div class="pp-col">
                        <div class="pp-card-group">
                            <div class="pp-outside-header">Primary CTA &amp; Active Tab Accent</div>
                            <div class="pp-swatch-card" style="background-color: #F5A623; color: #111111;" onclick="copyPaletteHex('#F5A623')">
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px 0; font-size: 12px; font-weight: 700;">
                                    <span>Primary</span>
                                    <span style="font-family: var(--ws-mono);">#F5A623</span>
                                </div>
                                <div class="pp-ramp-bar">
                                    <div style="background:#1f1200"></div><div style="background:#3d2400"></div><div style="background:#5c3700"></div><div style="background:#7a4900"></div><div style="background:#995c00"></div><div style="background:#b86e00"></div><div style="background:#d68507"></div><div style="background:#e69512"></div><div style="background:#f5a623"></div><div style="background:#f8bc54"></div><div style="background:#fbd38a"></div><div style="background:#fff6e5"></div>
                                </div>
                            </div>
                        </div>

                        <div class="pp-card-group">
                            <div class="pp-outside-header">Card &amp; Table Column Headers</div>
                            <div id="ppSwatchSecondary" class="pp-swatch-card" style="background-color: #D8D3C7; color: #111111;" onclick="copyPaletteHex(ppCurrentSecHex)">
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px 0; font-size: 12px; font-weight: 700;">
                                    <span>Secondary</span>
                                    <span id="ppHexSecondary" style="font-family: var(--ws-mono);">#D8D3C7</span>
                                </div>
                                <div class="pp-ramp-bar">
                                    <div style="background:#181a20"></div><div style="background:#282b33"></div><div style="background:#423e37"></div><div style="background:#5e584e"></div><div style="background:#7a7265"></div><div style="background:#968d7e"></div><div style="background:#b0a798"></div><div style="background:#c4bcae"></div><div style="background:#d8d3c7"></div><div style="background:#ded8cd"></div><div style="background:#ece8df"></div><div style="background:#f7f6f2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="pp-card-group">
                            <div class="pp-outside-header">Semantic Status &amp; Financial Figures</div>
                            <div class="pp-swatch-card" style="background-color: #C6EDD1; color: #111111;" onclick="copyPaletteHex('#15803D')">
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px 0; font-size: 12px; font-weight: 700;">
                                    <span>Tertiary</span>
                                    <span style="font-family: var(--ws-mono);">#15803D</span>
                                </div>
                                <div class="pp-ramp-bar">
                                    <div style="background:#052e16"></div><div style="background:#064e24"></div><div style="background:#14532d"></div><div style="background:#166534"></div><div style="background:#15803d"></div><div style="background:#16a34a"></div><div style="background:#22c55e"></div><div style="background:#4ade80"></div><div style="background:#86efac"></div><div style="background:#bbf7d0"></div><div style="background:#c6edd1"></div><div style="background:#eaf7ee"></div>
                                </div>
                            </div>
                        </div>

                        <div class="pp-card-group">
                            <div class="pp-outside-header">Page Backdrop, Cards &amp; Text Ink</div>
                            <div id="ppSwatchNeutral" class="pp-swatch-card" style="background-color: #191919; color: #FFFFFF;" onclick="copyPaletteHex(ppCurrentNeuHex)">
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px 0; font-size: 12px; font-weight: 700;">
                                    <span>Neutral</span>
                                    <span id="ppHexNeutral" style="font-family: var(--ws-mono);">#191919</span>
                                </div>
                                <div class="pp-ramp-bar">
                                    <div style="background:#0e0f12"></div><div style="background:#111111"></div><div style="background:#191919"></div><div style="background:#282b33"></div><div style="background:#3a3a3a"></div><div style="background:#4a4a4a"></div><div style="background:#737373"></div><div style="background:#9e9e9e"></div><div style="background:#dcd8d0"></div><div style="background:#ebe7df"></div><div style="background:#f1efe9"></div><div style="background:#ffffff"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMN 2: 3 Stacked Typography Tiles -->
                    <div class="pp-col">
                        <div class="pp-card-group flex-1">
                            <div class="pp-outside-header">Page &amp; Card Title Font</div>
                            <div class="pp-bento-tile">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--pb-text-muted);">
                                    <span>Headline</span>
                                    <span>Plus Jakarta Sans</span>
                                </div>
                                <div style="display: flex; align-items: baseline; justify-content: space-between; margin-top: 8px;">
                                    <span style="font-size: 11px; font-family: var(--ws-mono); color: var(--pb-text-muted);">700–800 · 16px–38px</span>
                                    <span style="font-size: 44px; font-weight: 800; line-height: 1; color: var(--pb-text-main);">Aa</span>
                                </div>
                            </div>
                        </div>

                        <div class="pp-card-group flex-1">
                            <div class="pp-outside-header">Table Cell &amp; Body Font</div>
                            <div class="pp-bento-tile">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--pb-text-muted);">
                                    <span>Body</span>
                                    <span>Plus Jakarta Sans</span>
                                </div>
                                <div style="display: flex; align-items: baseline; justify-content: space-between; margin-top: 8px;">
                                    <span style="font-size: 11px; font-family: var(--ws-mono); color: var(--pb-text-muted);">400–500 · 14.5px–15px</span>
                                    <span style="font-size: 44px; font-weight: 500; line-height: 1; color: var(--pb-text-sec);">Aa</span>
                                </div>
                            </div>
                        </div>

                        <div class="pp-card-group flex-1">
                            <div class="pp-outside-header">Table Column Header &amp; Meta Font</div>
                            <div class="pp-bento-tile">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--pb-text-muted);">
                                    <span>Label</span>
                                    <span>Jakarta / Mono</span>
                                </div>
                                <div style="display: flex; align-items: baseline; justify-content: space-between; margin-top: 8px;">
                                    <span style="font-size: 11px; font-family: var(--ws-mono); color: var(--pb-text-muted);">700 · 11.5px · +0.6px</span>
                                    <span style="font-size: 44px; font-weight: 700; line-height: 1; color: var(--pb-th-text);">Aa</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMN 3: Action Buttons, Header Hierarchy & Status -->
                    <div class="pp-col">
                        <div class="pp-card-group flex-1">
                            <div class="pp-outside-header">Action Buttons (Text-Only Standard)</div>
                            <div class="pp-bento-tile" style="justify-content: center;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                    <button type="button" style="padding: 8px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; border: none; background: #F5A623; color: #FFFFFF; cursor: pointer;">Primary</button>
                                    <button type="button" style="padding: 8px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; border: none; background: var(--pb-charcoal); color: var(--pb-charcoal-text); cursor: pointer;">Secondary</button>
                                    <button type="button" style="padding: 8px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; background: var(--pb-th-dark); color: var(--pb-text-main); border: 1.5px solid var(--pb-th-dark-border); cursor: pointer;">Inverted</button>
                                    <button type="button" style="padding: 8px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; background: var(--pb-tile-bg); color: var(--pb-text-main); border: 1.5px solid var(--pb-border); cursor: pointer;">Outlined</button>
                                </div>
                            </div>
                        </div>

                        <div class="pp-card-group flex-1">
                            <div class="pp-outside-header">Header Color Hierarchy &amp; Metrics</div>
                            <div class="pp-bento-tile" style="justify-content: center; gap: 10px;">
                                <div style="width: 100%; height: 8px; border-radius: 999px; background: var(--pb-tile-raised); overflow: hidden;">
                                    <div style="width: 72%; height: 100%; border-radius: 999px; background: #F5A623;"></div>
                                </div>
                                <div style="width: 100%; height: 8px; border-radius: 999px; background: var(--pb-tile-raised); overflow: hidden;">
                                    <div style="width: 86%; height: 100%; border-radius: 999px; background: var(--pb-th-dark);"></div>
                                </div>
                                <div style="width: 100%; height: 8px; border-radius: 999px; background: var(--pb-tile-raised); overflow: hidden;">
                                    <div style="width: 58%; height: 100%; border-radius: 999px; background: #15803D;"></div>
                                </div>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; flex: 1;">
                            <div class="pp-card-group flex-1">
                                <div class="pp-outside-header">Brand Logo</div>
                                <div class="pp-bento-tile" style="align-items: center; justify-content: center;">
                                    <div style="width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; background: rgba(245, 166, 35, 0.18); color: #d68507; border: 1.5px solid #F5A623;">P</div>
                                </div>
                            </div>
                            <div class="pp-card-group flex-1">
                                <div class="pp-outside-header">Status Badge</div>
                                <div class="pp-bento-tile" style="align-items: center; justify-content: center; padding: 10px;">
                                    <span style="padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background: #eaf7ee; color: #15803d; border: 1px solid #c6edd1; white-space: nowrap;">Active Tenant</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMN 4: Form Input, Segmented Tabs & Table Action Icons -->
                    <div class="pp-col">
                        <div class="pp-card-group flex-1">
                            <div class="pp-outside-header">Form Input &amp; Search Box</div>
                            <div class="pp-bento-tile" style="justify-content: center;">
                                <div style="display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-radius: 14px; background: var(--pb-input-bg); border: 1.5px solid var(--pb-input-border);">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--pb-text-muted); flex-shrink: 0;">
                                        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                    <span style="font-size: 12px; font-weight: 500; color: var(--pb-text-muted);">Search</span>
                                </div>
                            </div>
                        </div>

                        <div class="pp-card-group flex-1">
                            <div class="pp-outside-header">View Segmented Pill Tabs</div>
                            <div class="pp-bento-tile" style="justify-content: center;">
                                <div style="display: flex; align-items: center; gap: 4px; padding: 5px; border-radius: 999px; background: var(--pb-tile-raised); border: 1px solid var(--pb-border);">
                                    <span style="flex: 1; text-align: center; padding: 5px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700; background: #F5A623; color: #FFFFFF;">Active</span>
                                    <span style="flex: 1; text-align: center; padding: 5px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 600; color: var(--pb-text-sec);">Historical</span>
                                </div>
                            </div>
                        </div>

                        <div class="pp-card-group flex-1">
                            <div class="pp-outside-header">Table Actions Icon Controls</div>
                            <div class="pp-bento-tile" style="justify-content: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <div title="View Details" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: var(--pb-tile-bg); border: 1.5px solid var(--pb-border); color: var(--pb-text-main);">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </div>
                                    <div title="Edit Record" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: var(--pb-tile-bg); border: 1.5px solid var(--pb-border); color: var(--pb-text-main);">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </div>
                                    <div title="View Lease Agreement" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: rgba(245, 166, 35, 0.16); border: 1.5px solid #F5A623; color: #d68507;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                    </div>
                                    <div title="Delete Record" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #fee2e2; border: 1.5px solid #fca5a5; color: #dc2626;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
var ppCurrentSecHex = '#D8D3C7';
var ppCurrentNeuHex = '#191919';

function togglePaletteModal(show) {
    var modal = document.getElementById('designPaletteModal');
    if (!modal) return;
    if (show) {
        var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        setDashboardPaletteTheme(currentTheme);
        modal.classList.add('open');
    } else {
        modal.classList.remove('open');
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        togglePaletteModal(false);
    }
});

function setDashboardPaletteTheme(mode) {
    var root = document.getElementById('ppPaletteRoot');
    var title = document.getElementById('ppBoardTitle');
    var btnLight = document.getElementById('ppBtnLight');
    var btnDark = document.getElementById('ppBtnDark');
    var swatchSec = document.getElementById('ppSwatchSecondary');
    var hexSec = document.getElementById('ppHexSecondary');
    var swatchNeu = document.getElementById('ppSwatchNeutral');
    var hexNeu = document.getElementById('ppHexNeutral');

    if (mode === 'dark') {
        root.className = 'pp-board-dark';
        title.textContent = 'PropPulse PMS — Stylized Design Palette (Obsidian Dark)';
        btnDark.style.background = '#f5a623';
        btnDark.style.color = '#111111';
        btnLight.style.background = 'transparent';
        btnLight.style.color = '#a0a6b2';

        ppCurrentSecHex = '#181A20';
        swatchSec.style.backgroundColor = '#181A20';
        swatchSec.style.color = '#F5F6F8';
        hexSec.textContent = '#181A20';

        ppCurrentNeuHex = '#0E0F12';
        swatchNeu.style.backgroundColor = '#0E0F12';
        swatchNeu.style.color = '#F5F6F8';
        hexNeu.textContent = '#0E0F12';
    } else {
        root.className = 'pp-board-light';
        title.textContent = 'PropPulse PMS — Stylized Design Palette (Warm Light)';
        btnLight.style.background = '#f5a623';
        btnLight.style.color = '#ffffff';
        btnDark.style.background = 'transparent';
        btnDark.style.color = '#4a4a4a';

        ppCurrentSecHex = '#D8D3C7';
        swatchSec.style.backgroundColor = '#D8D3C7';
        swatchSec.style.color = '#111111';
        hexSec.textContent = '#D8D3C7';

        ppCurrentNeuHex = '#191919';
        swatchNeu.style.backgroundColor = '#191919';
        swatchNeu.style.color = '#FFFFFF';
        hexNeu.textContent = '#191919';
    }
}

function copyPaletteHex(hex) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(hex);
    }
    var toast = document.getElementById('ppCopyToast');
    if (toast) {
        toast.textContent = 'Copied ' + hex;
        toast.style.display = 'inline-block';
        setTimeout(function() {
            toast.style.display = 'none';
        }, 1400);
    }
}
</script>
