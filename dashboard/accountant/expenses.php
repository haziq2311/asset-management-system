<?php
// expenses.php
require_once '../../includes/check_auth.php';
require_once '../../includes/db.php';

check_auth(['accountant', 'it_operation']);

// Get monthly expenses for current year
$current_year = date('Y');
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;

// Get assets for dropdown
$assets_query = $db->pdo->query("SELECT asset_id, asset_name FROM assets WHERE is_active = 1 LIMIT 20");
$assets = $assets_query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracking - Asset Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .content-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .expense-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .expense-card:hover {
            transform: translateY(-5px);
        }
        .category-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
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
                            <h1>Expense Tracking</h1>
                            <p class="text-muted">Monitor and manage asset-related expenses</p>
                        </div>
                        <div>
                            <button class="btn btn-success me-2" onclick="addExpense()">
                                <i class="bi bi-plus-circle"></i> Add Expense
                            </button>
                            <button class="btn btn-primary" onclick="exportExpenses()">
                                <i class="bi bi-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="container-fluid mt-4">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card expense-card bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Total Expenses (YTD)</h6>
                                    <h3>RM 45,280.50</h3>
                                    <i class="bi bi-cash position-absolute" style="right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
                                    <small class="d-block mt-2">↑ 12% vs last year</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card expense-card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="card-title">This Month</h6>
                                    <h3>RM 8,450.00</h3>
                                    <i class="bi bi-calendar-month position-absolute" style="right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
                                    <small class="d-block mt-2"><?php echo date('F Y'); ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card expense-card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Maintenance Cost</h6>
                                    <h3>RM 12,550.00</h3>
                                    <i class="bi bi-tools position-absolute" style="right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
                                    <small class="d-block mt-2">28% of total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card expense-card bg-warning text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Pending Approvals</h6>
                                    <h3>3</h3>
                                    <i class="bi bi-clock-history position-absolute" style="right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
                                    <small class="d-block mt-2">RM 2,350.00</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Month</label>
                                    <select name="month" class="form-select">
                                        <?php for($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                                <?php echo $m == $selected_month ? 'selected' : ''; ?>>
                                            <?php echo date('F', mktime(0,0,0,$m,1)); ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Year</label>
                                    <select name="year" class="form-select">
                                        <?php for($y = 2024; $y >= 2020; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $y == $selected_year ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" id="categoryFilter">
                                        <option value="">All Categories</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Repair">Repair</option>
                                        <option value="Fuel">Fuel</option>
                                        <option value="Insurance">Insurance</option>
                                        <option value="License">License</option>
                                        <option value="Utilities">Utilities</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search"></i> Apply Filters
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Expense by Category -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Expenses by Category</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="categoryChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">Monthly Trend</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="trendChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Expense List -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Expense Transactions - <?php echo date('F Y', mktime(0,0,0,$selected_month,1,$selected_year)); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="expenseTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Reference</th>
                                            <th>Description</th>
                                            <th>Category</th>
                                            <th>Asset</th>
                                            <th>Amount (RM)</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Sample data - would come from database -->
                                        <tr>
                                            <td>15/02/2024</td>
                                            <td>INV-001</td>
                                            <td>Oil change and service</td>
                                            <td><span class="badge bg-info">Maintenance</span></td>
                                            <td>Nissan Navara QS1043M</td>
                                            <td class="text-end">850.00</td>
                                            <td><span class="badge bg-success">Paid</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>10/02/2024</td>
                                            <td>INV-002</td>
                                            <td>Printer toner replacement</td>
                                            <td><span class="badge bg-warning">Supplies</span></td>
                                            <td>Brother MFC-T4500DW</td>
                                            <td class="text-end">320.50</td>
                                            <td><span class="badge bg-success">Paid</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>05/02/2024</td>
                                            <td>INV-003</td>
                                            <td>Annual vehicle insurance</td>
                                            <td><span class="badge bg-danger">Insurance</span></td>
                                            <td>Isuzu D-Max QAA850W</td>
                                            <td class="text-end">2,450.00</td>
                                            <td><span class="badge bg-success">Paid</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>01/02/2024</td>
                                            <td>INV-004</td>
                                            <td>Aircon servicing</td>
                                            <td><span class="badge bg-info">Maintenance</span></td>
                                            <td>York Air-conditioner</td>
                                            <td class="text-end">180.00</td>
                                            <td><span class="badge bg-success">Paid</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>28/01/2024</td>
                                            <td>INV-005</td>
                                            <td>Fuel card top-up</td>
                                            <td><span class="badge bg-secondary">Fuel</span></td>
                                            <td>Fleet Vehicles</td>
                                            <td class="text-end">3,500.00</td>
                                            <td><span class="badge bg-warning">Pending</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-secondary">
                                        <tr>
                                            <th colspan="5" class="text-end">Total:</th>
                                            <th class="text-end">RM 7,300.50</th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Add Expense Modal -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="expenseForm" method="POST" action="save_expense.php">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Date *</label>
                                <input type="date" name="expense_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference No.</label>
                                <input type="text" name="reference_no" class="form-control" placeholder="INV-001">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Repair">Repair</option>
                                    <option value="Fuel">Fuel</option>
                                    <option value="Insurance">Insurance</option>
                                    <option value="License">License</option>
                                    <option value="Utilities">Utilities</option>
                                    <option value="Supplies">Supplies</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Asset</label>
                                <select name="asset_id" class="form-select">
                                    <option value="">Select Asset (Optional)</option>
                                    <?php foreach($assets as $asset): ?>
                                    <option value="<?php echo $db->sanitizePDO($asset['asset_id']); ?>">
                                        <?php echo htmlspecialchars(substr($asset['asset_name'], 0, 50)); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Amount (RM) *</label>
                                <input type="number" name="amount" step="0.01" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Paid">Paid</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Approved">Approved</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveExpense()">Save Expense</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            $('#expenseTable').DataTable({
                pageLength: 25,
                order: [[0, 'desc']]
            });
            
            // Category filter
            $('#categoryFilter').on('change', function() {
                var category = $(this).val();
                $('#expenseTable').DataTable().column(3).search(category).draw();
            });
            
            // Charts
            const ctx1 = document.getElementById('categoryChart').getContext('2d');
            new Chart(ctx1, {
                type: 'pie',
                data: {
                    labels: ['Maintenance', 'Fuel', 'Insurance', 'Supplies', 'Repair', 'Utilities'],
                    datasets: [{
                        data: [45, 25, 15, 8, 5, 2],
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
            
            const ctx2 = document.getElementById('trendChart').getContext('2d');
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Monthly Expenses',
                        data: [8250, 8450, 7900, 8200, 8800, 9100],
                        borderColor: '#4e73df',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
        
        function addExpense() {
            new bootstrap.Modal(document.getElementById('addExpenseModal')).show();
        }
        
        function saveExpense() {
            document.getElementById('expenseForm').submit();
        }
        
        function exportExpenses() {
            window.location.href = 'export_expenses.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>';
        }
    </script>
</body>
</html>