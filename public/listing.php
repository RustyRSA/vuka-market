<?php
require_once __DIR__ . '/../includes/auth.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = db()->prepare(
    'SELECT l.*, c.name AS category, u.user_id AS seller_id, u.full_name AS seller,
            u.rating_avg, u.id_verified,
            (SELECT file_path FROM listing_images i WHERE i.listing_id = l.listing_id AND i.is_primary = 1 LIMIT 1) AS image
     FROM listings l
     JOIN categories c ON c.category_id = l.category_id
     JOIN users u      ON u.user_id     = l.seller_id
     WHERE l.listing_id = ? LIMIT 1'
);
$stmt->execute([$id]);
$l = $stmt->fetch();
if (!$l) { http_response_code(404); exit('Listing not found.'); }

$pageTitle = $l['title'];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
  <div class="col-lg-6">
    <img src="<?= e($l['image'] ?: 'assets/img/placeholder.png') ?>" class="img-fluid rounded-3 border" alt="<?= e($l['title']) ?>">
  </div>
  <div class="col-lg-6">
    <h2 class="fw-bold"><?= e($l['title']) ?></h2>
    <div class="display-6 vk-price mb-3"><?= money($l['price']) ?></div>
    <p><span class="badge bg-secondary"><?= e($l['category']) ?></span>
       <span class="badge bg-light text-dark border text-capitalize"><?= e(str_replace('_',' ',$l['item_condition'])) ?></span></p>
    <p class="text-muted"><?= nl2br(e($l['description'])) ?></p>
    <div class="card vk-seller-box p-3 mb-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-semibold"><?= e($l['seller']) ?>
            <?php if ($l['id_verified']): ?><span class="badge bg-success">Verified</span><?php endif; ?>
          </div>
          <div class="vk-stars">&#9733; <?= number_format((float)$l['rating_avg'], 1) ?> seller rating</div>
        </div>
        <div class="text-end small text-muted"><?= e($l['location']) ?></div>
      </div>
    </div>
    <form action="checkout.php" method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="listing_id" value="<?= (int)$l['listing_id'] ?>">
      <button class="btn btn-success btn-lg w-100" type="submit">Buy with escrow protection</button>
    </form>
    <p class="small text-muted mt-2">Your money is held safely and only released to the seller once you confirm the item.</p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
