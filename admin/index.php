<?php
require_once __DIR__ . '/../includes/auth.php';
require_staff(); // any staff member may view the dashboard
$pageTitle = 'Dashboard';

$counts = [
  'Users'      => (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
  'Listings'   => (int) db()->query('SELECT COUNT(*) FROM listings')->fetchColumn(),
  'Orders'     => (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
  'In escrow'  => (int) db()->query('SELECT COUNT(*) FROM transactions WHERE escrow_status = "held"')->fetchColumn(),
];
$recent = db()->query(
  'SELECT o.order_id, l.title, o.amount, o.status, o.created_at
   FROM orders o JOIN listings l ON l.listing_id = o.listing_id
   ORDER BY o.created_at DESC LIMIT 6'
)->fetchAll();

require_once __DIR__ . '/_header.php';
?>
<h3 class="mb-4">Dashboard</h3>
<div class="row g-3 mb-4">
  <?php foreach ($counts as $label => $n): ?>
    <div class="col-6 col-lg-3">
      <div class="card vk-stat p-3">
        <div class="vk-stat-num"><?= $n ?></div>
        <div class="text-muted"><?= e($label) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<div class="card p-3">
  <h6 class="mb-3">Recent orders</h6>
  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead><tr><th>#</th><th>Item</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $o): ?>
        <tr>
          <td><?= (int)$o['order_id'] ?></td>
          <td><?= e($o['title']) ?></td>
          <td><?= money($o['amount']) ?></td>
          <td><span class="badge bg-info text-dark text-capitalize"><?= e(str_replace('_',' ',$o['status'])) ?></span></td>
          <td class="small text-muted"><?= e($o['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
