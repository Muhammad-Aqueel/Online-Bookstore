# BookStore (PHP + MySQL) — Admin/Seller/Buyer Multi‑Role App

A complete **online bookstore** built with **plain PHP (PDO)** and **MySQL**, featuring distinct **Admin**, **Seller**, and **Buyer** areas. It includes a one‑click **installer**, product (book) management for **physical & digital** formats, a shopping cart and checkout flow (mock payment), **digital downloads**, **reviews**, **wishlists**, and role‑based access control — all with a Tailwind‑styled UI (via CDN).

---

## Features

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
- The **coupon system** allows discounts during checkout, supporting both percentage-based and fixed-amount coupons.

### Digital Downloads & Library
- Sellers can upload a **digital file** and optional **preview**.
- Buyers get a **Digital Library** of purchased eBooks.
- Secure **download endpoint** validates purchase, order status (`payment_status = 'completed'`), and updates **download count** per item.

### Messaging Feature
- The messaging system enables secure communication between buyers, sellers, and admins.
- Buyer can create new threads with a seller (about an order, book, etc.).
- Seller can reply to buyer threads, or continue ongoing conversations.
- Admin has oversight: can list all threads and intervene in conversations.
```text
                        ┌────────────┐
                        │   Admin    │
                        │────────────│
                        │ - View All │
                        │   Threads  │
                        │ - Intervene│
                        └────┬───────┘
                             │ Admin can join/monitor
      ┌──────────────────────┼────────────────┐
      ▼                      ▼                ▼
┌────────────┐     Thread Created      ┌────────────┐
│   Buyer    │ ──────────────────────► │   Seller   │
│────────────│   (order, book, etc.)   │────────────│
│ Start new  │                         │ - Reply    │
│   thread   │           Replies       │ - Continue │
└────────────┘ ◄────────────────────── └────────────┘
        ▲       Ongoing conversation             ▲
        └────────────────────────────────────────┘
```
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
│
├── admin/                  # Admin dashboard & management
│   ├── approve_book.php    # Approve seller book listings
│   ├── books.php           # Manage all books
│   ├── categories.php      # Manage categories
│   ├── delete_user.php     # Remove a user
│   ├── earnings.php        # Admin earnings/commissions
│   ├── edit_user.php       # Edit user details
│   ├── fetch_messages.php  # Fetch messages (AJAX/utility for threads)
│   ├── index.php           # Admin dashboard home
│   ├── messages.php        # Messaging system - list threads
│   ├── order_details.php   # Order detail view
│   ├── orders.php          # View all orders
│   ├── reject_book.php     # Reject book submissions
│   ├── seller_profiles.php # Review seller profiles
│   ├── sellers.php         # Manage sellers
│   ├── settings.php        # Site settings (commission, etc.)
│   ├── thread.php          # Messaging system - single thread view
│   └── users.php           # Manage users
│
├── assets/                 # Static assets
│   ├── css/
│   │   └── styles.css      # Main stylesheet
│   ├── digital_books/      # Digital book files (eBooks)
│   ├── images/             # Image storage
│   │   ├── books/          # Book cover images
│   │   ├── logos/          # Seller/store logos
│   │   └── users/          # User avatars
│   │       └── default.png # Default profile image
│   ├── js/
│   │   └── main.js         # Global JavaScript
│   └── previews/           # Book preview files
│
├── auth/                   # Authentication
│   ├── account.php         # User account page
│   ├── login.php           # User login
│   ├── logout.php          # Logout handler
│   ├── register.php        # User registration
│   └── reset_password.php  # Password reset
│
├── buyer/                  # Buyer area
│   ├── book.php            # Book details page
│   ├── cancel_order.php    # Cancel an order
│   ├── cart.php            # Shopping cart
│   ├── checkout.php        # Checkout form & coupon input
│   ├── checkout_process.php# Handle checkout & order creation
│   ├── download.php        # eBook download handler
│   ├── fetch_messages.php  # Fetch buyer messages (AJAX/utility)
│   ├── index.php           # Buyer dashboard/home
│   ├── library.php         # Digital library for eBooks
│   ├── messages.php        # List threads (buyer)
│   ├── new_thread.php      # Start a new message thread
│   ├── order_confirmation.php # Order confirmation page
│   ├── order_details.php   # Buyer order details
│   ├── orders.php          # Buyer order history
│   ├── search.php          # Search form main page
│   ├── search_results.php  # Search results (AJAX based)
│   ├── search_suggest.php  # AJAX suggestions
│   ├── sidebar_filters.php # Sidebar filter options
│   ├── thread.php          # Messaging system - single thread view
│   └── wishlist.php        # Wishlist management
│
├── config/
│   └── database.php        # Database connection settings
│
├── includes/               # Shared includes
│   ├── auth.php            # Authentication check helpers
│   ├── footer.php          # Common footer
│   ├── header.php          # Common header/navbar
│   └── helpers.php         # Utility/helper functions
│
├── install/                # Installer
│   ├── .htaccess           # Restrict access post-install
│   ├── 403.html            # Installer forbidden page
│   ├── index.php           # Installation script
│   ├── install.sql         # SQL schema
│   └── server_check.php    # Check server/database requirements
│
├── seller/                 # Seller area
│   ├── add_book.php        # Add new book
│   ├── books.php           # Manage seller's books
│   ├── coupons.php         # Manage seller coupons
│   ├── earnings.php        # Seller earnings overview
│   ├── edit_book.php       # Edit book details
│   ├── fetch_messages.php  # Fetch seller messages (AJAX/utility)
│   ├── index.php           # Seller dashboard/home
│   ├── messages.php        # Messaging system - list threads
│   ├── order_details.php   # Order details (seller side)
│   ├── orders.php          # Seller order list
│   ├── profile.php         # Seller profile management
│   └── thread.php          # Messaging system - single thread view
│
├── .gitignore              # Git ignored files list
├── .htaccess               # Apache rewrite & access rules
├── 403.php                 # Custom 403 Forbidden page
├── 404.php                 # Custom 404 Not Found page
├── LICENSE                 # License information
├── README.md               # Project documentation
├── about.php               # About page
├── contact.php             # Contact page
├── faq.php                 # FAQ page
├── index.php               # Homepage
├── returns.php             # Returns policy page
└── shipping.php            # Shipping policy page
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

---

## Credits

Built with ❤️ using PHP, MySQL, and Tailwind (CDN).