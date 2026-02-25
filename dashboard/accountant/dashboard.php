<?php
// accountant_dashboard.php
require_once '../../includes/check_auth.php';
check_auth(['accountant', 'it_operation']);
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
                    <h1>Accountant Dashboard</h1>
                    <p>Financial management and reporting</p>
                </div>
                
                <div class="container-fluid mt-4">
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Asset Value</h5>
                                    <h2>$0</h2>
                                    <i class="bi bi-currency-dollar" style="font-size: 48px; opacity: 0.5; position: absolute; right: 20px; top: 20px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Monthly Depreciation</h5>
                                    <h2>$0</h2>
                                    <i class="bi bi-graph-down" style="font-size: 48px; opacity: 0.5; position: absolute; right: 20px; top: 20px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Maintenance Cost</h5>
                                    <h2>$0</h2>
                                    <i class="bi bi-tools" style="font-size: 48px; opacity: 0.5; position: absolute; right: 20px; top: 20px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Audit Reports</h5>
                                    <h2>0</h2>
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