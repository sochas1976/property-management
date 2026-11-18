<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/tenants') ?>">&larr; Back to Tenant Directory</a>
</div>

<div class="card" style="max-width: 750px; margin: 0 auto; border-radius: 12px; overflow: hidden; border: 1.5px solid var(--ws-border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <div class="card-header" style="background-color: var(--table-th-dark, #d8d3c7); border-bottom: 1.5px solid var(--table-th-dark-border, #c4bcae); padding: 16px 20px;">
        <h2 class="card-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--table-th-dark-text, var(--ws-text));">Register New Tenant</h2>
    </div>
    <div class="card-body">
        <form action="<?= Router::url('/tenants') ?>" method="POST">
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" placeholder="John" required>
                </div>
                <div class="form-group col-6">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Doe" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="john.doe@example.com" required>
                </div>
                <div class="form-group col-6">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="(555) 000-0000" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="emergency_contact">Emergency Contact Name</label>
                    <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" placeholder="Jane Doe (Spouse)">
                </div>
                <div class="form-group col-6">
                    <label for="emergency_phone">Emergency Contact Phone</label>
                    <input type="tel" id="emergency_phone" name="emergency_phone" class="form-control" placeholder="(555) 111-2222">
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Tenant Notes & Special Provisions</label>
                <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Pet details, parking permit numbers, accessibility requests..."></textarea>
            </div>

            <div class="form-actions">
                <a href="<?= Router::url('/tenants') ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Register Tenant</button>
            </div>
        </form>
    </div>
</div>
