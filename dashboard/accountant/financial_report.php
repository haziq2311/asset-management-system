<?php
// financial_reports.php
require_once '../../includes/check_auth.php';
require_once '../../includes/db.php';

check_auth(['accountant', 'it_operation']);

// Get summary data using PDO
$total_assets_query = $db->pdo->query("SELECT COUNT(*) as count FROM assets WHERE is_active = 1");
$total_assets = $total_assets_query->fetch(PDO::FETCH_ASSOC)['count'];

$total_cost_query = $db->pdo->query("SELECT SUM(cost) as total FROM assets WHERE is_active = 1");
$total_cost = $total_cost_query->fetch(PDO::FETCH_ASSOC)['total'];

// Get asset classes count
$class_count_query = $db->pdo->query("SELECT COUNT(*) FROM asset_classes");
$class_count = $class_count_query->fetchColumn();

// Get depreciation summary
$depreciation_query = $db->pdo->query("
    SELECT 
        asset_class,
        depreciation_rate,
        SUM(cost) as total_cost,
        SUM(cost * (depreciation_rate/100)) as yearly_depreciation
    FROM assets 
    WHERE is_active = 1 
    GROUP BY asset_class
");
$depreciation_result = $depreciation_query->fetchAll(PDO::FETCH_ASSOC);

// Get asset classes for filter
$classes_query = $db->pdo->query("SELECT class_name FROM asset_classes");
$classes = $classes_query->fetchAll(PDO::FETCH_ASSOC);

// Get class summary
$class_summary_query = $db->pdo->query("
    SELECT 
        asset_class,
        COUNT(*) as count,
        SUM(cost) as total_cost,
        AVG(cost) as avg_cost
    FROM assets 
    WHERE is_active = 1
    GROUP BY asset_class
    ORDER BY total_cost DESC
");
$class_summary = $class_summary_query->fetchAll(PDO::FETCH_ASSOC);

// Get recent acquisitions
$recent_query = $db->pdo->prepare("
    SELECT asset_name, asset_class, acquisition_date, cost, depreciation_rate
    FROM assets 
    WHERE YEAR(acquisition_date) = :year AND is_active = 1
    ORDER BY acquisition_date DESC
    LIMIT 10
");
$recent_query->execute([':year' => date('Y')]);
$recent = $recent_query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - Asset Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .content-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .stat-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
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
                            <h1>Financial Reports</h1>
                            <p class="text-muted">Comprehensive asset financial analysis and reporting</p>
                        </div>
                        <div>
                            <button class="btn btn-success me-2" onclick="exportToExcel()">
                                <i class="bi bi-file-earmark-excel"></i> Export to Excel
                            </button>
                            <button class="btn btn-primary" onclick="printReport()">
                                <i class="bi bi-printer"></i> Print Report
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
                                    <h6 class="card-title">Total Assets</h6>
                                    <h2><?php echo number_format($total_assets); ?></h2>
                                    <i class="bi bi-boxes position-absolute" style="right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Total Cost</h6>
                                    <h2>RM <?php echo number_format($total_cost, 2); ?></h2>
                                    <i class="bi bi-cash-stack position-absolute" style="right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Average Age</h6>
                                    <h2>3.2 Years</h2>
                                    <i class="bi bi-calendar position-absolute" style="right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card bg-warning text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Asset Classes</h6>
                                    <h2><?php echo $class_count; ?></h2>
                                    <i class="bi bi-grid position-absolute" style="right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Section -->
                    <div class="filter-section">
                        <form id="reportFilter" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Asset Class</label>
                                <select class="form-select" id="assetClass">
                                    <option value="">All Classes</option>
                                    <?php foreach($classes as $class): ?>
                                    <option value="<?php echo $db->sanitizePDO($class['class_name']); ?>">
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Year From</label>
                                <select class="form-select" id="yearFrom">
                                    <option value="">Any</option>
                                    <?php for($i=2024; $i>=2018; $i--): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Year To</label>
                                <select class="form-select" id="yearTo">
                                    <option value="">Any</option>
                                    <?php for($i=2024; $i>=2018; $i--): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" onclick="applyFilter()">
                                    <i class="bi bi-funnel"></i> Apply Filter
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Asset Value by Class -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Asset Value by Class</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="assetClassTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Asset Class</th>
                                            <th>Number of Assets</th>
                                            <th>Total Cost (RM)</th>
                                            <th>Average Cost (RM)</th>
                                            <th>% of Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($class_summary as $row): 
                                            $percentage = ($row['total_cost'] / $total_cost) * 100;
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['asset_class']); ?></strong></td>
                                            <td><?php echo number_format($row['count']); ?></td>
                                            <td>RM <?php echo number_format($row['total_cost'], 2); ?></td>
                                            <td>RM <?php echo number_format($row['avg_cost'], 2); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                        <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%"></div>
                                                    </div>
                                                    <span><?php echo number_format($percentage, 1); ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Depreciation Schedule -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Depreciation Summary by Class</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="depreciationTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Asset Class</th>
                                            <th>Depreciation Method</th>
                                            <th>Rate</th>
                                            <th>Total Cost (RM)</th>
                                            <th>Annual Depreciation (RM)</th>
                                            <th>Monthly Depreciation (RM)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($depreciation_result as $row):
                                            $annual_dep = $row['yearly_depreciation'] ?? 0;
                                            $monthly_dep = $annual_dep / 12;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['asset_class']); ?></td>
                                            <td>Diminishing Value</td>
                                            <td><?php echo $row['depreciation_rate'] ?? 20; ?>%</td>
                                            <td>RM <?php echo number_format($row['total_cost'], 2); ?></td>
                                            <td>RM <?php echo number_format($annual_dep, 2); ?></td>
                                            <td>RM <?php echo number_format($monthly_dep, 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-secondary">
                                        <tr>
                                            <th colspan="3">Total</th>
                                            <th>RM <?php echo number_format($total_cost, 2); ?></th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Acquisitions -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Recent Acquisitions (<?php echo date('Y'); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Asset Name</th>
                                            <th>Class</th>
                                            <th>Acquisition Date</th>
                                            <th>Cost (RM)</th>
                                            <th>Depreciation Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recent as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(substr($row['asset_name'], 0, 50)) . (strlen($row['asset_name']) > 50 ? '...' : ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['asset_class']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['acquisition_date'])); ?></td>
                                            <td>RM <?php echo number_format($row['cost'], 2); ?></td>
                                            <td><?php echo $row['depreciation_rate']; ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
    <script>
        $(document).ready(function() {
            $('#assetClassTable').DataTable({
                pageLength: 10,
                order: [[2, 'desc']]
            });
        });
        
        function applyFilter() {
            alert('Filter functionality would be implemented here');
        }
        
        function exportToExcel() {
            window.location.href = 'export_financial_report.php';
        }
        
        function printReport() {
            window.print();
        }
    </script>
</body>
</html>