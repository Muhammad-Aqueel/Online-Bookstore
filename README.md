# BookStore (PHP + MySQL) — Admin/Seller/Buyer Multi‑Role App

A complete **online bookstore** built with **plain PHP (PDO)** and **MySQL**, featuring distinct **Admin**, **Seller**, and **Buyer** areas. It includes a one‑click **installer**, product (book) management for **physical & digital** formats, a shopping cart and checkout flow (mock payment), **digital downloads**, **reviews**, **wishlists**, and role‑based access control — all with a Tailwind‑styled UI (via CDN).

---

## Features (in detail)

### Public & General
- Landing page with **latest approved books**.
- Static pages: **About**, **Contact**, **FAQ**, **Shipping**, **Returns**.
- Mobile‑friendly UI using **Tailwind CSS (CDN)** and **Font Awesome** icons.
- Centralized layout via `includes/header.php` and `includes/footer.php`.

### Authentication & Accounts
- **Register / Login / Logout** (passwords hashed with `password_hash`).
- **Role-based session**: `admin`, `seller`, `buyer` with `requireAuth(<role>)` guards.
- **Account page** for profile viewing.
- **Password reset (token-based)** — demo flow stores token and allows reset (no email sending by default).

### Catalog & Search
- Books can be **physical**, **digital**, or **both**.
- **Categories** with optional **parent/child** hierarchy (`categories.parent_id`).
- **Book details** page with cover, metadata, description, **preview file** (PDF/images) if uploaded.
- **Search & filters**: query by keywords, category, price range, and format (physical/digital).
- **Reviews**: 1–5 star rating with comments; average rating displayed on book page.
- **Wishlist** for buyers (many‑to‑many: `wishlists`).

### Cart, Checkout & Orders
- Persistent **shopping cart** in session with item counts and totals.
- **Checkout** creates **orders** and **order_items** (mock payment flow).
- **Order statuses**: `pending`, `processing`, `shipped`, `delivered`, `cancelled`.
- Buyers can **view orders**, **order details**, **cancel orders** (when allowed).
- **Stock management**: decrements inventory on **physical** purchases.

### Digital Downloads & Library
- Sellers can upload a **digital file** and optional **preview**.
- Buyers get a **Digital Library** of purchased eBooks.
- Secure **download endpoint** validates purchase, order status (`payment_status = 'completed'`), and updates **download count** per item.

### Seller Portal
- **Add/Edit/Delete books**, set **price**, **stock**, formats, **cover image**, **preview**, and **categories**.
- **Orders** listing for orders containing the seller’s items.
- **Earnings report** (per day + monthly net, assumes 15% platform commission).

### Admin Portal
- **Dashboard**: overview counts for users, sellers, books, orders.
- **User management**: activate/deactivate, change role (`admin/seller/buyer`).
- **Seller profiles** review page.
- **Categories management**: CRUD with parent/child linkage.
- **Books approval workflow**: approve/reject new or edited books before they appear on the marketplace.
- **Orders management**: change order status.
- **Platform earnings report** with a configurable (in code) commission rate (default **15%**).

### Security & Best Practices Implemented
- **PDO prepared statements** throughout (SQL injection protection).
- **CSRF tokens** for sensitive actions (helpers: `generateCsrfToken`, `verifyCsrfToken`).
- **Password hashing** via `password_hash()`.
- **Role checks** and route guards (`requireAuth`, `hasRole`).
- Basic **.htaccess** hardening (disable directory listing, custom error docs).

> **Note:** Email sending and real payment gateway are **not implemented**; the checkout simulates a successful payment for demo purposes.

---

## Tech Stack

- **PHP** (plain, no framework) — works with PHP **7.4+ / 8.x**  
- **MySQL/MariaDB** (SQL in `install/install.sql`)  
- **Apache** (recommended) with `.htaccess` support (Nginx works too with equivalent rules)  
- **Tailwind CSS (CDN)** + **Font Awesome (CDN)**  
- **No Composer/Node build required**

---

## Notice before installation
>**Warning:** Sometimes antivirus detect `test_connection.php` as,
**Type of risk:** `webshell.phpex.post.eval`
due to its checking for host and database connection(code), so allow this file in antivrius or add in ignore list without any worries.
---

## Installation

### Prerequisites
- PHP **7.4+** with PDO MySQL extension enabled
- MySQL **5.7+** / MariaDB **10.3+**
- Apache (or Nginx) + ability to write to upload folders

### Quick Install (via built‑in Installer)
1. **Copy** the `book_store/` folder to your web root (e.g., `htdocs/` or `public_html/`).  
2. Ensure the web server can **write** to these folders (create them if they don’t exist):  
   - `assets/images/books/`  
   - `assets/digital_books/`  
   - `assets/previews/`  
3. In your browser, open: `http://<your-host>/book_store/install/`  
4. Enter **DB credentials** and create the **initial Admin** account.  
5. Upon success, the installer will generate `config/database.php`, run the schema, and compute `BASE_URL`.  
6. **Delete** the `/install` directory for security.  
7. Log in with the Admin account you just created.

### Manual Install (alternative)
1. Create a database (e.g., `bookstore`).  
2. Import `install/install.sql`.  
3. Copy `install/config.template` logic (or use the installer’s generated output) to create `config/database.php` with:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - `BASE_URL` (e.g., `http://localhost/book_store`)
4. Create upload directories and make them writable:  
   `assets/images/books/`, `assets/digital_books/`, `assets/previews/`
5. Place the project under your web root and visit `index.php`.

### Running Locally
- Using PHP’s built‑in server:  
  ```bash
  php -S localhost:8000 -t book_store
  ```
  Then browse to `http://localhost:8000/` (custom error pages and `.htaccess` features are Apache‑specific but the app itself works).
- Or use XAMPP for easier setup.

---

## Database Overview

Key tables (see `install/install.sql` for full schema):
- `users` (roles: `admin`, `seller`, `buyer`, profile fields, status/approval flags)
- `seller_profiles` (store metadata, KYC flag)
- `categories` (with `parent_id` for nesting)
- `books` (formats, stock, images, preview, digital file, approval flag)
- `book_categories` (many‑to‑many join)
- `orders`, `order_items` (status, payment status, download counts)
- `reviews` (rating 1–5 + comment)
- `wishlists` (user↔book mapping)
- `settings` (key/value pairs)
- `password_resets` (token + expiry; demo flow)

---

## Directory Tree

```text
book_store/
├── admin/
│   ├── approve_book.php
│   ├── books.php
│   ├── categories.php
│   ├── delete_user.php
│   ├── earnings.php
│   ├── edit_user.php
│   ├── index.php
│   ├── order_details.php
│   ├── orders.php
│   ├── reject_book.php
│   ├── seller_profiles.php
│   ├── sellers.php
│   ├── settings.php
│   └── users.php
│
├── assets/
│   ├── css/
│   │   └── styles.css
│   ├── digital_books/
│   ├── images/
│   │   ├── books/
│   │   ├── logos/
│   │   │   └── logo_689c10673fcf9.png
│   │   └── users/
│   │       └── default.png
│   ├── js/
│   │   └── main.js
│   └── previews/
│
├── auth/
│   ├── account.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   └── reset_password.php
│
├── buyer/
│   ├── book.php
│   ├── cancel_order.php
│   ├── cart.php
│   ├── checkout.php
│   ├── checkout_process.php
│   ├── download.php
│   ├── index.php
│   ├── library.php
│   ├── order_confirmation.php
│   ├── order_details.php
│   ├── orders.php
│   ├── search.php
│   ├── search_results.php
│   ├── search_suggest.php
│   ├── sidebar_filters.php
│   ├── suggest.php
│   └── wishlist.php
│
├── config/
│   └── database.php
│
├── includes/
│   ├── auth.php
│   ├── footer.php
│   ├── header.php
│   ├── header copy.php
│   └── helpers.php
│
├── install/
│   ├── .htaccess
│   ├── 403.html
│   ├── index.php
│   ├── install.sql
│   ├── install.sql_
│   └── server_check.php
│
├── seller/
│   ├── add_book.php
│   ├── books.php
│   ├── earnings.php
│   ├── edit_book.php
│   ├── index.php
│   ├── order_details.php
│   ├── orders.php
│   └── profile.php
│ 
├── .htaccess
├── 403.php
├── 404.php
├── README.md
├── about.php
├── contact.php
├── faq.php
├── index.php
├── returns.php
└── shipping.php
```

> Upload folders (`assets/images/books/`, `assets/digital_books/`, `assets/previews/`) may be **absent in a fresh clone** — create them and set proper permissions.

---

## Configuration

Most settings live in `config/database.php` (generated by the installer): DB credentials, `BASE_URL`, and constants like `SITE_NAME` & `ITEMS_PER_PAGE`. Commission rates are hardcoded in earnings pages; adjust as needed.

---

## Known Limitations / To‑Dos
- Email delivery for **password reset** is not implemented (demo token flow only).
- Checkout uses a **mock** payment processor; integrate a real gateway for production.
- Add server‑side file type/size validation and virus scanning for uploads in production.
- Consider rate limiting / captcha for auth and form endpoints in production.
- Add pagination to more listing pages if catalogs grow large.
- Discount coupons system
- Messaging between Users (Admin, Seller and Buyer)

---

## License

No license file was included. If you intend to publish this project, please add an appropriate license.

---

## Credits

Built with ❤️ using PHP, MySQL, and Tailwind (CDN).