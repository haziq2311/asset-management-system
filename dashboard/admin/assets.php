<?php
require_once '../../includes/check_auth.php';
check_auth(['admin', 'it_operation']);

require_once '../../includes/db.php';
$conn = $db->conn;

// Handle CRUD Operations
$message = '';
$message_type = '';

// Create or Update Asset
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $asset_id = $_POST['asset_id'] ?? '';
        $asset_tag = $_POST['asset_tag'] ?? '';
        $asset_name = $_POST['asset_name'] ?? '';
        $asset_class = $_POST['asset_class'] ?? '';
        $model = $_POST['model'] ?? '';
        $manufacturer = $_POST['manufacturer'] ?? '';
        $serial_number = $_POST['serial_number'] ?? '';
        $purchase_order_number = $_POST['purchase_order_number'] ?? '';
        $acquisition_date = $_POST['acquisition_date'] ?? '';
        $warranty_expiry = $_POST['warranty_expiry'] ?? '';
        $vendor = $_POST['vendor'] ?? '';
        $cost = $_POST['cost'] ?? '';
        $asset_status = $_POST['asset_status'] ?? 'In Stock';
        $location_id = $_POST['location_id'] ?? '';
        $owner_department_id = $_POST['owner_department_id'] ?? '';
        $assigned_to_user_id = $_POST['assigned_to_user_id'] ?? '';
        $remarks = $_POST['remarks'] ?? '';

        if ($_POST['action'] == 'create') {
            // Generate unique asset_id
            $asset_id = 'AST' . date('Ymd') . rand(100, 999);
            
            $sql = "INSERT INTO assets (asset_id, asset_tag, asset_name, asset_class, model, manufacturer, 
                    serial_number, purchase_order_number, acquisition_date, warranty_expiry, vendor, cost, 
                    asset_status, location_id, owner_department_id, assigned_to_user_id, remarks, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssssssdssssss", 
                $asset_id, $asset_tag, $asset_name, $asset_class, $model, $manufacturer,
                $serial_number, $purchase_order_number, $acquisition_date, $warranty_expiry, 
                $vendor, $cost, $asset_status, $location_id, $owner_department_id, 
                $assigned_to_user_id, $remarks, $_SESSION['user_id']
            );
            
            if ($stmt->execute()) {
                $message = "Asset created successfully!";
                $message_type = "success";
                
                // Log activity
                $log_sql = "INSERT INTO activity_logs (user_id, activity, details, ip_address) VALUES (?, 'Created Asset', ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_details = "Created asset: " . $asset_name;
                $log_stmt->bind_param("sss", $_SESSION['user_id'], $log_details, $_SERVER['REMOTE_ADDR']);
                $log_stmt->execute();
            } else {
                $message = "Error creating asset: " . $conn->error;
                $message_type = "danger";
            }
            
        } elseif ($_POST['action'] == 'update') {
            $sql = "UPDATE assets SET asset_tag=?, asset_name=?, asset_class=?, model=?, manufacturer=?, 
                    serial_number=?, purchase_order_number=?, acquisition_date=?, warranty_expiry=?, 
                    vendor=?, cost=?, asset_status=?, location_id=?, owner_department_id=?, 
                    assigned_to_user_id=?, remarks=?, updated_by=? WHERE asset_id=?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssssdsssssss", 
                $asset_tag, $asset_name, $asset_class, $model, $manufacturer,
                $serial_number, $purchase_order_number, $acquisition_date, $warranty_expiry,
                $vendor, $cost, $asset_status, $location_id, $owner_department_id,
                $assigned_to_user_id, $remarks, $_SESSION['user_id'], $asset_id
            );
            
            if ($stmt->execute()) {
                $message = "Asset updated successfully!";
                $message_type = "success";
                
                // Log activity
                $log_sql = "INSERT INTO activity_logs (user_id, activity, details, ip_address) VALUES (?, 'Updated Asset', ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_details = "Updated asset: " . $asset_name;
                $log_stmt->bind_param("sss", $_SESSION['user_id'], $log_details, $_SERVER['REMOTE_ADDR']);
                $log_stmt->execute();
            } else {
                $message = "Error updating asset: " . $conn->error;
                $message_type = "danger";
            }
        }
    }
}

// Delete Asset
if (isset($_GET['delete'])) {
    $asset_id = $_GET['delete'];
    
    // Get asset name for logging
    $name_sql = "SELECT asset_name FROM assets WHERE asset_id = ?";
    $name_stmt = $conn->prepare($name_sql);
    $name_stmt->bind_param("s", $asset_id);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();
    $asset_name = $name_result->fetch_assoc()['asset_name'];
    
    $sql = "DELETE FROM assets WHERE asset_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $asset_id);
    
    if ($stmt->execute()) {
        $message = "Asset deleted successfully!";
        $message_type = "success";
        
        // Log activity
        $log_sql = "INSERT INTO activity_logs (user_id, activity, details, ip_address) VALUES (?, 'Deleted Asset', ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_details = "Deleted asset: " . $asset_name;
        $log_stmt->bind_param("sss", $_SESSION['user_id'], $log_details, $_SERVER['REMOTE_ADDR']);
        $log_stmt->execute();
    } else {
        $message = "Error deleting asset: " . $conn->error;
        $message_type = "danger";
    }
}

// Fetch assets with related data
$assets = [];
$sql = "SELECT a.*, l.name as location_name, d.name as department_name, 
        u.full_name as assigned_to, ac.class_name 
        FROM assets a 
        LEFT JOIN locations l ON a.location_id = l.location_id 
        LEFT JOIN departments d ON a.owner_department_id = d.department_id 
        LEFT JOIN users u ON a.assigned_to_user_id = u.user_id 
        LEFT JOIN asset_classes ac ON a.asset_class = ac.class_id 
        ORDER BY a.created_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $assets = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch dropdown data
$locations = $conn->query("SELECT * FROM locations ORDER BY name");
$departments = $conn->query("SELECT * FROM departments ORDER BY name");
$users = $conn->query("SELECT user_id, full_name, username FROM users WHERE is_active = 1 ORDER BY full_name");
$asset_classes = $conn->query("SELECT * FROM asset_classes ORDER BY class_name");

// Stats
$total_assets = count($assets);
$active_assets = 0;
$in_stock_assets = 0;
$assigned_assets = 0;
$under_maintenance = 0;

foreach ($assets as $asset) {
    if ($asset['asset_status'] == 'Active') $active_assets++;
    elseif ($asset['asset_status'] == 'In Stock') $in_stock_assets++;
    elseif ($asset['asset_status'] == 'Assigned') $assigned_assets++;
    elseif ($asset['asset_status'] == 'Under Maintenance') $under_maintenance++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Management - Asset Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-in-stock { background: #cce5ff; color: #004085; }
        .status-assigned { background: #fff3cd; color: #856404; }
        .status-maintenance { background: #f8d7da; color: #721c24; }
        .status-disposed { background: #e2e3e5; color: #383d41; }
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .quick-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .action-btns .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'adsidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="content-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="bi bi-box"></i> Asset Management</h1>
                            <p>Manage and track all company assets</p>
                        </div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assetModal">
                            <i class="bi bi-plus-lg"></i> Add New Asset
                        </button>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="quick-stats">
                    <div class="row">
                        <div class="col-md-2 col-6">
                            <h6>Total Assets</h6>
                            <h3><?php echo $total_assets; ?></h3>
                        </div>
                        <div class="col-md-2 col-6">
                            <h6>Active</h6>
                            <h3><?php echo $active_assets; ?></h3>
                        </div>
                        <div class="col-md-2 col-6">
                            <h6>In Stock</h6>
                            <h3><?php echo $in_stock_assets; ?></h3>
                        </div>
                        <div class="col-md-2 col-6">
                            <h6>Assigned</h6>
                            <h3><?php echo $assigned_assets; ?></h3>
                        </div>
                        <div class="col-md-2 col-6">
                            <h6>Maintenance</h6>
                            <h3><?php echo $under_maintenance; ?></h3>
                        </div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="In Stock">In Stock</option>
                                <option value="Assigned">Assigned</option>
                                <option value="Under Maintenance">Under Maintenance</option>
                                <option value="Disposed">Disposed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="classFilter">
                                <option value="">All Classes</option>
                                <?php 
                                $asset_classes->data_seek(0);
                                while($class = $asset_classes->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $class['class_name']; ?>"><?php echo $class['class_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="locationFilter">
                                <option value="">All Locations</option>
                                <?php 
                                $locations->data_seek(0);
                                while($loc = $locations->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $loc['name']; ?>"><?php echo $loc['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="searchFilter" placeholder="Search...">
                        </div>
                    </div>
                </div>

                <!-- Assets Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="assetsTable">
                                <thead>
                                    <tr>
                                        <th>Asset Tag</th>
                                        <th>Name</th>
                                        <th>Class</th>
                                        <th>Model</th>
                                        <th>Serial Number</th>
                                        <th>Status</th>
                                        <th>Location</th>
                                        <th>Assigned To</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assets as $asset): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($asset['asset_tag'] ?? 'N/A'); ?></strong></td>
                                            <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                            <td><?php echo htmlspecialchars($asset['class_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($asset['model'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($asset['serial_number'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch($asset['asset_status']) {
                                                    case 'Active':
                                                        $status_class = 'status-active';
                                                        break;
                                                    case 'In Stock':
                                                        $status_class = 'status-in-stock';
                                                        break;
                                                    case 'Assigned':
                                                        $status_class = 'status-assigned';
                                                        break;
                                                    case 'Under Maintenance':
                                                        $status_class = 'status-maintenance';
                                                        break;
                                                    default:
                                                        $status_class = 'status-disposed';
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <?php echo $asset['asset_status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($asset['location_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($asset['assigned_to'] ?? 'Unassigned'); ?></td>
                                            <td class="action-btns">
                                                <button class="btn btn-sm btn-info view-asset" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#viewAssetModal"
                                                        data-asset='<?php echo json_encode($asset); ?>'>
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning edit-asset" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#assetModal"
                                                        data-asset='<?php echo json_encode($asset); ?>'>
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="?delete=<?php echo $asset['asset_id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this asset?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Asset Modal (Create/Edit) -->
    <div class="modal fade" id="assetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assetModalTitle">Add New Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="assetForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="create">
                        <input type="hidden" name="asset_id" id="asset_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Asset Tag</label>
                                <input type="text" class="form-control" name="asset_tag" id="asset_tag">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Asset Name *</label>
                                <input type="text" class="form-control" name="asset_name" id="asset_name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Asset Class</label>
                                <select class="form-select" name="asset_class" id="asset_class">
                                    <option value="">Select Class</option>
                                    <?php 
                                    $asset_classes->data_seek(0);
                                    while($class = $asset_classes->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $class['class_id']; ?>"><?php echo $class['class_name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" name="model" id="model">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input type="text" class="form-control" name="manufacturer" id="manufacturer">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Serial Number</label>
                                <input type="text" class="form-control" name="serial_number" id="serial_number">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Purchase Order Number</label>
                                <input type="text" class="form-control" name="purchase_order_number" id="purchase_order_number">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vendor</label>
                                <input type="text" class="form-control" name="vendor" id="vendor">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Acquisition Date</label>
                                <input type="date" class="form-control" name="acquisition_date" id="acquisition_date">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Warranty Expiry</label>
                                <input type="date" class="form-control" name="warranty_expiry" id="warranty_expiry">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cost</label>
                                <input type="number" step="0.01" class="form-control" name="cost" id="cost">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="asset_status" id="asset_status" required>
                                    <option value="In Stock">In Stock</option>
                                    <option value="Active">Active</option>
                                    <option value="Assigned">Assigned</option>
                                    <option value="Under Maintenance">Under Maintenance</option>
                                    <option value="Disposed">Disposed</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location</label>
                                <select class="form-select" name="location_id" id="location_id">
                                    <option value="">Select Location</option>
                                    <?php 
                                    $locations->data_seek(0);
                                    while($loc = $locations->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $loc['location_id']; ?>"><?php echo $loc['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="owner_department_id" id="owner_department_id">
                                    <option value="">Select Department</option>
                                    <?php 
                                    $departments->data_seek(0);
                                    while($dept = $departments->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Assigned To</label>
                            <select class="form-select" name="assigned_to_user_id" id="assigned_to_user_id">
                                <option value="">Select User</option>
                                <?php 
                                $users->data_seek(0);
                                while($user = $users->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $user['user_id']; ?>"><?php echo $user['full_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" id="remarks" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Asset Modal -->
    <div class="modal fade" id="viewAssetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asset Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewAssetContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#assetsTable').DataTable({
                pageLength: 25,
                order: [[1, 'asc']],
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf']
            });

            // Apply filters
            $('#statusFilter, #classFilter, #locationFilter').on('change', function() {
                table.draw();
            });

            $('#searchFilter').on('keyup', function() {
                table.search(this.value).draw();
            });

            // Custom filter for status
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var status = $('#statusFilter').val();
                    var statusData = data[5];
                    if (status === '' || statusData.includes(status)) {
                        return true;
                    }
                    return false;
                }
            );

            // Edit Asset
            $('.edit-asset').on('click', function() {
                var asset = $(this).data('asset');
                $('#assetModalTitle').text('Edit Asset');
                $('#action').val('update');
                $('#asset_id').val(asset.asset_id);
                $('#asset_tag').val(asset.asset_tag);
                $('#asset_name').val(asset.asset_name);
                $('#asset_class').val(asset.asset_class);
                $('#model').val(asset.model);
                $('#manufacturer').val(asset.manufacturer);
                $('#serial_number').val(asset.serial_number);
                $('#purchase_order_number').val(asset.purchase_order_number);
                $('#acquisition_date').val(asset.acquisition_date);
                $('#warranty_expiry').val(asset.warranty_expiry);
                $('#vendor').val(asset.vendor);
                $('#cost').val(asset.cost);
                $('#asset_status').val(asset.asset_status);
                $('#location_id').val(asset.location_id);
                $('#owner_department_id').val(asset.owner_department_id);
                $('#assigned_to_user_id').val(asset.assigned_to_user_id);
                $('#remarks').val(asset.remarks);
            });

            // Reset form for new asset
            $('#assetModal').on('hidden.bs.modal', function() {
                if (!$('#action').val() == 'update') {
                    $('#assetForm')[0].reset();
                    $('#assetModalTitle').text('Add New Asset');
                    $('#action').val('create');
                    $('#asset_id').val('');
                }
            });

            // View Asset
            $('.view-asset').on('click', function() {
                var asset = $(this).data('asset');
                var html = `
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><th>Asset ID:</th><td>${asset.asset_id}</td></tr>
                                <tr><th>Asset Tag:</th><td>${asset.asset_tag || 'N/A'}</td></tr>
                                <tr><th>Asset Name:</th><td>${asset.asset_name}</td></tr>
                                <tr><th>Class:</th><td>${asset.class_name || 'N/A'}</td></tr>
                                <tr><th>Model:</th><td>${asset.model || 'N/A'}</td></tr>
                                <tr><th>Manufacturer:</th><td>${asset.manufacturer || 'N/A'}</td></tr>
                                <tr><th>Serial Number:</th><td>${asset.serial_number || 'N/A'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><th>Status:</th><td><span class="status-badge status-${asset.asset_status.toLowerCase().replace(' ', '-')}">${asset.asset_status}</span></td></tr>
                                <tr><th>Location:</th><td>${asset.location_name || 'N/A'}</td></tr>
                                <tr><th>Department:</th><td>${asset.department_name || 'N/A'}</td></tr>
                                <tr><th>Assigned To:</th><td>${asset.assigned_to || 'Unassigned'}</td></tr>
                                <tr><th>Cost:</th><td>${asset.cost ? '$' + parseFloat(asset.cost).toFixed(2) : 'N/A'}</td></tr>
                                <tr><th>Acquisition Date:</th><td>${asset.acquisition_date || 'N/A'}</td></tr>
                                <tr><th>Warranty Expiry:</th><td>${asset.warranty_expiry || 'N/A'}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <h6>Remarks:</h6>
                            <p>${asset.remarks || 'No remarks'}</p>
                        </div>
                    </div>
                `;
                $('#viewAssetContent').html(html);
            });
        });
    </script>
</body>
</html>