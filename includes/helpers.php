<?php
// Ensure this file is always included AFTER config/database.php (for BASE_URL)
// and includes/auth.php (for isLoggedIn and hasRole).

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateCsrfToken() {
    // Ensure session is started before accessing $_SESSION
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    // Ensure session is started before accessing $_SESSION
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to generate a URL-friendly slug
function createSlug($text) {
    // Convert to lowercase
    $text = strtolower($text);
    // Replace non-alphanumeric characters (excluding hyphens) with a single hyphen
    $text = preg_replace('/[^a-z0-9-]+/', '-', $text);
    // Remove duplicate hyphens
    $text = preg_replace('/--+/', '-', $text);
    // Trim hyphens from beginning and end
    $text = trim($text, '-');
    return $text;
}


function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) { // Calls isLoggedIn from auth.php
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function redirectIfNotAdmin() {
    // Assuming hasRole is available (e.g., from auth.php)
    if (!hasRole('admin')) { 
        header('Location: ' . BASE_URL . '/403.php');
        exit;
    }
}
