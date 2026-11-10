<?php
use App\Core\Router;
?>

<div class="breadcrumb">
    <a href="<?= Router::url('/leases') ?>">&larr; Back to Leases</a>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Create Lease Agreement</h2>
        <p class="text-muted text-xs">Assign a resident to an available unit and upload the signed agreement file</p>
    </div>
    <div class="card-body">
        <form action="<?= Router::url('/leases') ?>" method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="unit_id">Select Vacant Unit *</label>
                    <select id="unit_id" name="unit_id" class="form-control" required>
                        <option value="">-- Select Vacant Unit --</option>
                        <?php foreach ($vacantUnits as $vu): ?>
                            <option value="<?= $vu['id'] ?>" data-rent="<?= $vu['target_rent'] ?>" data-deposit="<?= $vu['security_deposit'] ?>">
                                <?= htmlspecialchars($vu['property_name']) ?> &bull; Unit <?= htmlspecialchars($vu['unit_number']) ?> ($<?= number_format((float)$vu['target_rent'], 2) ?>/mo)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="tenant_id">Select Tenant *</label>
                    <select id="tenant_id" name="tenant_id" class="form-control" required>
                        <option value="">-- Select Tenant --</option>
                        <?php foreach ($tenants as $t): ?>
                            <option value="<?= $t['id'] ?>">
                                <?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?> (<?= htmlspecialchars($t['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="start_date">Lease Start Date *</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group col-6">
                    <label for="end_date">Lease End Date *</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="rent_amount">Monthly Rent ($) *</label>
                    <input type="number" id="rent_amount" name="rent_amount" class="form-control" step="0.01" placeholder="2500.00" required>
                </div>
                <div class="form-group col-6">
                    <label for="deposit_held">Security Deposit Held ($)</label>
                    <input type="number" id="deposit_held" name="deposit_held" class="form-control" step="0.01" placeholder="2500.00">
                </div>
            </div>

            <div class="form-group">
                <label for="lease_document">
                    Attach Signed Lease Agreement (PDF or Image)
                    <span class="badge badge-subtle">Local Storage Vault</span>
                </label>
                <input type="file" id="lease_document" name="lease_document" class="form-control" accept=".pdf,image/png,image/jpeg,image/webp">
                <small class="text-muted">Files are stored securely in your protected local <code>/storage/documents/leases/</code> directory outside public web access.</small>
            </div>

            <div class="form-group">
                <label for="terms">Contract Terms & Special Clauses</label>
                <textarea id="terms" name="terms" class="form-control" rows="3" placeholder="Late fee of $50 after 5th of month, no pets without written addendum, parking stall #12 assigned..."></textarea>
            </div>

            <div class="form-actions">
                <a href="<?= Router::url('/leases') ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Lease</button>
            </div>
        </form>
    </div>
</div>
