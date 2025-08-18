<?php

require_once "../config/database.php";
require_once "../includes/auth.php";
requireAuth("admin");
$pageTitle = "Messages";

$stmt = $pdo->query("SELECT * FROM threads ORDER BY updated_at DESC");
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>
<div class="px-4 mx-4 my-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">All Conversations</h1>
    <?php if($threads): ?>
        <div class="overflow-y-auto max-h-[2000px] mt-4">
            <?php foreach ($threads as $t): ?>
                <ul class="divide-y my-3 bg-white shadow rounded">
                    <li class="p-4 hover:bg-gray-50">
                        <a href="thread.php?id=<?= $t['id'] ?>" class="block">
                            <div class="font-semibold"><?= htmlspecialchars($t['subject']) ?></div>
                            <div class="text-sm text-gray-500">Last updated: <?= $t['updated_at'] ?></div>
                        </a>
                    </li>
                </ul>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <p class="text-gray-600">No conversation threads available.</p>
            </div>
    <?php endif; ?>
</div>
<?php include "../includes/footer.php"; ?>
