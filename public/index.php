<?php
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Buy & sell near you';

$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$sql = 'SELECT l.*, c.name AS category, u.full_name AS seller, u.rating_avg,
               (SELECT file_path FROM listing_images i WHERE i.listing_id = l.listing_id AND i.is_primary = 1 LIMIT 1) AS image
        FROM listings l
        JOIN categories c ON c.category_id = l.category_id
        JOIN users u      ON u.user_id     = l.seller_id
        WHERE l.status = "active"';
$params = [];
if ($catId) { $sql .= ' AND l.category_id = ?'; $params[] = $catId; }
$sql .= ' ORDER BY l.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="vk-hero text-center text-white rounded-4 p-5 mb-4">
  <h1 class="fw-bold">Township trade, made safe.</h1>
  <p class="lead mb-0">Buy and sell directly with people near you &mdash; protected by secure escrow and verified sellers.</p>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
  <a href="index.php" class="btn btn-sm <?= $catId ? 'btn-outline-secondary' : 'btn-dark' ?>">All</a>
  <?php foreach ($categories as $c): ?>
    <a href="index.php?cat=<?= (int)$c['category_id'] ?>"
       class="btn btn-sm <?= $catId === (int)$c['category_id'] ? 'btn-dark' : 'btn-outline-secondary' ?>">
       <?= e($c['name']) ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <?php if (!$listings): ?>
    <p class="text-muted">No items found. Be the first to <a href="sell.php">list something</a>.</p>
  <?php endif; ?>
  <?php foreach ($listings as $l): ?>
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card vk-card h-100">
        <a href="listing.php?id=<?= (int)$l['listing_id'] ?>">
          <img src="<?= e($l['image'] ?: 'assets/img/placeholder.png') ?>" class="card-img-top vk-thumb" alt="<?= e($l['title']) ?>">
        </a>
        <div class="card-body">
          <div class="vk-price"><?= money($l['price']) ?></div>
          <a class="vk-title" href="listing.php?id=<?= (int)$l['listing_id'] ?>"><?= e($l['title']) ?></a>
          <div class="small text-muted mt-1"><?= e($l['location']) ?></div>
          <div class="small mt-1">
            <span class="badge bg-light text-dark border"><?= e($l['category']) ?></span>
            <span class="vk-stars">&#9733; <?= number_format((float)$l['rating_avg'], 1) ?></span>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
