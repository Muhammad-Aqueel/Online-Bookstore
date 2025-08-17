<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php'; // sanitizeInput, generateCsrfToken, verifyCsrfToken

requireAuth('admin');

$pageTitle = "System Settings";

// Handle form submission (add or update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $name = sanitizeInput($_POST['name']);
    $value = sanitizeInput($_POST['value']);

    if (!empty($name) && isset($value)) {
        // Check if setting exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE name = ?");
        $stmt->execute([$name]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            // Update existing
            $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE name = ?");
            $stmt->execute([$value, $name]);
            $_SESSION['success'] = "Setting '$name' updated successfully.";
        } else {
            // Insert new
            $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?)");
            $stmt->execute([$name, $value]);
            $_SESSION['success'] = "Setting '$name' added successfully.";
        }
    }

    header("Location: " . BASE_URL . "/admin/settings.php");
    exit;
}

// Fetch all settings
$settings = $pdo->query("SELECT * FROM settings ORDER BY name ASC")->fetchAll();

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">System Settings</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Add / Edit Setting Form -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="mb-2 flex justify-between items-center flex-wrap">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Add / Edit Setting</h2>
        <!-- <a href="settings.php" class="px-4 py-2 rounded-md bg-sky-600 text-white hover:bg-gray-300 hover:text-gray-700 mb-2"><i class="fas fa-cog mr-2"></i>All Settings</a> -->
    </div>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <div>
            <label for="name" class="block text-gray-700 text-sm font-bold mb-1">Setting Name:</label>
            <input type="text" id="name" name="name" required
                   placeholder="e.g., commission"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
        </div>

        <div>
            <label for="value" class="block text-gray-700 text-sm font-bold mb-1">Value:</label>
            <input type="text" id="value" name="value" required
                   placeholder="e.g., 0.15"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
        </div>

        <button type="submit" class="bg-sky-600 text-white px-6 py-2 rounded-md hover:bg-sky-700 transition duration-300">Save Setting</button>
    </form>
</div>


    <!-- Existing Settings Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quick Edit</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($settings as $setting): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                <?php echo htmlspecialchars($setting['name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                <?php echo htmlspecialchars($setting['value']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500 text-sm">
                                <?php echo $setting['updated_at']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" class="flex items-center space-x-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($setting['name']); ?>">
                                    <input type="text" name="value" value="<?php echo htmlspecialchars($setting['value']); ?>"
                                        class="border border-gray-300 rounded-md p-1 w-32">
                                    <button type="submit" class="bg-sky-600 text-white px-2 py-1 rounded hover:bg-sky-700 text-sm">
                                        Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($settings)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No settings found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>