<?php
use App\Core\Router;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Page Not Found — PropPulse</title>
    <link rel="stylesheet" href="<?= Router::url('/css/app.css') ?>">
</head>
<body style="background: var(--ws-bg, #f7f6f2); display: flex; align-items: center; justify-content: center; min-height: 100vh;">
    <div style="text-align: center; max-width: 500px; padding: 40px; background: var(--ws-surface, #ffffff); border: 1.5px solid var(--ws-border, #ebe7df); border-radius: 20px; box-shadow: var(--shadow-md);">
        <h1 style="font-size: 72px; margin: 0; color: var(--ws-accent, #f5a623); font-weight: 800;">404</h1>
        <h2 style="margin: 10px 0 15px; color: var(--ws-text, #111111);">Page Not Found</h2>
        <p style="color: var(--ws-text-muted, #737373); margin-bottom: 25px;">The property management page or record you requested does not exist or has been moved.</p>
        <a href="<?= Router::url('/dashboard') ?>" class="btn btn-primary">Return to Dashboard</a>
    </div>
</body>
</html>
