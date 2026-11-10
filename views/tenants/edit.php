<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/tenants/' . $tenant['id']) ?>">&larr; Back to <?= htmlspecialchars($tenant['first_name'] . ' ' . $tenant['last_name']) ?></a>
</div>

<div class="card" style="max-width: 750px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Edit Tenant Profile</h2>
    </div>
    <div class="card-body">
        <form action="<?= Router::url('/tenants/' . $tenant['id'] . '/update') ?>" method="POST">
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($tenant['first_name']) ?>" required>
                </div>
                <div class="form-group col-6">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($tenant['last_name']) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($tenant['email']) ?>" required>
                </div>
                <div class="form-group col-6">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($tenant['phone']) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="emergency_contact">Emergency Contact Name</label>
                    <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($tenant['emergency_contact'] ?? '') ?>">
                </div>
                <div class="form-group col-6">
                    <label for="emergency_phone">Emergency Contact Phone</label>
                    <input type="tel" id="emergency_phone" name="emergency_phone" class="form-control" value="<?= htmlspecialchars($tenant['emergency_phone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Tenant Notes & Special Provisions</label>
                <textarea id="notes" name="notes" class="form-control" rows="3"><?= htmlspecialchars($tenant['notes'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <a href="<?= Router::url('/tenants/' . $tenant['id']) ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
