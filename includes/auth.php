<?php
// session_start() removed from here. It should be called once in the main entry file (e.g., index.php, login.php).

// Check if user is logged in
function isLoggedIn() {
    // Ensure session is started before accessing $_SESSION
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

// Get current user data
function currentUser() {
    global $pdo;
    
    if (!isLoggedIn()) return null; // Use the local isLoggedIn for consistency
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Login function
function login($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username']; // Store username for display
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    
    return false;
}

// Logout function
function logout() {
    session_unset();
    session_destroy();
}

// Check if user has specific role
function hasRole($role) {
    // Ensure session is started before accessing $_SESSION
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Redirect if not logged in or doesn't have required role
function requireAuth($role = null) {
    // Assuming BASE_URL is defined (e.g., in config/database.php which is loaded by entry files)

    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
    
    if ($role && !hasRole($role)) {
        header('Location: ' . BASE_URL . '/403.php');
        exit;
    }
}
