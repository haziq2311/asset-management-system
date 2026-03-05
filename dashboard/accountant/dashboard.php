<?php
// accountant_dashboard.php
require_once '../../includes/check_auth.php';
check_auth(['accountant', 'it_operation']);
require_once '../../includes/db.php';

$conn = $db->conn;

// Total asset value (sum of cost)
$total_value = $conn->query("SELECT COALESCE(SUM(cost), 0) as total FROM assets WHERE is_active = 1")->fetch_assoc()['total'];

// Monthly depreciation (assets with straight-line: cost * depreciation_rate / 100 / 12)
$monthly_dep = $conn->query("SELECT COALESCE(SUM(cost * depreciation_rate / 100 / 12), 0) as total FROM assets WHERE is_active = 1 AND depreciation_rate IS NOT NULL")->fetch_assoc()['total'];

// Total maintenance cost this year
$maintenance_cost = $conn->query("SELECT COALESCE(SUM(cost), 0) as total FROM maintenance WHERE YEAR(start_date) = YEAR(CURDATE())")->fetch_assoc()['total'];

// Total disposals (used as audit/write-off count)
$disposal_count = $conn->query("SELECT COUNT(*) as c FROM disposals")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Dashboard - Asset Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .content-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include 'accsidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="content-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1>Accountant Dashboard</h1>
                            <p class="mb-0">Financial management and reporting</p>
                        </div>
                        <?php if ($_SESSION['role'] === 'it_operation'): ?>
                        <a href="../information_system/dashboard.php" class="btn btn-dark">
                            <i class="bi bi-house-fill me-1"></i> IT Dashboard
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="container-fluid mt-4">
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Asset Value</h5>
                                    <h2>RM <?php echo number_format($total_value, 2); ?></h2>
                                    <i class="bi bi-currency-dollar" style="font-size: 48px; opacity: 0.5; position: absolute; right: 20px; top: 20px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Monthly Depreciation</h5>
                                    <h2>RM <?php echo number_format($monthly_dep, 2); ?></h2>
                                    <i class="bi bi-graph-down" style="font-size: 48px; opacity: 0.5; position: absolute; right: 20px; top: 20px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Maintenance Cost (This Year)</h5>
                                    <h2>RM <?php echo number_format($maintenance_cost, 2); ?></h2>
                                    <i class="bi bi-tools" style="font-size: 48px; opacity: 0.5; position: absolute; right: 20px; top: 20px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Disposals / Write-offs</h5>
                                    <h2><?php echo $disposal_count; ?></h2>
                                    <i class="bi bi-clipboard-check" style="font-size: 48px; opacity: 0.5; position: absolute; right: 20px; top: 20px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>