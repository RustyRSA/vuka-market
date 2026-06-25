<?php
require_once __DIR__ . '/../includes/auth.php';
$q = trim($_GET['q'] ?? '');
$pageTitle = 'Search: ' . $q;

$stmt = db()->prepare(
    'SELECT l.*, u.full_name AS seller,
            (SELECT file_path FROM listing_images i WHERE i.listing_id = l.listing_id AND i.is_primary = 1 LIMIT 1) AS image
     FROM listings l JOIN users u ON u.user_id = l.seller_id
     WHERE l.status = "active" AND (l.title LIKE ? OR l.description LIKE ?)
     ORDER BY l.created_at DESC'
);
$like = '%' . $q . '%';
$stmt->execute([$like, $like]);
$listings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><?= count($listings) ?> result(s) for &ldquo;<?= e($q) ?>&rdquo;</h4>
<div class="row g-3">
  <?php foreach ($listings as $l): ?>
    <div class="col-6 col-md-4 col-lg-3">
      <div class="card vk-card h-100">
        <a href="listing.php?id=<?= (int)$l['listing_id'] ?>">
          <img src="<?= e($l['image'] ?: 'assets/img/placeholder.png') ?>" class="card-img-top vk-thumb" alt="">
        </a>
        <div class="card-body">
          <div class="vk-price"><?= money($l['price']) ?></div>
          <a class="vk-title" href="listing.php?id=<?= (int)$l['listing_id'] ?>"><?= e($l['title']) ?></a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
