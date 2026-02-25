<?php
// sidebar.php - Accountant Dashboard Sidebar
?>
<style>
    .sidebar {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        min-height: 100vh;
        padding-top: 20px;
    }
    .sidebar .nav-link {
        color: white;
        padding: 10px 15px;
        margin: 5px 0;
    }
    .sidebar .nav-link:hover {
        background: rgba(255,255,255,0.1);
    }
    .sidebar .nav-link.active {
        background: rgba(255,255,255,0.2);
    }
</style>

<nav class="col-md-3 col-lg-2 d-md-block sidebar">
    <div class="position-sticky">
        <div class="text-center mb-4">
            <i class="bi bi-calculator" style="font-size: 48px;"></i>
            <h4 class="mt-2">Accountant Dashboard</h4>
            <p class="mb-0">Welcome, <?php echo htmlspecialchars(get_user_name()); ?></p>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="financial_report.php">
                    <i class="bi bi-cash-stack"></i> Financial Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="depreciation.php">
                    <i class="bi bi-graph-up"></i> Depreciation
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="expenses.php">
                    <i class="bi bi-receipt"></i> Expenses
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="../../auth/logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>