<?php
// depreciation.php
require_once '../../includes/check_auth.php';
require_once '../../includes/db.php';

check_auth(['accountant', 'it_operation']);

// Get current year
$current_year = date('Y');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;

// Calculate depreciation for each asset
$depreciation_query = $db->pdo->query("
    SELECT 
        a.*,
        ac.class_name,
        TIMESTAMPDIFF(YEAR, a.acquisition_date, CURDATE()) as age_years,
        CASE 
            WHEN a.depreciation_method = 'Straight Line' 
            THEN a.cost / a.life_expectancy_years
            ELSE a.cost * (a.depreciation_rate/100)
        END as annual_depreciation
    FROM assets a
    LEFT JOIN asset_classes ac ON a.asset_class = ac.class_name
    WHERE a.is_active = 1
    ORDER BY a.acquisition_date DESC
");
$assets = $depreciation_query->fetchAll(PDO::FETCH_ASSOC);

// Get summary data
$summary_query = $db->pdo->query("
    SELECT 
        SUM(cost) as total_cost,
        SUM(CASE 
            WHEN depreciation_method = 'Straight Line' 
            THEN cost / life_expectancy_years
            ELSE cost * (depreciation_rate/100)
        END) as total_annual_dep
    FROM assets WHERE is_active = 1
");
$summary = $summary_query->fetch(PDO::FETCH_ASSOC);

// Get fully depreciated count
$fully_dep_query = $db->pdo->query("
    SELECT COUNT(*) as count 
    FROM assets 
    WHERE TIMESTAMPDIFF(YEAR, acquisition_date, CURDATE()) >= 
        CASE 
            WHEN depreciation_method = 'Straight Line' THEN life_expectancy_years
            ELSE 5
        END
");
$fully_dep = $fully_dep_query->fetch(PDO::FETCH_ASSOC)['count'];

// Get asset classes for filter
$classes_query = $db->pdo->query("SELECT class_name FROM asset_classes");
$classes = $classes_query->fetchAll(PDO::FETCH_ASSOC);

$monthly_dep = $summary['total_annual_dep'] / 12;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depreciation Schedule - Asset Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .content-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .depreciation-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card {
            border: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .progress {
            height: 8px;
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
                            <h1>Depreciation Schedule</h1>
                            <p class="text-muted">Asset depreciation calculation and tracking</p>
                        </div>
                        <div>
                            <button class="btn btn-success me-2" onclick="exportToExcel()">
                                <i class="bi bi-file-earmark-excel"></i> Export
                            </button>
                            <button class="btn btn-primary" onclick="generateReport()">
                                <i class="bi bi-file-earmark-pdf"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="container-fluid mt-4">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Total Asset Cost</h6>
                                    <h3>RM <?php echo number_format($summary['total_cost'], 2); ?></h3>
                                    <i class="bi bi-calculator position-absolute" style="right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Annual Depreciation</h6>
                                    <h3>RM <?php echo number_format($summary['total_annual_dep'], 2); ?></h3>
                                    <i class="bi bi-graph-down position-absolute" style="right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Monthly Depreciation</h6>
                                    <h3>RM <?php echo number_format($monthly_dep, 2); ?></h3>
                                    <i class="bi bi-calendar-check position-absolute" style="right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card bg-warning text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Fully Depreciated</h6>
                                    <h3><?php echo $fully_dep; ?></h3>
                                    <i class="bi bi-hourglass-split position-absolute" style="right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Year Filter -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Select Year</label>
                                    <select name="year" class="form-select" onchange="this.form.submit()">
                                        <?php for($y = 2024; $y >= 2018; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $y == $selected_year ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Asset Class</label>
                                    <select class="form-select" id="classFilter">
                                        <option value="">All Classes</option>
                                        <?php foreach($classes as $class): ?>
                                        <option value="<?php echo $db->sanitizePDO($class['class_name']); ?>">
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Depreciation Table -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Depreciation Schedule for Year <?php echo $selected_year; ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="depreciationTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Asset Name</th>
                                            <th>Class</th>
                                            <th>Acquisition Date</th>
                                            <th>Cost (RM)</th>
                                            <th>Method</th>
                                            <th>Rate</th>
                                            <th>Accumulated Dep.</th>
                                            <th>Annual Dep.</th>
                                            <th>Monthly Dep.</th>
                                            <th>Net Book Value</th>
                                            <th>% Depreciated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($assets as $asset): 
                                            $age = date('Y') - date('Y', strtotime($asset['acquisition_date']));
                                            $accumulated = $asset['annual_depreciation'] * min($age, 5); // Cap at useful life
                                            $nbv = max($asset['cost'] - $accumulated, 0);
                                            $dep_percent = ($accumulated / $asset['cost']) * 100;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(substr($asset['asset_name'], 0, 40)); ?></td>
                                            <td><?php echo htmlspecialchars($asset['asset_class']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($asset['acquisition_date'])); ?></td>
                                            <td class="text-end"><?php echo number_format($asset['cost'], 2); ?></td>
                                            <td><?php echo $asset['depreciation_method'] ?? 'Diminishing'; ?></td>
                                            <td class="text-center"><?php echo $asset['depreciation_rate']; ?>%</td>
                                            <td class="text-end"><?php echo number_format($accumulated, 2); ?></td>
                                            <td class="text-end"><?php echo number_format($asset['annual_depreciation'], 2); ?></td>
                                            <td class="text-end"><?php echo number_format($asset['annual_depreciation']/12, 2); ?></td>
                                            <td class="text-end"><strong>RM <?php echo number_format($nbv, 2); ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2">
                                                        <div class="progress-bar <?php echo $dep_percent >= 100 ? 'bg-danger' : ($dep_percent >= 75 ? 'bg-warning' : 'bg-success'); ?>" 
                                                             style="width: <?php echo min($dep_percent, 100); ?>%">
                                                        </div>
                                                    </div>
                                                    <span><?php echo number_format($dep_percent, 1); ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Depreciation Chart Section -->
                    <div class="row mt-4">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Depreciation by Class</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="depChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Depreciation Projection (Next 5 Years)</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="projectionChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            $('#depreciationTable').DataTable({
                pageLength: 25,
                order: [[2, 'desc']]
            });
            
            // Class filter
            $('#classFilter').on('change', function() {
                var classVal = $(this).val();
                $('#depreciationTable').DataTable().column(1).search(classVal).draw();
            });
            
            // Sample chart data - in production, this would come from PHP
            const ctx = document.getElementById('depChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Computers', 'Motor Vehicles', 'Office Equipment', 'Furniture', 'Software', 'Equipment'],
                    datasets: [{
                        data: [35, 25, 20, 10, 7, 3],
                        backgroundColor: [
                            '#4e73df',
                            '#1cc88a',
                            '#36b9cc',
                            '#f6c23e',
                            '#e74a3b',
                            '#858796'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
        
        function exportToExcel() {
            window.location.href = 'export_depreciation.php?year=<?php echo $selected_year; ?>';
        }
        
        function generateReport() {
            window.open('depreciation_report.php?year=<?php echo $selected_year; ?>', '_blank');
        }
    </script>
</body>
</html>