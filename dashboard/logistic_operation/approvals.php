<?php
require_once '../../includes/check_auth.php';
check_auth(['logistic_coordinator', 'it_operation']);
require_once '../../includes/db.php';

$conn    = $db->conn;
$user_id = $_SESSION['user_id'];

$message    = '';
$alert_type = '';

// ── Handle Approve / Reject ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decision'])) {
    $movement_id = trim($_POST['movement_id']);
    $decision    = trim($_POST['decision']); // 'Approved' or 'Rejected'
    $asset_id    = trim($_POST['asset_id']);
    $movement_type = trim($_POST['movement_type']);
    $approver_note = trim($_POST['approver_note'] ?? '');

    // Update movement status
    $upd_sql = "UPDATE asset_movements SET status = ?, remarks = CONCAT(remarks, ?) WHERE movement_id = ?";
    $note_suffix = !empty($approver_note) ? " | Approver note: {$approver_note}" : '';
    $upd_stmt = $conn->prepare($upd_sql);
    $upd_stmt->bind_param("sss", $decision, $note_suffix, $movement_id);

    if ($upd_stmt->execute()) {
        // If approved, update asset status
        if ($decision === 'Approved') {
            if ($movement_type === 'Issue') {
                // Find who it's being issued to from remarks
                $rem_sql = "SELECT remarks, performed_by_user_id FROM asset_movements WHERE movement_id = ?";
                $rem_stmt = $conn->prepare($rem_sql);
                $rem_stmt->bind_param("s", $movement_id);
                $rem_stmt->execute();
                $rem_row = $rem_stmt->get_result()->fetch_assoc();
                $rem_stmt->close();

                // Try to find "For: [name/id]" in remarks and assign
                $assigned_uid = $rem_row['performed_by_user_id']; // default: requester themselves
                if (preg_match('/For: ([^\|]+)/', $rem_row['remarks'] ?? '', $m)) {
                    $for_name = trim($m[1]);
                    $find_u = $conn->prepare("SELECT user_id FROM users WHERE full_name = ? OR username = ? LIMIT 1");
                    $find_u->bind_param("ss", $for_name, $for_name);
                    $find_u->execute();
                    $found_u = $find_u->get_result()->fetch_assoc();
                    $find_u->close();
                    if ($found_u) $assigned_uid = $found_u['user_id'];
                }

                $ast_sql = "UPDATE assets SET asset_status = 'Assigned', assigned_to_user_id = ? WHERE asset_id = ?";
                $ast_stmt = $conn->prepare($ast_sql);
                $ast_stmt->bind_param("ss", $assigned_uid, $asset_id);
                $ast_stmt->execute();
                $ast_stmt->close();

            } elseif ($movement_type === 'Return') {
                $ast_sql = "UPDATE assets SET asset_status = 'In Stock', assigned_to_user_id = NULL WHERE asset_id = ?";
                $ast_stmt = $conn->prepare($ast_sql);
                $ast_stmt->bind_param("s", $asset_id);
                $ast_stmt->execute();
                $ast_stmt->close();
            }
        }

        $icon = $decision === 'Approved' ? '✓' : '✗';
        $message    = "{$icon} Request <strong>{$decision}</strong> successfully.";
        $alert_type = $decision === 'Approved' ? 'success' : 'warning';
    } else {
        $message    = "Failed to update request. Please try again.";
        $alert_type = 'danger';
    }
    $upd_stmt->close();
}

// ── Filter ────────────────────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'Pending';
$allowed_filters = ['Pending', 'Approved', 'Rejected', 'All'];
if (!in_array($filter, $allowed_filters)) $filter = 'Pending';

$where = $filter !== 'All' ? "WHERE m.status = ?" : "WHERE 1=1";

$sql = "SELECT m.*, 
               a.asset_name, a.serial_number, a.asset_id AS a_asset_id,
               ac.class_name,
               u.full_name AS requester_name, u.username AS requester_username
        FROM asset_movements m
        LEFT JOIN assets a ON m.asset_id = a.asset_id
        LEFT JOIN asset_classes ac ON a.asset_class = ac.class_id
        LEFT JOIN users u ON m.performed_by_user_id = u.user_id
        {$where}
        ORDER BY m.movement_date DESC";

$stmt = $conn->prepare($sql);
if ($filter !== 'All') {
    $stmt->bind_param("s", $filter);
}
$stmt->execute();
$movements = $stmt->get_result();
$stmt->close();

// Count badges
$counts = [];
foreach (['Pending', 'Approved', 'Rejected'] as $s) {
    $cs = $conn->prepare("SELECT COUNT(*) as c FROM asset_movements WHERE status = ?");
    $cs->bind_param("s", $s);
    $cs->execute();
    $counts[$s] = $cs->get_result()->fetch_assoc()['c'];
    $cs->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approvals / Requests · Logistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { background: #f4f6fb; }
        .page-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 1.5rem 2rem; margin-bottom: 2rem; }
        .card { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .card-header { border-radius: 14px 14px 0 0 !important; }
        .alert-custom { border-radius: 50px; border: none; padding: 0.85rem 1.5rem; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
        .alert-success-c { background: #e1f7ed; color: #0d683e; }
        .alert-warning-c { background: #fff8e1; color: #7a5c00; }
        .alert-danger-c  { background: #fef1f0; color: #ab2e2e; }
        .status-pill { font-size: 0.78rem; font-weight: 600; padding: 0.3rem 0.8rem; border-radius: 50px; }
        .pill-pending  { background: #fff3cd; color: #856404; }
        .pill-approved { background: #d1e7dd; color: #0a3622; }
        .pill-rejected { background: #f8d7da; color: #58151c; }
        .remarks-cell { max-width: 220px; font-size: 0.82rem; color: #555; }
        .btn-approve { background: #28a745; color: white; border: none; border-radius: 8px; padding: 0.35rem 0.9rem; font-size: 0.82rem; font-weight: 600; }
        .btn-approve:hover { background: #218838; color: white; }
        .btn-reject { background: #dc3545; color: white; border: none; border-radius: 8px; padding: 0.35rem 0.9rem; font-size: 0.82rem; font-weight: 600; }
        .btn-reject:hover { background: #b02a37; color: white; }
        .filter-tabs .nav-link { border-radius: 50px; padding: 0.45rem 1.2rem; font-weight: 500; color: #555; }
        .filter-tabs .nav-link.active { background: #667eea; color: white; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'whsidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-3">

            <div class="page-header">
                <h2 class="mb-0"><i class="bi bi-check2-circle me-2"></i>Approvals / Requests</h2>
                <p class="mb-0 mt-1 opacity-75">Review and action Issue / Return requests from the Operations team.</p>
            </div>

            <!-- Alert -->
            <?php if ($message): ?>
                <div class="alert-custom alert-<?php echo $alert_type; ?>-c mb-4">
                    <i class="bi <?php echo $alert_type === 'success' ? 'bi-check-circle-fill' : 'bi-info-circle-fill'; ?>"></i>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <ul class="nav filter-tabs mb-3 gap-2">
                <?php foreach (['Pending', 'Approved', 'Rejected', 'All'] as $f): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === $f ? 'active' : ''; ?>" href="?filter=<?php echo $f; ?>">
                        <?php echo $f; ?>
                        <?php if (isset($counts[$f]) && $counts[$f] > 0): ?>
                            <span class="badge <?php echo $f === 'Pending' ? 'bg-warning text-dark' : ($f === 'Approved' ? 'bg-success' : 'bg-danger'); ?> ms-1">
                                <?php echo $counts[$f]; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Requests Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Requested By</th>
                                    <th>Asset</th>
                                    <th>Serial No.</th>
                                    <th>Type</th>
                                    <th>Remarks</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($movements && $movements->num_rows > 0): ?>
                                    <?php while ($m = $movements->fetch_assoc()): ?>
                                    <tr>
                                        <td><small><?php echo date('d/m/Y H:i', strtotime($m['movement_date'])); ?></small></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($m['requester_name'] ?? '—'); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($m['requester_username'] ?? ''); ?></small>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($m['asset_name'] ?? '—'); ?></strong></td>
                                        <td><code><?php echo htmlspecialchars($m['serial_number'] ?? '—'); ?></code></td>
                                        <td>
                                            <span class="badge <?php echo $m['movement_type'] === 'Issue' ? 'bg-primary' : 'bg-secondary'; ?>">
                                                <?php echo htmlspecialchars($m['movement_type']); ?>
                                            </span>
                                        </td>
                                        <td class="remarks-cell"><?php echo htmlspecialchars($m['remarks'] ?? '—'); ?></td>
                                        <td>
                                            <span class="status-pill pill-<?php echo strtolower($m['status']); ?>">
                                                <?php echo $m['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($m['status'] === 'Pending'): ?>
                                                <!-- Approve/Reject buttons trigger modal -->
                                                <button class="btn-approve me-1" onclick="openDecision('<?php echo $m['movement_id']; ?>', '<?php echo htmlspecialchars($m['asset_id']); ?>', '<?php echo $m['movement_type']; ?>', 'Approved')">
                                                    <i class="bi bi-check-lg"></i> Approve
                                                </button>
                                                <button class="btn-reject" onclick="openDecision('<?php echo $m['movement_id']; ?>', '<?php echo htmlspecialchars($m['asset_id']); ?>', '<?php echo $m['movement_type']; ?>', 'Rejected')">
                                                    <i class="bi bi-x-lg"></i> Reject
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            No <?php echo strtolower($filter); ?> requests found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Decision Modal -->
<div class="modal fade" id="decisionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header" id="modalHeader">
                <h5 class="modal-title" id="modalTitle">Confirm Decision</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modalBody" class="text-muted mb-3"></p>
                <label class="form-label fw-500">Approver Note <small class="text-muted">(optional)</small></label>
                <textarea class="form-control" id="approverNote" rows="2" placeholder="Add a note for the requester..."></textarea>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="decisionForm">
                    <input type="hidden" name="movement_id"   id="f_movement_id">
                    <input type="hidden" name="asset_id"      id="f_asset_id">
                    <input type="hidden" name="movement_type" id="f_movement_type">
                    <input type="hidden" name="decision"      id="f_decision">
                    <input type="hidden" name="approver_note" id="f_approver_note">
                    <button type="submit" class="btn" id="f_submit_btn">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openDecision(movementId, assetId, movementType, decision) {
    document.getElementById('f_movement_id').value   = movementId;
    document.getElementById('f_asset_id').value      = assetId;
    document.getElementById('f_movement_type').value = movementType;
    document.getElementById('f_decision').value      = decision;
    document.getElementById('approverNote').value     = '';

    const isApprove = decision === 'Approved';
    const header = document.getElementById('modalHeader');
    const title  = document.getElementById('modalTitle');
    const body   = document.getElementById('modalBody');
    const btn    = document.getElementById('f_submit_btn');

    header.className = 'modal-header ' + (isApprove ? 'bg-success text-white' : 'bg-danger text-white');
    title.textContent = isApprove ? '✓ Approve Request' : '✗ Reject Request';
    body.textContent  = isApprove
        ? 'Are you sure you want to approve this request? The asset status will be updated automatically.'
        : 'Are you sure you want to reject this request? The requester will be notified.';
    btn.className = 'btn ' + (isApprove ? 'btn-success' : 'btn-danger');
    btn.textContent = isApprove ? 'Approve' : 'Reject';

    // Pass approver note to hidden input on submit
    document.getElementById('decisionForm').addEventListener('submit', function () {
        document.getElementById('f_approver_note').value = document.getElementById('approverNote').value;
    }, { once: true });

    new bootstrap.Modal(document.getElementById('decisionModal')).show();
}
</script>
</body>
</html>