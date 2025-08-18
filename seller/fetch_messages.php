<?php

require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/helpers.php";

$threadId = (int)$_GET['thread_id'];
$lastId   = (int)($_GET['last_id'] ?? 0);

// Ensure user is participant
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT 1 FROM thread_participants WHERE thread_id=? AND user_id=?");
$stmt->execute([$threadId, $userId]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Fetch only messages newer than lastId
$stmt = $pdo->prepare("
    SELECT m.*, u.username 
    FROM thread_messages m
    JOIN users u ON u.id=m.user_id
    WHERE m.thread_id=? AND m.id>? 
    ORDER BY m.created_at ASC
");
$stmt->execute([$threadId, $lastId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($messages);
