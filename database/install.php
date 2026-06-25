<?php
/**
 * One-time seed script. Run from the browser AFTER importing schema.sql:
 *    https://your-site/database/install.php
 * It inserts demo staff + member accounts, listings and a sample escrow order.
 * DELETE THIS FILE after running it in production.
 *
 * Default password for every demo account:  Password123!
 */
require_once __DIR__ . '/../config/db.php';
$pdo = db();
$hash = password_hash('Password123!', PASSWORD_DEFAULT);

function roleId(PDO $pdo, string $name): int {
    $s = $pdo->prepare('SELECT role_id FROM roles WHERE role_name = ?');
    $s->execute([$name]);
    return (int)$s->fetchColumn();
}
function catId(PDO $pdo, string $slug): int {
    $s = $pdo->prepare('SELECT category_id FROM categories WHERE slug = ?');
    $s->execute([$slug]);
    return (int)$s->fetchColumn();
}

$users = [
    ['Administrator', 'Thabo Admin',   'admin@vukamarket.co.za',     '0821110001', 'active', 1],
    ['Moderator',     'Lerato Mod',    'moderator@vukamarket.co.za', '0821110002', 'active', 1],
    ['Support Agent', 'Sipho Support', 'support@vukamarket.co.za',   '0821110003', 'active', 1],
    ['Member',        'Nomsa Dlamini', 'nomsa@example.com',          '0721234567', 'active', 1],
    ['Member',        'Kabelo Mokoena','kabelo@example.com',         '0739876543', 'active', 0],
];
$ins = $pdo->prepare('INSERT INTO users (role_id, full_name, email, phone, password_hash, status, id_verified)
                      VALUES (?, ?, ?, ?, ?, ?, ?)');
foreach ($users as $u) {
    try { $ins->execute([roleId($pdo,$u[0]), $u[1], $u[2], $u[3], $hash, $u[4], $u[5]]); }
    catch (PDOException $e) { /* already seeded */ }
}

$nomsa  = (int)$pdo->query('SELECT user_id FROM users WHERE email="nomsa@example.com"')->fetchColumn();
$kabelo = (int)$pdo->query('SELECT user_id FROM users WHERE email="kabelo@example.com"')->fetchColumn();

$listings = [
    [$nomsa,  'phones',      'Samsung Galaxy A14 - good condition', 'Barely used, includes charger and box.', 2200.00, 'good',     'Soweto, Gauteng'],
    [$nomsa,  'crafts',      'Handmade beaded necklace set',        'Locally made Zulu beadwork, 3 pieces.',  180.00,  'new',      'Soweto, Gauteng'],
    [$kabelo, 'electronics', 'JBL Bluetooth speaker',               'Loud and clear, great battery life.',    650.00,  'like_new', 'Khayelitsha, WC'],
    [$kabelo, 'fashion',     'Nike sneakers UK 9',                  'Worn twice, too small for me.',          900.00,  'like_new', 'Khayelitsha, WC'],
    [$nomsa,  'home',        'Two-seater couch',                    'Comfortable, minor wear. Pickup only.',  1500.00, 'good',     'Soweto, Gauteng'],
    [$kabelo, 'electronics', 'HP laptop charger 65W',               'Genuine HP, works perfectly.',           250.00,  'good',     'Khayelitsha, WC'],
];
$li = $pdo->prepare('INSERT INTO listings (seller_id, category_id, title, description, price, item_condition, location, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, "active")');
$img = $pdo->prepare('INSERT INTO listing_images (listing_id, file_path, is_primary) VALUES (?, ?, 1)');
foreach ($listings as $l) {
    $li->execute([$l[0], catId($pdo,$l[1]), $l[2], $l[3], $l[4], $l[5], $l[6]]);
    $img->execute([(int)$pdo->lastInsertId(), 'assets/img/placeholder.png']);
}

// One completed escrow order with a review (Kabelo buys Nomsa's necklace)
$necklace = (int)$pdo->query('SELECT listing_id FROM listings WHERE title LIKE "Handmade beaded%" LIMIT 1')->fetchColumn();
if ($necklace) {
    $pdo->prepare('INSERT INTO orders (listing_id, buyer_id, seller_id, amount, delivery_method, status)
                   VALUES (?, ?, ?, 180.00, "pickup_point", "completed")')->execute([$necklace, $kabelo, $nomsa]);
    $oid = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO transactions (order_id, gateway, gateway_ref, amount, escrow_status, released_at)
                   VALUES (?, "payfast", "PAYFAST-204815", 190.80, "released", NOW())')->execute([$oid]);
    $pdo->prepare('INSERT INTO reviews (order_id, reviewer_id, reviewee_id, rating, comment)
                   VALUES (?, ?, ?, 5, "Great seller, item exactly as described!")')->execute([$oid, $kabelo, $nomsa]);
    $pdo->prepare('UPDATE users SET rating_avg = 5.00 WHERE user_id = ?')->execute([$nomsa]);
}

echo 'Seed complete. Demo login password for all accounts: Password123!  --  Please delete this file.';
