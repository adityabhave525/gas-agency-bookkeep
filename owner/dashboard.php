<?php
// --- Phase 1: Initialize and Process Data ---
require_once '../db.php';

// -- Security: Access Control --
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'Owner') {
    die("<div style='font-family: sans-serif; padding: 20px;'><h2>Access Denied</h2><p>You do not have permission to view this page.</p></div>");
}

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// --- Fetch KPI Data ---
// 1. Get total closing stock for today
$stmt_today = $conn->prepare("SELECT SUM(closing_stock_full) as total_full, SUM(closing_stock_empty) as total_empty FROM inventory WHERE inventory_date = ?");
$stmt_today->bind_param("s", $today);
$stmt_today->execute();
$kpi_today = $stmt_today->get_result()->fetch_assoc();
$total_full_today = $kpi_today['total_full'] ?? 0;
$total_empty_today = $kpi_today['total_empty'] ?? 0;

// 2. Get total closing stock from yesterday (which is today's opening stock)
$stmt_yesterday = $conn->prepare("SELECT SUM(closing_stock_full) as total_full, SUM(closing_stock_empty) as total_empty FROM inventory WHERE inventory_date = ?");
$stmt_yesterday->bind_param("s", $yesterday);
$stmt_yesterday->execute();
$kpi_yesterday = $stmt_yesterday->get_result()->fetch_assoc();
$opening_stock_full = $kpi_yesterday['total_full'] ?? 0;
$opening_stock_empty = $kpi_yesterday['total_empty'] ?? 0;

// 3. Get total active vehicles
$active_vehicles = $conn->query("SELECT COUNT(vehicle_id) as count FROM vehicles WHERE is_active = 1")->fetch_assoc()['count'] ?? 0;


// --- Fetch Chart Data ---
// Get stock breakdown by cylinder type for today
$sql_chart = "SELECT ct.type_name, inv.closing_stock_full, inv.closing_stock_empty 
              FROM cylinder_types ct
              LEFT JOIN inventory inv ON ct.cylinder_type_id = inv.cylinder_type_id AND inv.inventory_date = ?
              ORDER BY ct.type_name";
$stmt_chart = $conn->prepare($sql_chart);
$stmt_chart->bind_param("s", $today);
$stmt_chart->execute();
$chart_data_result = $stmt_chart->get_result()->fetch_all(MYSQLI_ASSOC);

// Prepare data for Chart.js by encoding it into JSON
$chart_labels = [];
$chart_data_full = [];
$chart_data_empty = [];
foreach ($chart_data_result as $row) {
    $chart_labels[] = $row['type_name'];
    $chart_data_full[] = $row['closing_stock_full'] ?? 0;
    $chart_data_empty[] = $row['closing_stock_empty'] ?? 0;
}
$chart_labels_json = json_encode($chart_labels);
$chart_data_full_json = json_encode($chart_data_full);
$chart_data_empty_json = json_encode($chart_data_empty);


// --- Phase 2: Render Page ---
require_once '../partials/header.php';
?>

<div class="container-fluid">
    <h1 class="h2 mb-4">Owner Dashboard</h1>

    <!-- KPI Cards Row -->
    <div class="row">
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white bg-success shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-box-seam-fill"></i> Total Full Cylinders</h5>
                    <p class="card-text fs-1 fw-bold"><?php echo $total_full_today; ?></p>
                    <small>Opening Stock: <?php echo $opening_stock_full; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white bg-warning shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-box-fill"></i> Total Empty Cylinders</h5>
                    <p class="card-text fs-1 fw-bold"><?php echo $total_empty_today; ?></p>
                    <small>Opening Stock: <?php echo $opening_stock_empty; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white bg-info shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-truck"></i> Active Vehicles</h5>
                    <p class="card-text fs-1 fw-bold"><?php echo $active_vehicles; ?></p>
                    <small>Ready for delivery</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card bg-light shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-speedometer2"></i> Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="inventory.php" class="btn btn-dark">Manage Inventory</a>
                        <a href="vehicles.php" class="btn btn-dark">Manage Vehicles</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Row -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Stock Levels by Cylinder Type (<?php echo date('F j, Y'); ?>)</h5>
                </div>
                <div class="card-body">
                    <div style="height: 400px;"> <!-- Wrapper div to control chart height -->
                        <canvas id="stockChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js from a CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('stockChart').getContext('2d');

        // Retrieve the PHP-generated JSON data
        const chartLabels = <?php echo $chart_labels_json; ?>;
        const fullStockData = <?php echo $chart_data_full_json; ?>;
        const emptyStockData = <?php echo $chart_data_empty_json; ?>;

        const stockChart = new Chart(ctx, {
            type: 'bar', // Type of chart
            data: {
                labels: chartLabels,
                datasets: [{
                        label: 'Full Stock',
                        data: fullStockData,
                        backgroundColor: 'rgba(25, 135, 84, 0.7)', // Green
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Empty Stock',
                        data: emptyStockData,
                        backgroundColor: 'rgba(255, 193, 7, 0.7)', // Yellow
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Important for fitting into the container
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Cylinders'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Cylinder Type'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    });
</script>

<?php
require_once '../partials/footer.php';
?>