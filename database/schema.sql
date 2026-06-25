-- ============================================================
-- Vuka Market - C2C E-Commerce Platform
-- Database schema (MySQL 8 / MariaDB 10+)
-- Charset: utf8mb4
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS disputes;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS listing_images;
DROP TABLE IF EXISTS listings;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------
-- RBAC: roles, permissions, and the role_permissions bridge
-- ----------------------------------------------------------
CREATE TABLE roles (
    role_id      INT AUTO_INCREMENT PRIMARY KEY,
    role_name    VARCHAR(50)  NOT NULL UNIQUE,
    description  VARCHAR(255) NULL,
    is_staff     TINYINT(1)   NOT NULL DEFAULT 0,   -- 1 = may access the admin site
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    perm_key      VARCHAR(50)  NOT NULL UNIQUE,
    description   VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE role_permissions (
    role_id       INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id)       REFERENCES roles(role_id)       ON DELETE CASCADE,
    CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Users (every account has exactly one role)
-- ----------------------------------------------------------
CREATE TABLE users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    role_id        INT NOT NULL,
    full_name      VARCHAR(120) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    phone          VARCHAR(20)  NULL,
    password_hash  VARCHAR(255) NOT NULL,
    id_verified    TINYINT(1)   NOT NULL DEFAULT 0,
    status         ENUM('active','pending','suspended') NOT NULL DEFAULT 'active',
    rating_avg     DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Catalogue: categories and listings
-- ----------------------------------------------------------
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80) NOT NULL,
    slug        VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE listings (
    listing_id   INT AUTO_INCREMENT PRIMARY KEY,
    seller_id    INT NOT NULL,
    category_id  INT NOT NULL,
    title        VARCHAR(150) NOT NULL,
    description  TEXT NULL,
    price        DECIMAL(10,2) NOT NULL,
    item_condition ENUM('new','like_new','good','fair') NOT NULL DEFAULT 'good',
    location     VARCHAR(120) NULL,
    status       ENUM('active','sold','removed','pending') NOT NULL DEFAULT 'active',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_listing_seller   FOREIGN KEY (seller_id)   REFERENCES users(user_id),
    CONSTRAINT fk_listing_category FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE listing_images (
    image_id   INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    file_path  VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_img_listing FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Orders, escrow transactions, reviews and disputes
-- ----------------------------------------------------------
CREATE TABLE orders (
    order_id        INT AUTO_INCREMENT PRIMARY KEY,
    listing_id      INT NOT NULL,
    buyer_id        INT NOT NULL,
    seller_id       INT NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    delivery_method ENUM('pickup_point','courier') NOT NULL DEFAULT 'pickup_point',
    status          ENUM('pending','paid_escrow','shipped','completed','disputed','refunded','cancelled')
                    NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_listing FOREIGN KEY (listing_id) REFERENCES listings(listing_id),
    CONSTRAINT fk_order_buyer   FOREIGN KEY (buyer_id)   REFERENCES users(user_id),
    CONSTRAINT fk_order_seller  FOREIGN KEY (seller_id)  REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id       INT NOT NULL,
    gateway        ENUM('payfast','yoco','ozow') NOT NULL,
    gateway_ref    VARCHAR(100) NULL,
    amount         DECIMAL(10,2) NOT NULL,
    escrow_status  ENUM('held','released','refunded') NOT NULL DEFAULT 'held',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    released_at    TIMESTAMP NULL,
    CONSTRAINT fk_txn_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reviews (
    review_id   INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating      TINYINT NOT NULL,            -- 1..5
    comment     VARCHAR(500) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rev_order    FOREIGN KEY (order_id)    REFERENCES orders(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_rev_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(user_id),
    CONSTRAINT fk_rev_reviewee FOREIGN KEY (reviewee_id) REFERENCES users(user_id),
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE disputes (
    dispute_id  INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    raised_by   INT NOT NULL,
    reason      VARCHAR(255) NOT NULL,
    status      ENUM('open','under_review','resolved','rejected') NOT NULL DEFAULT 'open',
    resolution  VARCHAR(500) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    CONSTRAINT fk_disp_order FOREIGN KEY (order_id)  REFERENCES orders(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_disp_user  FOREIGN KEY (raised_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Seed data: roles, permissions, mappings and categories
-- ----------------------------------------------------------
INSERT INTO roles (role_name, description, is_staff) VALUES
('Administrator', 'Full system access; manages users, roles and settings', 1),
('Moderator',     'Moderates listings and reviews flagged content',        1),
('Support Agent', 'Handles disputes and assists members',                  1),
('Member',        'Standard consumer who can buy and sell goods',          0);

INSERT INTO permissions (perm_key, description) VALUES
('manage_users',     'Create, view, update and delete user accounts'),
('manage_roles',     'Create, view, update and delete user types (roles)'),
('moderate_listings','Approve, edit or remove listings'),
('handle_disputes',  'View and resolve buyer/seller disputes'),
('view_reports',     'View platform analytics and reports');

-- Administrator gets every permission
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id FROM roles r CROSS JOIN permissions p
WHERE r.role_name = 'Administrator';

-- Moderator: moderate listings + view reports
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id FROM roles r JOIN permissions p
ON p.perm_key IN ('moderate_listings','view_reports')
WHERE r.role_name = 'Moderator';

-- Support Agent: handle disputes
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id FROM roles r JOIN permissions p
ON p.perm_key IN ('handle_disputes')
WHERE r.role_name = 'Support Agent';

INSERT INTO categories (name, slug) VALUES
('Electronics','electronics'),
('Fashion & Clothing','fashion'),
('Home & Furniture','home'),
('Handmade & Crafts','crafts'),
('Phones & Accessories','phones'),
('Other','other');
