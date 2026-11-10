<?php
use App\Core\Router;
?>
<div class="auth-card-container">
    <div class="auth-card">
        <div style="display: flex; justify-content: flex-end; margin-bottom: 6px;">
            <button type="button" class="theme-toggle-btn" id="theme-toggle-btn" onclick="toggleAppTheme(event)" title="Toggle Light / Dark mode" aria-label="Toggle Theme">
                <span class="theme-icon-dark">🌙</span>
                <span class="theme-icon-light">☀️</span>
            </button>
        </div>
        <div class="auth-header">
            <div class="auth-icon">🏢</div>
            <h2>Sign in to PropPulse</h2>
            <p>Enter your credentials to access your properties dashboard</p>
        </div>

        <form action="<?= Router::url('/login') ?>" method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="admin@proppulse.com" required autofocus value="admin@proppulse.com">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required value="password">
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
        </form>

        <div class="auth-demo-notice">
            <p><strong>Demo Seed Credentials (XAMPP):</strong></p>
            <p>Email: <code>admin@proppulse.com</code> &bull; Password: <code>password</code></p>
        </div>
    </div>
</div>
