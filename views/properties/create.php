<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/properties') ?>">&larr; Back to Properties</a>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Add New Property</h2>
    </div>
    <div class="card-body">
        <form action="<?= Router::url('/properties') ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Property Name *</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Pacific Coast Apartments" required>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="property_type">Property Type *</label>
                    <select id="property_type" name="property_type" class="form-control">
                        <option value="multi_family">Multi-Family Residential</option>
                        <option value="single_family">Single Family Home</option>
                        <option value="apartment">Apartment Complex</option>
                        <option value="commercial">Commercial / Office</option>
                        <option value="condo">Condominium</option>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="manager_id">Assigned Property Manager</label>
                    <select id="manager_id" name="manager_id" class="form-control">
                        <option value="">-- Select Manager --</option>
                        <?php foreach ($managers as $mgr): ?>
                            <option value="<?= $mgr['id'] ?>"><?= htmlspecialchars($mgr['full_name']) ?> (<?= htmlspecialchars($mgr['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="address_line1">Street Address *</label>
                <input type="text" id="address_line1" name="address_line1" class="form-control" placeholder="1234 Main St" required>
            </div>

            <div class="form-group">
                <label for="address_line2">Address Line 2</label>
                <input type="text" id="address_line2" name="address_line2" class="form-control" placeholder="Building B, Suite 100">
            </div>

            <div class="form-row">
                <div class="form-group col-4">
                    <label for="city">City *</label>
                    <input type="text" id="city" name="city" class="form-control" placeholder="City" required>
                </div>
                <div class="form-group col-4">
                    <label for="state">State / Province *</label>
                    <input type="text" id="state" name="state" class="form-control" placeholder="CA" required>
                </div>
                <div class="form-group col-4">
                    <label for="zip_code">ZIP / Postal Code *</label>
                    <input type="text" id="zip_code" name="zip_code" class="form-control" placeholder="90210" required>
                </div>
            </div>

            <div class="form-group">
                <label for="photo">Property Photo</label>
                <input type="file" id="photo" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                <small class="text-muted">Max 5 MB. Accepts JPG, PNG, WebP, GIF.</small>
            </div>

            <div class="form-group">
                <label for="description">Property Description / Notes</label>
                <textarea id="description" name="description" class="form-control" rows="3" placeholder="Key amenities, parking regulations, access gate codes..."></textarea>
            </div>

            <div class="form-actions">
                <a href="<?= Router::url('/properties') ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Property</button>
            </div>
        </form>
    </div>
</div>
