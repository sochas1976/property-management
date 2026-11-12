<?php
use App\Core\Auth;
use App\Core\Router;
$cssVersion = defined('ROOT_PATH') && file_exists(ROOT_PATH . '/public/css/app.css') 
    ? filemtime(ROOT_PATH . '/public/css/app.css') 
    : time();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Property Management') ?> — PropPulse</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Caveat:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= Router::url('/css/app.css') ?>?v=<?= $cssVersion ?>">
    <script>
        // Apply saved theme immediately to prevent flashing
        (function() {
            try {
                var savedTheme = localStorage.getItem('ws_theme') || localStorage.getItem('hc_theme') || 'light';
                document.documentElement.setAttribute('data-theme', savedTheme);
            } catch(e) {}
        })();

        // Bulletproof debounced global theme toggle function
        var _themeLastToggle = 0;
        function toggleAppTheme(e) {
            if (e) {
                if (typeof e.preventDefault === 'function') e.preventDefault();
                if (typeof e.stopPropagation === 'function') e.stopPropagation();
            }
            var now = Date.now();
            if (now - _themeLastToggle < 250) {
                return false;
            }
            _themeLastToggle = now;

            var html = document.documentElement;
            var current = html.getAttribute('data-theme') || 'light';
            var next = (current === 'light') ? 'dark' : 'light';
            html.setAttribute('data-theme', next);
            try {
                localStorage.setItem('ws_theme', next);
                localStorage.setItem('hc_theme', next);
            } catch(err) {}
            return false;
        }
    </script>
</head>
<body>
    <div class="app-layout">
        <?php if (Auth::check()): ?>
            <!-- Wealthsimple Header -->
            <header class="navbar">
                <div class="navbar-container">
                    <a href="<?= Router::url('/dashboard') ?>" class="brand-logo">
                        <div class="logo-icon">P</div>
                        <span>PropPulse<span class="brand-badge">PMS</span></span>
                    </a>

                    <nav class="nav-links">
                        <a href="<?= Router::url('/dashboard') ?>" class="nav-item">Dashboard</a>
                        <a href="<?= Router::url('/properties') ?>" class="nav-item">Properties</a>
                        <a href="<?= Router::url('/applicants') ?>" class="nav-item">Applicants</a>
                        <a href="<?= Router::url('/tenants') ?>" class="nav-item">Tenants</a>
                        <a href="<?= Router::url('/leases') ?>" class="nav-item">Leases</a>
                        <a href="<?= Router::url('/maintenance') ?>" class="nav-item">Maintenance</a>
                        <a href="<?= Router::url('/finances') ?>" class="nav-item">Finances</a>
                    </nav>

                    <div class="user-menu">
                        <?php if (defined('TEST_MODE_AVAILABLE') && TEST_MODE_AVAILABLE): ?>
                            <div id="pp-test-mode-nav-wrap" style="display: inline-flex; align-items: center; gap: 8px;">
                                <?php $isTestActive = (($_COOKIE['pp_test_mode_active'] ?? '') === 'true'); ?>
                                <button type="button" class="pp-test-mode-nav-btn <?= $isTestActive ? 'active' : '' ?>" id="navbar-test-mode-btn" title="Development Test Mode">
                                    <span class="pp-test-mode-dot"></span>
                                    <span>Test Mode: <strong class="pp-test-state-label"><?= $isTestActive ? 'ON' : 'OFF' ?></strong></span>
                                </button>
                                <div id="pp-test-date-picker-wrap" style="display: <?= $isTestActive ? 'inline-flex' : 'none' ?>; align-items: center; gap: 6px; background: rgba(245, 158, 11, 0.12); border: 1.5px solid #f59e0b; padding: 3px 10px; border-radius: 999px; transition: all 0.2s ease;">
                                    <label for="pp-test-simulated-date" style="font-size: 11.5px; font-weight: 700; color: #b45309; margin: 0; user-select: none; display: flex; align-items: center; gap: 4px; cursor: pointer;">
                                        <span>📅</span> Date:
                                    </label>
                                    <input type="date" id="pp-test-simulated-date" style="padding: 1px 6px; font-size: 12px; font-weight: 600; border: 1px solid var(--ws-border); border-radius: 6px; background: var(--ws-card-bg); color: var(--ws-text); outline: none; cursor: pointer; height: 26px;" value="<?= htmlspecialchars(\App\Core\AppDate::todayString()) ?>">
                                    <button type="button" id="pp-test-date-today-btn" style="border: none; background: #f59e0b; color: #ffffff; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; cursor: pointer; line-height: 1.3;" title="Reset simulated date to real today">Today</button>
                                </div>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="theme-toggle-btn" id="theme-toggle-btn" onclick="toggleAppTheme(event)" title="Toggle Light / Dark mode" aria-label="Toggle Theme">
                            <span class="theme-icon-dark">🌙</span>
                            <span class="theme-icon-light">☀️</span>
                        </button>
                        <span class="user-info">
                            <strong><?= htmlspecialchars(Auth::user()['full_name'] ?? 'User') ?></strong>
                            <span class="badge badge-subtle"><?= ucfirst(Auth::role() ?? 'User') ?></span>
                        </span>
                        <a href="<?= Router::url('/logout') ?>" class="btn btn-outline btn-sm">Sign Out</a>
                    </div>
                </div>
            </header>
        <?php endif; ?>

        <main class="main-content">
            <div class="content-container">
                <?php require VIEW_PATH . '/layouts/alert.php'; ?>
