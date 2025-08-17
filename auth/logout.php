<?php
require_once '../includes/auth.php'; // Include the file containing the logout() function
session_start();
logout(); // Call the logout function

header('Location: login.php'); // Redirect to the login page or homepage
exit;
?>