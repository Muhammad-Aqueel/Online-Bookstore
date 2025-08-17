-- Database: bookstore
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
-- Seller profiles
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
    parent_id INT NULL,
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
    stock INT NOT NULL DEFAULT 0,
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
-- Book categories (many-to-many)
-- ==============================
CREATE TABLE IF NOT EXISTS book_categories (
    book_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (book_id, category_id),
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ==============================
-- Orders
-- ==============================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==============================
-- Order items
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

-- Insert commission setting
INSERT INTO settings (name, value) VALUES ('commission', '0.15')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- ==============================
-- Password resets
-- ==============================
CREATE TABLE IF NOT EXISTS password_resets (
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    PRIMARY KEY (email)
);

-- ==============================
-- Indexes for performance
-- ==============================

-- Books search fields
CREATE INDEX idx_books_title        ON books(title);
CREATE INDEX idx_books_author       ON books(author);
CREATE INDEX idx_books_isbn         ON books(isbn);
CREATE INDEX idx_books_description  ON books(description(100));

-- Books filters
CREATE INDEX idx_books_price        ON books(price);
CREATE INDEX idx_books_format       ON books(is_physical, is_digital);
CREATE INDEX idx_books_seller       ON books(seller_id);
CREATE INDEX idx_books_created      ON books(created_at);
CREATE INDEX idx_books_approved     ON books(approved);

-- Many-to-many
CREATE INDEX idx_book_categories_book  ON book_categories(book_id);
CREATE INDEX idx_book_categories_cat   ON book_categories(category_id);

-- Reviews
CREATE INDEX idx_reviews_book       ON reviews(book_id);
CREATE INDEX idx_reviews_rating     ON reviews(rating);

-- Sellers
CREATE INDEX idx_seller_profiles_store ON seller_profiles(store_name);

-- Orders
CREATE INDEX idx_orders_buyer       ON orders(buyer_id);
CREATE INDEX idx_orders_status      ON orders(status);
CREATE INDEX idx_orders_date        ON orders(order_date);

-- Order items
CREATE INDEX idx_order_items_order  ON order_items(order_id);
CREATE INDEX idx_order_items_book   ON order_items(book_id);

-- Wishlists
CREATE INDEX idx_wishlists_user     ON wishlists(user_id);
CREATE INDEX idx_wishlists_book     ON wishlists(book_id);

-- ==============================
-- Optional Full-Text Search (for large datasets)
-- ==============================
ALTER TABLE books 
    ADD FULLTEXT idx_books_text (title, author, description);
