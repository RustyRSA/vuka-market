<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$pageTitle = 'Secure checkout';

$listingId = (int)($_POST['listing_id'] ?? $_GET['listing_id'] ?? 0);
$stmt = db()->prepare('SELECT l.*, u.full_name AS seller FROM listings l JOIN users u ON u.user_id = l.seller_id WHERE l.listing_id = ?');
$stmt->execute([$listingId]);
$l = $stmt->fetch();
if (!$l || $l['status'] !== 'active') { http_response_code(404); exit('Item is no longer available.'); }

$fee   = round($l['price'] * PLATFORM_FEE_PCT, 2);
$total = $l['price'] + $fee;

// Step 2: confirm payment -> create order + held escrow transaction
if (($_POST['action'] ?? '') === 'pay' && csrf_check($_POST['csrf'] ?? null)) {
    $gateway = $_POST['gateway'] ?? 'payfast';
    $method  = $_POST['delivery_method'] ?? 'pickup_point';
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $o = $pdo->prepare('INSERT INTO orders (listing_id, buyer_id, seller_id, amount, delivery_method, status)
                            VALUES (?, ?, ?, ?, ?, "paid_escrow")');
        $o->execute([$l['listing_id'], current_user()['user_id'], $l['seller_id'], $l['price'], $method]);
        $orderId = (int)$pdo->lastInsertId();

        $t = $pdo->prepare('INSERT INTO transactions (order_id, gateway, gateway_ref, amount, escrow_status)
                            VALUES (?, ?, ?, ?, "held")');
        $t->execute([$orderId, $gateway, strtoupper($gateway) . '-' . random_int(100000, 999999), $total]);

        $pdo->prepare('UPDATE listings SET status = "sold" WHERE listing_id = ?')->execute([$l['listing_id']]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        exit('Payment could not be processed.');
    }
    set_flash('success', 'Payment received and held in escrow. It will be released to the seller once you confirm delivery.');
    redirect('dashboard.php');
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <h3 class="mb-3">Secure checkout</h3>
    <div class="card p-4 shadow-sm mb-3">
      <div class="d-flex justify-content-between"><span><?= e($l['title']) ?></span><span><?= money($l['price']) ?></span></div>
      <div class="d-flex justify-content-between text-muted small"><span>Buyer protection fee (<?= PLATFORM_FEE_PCT*100 ?>%)</span><span><?= money($fee) ?></span></div>
      <hr>
      <div class="d-flex justify-content-between fw-bold"><span>Total</span><span><?= money($total) ?></span></div>
    </div>
    <form method="post" class="card p-4 shadow-sm">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="listing_id" value="<?= (int)$l['listing_id'] ?>">
      <input type="hidden" name="action" value="pay">
      <div class="mb-3"><label class="form-label">Delivery method</label>
        <select name="delivery_method" class="form-select">
          <option value="pickup_point">Pargo / PUDO pickup point</option>
          <option value="courier">Courier to my address</option>
        </select></div>
      <div class="mb-3"><label class="form-label">Pay with</label>
        <select name="gateway" class="form-select">
          <option value="payfast">PayFast</option><option value="yoco">Yoco</option><option value="ozow">Ozow (Instant EFT)</option>
        </select></div>
      <button class="btn btn-success btn-lg">Pay <?= money($total) ?> into escrow</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
