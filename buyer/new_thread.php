<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once '../includes/helpers.php';
requireAuth("buyer");

$userId = $_SESSION['user_id'];
$pageTitle = "Start Conversation";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf'])) {
    $pdo->beginTransaction();
    try {
        // Create thread
        $stmt = $pdo->prepare("INSERT INTO threads (subject, created_by, seller_id) VALUES (?, ?, ?)");
        $stmt->execute([trim($_POST['subject']), $userId, $_POST['seller_id']]);
        $threadId = $pdo->lastInsertId();

        // Add buyer as participant
        $pdo->prepare("INSERT INTO thread_participants (thread_id, user_id, role) VALUES (?, ?, 'buyer')")
            ->execute([$threadId, $userId]);

        // Attach a seller
        if (!empty($_POST['seller_id'])) {
            $pdo->prepare("INSERT INTO thread_participants (thread_id, user_id, role) VALUES (?, ?, 'seller')")->execute([$threadId, (int)$_POST['seller_id']]);
        }

        // Optionally: add admin as moderator
        $adminId = $pdo->query("SELECT id FROM users WHERE role='admin' AND is_active=1 LIMIT 1")->fetchColumn();
        if ($adminId) {
            $pdo->prepare("INSERT INTO thread_participants (thread_id, user_id, role) VALUES (?, ?, 'admin')")
                ->execute([$threadId, $adminId]);
        }

        // Insert first message
        $pdo->prepare("INSERT INTO thread_messages (thread_id, user_id, role, message) VALUES (?, ?, 'buyer', ?)")
            ->execute([$threadId, $userId, $_POST['message']]);

        $pdo->commit();
        header("Location: thread.php?id=".$threadId);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        if (!$stmt->fetch()) {
            $_SESSION['error'] = "Error creating thread: ".$e->getMessage(); 
            header('Location: ' . BASE_URL . '/buyer/messages.php'); 
            exit;
        }
    }
}

$sellers = $pdo->query("SELECT * FROM users WHERE `role` = 'seller'")->fetchAll();

include "../includes/header.php";
?>
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <form method="post" class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-sky-800 mb-6 text-center">Start New Thread</h1>
        <input type="hidden" name="csrf" value="<?= generateCsrfToken() ?>">

        <!-- Select Seller -->
        <select name="seller_id" id="seller_id" required 
                class="w-full px-3 py-2 border border-gray-300 rounded-md mb-3">
            <option value="" disabled selected>Choose a seller</option>
            <?php foreach ($sellers as $seller): ?>
                <option value="<?= htmlspecialchars($seller['id']) ?>">
                    <?= htmlspecialchars(trim($seller['first_name'] . ' ' . $seller['last_name'] .' ('. $seller['username'] .')')) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Subject -->
        <input type="text" name="subject" placeholder="Subject" required 
               class="w-full px-3 py-2 border border-gray-300 rounded-md mb-3">

        <!-- Message -->
        <textarea name="message" rows="4" placeholder="Message..." required 
                  class="w-full px-3 py-2 border border-gray-300 rounded-md mb-3"></textarea>

        <!-- Actions -->
        <div class="flex items-center gap-3">
            <button type="submit"
                class="bg-sky-600 text-white px-6 py-2 rounded-md hover:bg-sky-700 transition duration-300">
                Start Conversation
            </button>
            <a href="<?= BASE_URL ?>/buyer/messages.php" 
               class="px-4 py-2 rounded-md bg-gray-600 text-white hover:bg-gray-700">
               Cancel
            </a>
        </div>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
