<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth('seller');

$user = currentUser();
$pageTitle = "Earnings Report";

// Get current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Get selected month and year from query params
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

// Validate month and year
$selectedMonth = max(1, min(12, $selectedMonth));
$selectedYear = max(2020, min((int)date('Y'), $selectedYear));

// Calculate start and end dates for the selected period
$startDate = date('Y-m-01', strtotime("$selectedYear-$selectedMonth-01"));
$endDate = date('Y-m-t', strtotime("$selectedYear-$selectedMonth-01"));

// Calculate seller's share (e.g. 0.85 if commission is 0.15)
$sellerRate = 1 - PLATFORM_COMMISSION_RATE;

// Get earnings data for the selected period
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(o.order_date, '%Y-%m-%d') AS day, 
        COUNT(DISTINCT o.id) AS order_count, 
        SUM(oi.quantity) AS book_count, 
        SUM(oi.price * oi.quantity) AS total_amount, 
        SUM(oi.price * oi.quantity) - o.discount_amount AS gross_amount,  -- Apply the discount to the entire order
        (SUM(oi.price * oi.quantity) - o.discount_amount) * ? AS net_amount,  -- 85% of amount_after_discount
        o.discount_amount
    FROM 
        orders o 
    JOIN 
        order_items oi ON o.id = oi.order_id 
    JOIN 
        books b ON oi.book_id = b.id 
    WHERE 
        b.seller_id = ? 
        AND o.order_date BETWEEN ? AND ? 
        AND o.payment_status = 'completed' 
        AND (o.status = 'shipped' OR o.status = 'delivered') 
    GROUP BY 
        DATE_FORMAT(o.order_date, '%Y-%m-%d') 
    ORDER BY 
        o.order_date;");
$stmt->execute([$sellerRate, $user['id'], $startDate, $endDate]);
$dailyEarnings = $stmt->fetchAll();

// Calculate totals
$totalOrders = 0;
$totalBooks = 0;
$totalAll = 0;
$totalDiscount = 0;
$totalGross = 0;
$totalNet = 0;

foreach ($dailyEarnings as $day) {
    $totalOrders += $day['order_count'];
    $totalBooks += $day['book_count'];
    $totalAll += $day['total_amount'];
    $totalDiscount += $day['discount_amount'];
    $totalGross += $day['gross_amount'];
    $totalNet += $day['net_amount'];
}

// Get monthly earnings for the year (for chart)
$monthlyStmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(o.order_date, '%Y-%m') as month,
        (SUM(oi.price * oi.quantity) - SUM(DISTINCT o.discount_amount)) * ? as net_amount
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN books b ON oi.book_id = b.id
    WHERE b.seller_id = ? 
      AND DATE_FORMAT(o.order_date, '%Y') = ?
      AND o.payment_status = 'completed' AND (o.status = 'shipped' OR o.status = 'delivered')
    GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
    ORDER BY month
");
$monthlyStmt->execute([$sellerRate, $user['id'], $selectedYear]);
$monthlyEarnings = $monthlyStmt->fetchAll();

// Prepare data for chart
$chartLabels = [];
$chartData = [];

for ($m = 1; $m <= 12; $m++) {
    $monthName = date('M', mktime(0, 0, 0, $m, 1));
    $chartLabels[] = $monthName;
    
    $found = false;
    foreach ($monthlyEarnings as $month) {
        if (date('m', strtotime($month['month'])) == str_pad($m, 2, '0', STR_PAD_LEFT)) {
            $chartData[] = (float)$month['net_amount'];
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $chartData[] = 0;
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Earnings Report</h1>
    
    <!-- Month/Year Selector -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="get" class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-gray-700 mb-1">Month</label>
                <select name="month" class="border rounded px-3 py-1">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $selectedMonth ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 mb-1">Year</label>
                <select name="year" class="border rounded px-3 py-1">
                    <?php for ($y = 2020; $y <= date('Y'); $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $selectedYear ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="self-end">
                <button type="submit" class="bg-sky-600 text-white px-4 py-1 rounded hover:bg-sky-700">
                    View Report
                </button>
            </div>
        </form>
    </div>
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-gray-500">Total Orders</h3>
            <p class="text-2xl font-bold text-sky-600"><?php echo $totalOrders; ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-gray-500">Books Sold</h3>
            <p class="text-2xl font-bold text-sky-600"><?php echo $totalBooks; ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-gray-500">Gross Earnings</h3>
            <p class="text-2xl font-bold text-sky-600">$<?php echo number_format($totalGross, 2); ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-gray-500">Net Earnings</h3>
            <p class="text-2xl font-bold text-sky-600">$<?php echo number_format($totalNet, 2); ?></p>
            <p class="text-xs text-gray-500">(<?php echo ((1 - PLATFORM_COMMISSION_RATE) * 100); ?>% of Gross Revenue)</p>
        </div>
    </div>
    
    <!-- Chart -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="font-semibold text-gray-900 mb-4">Monthly Earnings for <?php echo $selectedYear; ?></h3>
        <?php if (empty($chartData) || array_sum($chartData) == 0): ?>
            <p class="text-gray-600">No earnings data to display for the year.</p>
        <?php else: ?>
            <div style="height:400px;">
                <canvas id="earningsChart"></canvas>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Daily Earnings Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Daily Earnings for <?php echo date('F Y', strtotime("$selectedYear-$selectedMonth-01")); ?></h3>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Books Sold</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gross Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Net Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($dailyEarnings as $day): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M j, Y', strtotime($day['day'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $day['order_count']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $day['book_count']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($day['total_amount'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($day['discount_amount'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($day['gross_amount'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($day['net_amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($dailyEarnings)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No earnings data for this period</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($dailyEarnings)): ?>
                <tfoot class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Totals</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo $totalOrders; ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo $totalBooks; ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">$<?php echo number_format($totalAll, 2); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">$<?php echo number_format($totalDiscount, 2); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">$<?php echo number_format($totalGross, 2); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">$<?php echo number_format($totalNet, 2); ?></th>
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
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Net Earnings ($)',
                    data: <?php echo json_encode($chartData); ?>,
                    backgroundColor: 'rgba(56, 189, 248, 0.6)',
                    borderColor: 'rgba(56, 189, 248, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Allows chart to take available space
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.raw.toLocaleString();
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