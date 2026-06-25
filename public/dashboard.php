<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$pageTitle = 'My dashboard';
$uid = current_user()['user_id'];

// Action: buyer confirms delivery -> release escrow + complete order
if (($_POST['action'] ?? '') === 'confirm' && csrf_check($_POST['csrf'] ?? null)) {
    $orderId = (int)$_POST['order_id'];
    $pdo = db();
    $chk = $pdo->prepare('SELECT * FROM orders WHERE order_id = ? AND buyer_id = ?');
    $chk->execute([$orderId, $uid]);
    if ($chk->fetch()) {
        $pdo->prepare('UPDATE orders SET status = "completed" WHERE order_id = ?')->execute([$orderId]);
        $pdo->prepare('UPDATE transactions SET escrow_status = "released", released_at = NOW() WHERE order_id = ?')->execute([$orderId]);
        set_flash('success', 'Delivery confirmed. Escrow released to the seller.');
    }
    redirect('dashboard.php');
}

$myListings = db()->prepare('SELECT * FROM listings WHERE seller_id = ? ORDER BY created_at DESC');
$myListings->execute([$uid]);
$myListings = $myListings->fetchAll();

$myOrders = db()->prepare(
    'SELECT o.*, l.title, t.escrow_status, t.gateway
     FROM orders o JOIN listings l ON l.listing_id = o.listing_id
     LEFT JOIN transactions t ON t.order_id = o.order_id
     WHERE o.buyer_id = ? ORDER BY o.created_at DESC'
);
$myOrders->execute([$uid]);
$myOrders = $myOrders->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<h3 class="mb-3">Hello, <?= e(current_user()['full_name']) ?></h3>
<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#buying">My purchases</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#selling">My listings</button></li>
</ul>
<div class="tab-content">
  <div class="tab-pane fade show active" id="buying">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead><tr><th>Item</th><th>Amount</th><th>Status</th><th>Escrow</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($myOrders as $o): ?>
          <tr>
            <td><?= e($o['title']) ?></td>
            <td><?= money($o['amount']) ?></td>
            <td><span class="badge bg-info text-dark text-capitalize"><?= e(str_replace('_',' ',$o['status'])) ?></span></td>
            <td><span class="badge bg-secondary text-capitalize"><?= e($o['escrow_status'] ?? '-') ?></span></td>
            <td>
              <?php if ($o['status'] === 'paid_escrow'): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                  <input type="hidden" name="action" value="confirm">
                  <button class="btn btn-sm btn-success">Confirm delivery</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$myOrders): ?><tr><td colspan="5" class="text-muted">No purchases yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="tab-pane fade" id="selling">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead><tr><th>Item</th><th>Price</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($myListings as $l): ?>
          <tr>
            <td><a href="listing.php?id=<?= (int)$l['listing_id'] ?>"><?= e($l['title']) ?></a></td>
            <td><?= money($l['price']) ?></td>
            <td><span class="badge bg-light text-dark border text-capitalize"><?= e($l['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$myListings): ?><tr><td colspan="3" class="text-muted">You have no listings. <a href="sell.php">Sell something</a>.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
