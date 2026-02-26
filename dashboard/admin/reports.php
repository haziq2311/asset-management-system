<?php
require_once '../../includes/check_auth.php';
check_auth(['admin', 'it_operation']);

require_once '../../includes/db.php';
$conn = $db->conn;

// Handle Report Generation
$report_data = [];
$report_type = $_GET['type'] ?? 'assets';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$department = $_GET['department'] ?? '';

// Generate report based on type
switch($report_type) {
    case 'assets':
        $sql = "SELECT a.*, l.name as location_name, d.name as department_name, 
                u.full_name as assigned_to, ac.class_name 
                FROM assets a 
                LEFT JOIN locations l ON a.location_id = l.location_id 
                LEFT JOIN departments d ON a.owner_department_id = d.department_id 
                LEFT JOIN users u ON a.assigned_to_user_id = u.user_id 
                LEFT JOIN asset_classes ac ON a.asset_class = ac.class_id 
                WHERE 1=1";
        
        if (!empty($status)) {
            $sql .= " AND a.asset_status = '$status'";
        }
        if (!empty($department)) {
            $sql .= " AND a.owner_department_id = '$department'";
        }
        
        $sql .= " ORDER BY a.created_at DESC";
        $result = $conn->query($sql);
        if ($result) {
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
        }
        break;
        
    case 'movements':
        $sql = "SELECT am.*, a.asset_name, a.asset_tag, 
                u_from.full_name as performed_by_name,
                l_from.name as from_location_name,
                l_to.name as to_location_name
                FROM asset_movements am
                JOIN assets a ON am.asset_id = a.asset_id
                LEFT JOIN users u_from ON am.performed_by_user_id = u_from.user_id
                LEFT JOIN locations l_from ON am.from_location_id = l_from.location_id
                LEFT JOIN locations l_to ON am.to_location_id = l_to.location_id
                WHERE DATE(am.movement_date) BETWEEN '$date_from' AND '$date_to'";
        
        if (!empty($status)) {
            $sql .= " AND am.status = '$status'";
        }
        
        $sql .= " ORDER BY am.movement_date DESC";
        $result = $conn->query($sql);
        if ($result) {
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
        }
        break;
        
    case 'maintenance':
        $sql = "SELECT m.*, a.asset_name, a.asset_tag, a.model
                FROM maintenance m
                JOIN assets a ON m.asset_id = a.asset_id
                WHERE (m.start_date BETWEEN '$date_from' AND '$date_to')
                   OR (m.end_date BETWEEN '$date_from' AND '$date_to')";
        
        if (!empty($status)) {
            $sql .= " AND m.status = '$status'";
        }
        
        $sql .= " ORDER BY m.start_date DESC";
        $result = $conn->query($sql);
        if ($result) {
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
        }
        break;
        
    case 'depreciation':
        $sql = "SELECT a.*, ac.class_name,
                DATEDIFF(CURDATE(), a.acquisition_date)/365 as age_years,
                a.cost - (a.cost * COALESCE(a.depreciation_rate, 0) * DATEDIFF(CURDATE(), COALESCE(a.depreciation_start_date, a.acquisition_date))/36500) as current_value
                FROM assets a
                LEFT JOIN asset_classes ac ON a.asset_class = ac.class_id
                WHERE a.acquisition_date IS NOT NULL
                AND a.asset_status != 'Disposed'";
        
        if (!empty($department)) {
            $sql .= " AND a.owner_department_id = '$department'";
        }
        
        $sql .= " ORDER BY a.acquisition_date DESC";
        $result = $conn->query($sql);
        if ($result) {
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
        }
        break;
        
    case 'activity':
        $sql = "SELECT al.*, u.username, u.full_name 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.user_id 
                WHERE DATE(al.created_at) BETWEEN '$date_from' AND '$date_to'
                ORDER BY al.created_at DESC";
        $result = $conn->query($sql);
        if ($result) {
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
        }
        break;
}

// Fetch filter data
$departments = $conn->query("SELECT * FROM departments ORDER BY name");
$asset_statuses = ['Active', 'In Stock', 'Assigned', 'Under Maintenance', 'Disposed'];

// Calculate summary stats
$total_value = 0;
$total_count = count($report_data);
$status_counts = [];
$department_counts = [];

foreach ($report_data as $item) {
    if (isset($item['cost'])) {
        $total_value += floatval($item['cost']);
    }
    if (isset($item['asset_status'])) {
        $status_counts[$item['asset_status']] = ($status_counts[$item['asset_status']] ?? 0) + 1;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Asset Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .export-buttons .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'adsidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="report-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="bi bi-clipboard-data"></i> Reports & Analytics</h1>
                            <p>Generate and analyze comprehensive reports</p>
                        </div>
                        <div class="export-buttons">
                            <button class="btn btn-light" onclick="exportToCSV()">
                                <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                            </button>
                            <button class="btn btn-light" onclick="exportToPDF()">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </button>
                            <button class="btn btn-light" onclick="printReport()">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Report Type</label>
                            <select name="type" class="form-select" onchange="this.form.submit()">
                                <option value="assets" <?php echo $report_type == 'assets' ? 'selected' : ''; ?>>Assets Report</option>
                                <option value="movements" <?php echo $report_type == 'movements' ? 'selected' : ''; ?>>Movements Report</option>
                                <option value="maintenance" <?php echo $report_type == 'maintenance' ? 'selected' : ''; ?>>Maintenance Report</option>
                                <option value="depreciation" <?php echo $report_type == 'depreciation' ? 'selected' : ''; ?>>Depreciation Report</option>
                                <option value="activity" <?php echo $report_type == 'activity' ? 'selected' : ''; ?>>Activity Log</option>
                            </select>
                        </div>
                        
                        <?php if ($report_type != 'assets' && $report_type != 'depreciation'): ?>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                        </div>
                        <?php endif; ?>
                        
                        <?php if (in_array($report_type, ['assets', 'movements', 'maintenance'])): ?>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <?php if ($report_type == 'assets'): ?>
                                    <?php foreach ($asset_statuses as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $status == $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                    <?php endforeach; ?>
                                <?php elseif ($report_type == 'movements'): ?>
                                    <option value="Pending">Pending</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                <?php elseif ($report_type == 'maintenance'): ?>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (in_array($report_type, ['assets', 'depreciation'])): ?>
                        <div class="col-md-2">
                            <label class="form-label">Department</label>
                            <select name="department" class="form-select">
                                <option value="">All Departments</option>
                                <?php while($dept = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['department_id']; ?>" <?php echo $department == $dept['department_id'] ? 'selected' : ''; ?>>
                                        <?php echo $dept['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Generate
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Summary Stats -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <h6 class="text-muted">Total Records</h6>
                            <h2><?php echo $total_count; ?></h2>
                            <small>Based on current filter</small>
                        </div>
                    </div>
                    
                    <?php if ($report_type == 'assets' && $total_value > 0): ?>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <h6 class="text-muted">Total Value</h6>
                            <h2>$<?php echo number_format($total_value, 2); ?></h2>
                            <small>Asset cost total</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($status_counts)): ?>
                    <div class="col-md-6 mb-3">
                        <div class="stat-card">
                            <h6 class="text-muted">Status Distribution</h6>
                            <div class="row">
                                <?php foreach ($status_counts as $stat => $count): ?>
                                <div class="col-4">
                                    <strong><?php echo $stat; ?>:</strong> <?php echo $count; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Charts Section -->
                <?php if (!empty($report_data) && $report_type == 'assets'): ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="classChart"></canvas>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Report Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <?php 
                            $titles = [
                                'assets' => 'Assets Report',
                                'movements' => 'Asset Movements Report',
                                'maintenance' => 'Maintenance Report',
                                'depreciation' => 'Depreciation Report',
                                'activity' => 'Activity Log Report'
                            ];
                            echo $titles[$report_type] ?? 'Report';
                            ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="reportTable">
                                <thead>
                                    <tr>
                                        <?php if ($report_type == 'assets'): ?>
                                            <th>Asset Tag</th>
                                            <th>Asset Name</th>
                                            <th>Class</th>
                                            <th>Model</th>
                                            <th>Status</th>
                                            <th>Location</th>
                                            <th>Department</th>
                                            <th>Cost</th>
                                            <th>Acquisition Date</th>
                                        <?php elseif ($report_type == 'movements'): ?>
                                            <th>Date</th>
                                            <th>Asset</th>
                                            <th>Type</th>
                                            <th>From Location</th>
                                            <th>To Location</th>
                                            <th>Performed By</th>
                                            <th>Status</th>
                                        <?php elseif ($report_type == 'maintenance'): ?>
                                            <th>Asset</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Type</th>
                                            <th>Provider</th>
                                            <th>Cost</th>
                                            <th>Status</th>
                                        <?php elseif ($report_type == 'depreciation'): ?>
                                            <th>Asset</th>
                                            <th>Class</th>
                                            <th>Acquisition Date</th>
                                            <th>Original Cost</th>
                                            <th>Current Value</th>
                                            <th>Age (Years)</th>
                                            <th>Depreciation %</th>
                                        <?php elseif ($report_type == 'activity'): ?>
                                            <th>Date/Time</th>
                                            <th>User</th>
                                            <th>Activity</th>
                                            <th>Details</th>
                                            <th>IP Address</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data as $row): ?>
                                        <tr>
                                            <?php if ($report_type == 'assets'): ?>
                                                <td><?php echo htmlspecialchars($row['asset_tag'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['asset_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['class_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['model'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $row['asset_status'] == 'Active' ? 'success' : 
                                                            ($row['asset_status'] == 'In Stock' ? 'info' : 
                                                            ($row['asset_status'] == 'Assigned' ? 'warning' : 
                                                            ($row['asset_status'] == 'Under Maintenance' ? 'danger' : 'secondary'))); 
                                                    ?>">
                                                        <?php echo $row['asset_status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['location_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                                <td>$<?php echo number_format($row['cost'] ?? 0, 2); ?></td>
                                                <td><?php echo $row['acquisition_date'] ?? 'N/A'; ?></td>
                                                
                                            <?php elseif ($report_type == 'movements'): ?>
                                                <td><?php echo date('Y-m-d H:i', strtotime($row['movement_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['asset_name']); ?> (<?php echo $row['asset_tag']; ?>)</td>
                                                <td><?php echo htmlspecialchars($row['movement_type']); ?></td>
                                                <td><?php echo htmlspecialchars($row['from_location_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['to_location_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['performed_by_name'] ?? 'System'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $row['status'] == 'Completed' ? 'success' : ($row['status'] == 'Pending' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo $row['status']; ?>
                                                    </span>
                                                </td>
                                                
                                            <?php elseif ($report_type == 'maintenance'): ?>
                                                <td><?php echo htmlspecialchars($row['asset_name']); ?></td>
                                                <td><?php echo $row['start_date'] ?? 'N/A'; ?></td>
                                                <td><?php echo $row['end_date'] ?? 'N/A'; ?></td>
                                                <td><?php echo htmlspecialchars($row['maintenance_type'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['provider'] ?? 'N/A'); ?></td>
                                                <td>$<?php echo number_format($row['cost'] ?? 0, 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $row['status'] == 'Completed' ? 'success' : 
                                                            ($row['status'] == 'In Progress' ? 'info' : 
                                                            ($row['status'] == 'Scheduled' ? 'warning' : 'secondary')); 
                                                    ?>">
                                                        <?php echo $row['status']; ?>
                                                    </span>
                                                </td>
                                                
                                            <?php elseif ($report_type == 'depreciation'): ?>
                                                <td><?php echo htmlspecialchars($row['asset_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['class_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo $row['acquisition_date'] ?? 'N/A'; ?></td>
                                                <td>$<?php echo number_format($row['cost'] ?? 0, 2); ?></td>
                                                <td>$<?php echo number_format($row['current_value'] ?? $row['cost'], 2); ?></td>
                                                <td><?php echo number_format($row['age_years'] ?? 0, 1); ?></td>
                                                <td><?php echo number_format($row['depreciation_rate'] ?? 0, 1); ?>%</td>
                                                
                                            <?php elseif ($report_type == 'activity'): ?>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['username'] ?? 'System'); ?></td>
                                                <td><?php echo htmlspecialchars($row['activity']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($row['details'] ?? '', 0, 50)) . '...'; ?></td>
                                                <td><?php echo htmlspecialchars($row['ip_address'] ?? 'N/A'); ?></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <?php if (empty($report_data)): ?>
                                <p class="text-muted text-center py-4">No data available for the selected criteria</p>
                            <?php endif; ?>
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
            // Initialize DataTable
            $('#reportTable').DataTable({
                pageLength: 25,
                order: [[0, 'desc']],
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
            });

            <?php if (!empty($report_data) && $report_type == 'assets'): ?>
            // Status Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_keys($status_counts)); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($status_counts)); ?>,
                        backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6c757d']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        title: {
                            display: true,
                            text: 'Assets by Status'
                        }
                    }
                }
            });

            // Class Chart (simplified - get top 5 classes)
            const classCounts = {};
            <?php foreach ($report_data as $asset): ?>
                const className = '<?php echo $asset['class_name'] ?? 'Unclassified'; ?>';
                classCounts[className] = (classCounts[className] || 0) + 1;
            <?php endforeach; ?>
            
            const classCtx = document.getElementById('classChart').getContext('2d');
            new Chart(classCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(classCounts),
                    datasets: [{
                        label: 'Number of Assets',
                        data: Object.values(classCounts),
                        backgroundColor: '#667eea'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Assets by Class'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            stepSize: 1
                        }
                    }
                }
            });
            <?php endif; ?>
        });

        // Export functions
        function exportToCSV() {
            window.location.href = 'export_report.php?type=<?php echo $report_type; ?>&format=csv&' + new URLSearchParams(window.location.search).toString();
        }

        function exportToPDF() {
            window.location.href = 'export_report.php?type=<?php echo $report_type; ?>&format=pdf&' + new URLSearchParams(window.location.search).toString();
        }

        function printReport() {
            window.print();
        }
    </script>
</body>
</html>