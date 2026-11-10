<?php
use App\Core\Router;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Billing & Financial Ledger</h1>
        <p class="page-subtitle">Track rent roll receivables, generate invoices, and record tenant cash/ACH receipts</p>
    </div>
    <div class="action-buttons">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-invoice').classList.add('active')">+ Generate Invoice</button>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('modal-payment').classList.add('active')">💵 Record Payment</button>
    </div>
</div>

<!-- Financial Summary KPIs -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Total Invoiced</span>
            <span class="metric-icon">📑</span>
        </div>
        <div class="metric-value">$<?= number_format($totals['total_billed'], 2) ?></div>
        <div class="metric-footer">All generated rental charges</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Total Collected</span>
            <span class="metric-icon">💰</span>
        </div>
        <div class="metric-value text-success">$<?= number_format($totals['total_collected'], 2) ?></div>
        <div class="metric-footer">Successfully received & settled</div>
    </div>

    <div class="metric-card">
        <div class="metric-header">
            <span class="metric-label">Pending Balance</span>
            <span class="metric-icon">⏳</span>
        </div>
        <div class="metric-value text-warning">$<?= number_format($totals['total_pending'], 2) ?></div>
        <div class="metric-footer">Unsettled or due rent</div>
    </div>
</div>

<!-- Invoices Ledger -->
<div class="card" style="margin-bottom: 25px;">
    <div class="card-header">
        <h2 class="card-title">Rent Roll Invoices</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Property & Unit</th>
                    <th>Tenant</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted" style="padding: 40px;">
                            No invoices recorded yet. Click "Generate Invoice" above to bill a lease.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                            <td><?= htmlspecialchars($inv['property_name']) ?> (#<?= htmlspecialchars($inv['unit_number']) ?>)</td>
                            <td><?= htmlspecialchars($inv['tenant_name']) ?></td>
                            <td><?= htmlspecialchars($inv['due_date']) ?></td>
                            <td class="font-bold">$<?= number_format((float)$inv['amount'], 2) ?></td>
                            <td class="text-success">$<?= number_format((float)$inv['total_paid'], 2) ?></td>
                            <td>
                                <?php
                                $invClassMap = [
                                    'paid'           => 'badge-success',
                                    'pending'        => 'badge-warning',
                                    'partially_paid' => 'badge-info',
                                    'overdue'        => 'badge-danger',
                                ];
                                $invClass = $invClassMap[$inv['status']] ?? 'badge-subtle';
                                ?>
                                <span class="badge <?= $invClass ?>"><?= ucfirst(str_replace('_', ' ', $inv['status'])) ?></span>
                            </td>
                            <td>
                                <?php if ($inv['status'] !== 'paid'): ?>
                                    <button type="button" class="btn btn-outline btn-xs" onclick="openPaymentForInvoice('<?= $inv['id'] ?>', '<?= htmlspecialchars(addslashes($inv['invoice_number'])) ?>', '<?= (float)$inv['amount'] - (float)$inv['total_paid'] ?>')">
                                        Pay
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted text-xs">Settled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal 1: Generate Invoice -->
<div id="modal-invoice" class="modal-backdrop">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Generate Rent Invoice</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('modal-invoice').classList.remove('active')">&times;</button>
        </div>
        <form action="<?= Router::url('/finances/invoice') ?>" method="POST">
            <div class="form-group">
                <label for="lease_id">Select Active Lease *</label>
                <select id="lease_id" name="lease_id" class="form-control" required onchange="updateInvoiceAmount(this)">
                    <option value="">-- Select Active Lease --</option>
                    <?php foreach ($activeLeases as $l): ?>
                        <option value="<?= $l['id'] ?>" data-amount="<?= $l['rent_amount'] ?>">
                            <?= htmlspecialchars($l['property_name']) ?> (#<?= htmlspecialchars($l['unit_number']) ?>) &bull; <?= htmlspecialchars($l['tenant_name']) ?> ($<?= number_format((float)$l['rent_amount'], 2) ?>/mo)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="inv_amount">Billing Amount ($) *</label>
                    <input type="number" id="inv_amount" name="amount" class="form-control" step="0.01" placeholder="2500.00" required>
                </div>
                <div class="form-group col-6">
                    <label for="due_date">Due Date *</label>
                    <input type="date" id="due_date" name="due_date" class="form-control" required value="<?= date('Y-m-01', strtotime('+1 month')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Invoice Memo</label>
                <input type="text" id="description" name="description" class="form-control" value="Monthly Rent - <?= date('F Y') ?>" required>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-invoice').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Generate Invoice</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Record Payment -->
<div id="modal-payment" class="modal-backdrop">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Record Tenant Payment</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('modal-payment').classList.remove('active')">&times;</button>
        </div>
        <form action="<?= Router::url('/finances/payment') ?>" method="POST">
            <div class="form-group">
                <label for="pay_invoice_id">Target Invoice *</label>
                <select id="pay_invoice_id" name="invoice_id" class="form-control" required>
                    <option value="">-- Select Pending Invoice --</option>
                    <?php foreach ($invoices as $inv): ?>
                        <?php if ($inv['status'] !== 'paid'): ?>
                            <option value="<?= $inv['id'] ?>" data-balance="<?= (float)$inv['amount'] - (float)$inv['total_paid'] ?>">
                                <?= htmlspecialchars($inv['invoice_number']) ?> &bull; <?= htmlspecialchars($inv['tenant_name']) ?> (Due: $<?= number_format((float)$inv['amount'] - (float)$inv['total_paid'], 2) ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="amount_paid">Amount Paid ($) *</label>
                    <input type="number" id="amount_paid" name="amount_paid" class="form-control" step="0.01" placeholder="2500.00" required>
                </div>
                <div class="form-group col-6">
                    <label for="payment_date">Payment Date *</label>
                    <input type="date" id="payment_date" name="payment_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" class="form-control">
                        <option value="bank_transfer" selected>ACH / Bank Transfer</option>
                        <option value="card">Credit / Debit Card</option>
                        <option value="check">Paper Check</option>
                        <option value="cash">Cash Receipt</option>
                        <option value="online">Online Payment Portal</option>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="reference_number">Check / Confirmation #</label>
                    <input type="text" id="reference_number" name="reference_number" class="form-control" placeholder="e.g. CHK-1049 or TXN-998">
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Notes / Deposit Account</label>
                <input type="text" id="notes" name="notes" class="form-control" placeholder="Deposited into Operating Account #4412">
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-payment').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Record Payment</button>
            </div>
        </form>
    </div>
</div>
