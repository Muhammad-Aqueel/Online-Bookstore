<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth('admin');

$pageTitle = "Platform Earnings Report";

// Get overall stats
$totalGrossRevenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'completed' AND (status = 'shipped' OR status = 'delivered')")->fetchColumn();
$totalGrossRevenue = $totalGrossRevenue ? $totalGrossRevenue : 0;

// Calculate platform net earnings from completed orders
// This assumes total_amount in orders is the gross amount paid by buyer.
// And platform keeps PLATFORM_COMMISSION_RATE of the gross amount.
$platformNetEarnings = $totalGrossRevenue * PLATFORM_COMMISSION_RATE;

// Get orders count
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'completed' AND (status = 'shipped' OR status = 'delivered')")->fetchColumn();

// Prepare data for monthly earnings chart (last 12 months)
$chartLabels = [];
$chartData = []; // This will store platform's net earnings
$currentDate = new DateTime();

for ($i = 11; $i >= 0; $i--) {
    $date = (clone $currentDate)->modify("-$i months");
    $month = $date->format('m');
    $year = $date->format('Y');
    $label = $date->format('M Y'); // e.g., Jan 2023
    $chartLabels[] = $label;

    $startDate = date('Y-m-01', strtotime("$year-$month-01"));
    $endDate = date('Y-m-t', strtotime("$year-$month-01"));

    // Calculate gross revenue for the month
    // Changed to named parameters for consistency and avoiding errors
    $stmt = $pdo->prepare("
        SELECT SUM(total_amount)
        FROM orders
        WHERE payment_status = 'completed' AND (status = 'shipped' OR status = 'delivered') AND order_date >= :start_date AND order_date <= :end_date
    ");
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $monthlyGross = $stmt->fetchColumn();
    $monthlyGross = $monthlyGross ? $monthlyGross : 0;

    // Calculate platform's share for the month
    $monthlyPlatformEarnings = $monthlyGross * PLATFORM_COMMISSION_RATE;
    $chartData[] = round($monthlyPlatformEarnings, 2);
}


include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Platform Earnings Report</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Total Gross Revenue</h2>
            <p class="text-4xl font-bold text-sky-600">$<?php echo number_format($totalGrossRevenue, 2); ?></p>
            <p class="text-sm text-gray-500">From all completed orders</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Platform Net Earnings</h2>
            <p class="text-4xl font-bold text-green-600">$<?php echo number_format($platformNetEarnings, 2); ?></p>
            <p class="text-sm text-gray-500">(<?php echo (PLATFORM_COMMISSION_RATE * 100); ?>% of Gross Revenue)</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Total Orders</h2>
            <p class="text-4xl font-bold text-indigo-600"><?php echo number_format($totalOrders); ?></p>
            <p class="text-sm text-gray-500">Completed purchases</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-8 ">
        <?php if (empty($chartData) || array_sum($chartData) == 0): ?>
            <p class="text-gray-600">No earnings data to display for the last 12 months.</p>
        <?php else: ?>
            <canvas id="earningsChart" height="400"></canvas>
        <?php endif; ?>
    </div>

    <!-- This section can be expanded to show recent transactions or other details -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Recent Completed Orders</h3>
        </div>
        <?php
        $recentOrders = $pdo->query("
            SELECT o.id, o.order_date, o.total_amount, u.username as buyer_name
            FROM orders o
            JOIN users u ON o.buyer_id = u.id
            WHERE o.payment_status = 'completed' AND (o.status = 'shipped' OR o.status = 'delivered')
            ORDER BY o.order_date DESC
            LIMIT 10
        ")->fetchAll();
        ?>
        <div class="overflow-x-auto">
            <table class="data-table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Buyer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">#<?php echo htmlspecialchars($order['id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No completed orders yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($recentOrders)): ?>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <th colspan="3" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">$<?php echo number_format($totalGrossRevenue, 2); ?></th>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('earningsChart');
    // Only initialize the chart if the canvas element exists (i.e., there's data to display)
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?> ,
                datasets: [{
                    label: 'Platform Net Earnings ($)',
                    data: <?php echo json_encode($chartData); ?> ,
                    backgroundColor: 'rgba(56, 189, 248, 0.6)', // Tailwind sky-500 with opacity
                    borderColor: 'rgba(56, 189, 248, 1)', // Tailwind sky-500
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Allows chart to take available space
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Earnings ($)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Net Earnings: $' + context.raw.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
