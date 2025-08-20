-- ==============================
-- Database: bookstore
-- ==============================

-- CREATE DATABASE IF NOT EXISTS bookstore
--   DEFAULT CHARACTER SET utf8mb4
--   DEFAULT COLLATE utf8mb4_unicode_ci;

-- USE bookstore;

-- ==============================
-- Users
-- ==============================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'seller', 'buyer') NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    address TEXT,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ==============================
-- Seller Profiles
-- ==============================
CREATE TABLE IF NOT EXISTS seller_profiles (
    user_id INT PRIMARY KEY,
    store_name VARCHAR(100) NOT NULL,
    store_description TEXT,
    logo VARCHAR(255),
    payment_details TEXT,
    kyc_verified BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==============================
-- Categories
-- ==============================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    parent_id INT,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ==============================
-- Books
-- ==============================
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    isbn VARCHAR(20),
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0 NOT NULL,
    cover_image VARCHAR(255),
    preview_pages VARCHAR(255),
    is_physical BOOLEAN DEFAULT TRUE,
    is_digital BOOLEAN DEFAULT FALSE,
    digital_file VARCHAR(255),
    approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==============================
-- Book Categories (many-to-many)
-- ==============================
CREATE TABLE IF NOT EXISTS book_categories (
    book_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (book_id, category_id),
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ==============================
-- Coupons
-- ==============================
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(64) UNIQUE NOT NULL,
    type ENUM('percent','fixed') DEFAULT 'percent' NOT NULL,
    amount DECIMAL(12,2) DEFAULT 0.00 NOT NULL,
    active BOOLEAN DEFAULT TRUE NOT NULL,
    seller_id INT NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0.00,
    usage_limit INT,
    times_used INT DEFAULT 0,
    starts_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS coupon_usages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    buyer_id INT NOT NULL,
    used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id),
    FOREIGN KEY (buyer_id) REFERENCES users(id)
);

-- ==============================
-- Orders
-- ==============================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    coupon_id INT DEFAULT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id)
);

-- ==============================
-- Order Items
-- ==============================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_digital BOOLEAN DEFAULT FALSE,
    digital_downloads INT DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- ==============================
-- Reviews
-- ==============================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==============================
-- Wishlists
-- ==============================
CREATE TABLE IF NOT EXISTS wishlists (
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, book_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- ==============================
-- Settings
-- ==============================
CREATE TABLE IF NOT EXISTS settings (
    name VARCHAR(50) PRIMARY KEY,
    value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO settings (name, value)
VALUES ('commission', '0.15')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- ==============================
-- Password Resets
-- ==============================
CREATE TABLE IF NOT EXISTS password_resets (
    email VARCHAR(100) PRIMARY KEY,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL
);

-- ==============================
-- Messaging (Threads)
-- ==============================
CREATE TABLE IF NOT EXISTS threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    created_by INT NOT NULL,   -- who started (usually buyer)
    seller_id INT NOT NULL,    -- conversation is tied to a seller
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (seller_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS thread_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('buyer','seller','admin') NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS thread_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('buyer','seller','admin') NOT NULL,
    last_read_at DATETIME,
    FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ==============================
-- Indexes
-- ==============================
CREATE INDEX idx_books_title       ON books(title);
CREATE INDEX idx_books_author      ON books(author);
CREATE INDEX idx_books_isbn        ON books(isbn);
CREATE INDEX idx_books_description ON books(description(100));
CREATE INDEX idx_books_price       ON books(price);
CREATE INDEX idx_books_seller      ON books(seller_id);
CREATE INDEX idx_books_created     ON books(created_at);
CREATE INDEX idx_books_approved    ON books(approved);

CREATE INDEX idx_reviews_book      ON reviews(book_id);
CREATE INDEX idx_reviews_rating    ON reviews(rating);

CREATE INDEX idx_orders_buyer      ON orders(buyer_id);
CREATE INDEX idx_orders_status     ON orders(status);
CREATE INDEX idx_orders_date       ON orders(order_date);

CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_order_items_book  ON order_items(book_id);

CREATE INDEX idx_wishlists_user    ON wishlists(user_id);
CREATE INDEX idx_wishlists_book    ON wishlists(book_id);

CREATE INDEX idx_seller_profiles_store ON seller_profiles(store_name);

-- Optional Full-text search
ALTER TABLE books
  ADD FULLTEXT idx_books_text (title, author, description);
