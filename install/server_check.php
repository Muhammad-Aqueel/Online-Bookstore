<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'];
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    if(isset($_POST['name'])){
        $name = $_POST['name'];
    }

    try {
        if(isset($_POST['name'])){
            $pdo = new PDO("mysql:host=$host", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("USE `$name`");

            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded'>Connected to database <strong>" . htmlspecialchars($name) . "</strong></div>";
        } else {
            $pdo = new PDO("mysql:host=$host", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded'>Host connection successful <strong></strong></div>";
        }
    } catch (PDOException $e) {
        if(isset($_POST['name'])){
            error_log("DB Error: " . $e->getMessage()); // Log it
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded'>" . htmlspecialchars("Database connection failed") . "</div>";
        } else {
            error_log("Host Error: " . $e->getMessage()); // Log it
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded'>" . htmlspecialchars("Host connection failed") . "</div>";
        }
    }
}
