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
<body style="background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh;">
    <div style="text-align: center; max-width: 500px; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
        <h1 style="font-size: 72px; margin: 0; color: #4f46e5; font-weight: 800;">404</h1>
        <h2 style="margin: 10px 0 15px; color: #1e293b;">Page Not Found</h2>
        <p style="color: #64748b; margin-bottom: 25px;">The property management page or record you requested does not exist or has been moved.</p>
        <a href="<?= Router::url('/dashboard') ?>" class="btn btn-primary">Return to Dashboard</a>
    </div>
</body>
</html>
