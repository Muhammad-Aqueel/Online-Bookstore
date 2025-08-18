<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once '../includes/helpers.php';
requireAuth("seller");

$userId = $_SESSION['user_id'];
$pageTitle = "Coupons";

// --- Toggle status ---
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    try {
        $stmt = $pdo->prepare("UPDATE coupons SET active = NOT active WHERE id = ? AND seller_id = ?");
        $stmt->execute([$id, $userId]);
        $_SESSION['success'] = "Coupon status updated.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to update status: " . $e->getMessage();
    }
    header("Location: coupons.php");
    exit;
}

// --- Fetch coupon for edit ---
$editCoupon = null;
if (isset($_GET['edit_id'])) {
    $id = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ? AND seller_id = ?");
    $stmt->execute([$id, $userId]);
    $editCoupon = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editCoupon) {
        $_SESSION['error'] = "Coupon not found.";
        header("Location: coupons.php");
        exit;
    }
}

// --- Handle create or update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf'])) {
    $code   = strtoupper(trim($_POST['code']));
    $type   = $_POST['type'];
    $amount = $_POST['amount'];
    $min    = $_POST['min_order_amount'] ?: 0;
    $limit  = $_POST['usage_limit'] ?: null;
    $start  = $_POST['starts_at'] ?: date('Y-m-d H:i:s');
    $end    = $_POST['expires_at'] ?: null;

    try {
        if (!empty($_POST['coupon_id'])) {
            // update existing
            $stmt = $pdo->prepare("UPDATE coupons 
                SET code=?, type=?, amount=?, min_order_amount=?, usage_limit=?, starts_at=?, expires_at=? 
                WHERE id=? AND seller_id=?");
            $stmt->execute([$code, $type, $amount, $min, $limit, $start, $end, $_POST['coupon_id'], $userId]);
            $_SESSION['success'] = "Coupon updated successfully.";
        } else {
            // create new
            $stmt = $pdo->prepare("INSERT INTO coupons 
                (code, type, amount, seller_id, min_order_amount, usage_limit, starts_at, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $type, $amount, $userId, $min, $limit, $start, $end]);
            $_SESSION['success'] = "Coupon added successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to save coupon: " . $e->getMessage();
    }
    header("Location: coupons.php");
    exit;
}

// --- Fetch seller coupons ---
$stmt = $pdo->prepare("SELECT * FROM coupons WHERE seller_id = ?");
$stmt->execute([$userId]);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include "../includes/header.php"; ?>

<div class="container mx-auto px-4 py-8 max-w-3xl">
    <div class="bg-white rounded-lg shadow p-6 mb-4">
        <h1 class="text-2xl font-bold text-sky-800 mb-6 text-center">
            <?= $editCoupon ? "Edit Coupon" : "My Coupons" ?>
        </h1>
        <form id="couponForm" method="post" class="space-y-6">
            <input type="hidden" name="csrf" value="<?= generateCsrfToken() ?>">
            <?php if ($editCoupon): ?>
                <input type="hidden" name="coupon_id" value="<?= $editCoupon['id'] ?>">
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Coupon Code -->
                <div class="relative">
                    <input id="code" name="code" placeholder=" " required
                        value="<?= htmlspecialchars($editCoupon['code'] ?? '') ?>"
                        class="peer w-full px-3 py-2 border border-gray-300 rounded-md placeholder-transparent focus:border-sky-500 focus:ring-0">
                    <label for="code"
                        class="absolute left-3 top-2 text-gray-500 text-sm transition-all
                        peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base
                        peer-focus:-top-2 peer-focus:text-xs peer-focus:text-sky-600
                        peer-[:not(:placeholder-shown)]:-top-2 peer-[:not(:placeholder-shown)]:text-xs peer-[:not(:placeholder-shown)]:text-gray-700
                        bg-white px-1">
                        Coupon Code
                    </label>
                </div>
                <!-- Usage Limit -->
                <div class="relative">
                    <input id="usage_limit" name="usage_limit" type="number" min="1" placeholder=" "
                        value="<?= htmlspecialchars($editCoupon['usage_limit'] ?? '') ?>"
                        class="peer w-full px-3 py-2 border border-gray-300 rounded-md placeholder-transparent focus:border-sky-500 focus:ring-0">
                    <label for="usage_limit"
                        class="absolute left-3 top-2 text-gray-500 text-sm transition-all
                        peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base
                        peer-focus:-top-2 peer-focus:text-xs peer-focus:text-sky-600
                        peer-[:not(:placeholder-shown)]:-top-2 peer-[:not(:placeholder-shown)]:text-xs peer-[:not(:placeholder-shown)]:text-gray-700
                        bg-white px-1">
                        Usage Limit
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <!-- Amount -->
                <div class="relative">
                    <input id="amount" name="amount" type="number" step="1" min="1" placeholder=" " required
                        value="<?= htmlspecialchars($editCoupon['amount'] ?? '') ?>"
                        class="peer w-full px-3 py-2 border border-gray-300 rounded-md placeholder-transparent focus:border-sky-500 focus:ring-0">
                    <label for="amount"
                        class="absolute left-3 top-2 text-gray-500 text-sm transition-all
                        peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base
                        peer-focus:-top-2 peer-focus:text-xs peer-focus:text-sky-600
                        peer-[:not(:placeholder-shown)]:-top-2 peer-[:not(:placeholder-shown)]:text-xs peer-[:not(:placeholder-shown)]:text-gray-700
                        bg-white px-1">
                        Amount
                    </label>
                </div>

                <!-- Discount Type -->
                <div class="relative">
                    <select id="type" name="type" required
                        class="peer w-full px-3 py-2 border border-gray-300 rounded-md focus:border-sky-500 focus:ring-0">
                        <option value="" disabled <?= empty($editCoupon) ? 'selected' : '' ?>></option>
                        <option value="percent" <?= ($editCoupon['type'] ?? '') === 'percent' ? 'selected' : '' ?>>Percentage (% off)</option>
                        <option value="fixed" <?= ($editCoupon['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount ($ off)</option>
                    </select>
                    <label id="typelabel" for="type"
                        class="absolute left-3 top-2 text-gray-400 text-base transition-all
                            peer-focus:-top-2 peer-focus:text-xs peer-focus:text-sky-600 bg-white px-1">
                        Discount Type
                    </label>
                </div>

                <!-- Minimum Order Amount -->
                <div class="relative">
                    <input id="min_order_amount" name="min_order_amount" type="number" step="1" min="1" placeholder=" "
                        value="<?= htmlspecialchars($editCoupon['min_order_amount'] ?? '') ?>"
                        class="peer w-full px-3 py-2 border border-gray-300 rounded-md placeholder-transparent focus:border-sky-500 focus:ring-0">
                    <label for="min_order_amount"
                        class="absolute left-3 top-2 text-gray-500 text-sm transition-all
                        peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base
                        peer-focus:-top-2 peer-focus:text-xs peer-focus:text-sky-600
                        peer-[:not(:placeholder-shown)]:-top-2 peer-[:not(:placeholder-shown)]:text-xs peer-[:not(:placeholder-shown)]:text-gray-700
                        bg-white px-1">
                        Min Order Amount
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="relative">
                    <input id="starts_at" name="starts_at" type="datetime-local"
                        value="<?= $editCoupon ? date('Y-m-d\TH:i', strtotime($editCoupon['starts_at'])) : '' ?>"
                        class="peer border border-gray-300 p-2 rounded w-full">
                    <label for="starts_at" class="absolute left-2 -top-2 text-xs text-sky-600 bg-white px-1">
                        Start Date & Time
                    </label>
                </div>
                <div class="relative">
                    <input id="expires_at" name="expires_at" type="datetime-local"
                        value="<?= $editCoupon && $editCoupon['expires_at'] ? date('Y-m-d\TH:i', strtotime($editCoupon['expires_at'])) : '' ?>"
                        class="peer border border-gray-300 p-2 rounded w-full">
                    <label for="expires_at" class="absolute left-2 -top-2 text-xs text-sky-600 bg-white px-1">
                        Expiration Date & Time
                    </label>
                </div>
            </div>
            <button type="submit" class="mt-4 bg-sky-600 text-white px-6 py-2 rounded-md hover:bg-sky-700 transition duration-300">
                <?= $editCoupon ? "Update Coupon" : "Create Coupon" ?>
            </button>
            <?php if ($editCoupon): ?>
                <a href="coupons.php" class="inline-block px-4 py-2 rounded-md bg-gray-600 text-white hover:bg-gray-300 hover:text-gray-700 mb-2">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Coupon List -->
    <div class="overflow-auto max-h-[600px]">
        <table class="data-table min-w-full bg-white shadow rounded">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($coupons as $c): ?>
                <tr class="border-b">
                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($c['code']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= $c['type'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= $c['amount'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= $c['times_used'] ?>/<?= $c['usage_limit'] ?: "∞" ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?= $c['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?= $c['active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                        <a href="coupons.php?toggle_status=<?= $c['id'] ?>" 
                           class="text-xs bg-sky-600 text-white px-2 py-1 rounded hover:bg-sky-700"
                           onclick="return confirm('Are you sure you want to toggle this coupon\'s status?')">Toggle</a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                        <a href="coupons.php?edit_id=<?= $c['id'] ?>" class="text-sky-600 hover:text-sky-800 mr-2">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    const select = document.getElementById("type");
    const typelabel = document.getElementById("typelabel");

    function checkSelectValue() {
        if (select.value) {
            typelabel.classList.remove('top-2','text-gray-400','text-base','transition-all','peer-focus:-top-2','peer-focus:text-xs','peer-focus:text-sky-600');
            typelabel.classList.add('-top-2','text-xs','text-gray-700');
        }
    }

    // Run when changed
    select.addEventListener("change", checkSelectValue);
    // useful when edit
    checkSelectValue();

    document.getElementById("couponForm").addEventListener("submit", function (e) {
        const startInput = document.getElementById("starts_at");
        const expiresInput = document.getElementById("expires_at");

        const startValue = startInput.value;
        const expiresValue = expiresInput.value;

        // Only validate if expires_at has a value
        if (expiresValue) {
            const startDate = new Date(startValue);
            const expiresDate = new Date(expiresValue);

            if (expiresDate < startDate) {
                e.preventDefault(); // stop form submission
                alert("Expiration date cannot be earlier than the start date.");
                expiresInput.focus();
            }
        }
    });
</script>
<?php include "../includes/footer.php"; ?>


