<?php
require_once '../includes/db.php';

$message = '';
$alert_type = ''; // 'info' or 'success' or 'warning'

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $db->sanitize($_POST['email']);
    
    // Check if email exists
    $sql = "SELECT user_id, full_name FROM users WHERE email = ? AND is_active = 1";
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $update_sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?";
        $update_stmt = $db->conn->prepare($update_sql);
        $update_stmt->bind_param("sss", $token, $expiry, $user['user_id']);
        
        if ($update_stmt->execute()) {
            // In a real application, you would send an email here
            $message = "✓ Reset instructions sent. Please check your email (demo: token generated).";
            $alert_type = 'success';
        } else {
            $message = "Something went wrong. Please try again.";
            $alert_type = 'danger';
        }
        $update_stmt->close();
    } else {
        // Security: show same message even if email doesn't exist
        $message = "If an account exists with this email, you'll receive reset instructions.";
        $alert_type = 'info';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset password · Asset Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f2f5fa;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #1a2639;
        }

        /* soft card – exactly matching login/signup style */
        .reset-card {
            background: #ffffff;
            border-radius: 2.5rem;
            padding: 2.8rem 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 460px;
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(2px);
            transition: all 0.2s ease;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 1.8rem;
        }

        .icon-circle {
            width: 72px;
            height: 72px;
            background: linear-gradient(145deg, #1e2b5e, #2b3b7a);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 12px 18px -8px rgba(30,43,94,0.3);
        }

        .icon-circle i {
            font-size: 2.5rem;
            color: white;
        }

        h2 {
            font-weight: 650;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
            color: #121826;
            font-size: 1.9rem;
        }

        .greeting {
            color: #5b687c;
            font-size: 0.95rem;
            max-width: 280px;
            margin-left: auto;
            margin-right: auto;
        }

        /* form elements – clean */
        .form-label {
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #4b5a73;
            margin-bottom: 0.3rem;
        }

        .form-control {
            border: 1.5px solid #e2e8f0;
            border-radius: 20px;
            padding: 0.8rem 1.3rem;
            font-size: 0.95rem;
            background-color: #fcfdff;
            transition: border 0.15s, box-shadow 0.15s;
        }

        .form-control:focus {
            border-color: #2b3b7a;
            box-shadow: 0 0 0 4px rgba(43,59,122,0.1);
            outline: none;
            background-color: #ffffff;
        }

        /* primary button */
        .btn-reset {
            background: #1e2b5e;
            border: none;
            border-radius: 40px;
            padding: 0.85rem 1.2rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: background 0.15s, transform 0.1s;
            box-shadow: 0 10px 18px -8px #1e2b5e80;
            letter-spacing: 0.01em;
            margin-top: 0.5rem;
        }

        .btn-reset:hover {
            background: #25377a;
            transform: scale(1.01);
            color: white;
        }

        .btn-reset:disabled {
            opacity: 0.5;
            pointer-events: none;
            background: #7c8aa5;
            box-shadow: none;
        }

        /* alert styles – subtle, rounded */
        .alert-custom {
            border-radius: 60px;
            border: none;
            padding: 0.9rem 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-custom i {
            font-size: 1.2rem;
        }
        .alert-info-custom {
            background: #e7edfb;
            color: #1e3d7a;
        }
        .alert-success-custom {
            background: #e1f7ed;
            color: #0d683e;
        }
        .alert-danger-custom {
            background: #fef1f0;
            color: #ab2e2e;
        }

        /* footer links */
        .footer-links {
            margin-top: 2rem;
            text-align: center;
        }
        .footer-links a {
            color: #1e2b5e;
            font-weight: 500;
            text-decoration: none;
            border-bottom: 1px dotted #b5c0d0;
        }
        .footer-links a:hover {
            border-bottom: 2px solid #1e2b5e;
        }
        .back-link {
            font-size: 0.95rem;
            color: #4a5b79;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        hr {
            opacity: 0.2;
            margin: 1.8rem 0 0.8rem 0;
        }
        .hint {
            font-size: 0.8rem;
            color: #6f7d98;
            text-align: center;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>

<div class="reset-card">
    <!-- header with icon -->
    <div class="logo-area">
        <div class="icon-circle">
            <i class="bi bi-key"></i>
        </div>
        <h2>Forgot password?</h2>
        <p class="greeting">No worries, we'll send you reset instructions.</p>
    </div>

    <!-- dynamic message (clean alert) -->
    <?php if ($message): 
        $alert_class = 'alert-info-custom';
        if ($alert_type === 'success') $alert_class = 'alert-success-custom';
        if ($alert_type === 'danger') $alert_class = 'alert-danger-custom';
    ?>
        <div class="alert-custom <?php echo $alert_class; ?> mb-4" role="alert">
            <i class="bi <?php echo $alert_type === 'success' ? 'bi-check-circle-fill' : ($alert_type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill'); ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close" style="font-size:0.8rem;"></button>
        </div>
    <?php endif; ?>

    <!-- form -->
    <form method="POST" action="" id="resetForm">
        <div class="mb-4">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" 
                   placeholder="you@company.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required autofocus>
            <div class="hint">We'll send a reset link to this address</div>
        </div>

        <button type="submit" class="btn-reset" id="submitBtn">
            <i class="bi bi-envelope-paper me-2"></i>Send reset link
        </button>

        <!-- back to login -->
        <div class="footer-links">
            <a href="login.php" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to login
            </a>
        </div>
        <hr>
        <div class="text-center small text-secondary">
            Asset Management System · secure reset
        </div>
    </form>
</div>

<!-- Bootstrap JS for alert close -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- tiny UX: disable submit if email empty, enable if valid format (basic) -->
<script>
    (function() {
        const emailInput = document.getElementById('email');
        const submitBtn = document.getElementById('submitBtn');

        function validateEmail(email) {
            return email.includes('@') && email.includes('.') && email.length > 5;
        }

        function toggleButton() {
            const email = emailInput.value.trim();
            if (validateEmail(email)) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // initial check (in case pre-filled)
        setTimeout(toggleButton, 50);

        emailInput.addEventListener('input', toggleButton);
    })();
</script>

</body>
</html>