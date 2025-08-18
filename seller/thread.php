<?php

require_once "../config/database.php";
require_once "../includes/auth.php";
require_once '../includes/helpers.php';
requireAuth("seller");

$threadId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$pageTitle = "Conversation";

// Ensure seller is participant
$stmt = $pdo->prepare("SELECT * FROM thread_participants WHERE thread_id=? AND user_id=?");
$stmt->execute([$threadId, $userId]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "Not authorized"; 
    header('Location: ' . BASE_URL . '/seller/messages.php'); 
    exit;
}

// New message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf'])) {
    $stmt = $pdo->prepare("INSERT INTO thread_messages (thread_id, user_id, role, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$threadId, $userId, $_SESSION['user_role'], $_POST['message']]);
    $pdo->prepare("UPDATE threads SET updated_at=NOW() WHERE id=?")->execute([$threadId]);
    header("Location: thread.php?id=".$threadId);
    exit;
}

// Fetch thread subject
$stmt = $pdo->prepare("SELECT * FROM threads WHERE id=?");
$stmt->execute([$threadId]);
$thread = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$thread) { 
    $_SESSION['info'] = "Thread not found"; 
    header('Location: ' . BASE_URL . '/seller/messages.php'); 
    exit;
}

// Messages
$stmt = $pdo->prepare("SELECT m.*, u.username FROM thread_messages m
    JOIN users u ON u.id=m.user_id
    WHERE m.thread_id=? ORDER BY m.created_at ASC");
$stmt->execute([$threadId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>
<div class="mx-4 my-8">
    <div class="mb-2 flex justify-between items-center flex-wrap">
        <h1 class="text-2xl font-bold text-sky-800 mb-2">Subject: <?= htmlspecialchars($thread['subject']) ?></h1>
        <a href="messages.php" class="px-4 py-2 rounded-md bg-sky-600 text-white hover:bg-gray-300 hover:text-gray-700 mb-2"><i class="fa-solid fa-circle-arrow-left mr-2"></i>Go Back</a>
    </div>
    <div id="chatBox" class="bg-white shadow rounded p-4 space-y-4 overflow-y-auto max-h-[330px]">
        <?php foreach ($messages as $msg): ?>
            <?php
                // Default style
                $roleClass = 'bg-gray-50 text-gray-800 border border-gray-300';

                if ($msg['role'] === 'admin') {
                    $roleClass = 'bg-purple-100 text-purple-800 border border-purple-300';
                } elseif ($msg['role'] === 'seller') {
                    $roleClass = 'bg-blue-100 text-blue-800 border border-blue-300';
                } elseif ($msg['role'] === 'buyer') {
                    $roleClass = 'bg-green-100 text-green-800 border border-green-300';
                }
                if($msg['role'] === $userRole){
                    $roleClass .= ' ml-[50px]';
                } else {
                    $roleClass .= ' mr-[50px]';
                }
            ?>
            <div class="p-3 rounded <?= $roleClass ?>">
                <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                <div class="text-xs text-gray-500"><?= htmlspecialchars($msg['username']) ?> (<?= htmlspecialchars($msg['role']) ?>) | <?= htmlspecialchars($msg['created_at']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <form id="chatForm" method="post" class="mt-4">
        <input type="hidden" name="csrf" value="<?= generateCsrfToken() ?>">
        <div class="relative mt-4">
            <textarea id="messageInput" name="message" rows="3" required
                class="w-full border p-2 rounded pr-12 resize-none"
                placeholder="Type your reply..."></textarea>
            <button type="submit"
                class="absolute bottom-3 right-4 text-white px-2 py-1 rounded-full">
                <i class="fas fa-paper-plane text-sky-600 hover:text-sky-700 text-xl"></i>
            </button>
        </div>
    </form>
</div>
<?php include "../includes/footer.php"; ?>
