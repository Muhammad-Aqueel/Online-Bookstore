<?php
    // Start session to use $_SESSION messages if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if already installed
    if (file_exists('../config/database.php')) {
        // If database.php exists, the system is installed.
        // Redirect to login page, or display a message and tell them to remove install dir.
        $_SESSION['error'] = 'The system is already installed. Please remove the "install" directory for security reasons.';
        header('Location: ../auth/login.php');
        exit;
    }

    $errors = [];
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dbHost = $_POST['db_host'] ?? '';
        $dbName = $_POST['db_name'] ?? '';
        $dbUser = $_POST['db_user'] ?? '';
        $dbPass = $_POST['db_pass'] ?? '';
        $adminusername = $_POST['admin_name'] ?? '';
        $adminEmail = $_POST['admin_email'] ?? '';
        $adminPassword = $_POST['admin_password'] ?? '';

        // Validate inputs
        if (empty($dbHost)) $errors[] = 'Database Host is required.';
        if (empty($dbName)) $errors[] = 'Database Name is required.';
        if (empty($dbUser)) $errors[] = 'Database Username is required.';
        if (empty($adminusername)) $errors[] = 'Admin Name is required.';
        if (empty($adminEmail)) $errors[] = 'Admin Email is required.';
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid Admin Email format.';
        if (empty($adminPassword) || strlen($adminPassword) < 6) $errors[] = 'Admin Password is required and must be at least 6 characters.';

        if (empty($errors)) {
            try {
                // Define all the required directories
                $requiredDirs = [
                    __DIR__ . '/../assets/images/books/',
                    __DIR__ . '/../assets/digital_books/',
                    __DIR__ . '/../assets/previews/',
                    __DIR__ . '/../assets/images/logos/',
                    __DIR__ . '/../config/'
                ];

                // Create each directory if it doesn't exist
                foreach ($requiredDirs as $dir) {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true); // true = create nested directories
                    }
                }

                // Attempt to connect to the MySQL server (without specifying DB name yet)
                $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Create database if it doesn't exist, then use it
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $pdo->exec("USE `{$dbName}`");

                // Execute SQL schema file to create tables
                // Make sure install.sql is in the same directory as this index.php
                $sql = file_get_contents('install.sql');
                if ($sql === false) {
                    throw new Exception('install.sql file not found. Please ensure it is in the install/ directory.');
                }
                $pdo->exec($sql);

                // Create admin user
                $hashedAdminPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, first_name, last_name, is_verified, is_active, is_approved)
                SELECT ?, ?, ?, 'admin', 'Admin', 'User', 1, 1, 1
                WHERE NOT EXISTS (
                    SELECT 1 FROM users WHERE username = ? OR email = ?
                )");
                // For username, we'll use the email as a simple default for admin
                $stmt->execute([$adminusername, $adminEmail, $hashedAdminPassword, $adminusername, $adminEmail]);

                // Dynamically determine BASE_URL
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                // dirname(dirname($_SERVER['SCRIPT_NAME'])) will give /book_store or /
                $appBasePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
                $baseUrl = $protocol . '://' . $host . $appBasePath;

                // Generate database.php content
                $configContent = <<<EOT
                <?php
                // Start session at the very beginning of the script
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                // Prevent direct access
                define('BASE_PATH', __DIR__ . '/../'); // Define BASE_PATH relative to database.php itself
                defined('BASE_PATH') or \$_SESSION['warning'] = 'No direct script access allowed';

                // Database configuration
                define('DB_HOST', '{$dbHost}');
                define('DB_NAME', '{$dbName}');
                define('DB_USER', '{$dbUser}');
                define('DB_PASS', '{$dbPass}');

                // Establish database connection
                try {
                    \$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                } catch (PDOException \$e) {
                    \$_SESSION['error'] = "Database connection failed: " . \$e->getMessage();
                }

                // Base URL (dynamically set during installation)
                define('BASE_URL', '{$baseUrl}');

                // Other settings
                define('SITE_NAME', 'Online Bookstore'); // Default value, can be modified after installation
                define('ITEMS_PER_PAGE', 12); // Default value, can be modified after installation

                // Define the commission rate and Fetch commission value from settings
                \$stmt = \$pdo->prepare("SELECT value FROM settings WHERE name = ?");
                \$stmt->execute(['commission']);
                \$commission = \$stmt->fetchColumn();

                // Define constant
                define('PLATFORM_COMMISSION_RATE', (float)\$commission);
                EOT;

                if (file_put_contents('../config/database.php', $configContent) === false) {
                    throw new Exception('Could not write to config/database.php. Check folder permissions.');
                }
                
                // --- NEW: Generate custom 403.html for the install directory ---
                $error403Content = <<<HTML
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Access Denied</title>
                    <script src="https://cdn.tailwindcss.com"></script>
                    <style>
                        body { background-color: #f1f5f9; } /* Tailwind gray-100 */
                    </style>
                </head>
                <body class="flex items-center justify-center min-h-screen">
                    <div class="bg-white p-8 rounded-lg shadow-lg text-center">
                        <h1 class="text-4xl font-bold text-red-600 mb-4">403 Forbidden</h1>
                        <p class="text-gray-700 text-lg">You don't have permission to access this resource.</p>
                        <p class="text-gray-500 text-sm mt-4 mb-5">Please remove the 'install' directory after successful installation for security.</p>
                        <a href="../" class="bg-sky-600 text-white px-6 py-3 rounded-lg shadow-md hover:bg-sky-700 transition duration-300">
                            Go to Homepage
                        </a>
                    </div>
                </body>
                </html>
                HTML;
                if (file_put_contents('403.html', $error403Content) === false) {
                    throw new Exception('Could not write to install/403.html. Check folder permissions.');
                }

                // --- MODIFIED: Create .htaccess to block install directory and set custom error document ---
                $htaccessContent = "Options -Indexes\n"; // Prevent directory listing
                $htaccessContent .= "Deny from all\n"; // Deny all access
                // ErrorDocument path is relative to the DocumentRoot of the website
                // We use the dynamically determined base path to ensure it works correctly
                $htaccessContent .= "ErrorDocument 403 " . $appBasePath . "/install/403.html\n";
                
                if (file_put_contents('.htaccess', $htaccessContent) === false) {
                    throw new Exception('Could not write to .htaccess in install/ directory. Check folder permissions.');
                }

                $_SESSION['success'] = 'Installation complete! You will be redirected to the login page.';
                // Redirect after a short delay for the user to see the success message
                header('Refresh: 3; URL=' . $baseUrl . '/auth/login.php');
                exit;

            } catch (PDOException $e) {
                $errors[] = "Database error during installation: " . $e->getMessage();
                // Clean up potentially created database.php if DB connection failed
                if (file_exists('../config/database.php')) {
                    unlink('../config/database.php');
                }
            } catch (Exception $e) {
                $errors[] = "Installation failed: " . $e->getMessage();
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookstore Installation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-sky-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-xl m-6"> 
        <h1 class="text-2xl font-bold text-sky-800 mb-6 text-center">Bookstore Installation</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <p><?php echo htmlspecialchars($success); ?></p>
                <p class="mt-2 text-center">You will be redirected to the login page shortly.</p>
            </div>
        <?php else: ?>
            <p class="text-gray-600 mb-6 text-center">Please enter your MySQL database and initial admin user details.</p>

            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-1">Database Host <span class="text-red-500">*</span></label>
                    <input type="text" name="db_host" value="localhost" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                               
                <div>
                    <label class="block text-gray-700 mb-1">Database Username <span class="text-red-500">*</span></label>
                    <input type="text" name="db_user" value="root" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-1">Database Password</label>
                    <input type="password" name="db_pass" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-1">Database Name <span class="text-red-500">*</span></label>
                    <input type="text" name="db_name" value="bookstore" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <!-- Connection Check Buttons -->
                <div class="mb-3 flex flex-wrap justify-center gap-2">
                    <button 
                        type="button" 
                        class="border border-gray-400 text-gray-700 hover:bg-gray-100 px-4 py-2 rounded flex items-center gap-2 w-auto"
                        onclick="checkConnection()"
                    >
                        <i class="fas fa-plug"></i> Check Host Connection
                    </button>

                    <button 
                        type="button" 
                        class="border border-gray-400 text-gray-700 hover:bg-gray-100 px-4 py-2 rounded flex items-center gap-2 w-auto"
                        onclick="checkDatabase()"
                    >
                        <i class="fas fa-database"></i> Check Database Existence
                    </button>
                </div>

                <div id="check-result" class="mt-2"></div>
                
                <div class="border-t pt-4 mt-4">
                    <h2 class="text-xl font-semibold text-sky-800 mb-4">Admin Setup</h2>
                    <label class="block text-gray-700 mb-1">Admin Name <span class="text-red-500">*</span></label>
                    <input type="text" name="admin_name" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div class="border-t pt-4 mt-4">
                    <label class="block text-gray-700 mb-1">Admin Email <span class="text-red-500">*</span></label>
                    <input type="email" name="admin_email" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div>
                    <label class="block text-gray-700 mb-1">Admin Password <span class="text-red-500">*</span></label>
                    <input type="password" name="admin_password" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                
                <button type="submit" class="w-full bg-sky-600 text-white py-2 px-4 rounded-md hover:bg-sky-700">
                    Install
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function checkConnection() {
            const data = new FormData();
            data.append('host', document.querySelector('[name="db_host"]').value);
            data.append('user', document.querySelector('[name="db_user"]').value);
            data.append('pass', document.querySelector('[name="db_pass"]').value);

            fetch('server_check.php', { method: 'POST', body: data })
                .then(res => res.text())
                .then(html => document.getElementById('check-result').innerHTML = html)
                .catch(() => {
                    document.getElementById('check-result').innerHTML = 
                    '<div class="text-red-600 flex items-center gap-2"><i class="fas fa-times-circle"></i> Error checking connection.</div>';
                });
        }

        function checkDatabase() {
            const data = new FormData();
            data.append('host', document.querySelector('[name="db_host"]').value);
            data.append('user', document.querySelector('[name="db_user"]').value);
            data.append('pass', document.querySelector('[name="db_pass"]').value);
            data.append('name', document.querySelector('[name="db_name"]').value);

            fetch('server_check.php', { method: 'POST', body: data })
                .then(res => res.text())
                .then(html => document.getElementById('check-result').innerHTML = html)
                .catch(() => {
                    document.getElementById('check-result').innerHTML = 
                    '<div class="text-red-600 flex items-center gap-2"><i class="fas fa-times-circle"></i> Error checking connection.</div>';
                });
        }
    </script>
</body>
</html>
