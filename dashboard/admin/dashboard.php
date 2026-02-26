<?php
require_once '../../includes/check_auth.php';
check_auth(['admin', 'it_operation']);

// Database connection for stats
require_once '../../includes/db.php';
$conn = $db->conn;

// Fetch comprehensive stats
$total_users = 0;
$active_users = 0;
$total_assets = 0;
$active_assets = 0;
$in_stock_assets = 0;
$assigned_assets = 0;
$under_maintenance = 0;
$pending_requests = 0;
$total_departments = 0;
$total_locations = 0;
$total_value = 0;
$assets_due_warranty = 0;
$assets_needing_maintenance = 0;

// Fetch total users and active users
$sql_users = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
              FROM users";
$result_users = $conn->query($sql_users);
if ($result_users && $result_users->num_rows > 0) {
    $row = $result_users->fetch_assoc();
    $total_users = $row['total'];
    $active_users = $row['active'];
}

// Fetch total departments
$sql_dept = "SELECT COUNT(*) as total FROM departments";
$result_dept = $conn->query($sql_dept);
if ($result_dept && $result_dept->num_rows > 0) {
    $row = $result_dept->fetch_assoc();
    $total_departments = $row['total'];
}

// Fetch total locations
$sql_locations = "SELECT COUNT(*) as total FROM locations";
$result_locations = $conn->query($sql_locations);
if ($result_locations && $result_locations->num_rows > 0) {
    $row = $result_locations->fetch_assoc();
    $total_locations = $row['total'];
}

// Fetch asset statistics
$sql_assets = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN asset_status = 'Active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN asset_status = 'In Stock' THEN 1 ELSE 0 END) as in_stock,
                SUM(CASE WHEN asset_status = 'Assigned' THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN asset_status = 'Under Maintenance' THEN 1 ELSE 0 END) as maintenance,
                SUM(cost) as total_value,
                SUM(CASE WHEN warranty_expiry < CURDATE() + INTERVAL 30 DAY THEN 1 ELSE 0 END) as warranty_soon
              FROM assets";
$result_assets = $conn->query($sql_assets);
if ($result_assets && $result_assets->num_rows > 0) {
    $row = $result_assets->fetch_assoc();
    $total_assets = $row['total'];
    $active_assets = $row['active'];
    $in_stock_assets = $row['in_stock'];
    $assigned_assets = $row['assigned'];
    $under_maintenance = $row['maintenance'];
    $total_value = $row['total_value'];
    $assets_due_warranty = $row['warranty_soon'];
}

// Fetch pending asset movements
$sql_pending = "SELECT COUNT(*) as total FROM asset_movements WHERE status = 'Pending'";
$result_pending = $conn->query($sql_pending);
if ($result_pending && $result_pending->num_rows > 0) {
    $row = $result_pending->fetch_assoc();
    $pending_requests = $row['total'];
}

// Fetch assets needing maintenance (scheduled or in progress)
$sql_maintenance = "SELECT COUNT(*) as total FROM maintenance WHERE status IN ('Scheduled', 'In Progress')";
$result_maintenance = $conn->query($sql_maintenance);
if ($result_maintenance && $result_maintenance->num_rows > 0) {
    $row = $result_maintenance->fetch_assoc();
    $assets_needing_maintenance = $row['total'];
}

// Fetch asset distribution by class (for summary display)
$asset_by_class = [];
$sql_class = "SELECT ac.class_name, COUNT(*) as count 
              FROM assets a 
              JOIN asset_classes ac ON a.asset_class = ac.class_id 
              GROUP BY ac.class_name 
              ORDER BY count DESC";
$result_class = $conn->query($sql_class);
if ($result_class && $result_class->num_rows > 0) {
    $asset_by_class = $result_class->fetch_all(MYSQLI_ASSOC);
}

// Fetch asset distribution by department
$asset_by_department = [];
$sql_dept_assets = "SELECT d.name, COUNT(*) as count 
                    FROM assets a 
                    JOIN departments d ON a.owner_department_id = d.department_id 
                    GROUP BY d.name 
                    ORDER BY count DESC";
$result_dept_assets = $conn->query($sql_dept_assets);
if ($result_dept_assets && $result_dept_assets->num_rows > 0) {
    $asset_by_department = $result_dept_assets->fetch_all(MYSQLI_ASSOC);
}

// Fetch recent asset additions
$recent_assets = [];
$sql_recent = "SELECT a.*, ac.class_name 
               FROM assets a 
               LEFT JOIN asset_classes ac ON a.asset_class = ac.class_id 
               ORDER BY a.created_at DESC 
               LIMIT 6";
$result_recent = $conn->query($sql_recent);
if ($result_recent && $result_recent->num_rows > 0) {
    $recent_assets = $result_recent->fetch_all(MYSQLI_ASSOC);
}

// Fetch upcoming maintenance
$upcoming_maintenance = [];
$sql_upcoming = "SELECT m.*, a.asset_name, a.asset_tag 
                FROM maintenance m 
                JOIN assets a ON m.asset_id = a.asset_id 
                WHERE m.status IN ('Scheduled', 'In Progress') 
                AND m.start_date <= CURDATE() + INTERVAL 7 DAY
                ORDER BY m.start_date ASC 
                LIMIT 5";
$result_upcoming = $conn->query($sql_upcoming);
if ($result_upcoming && $result_upcoming->num_rows > 0) {
    $upcoming_maintenance = $result_upcoming->fetch_all(MYSQLI_ASSOC);
}

// Fetch recent activity logs
$recent_activities = [];
$sql_logs = "SELECT al.*, u.username, u.full_name 
             FROM activity_logs al 
             LEFT JOIN users u ON al.user_id = u.user_id 
             ORDER BY al.created_at DESC 
             LIMIT 8";
$result_logs = $conn->query($sql_logs);
if ($result_logs && $result_logs->num_rows > 0) {
    $recent_activities = $result_logs->fetch_all(MYSQLI_ASSOC);
}

// Calculate percentages for progress bars
$asset_utilization = $total_assets > 0 ? round(($active_assets + $assigned_assets) / $total_assets * 100) : 0;
$user_activity = $total_users > 0 ? round($active_users / $total_users * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Asset Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --info-color: #4895ef;
            --light-bg: #f8f9fa;
        }

        body {
            background: #f5f7fb;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Welcome Header */
        .welcome-header {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin: 20px 0 30px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            border: 1px solid rgba(0,0,0,0.03);
        }

        .welcome-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2b2d42;
            margin-bottom: 5px;
        }

        .welcome-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .date-badge {
            background: var(--light-bg);
            padding: 8px 15px;
            border-radius: 10px;
            color: #495057;
            font-size: 0.9rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.02);
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.05);
            border-color: transparent;
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.primary { background: #e9ecef; color: var(--primary-color); }
        .stat-icon.success { background: #e9ecef; color: var(--success-color); }
        .stat-icon.warning { background: #e9ecef; color: var(--warning-color); }
        .stat-icon.info { background: #e9ecef; color: var(--info-color); }

        .stat-label {
            color: #6c757d;
            font-size: 0.85rem;
            font-weight: 500;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: #2b2d42;
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .stat-trend {
            font-size: 0.8rem;
            color: #10b981;
            background: #e9f9f0;
            padding: 3px 8px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .progress-sm {
            height: 6px;
            background: #e9ecef;
            border-radius: 10px;
            margin-top: 15px;
        }

        .progress-bar {
            background: var(--primary-color);
            border-radius: 10px;
        }

        /* Status Cards */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-item {
            background: white;
            border-radius: 14px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(0,0,0,0.03);
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .status-dot.active { background: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
        .status-dot.in-stock { background: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .status-dot.assigned { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1); }
        .status-dot.maintenance { background: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1); }

        .status-info h4 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2b2d42;
            margin: 0;
        }

        .status-info p {
            margin: 0;
            color: #6c757d;
            font-size: 0.85rem;
        }

        /* Content Cards */
        .content-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid rgba(0,0,0,0.03);
        }

        .card-header-custom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2b2d42;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .view-all-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-all-link:hover {
            color: var(--secondary-color);
        }

        /* Tables */
        .table-custom {
            width: 100%;
            margin-bottom: 0;
        }

        .table-custom th {
            color: #6c757d;
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e9ecef;
            padding: 12px 8px;
        }

        .table-custom td {
            padding: 12px 8px;
            color: #2b2d42;
            border-bottom: 1px solid #f1f3f5;
        }

        .table-custom tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .badge-status {
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }

        .badge-active { background: #e9f9f0; color: #10b981; }
        .badge-instock { background: #e8f0fe; color: #3b82f6; }
        .badge-assigned { background: #fef3e2; color: #f59e0b; }
        .badge-maintenance { background: #fee9e9; color: #ef4444; }

        /* Activity List */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: var(--light-bg);
            border-radius: 14px;
            transition: all 0.2s ease;
        }

        .activity-item:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }

        .activity-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-top: 8px;
        }

        .activity-dot.success { background: #10b981; }
        .activity-dot.info { background: #3b82f6; }
        .activity-dot.warning { background: #f59e0b; }
        .activity-dot.danger { background: #ef4444; }

        .activity-content {
            flex: 1;
        }

        .activity-user {
            font-weight: 600;
            color: #2b2d42;
            margin-bottom: 4px;
        }

        .activity-desc {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #adb5bd;
        }

        /* Distribution Tags */
        .distribution-tag {
            background: var(--light-bg);
            padding: 8px 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .distribution-tag:last-child {
            margin-bottom: 0;
        }

        .tag-label {
            color: #495057;
            font-size: 0.9rem;
        }

        .tag-value {
            font-weight: 600;
            color: #2b2d42;
            background: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        /* Alert Banner */
        .alert-banner {
            background: #fff9e6;
            border-left: 4px solid #f59e0b;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-banner i {
            font-size: 1.2rem;
            color: #f59e0b;
        }

        .alert-banner .alert-text {
            flex: 1;
            color: #856404;
        }

        .alert-banner .btn-close {
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid, .status-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid, .status-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include 'adsidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Welcome Header -->
                <div class="welcome-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h1 class="welcome-title">
                                Welcome back, <?php echo htmlspecialchars(get_user_name()); ?>! 👋
                            </h1>
                            <p class="welcome-subtitle">
                                Here's what's happening with your assets today.
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="date-badge">
                                <i class="bi bi-calendar3 me-2"></i><?php echo date('l, F j, Y'); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon primary">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <span class="stat-trend">
                                <i class="bi bi-arrow-up"></i> <?php echo $asset_utilization; ?>%
                            </span>
                        </div>
                        <div class="stat-label">Total Assets</div>
                        <div class="stat-value"><?php echo number_format($total_assets); ?></div>
                        <div class="progress progress-sm">
                            <div class="progress-bar" style="width: <?php echo $asset_utilization; ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">Value: $<?php echo number_format($total_value); ?></small>
                            <small class="text-muted"><?php echo $asset_utilization; ?>% utilized</small>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon success">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                        <div class="stat-label">System Users</div>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-success" style="width: <?php echo $user_activity; ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted"><?php echo $active_users; ?> active</small>
                            <small class="text-muted"><?php echo $user_activity; ?>% active</small>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon warning">
                                <i class="bi bi-building"></i>
                            </div>
                        </div>
                        <div class="stat-label">Departments</div>
                        <div class="stat-value"><?php echo $total_departments; ?></div>
                        <div class="d-flex justify-content-between mt-3">
                            <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo $total_locations; ?> locations</small>
                            <small class="text-muted"><i class="bi bi-person-badge me-1"></i><?php echo $assigned_assets; ?> assigned</small>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon info">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div class="stat-label">Pending Actions</div>
                        <div class="stat-value"><?php echo $pending_requests + $assets_needing_maintenance; ?></div>
                        <div class="d-flex justify-content-between mt-3">
                            <small class="text-danger"><i class="bi bi-tools me-1"></i><?php echo $assets_needing_maintenance; ?> maintenance</small>
                            <small class="text-warning"><i class="bi bi-arrow-left-right me-1"></i><?php echo $pending_requests; ?> movements</small>
                        </div>
                    </div>
                </div>

                <!-- Status Distribution -->
                <div class="status-grid">
                    <div class="status-item">
                        <div class="status-dot active"></div>
                        <div class="status-info">
                            <h4><?php echo $active_assets; ?></h4>
                            <p>Active Assets</p>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-dot in-stock"></div>
                        <div class="status-info">
                            <h4><?php echo $in_stock_assets; ?></h4>
                            <p>In Stock</p>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-dot assigned"></div>
                        <div class="status-info">
                            <h4><?php echo $assigned_assets; ?></h4>
                            <p>Assigned</p>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-dot maintenance"></div>
                        <div class="status-info">
                            <h4><?php echo $under_maintenance; ?></h4>
                            <p>Maintenance</p>
                        </div>
                    </div>
                </div>

                <!-- Two Column Layout -->
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-lg-6">
                        <!-- Recent Assets -->
                        <div class="content-card">
                            <div class="card-header-custom">
                                <h5 class="card-title">
                                    <i class="bi bi-clock-history"></i>
                                    Recently Added Assets
                                </h5>
                                <a href="assets.php" class="view-all-link">
                                    View All <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table-custom">
                                    <thead>
                                        <tr>
                                            <th>Asset</th>
                                            <th>Class</th>
                                            <th>Status</th>
                                            <th>Added</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_assets as $asset): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($asset['asset_name']); ?></strong>
                                                <div><small class="text-muted"><?php echo $asset['asset_tag']; ?></small></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($asset['class_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge-status badge-<?php 
                                                    echo $asset['asset_status'] == 'Active' ? 'active' : 
                                                        ($asset['asset_status'] == 'In Stock' ? 'instock' : 
                                                        ($asset['asset_status'] == 'Assigned' ? 'assigned' : 'maintenance')); 
                                                ?>">
                                                    <?php echo $asset['asset_status']; ?>
                                                </span>
                                            </td>
                                            <td><small><?php echo date('M d, Y', strtotime($asset['created_at'])); ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Asset Distribution by Class -->
                        <div class="content-card">
                            <div class="card-header-custom">
                                <h5 class="card-title">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                    Assets by Class
                                </h5>
                                <span class="text-muted small"><?php echo count($asset_by_class); ?> classes</span>
                            </div>
                            
                            <div>
                                <?php foreach ($asset_by_class as $class): ?>
                                <div class="distribution-tag">
                                    <span class="tag-label"><?php echo htmlspecialchars($class['class_name']); ?></span>
                                    <span class="tag-value"><?php echo $class['count']; ?> assets</span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-lg-6">
                        <!-- Asset Distribution by Department -->
                        <div class="content-card">
                            <div class="card-header-custom">
                                <h5 class="card-title">
                                    <i class="bi bi-building"></i>
                                    Assets by Department
                                </h5>
                                <span class="text-muted small"><?php echo count($asset_by_department); ?> depts</span>
                            </div>
                            
                            <div>
                                <?php foreach ($asset_by_department as $dept): ?>
                                <div class="distribution-tag">
                                    <span class="tag-label"><?php echo htmlspecialchars($dept['name']); ?></span>
                                    <span class="tag-value"><?php echo $dept['count']; ?> assets</span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Upcoming Maintenance -->
                        <div class="content-card">
                            <div class="card-header-custom">
                                <h5 class="card-title">
                                    <i class="bi bi-tools"></i>
                                    Upcoming Maintenance
                                </h5>
                                <a href="reports.php?type=maintenance" class="view-all-link">
                                    View All <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            
                            <?php if (!empty($upcoming_maintenance)): ?>
                                <div class="activity-list">
                                    <?php foreach ($upcoming_maintenance as $maintenance): ?>
                                    <div class="activity-item">
                                        <div class="activity-dot warning"></div>
                                        <div class="activity-content">
                                            <div class="activity-user"><?php echo htmlspecialchars($maintenance['asset_name']); ?></div>
                                            <div class="activity-desc">
                                                <?php echo $maintenance['asset_tag']; ?> • Due: <?php echo date('M d, Y', strtotime($maintenance['start_date'])); ?>
                                            </div>
                                            <div class="activity-time">
                                                Status: <?php echo $maintenance['status']; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-4">No upcoming maintenance scheduled</p>
                            <?php endif; ?>
                        </div>

                        <!-- Quick Actions -->
                        <div class="content-card">
                            <div class="card-header-custom">
                                <h5 class="card-title">
                                    <i class="bi bi-lightning-charge"></i>
                                    Quick Actions
                                </h5>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="assets.php" class="btn btn-outline-primary w-100 py-3">
                                        <i class="bi bi-plus-circle d-block mb-2" style="font-size: 1.5rem;"></i>
                                        Add Asset
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="reports.php" class="btn btn-outline-success w-100 py-3">
                                        <i class="bi bi-file-text d-block mb-2" style="font-size: 1.5rem;"></i>
                                        Reports
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="user_manage.php" class="btn btn-outline-info w-100 py-3">
                                        <i class="bi bi-person-plus d-block mb-2" style="font-size: 1.5rem;"></i>
                                        Add User
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="reports.php?type=movements" class="btn btn-outline-warning w-100 py-3">
                                        <i class="bi bi-arrow-left-right d-block mb-2" style="font-size: 1.5rem;"></i>
                                        Movements
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Log -->
                <div class="content-card mt-3">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="bi bi-activity"></i>
                            Recent Activity
                        </h5>
                        <a href="reports.php?type=activity" class="view-all-link">
                            View All <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    
                    <?php if (!empty($recent_activities)): ?>
                        <div class="row g-3">
                            <?php foreach ($recent_activities as $activity): ?>
                            <div class="col-md-6">
                                <div class="activity-item">
                                    <div class="activity-dot <?php 
                                        echo strpos($activity['activity'], 'Created') !== false ? 'success' : 
                                            (strpos($activity['activity'], 'Updated') !== false ? 'info' : 
                                            (strpos($activity['activity'], 'Deleted') !== false ? 'danger' : 'warning')); 
                                    ?>"></div>
                                    <div class="activity-content">
                                        <div class="activity-user">
                                            <?php echo htmlspecialchars($activity['full_name'] ?? $activity['username'] ?? 'System'); ?>
                                        </div>
                                        <div class="activity-desc">
                                            <?php echo htmlspecialchars($activity['activity']); ?>
                                            <?php if ($activity['details']): ?>
                                                <small class="text-muted d-block"><?php echo htmlspecialchars(substr($activity['details'], 0, 60)); ?>...</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-time">
                                            <i class="bi bi-clock me-1"></i><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?>
                                            <?php if ($activity['ip_address']): ?>
                                                • <i class="bi bi-pc"></i> <?php echo $activity['ip_address']; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No recent activity</p>
                    <?php endif; ?>
                </div>

                <!-- Alerts Banner -->
                <?php if ($assets_due_warranty > 0 || $assets_needing_maintenance > 0): ?>
                <div class="alert-banner">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div class="alert-text">
                        <strong>Attention Required:</strong>
                        <?php if ($assets_due_warranty > 0): ?>
                            <?php echo $assets_due_warranty; ?> asset(s) warranty expiring within 30 days.
                        <?php endif; ?>
                        <?php if ($assets_needing_maintenance > 0): ?>
                            <?php echo $assets_needing_maintenance; ?> asset(s) require maintenance.
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>