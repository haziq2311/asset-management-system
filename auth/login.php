<?php
// login.php – clean, friendly, and modern redesign
require_once '../includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $db->sanitize($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $sql = "SELECT * FROM users WHERE username = :username AND is_active = 1";
        $stmt = $db->pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password_hash'])) {
                // Update last login
                $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
                $update_stmt = $db->pdo->prepare($update_sql);
                $update_stmt->bindParam(':user_id', $user['user_id'], PDO::PARAM_STR);
                $update_stmt->execute();
                
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['department_id'] = $user['department_id'];
                
                // role-based redirect
                switch ($user['role']) {
                    case 'admin': $redirect = '../dashboard/admin/dashboard.php'; break;
                    case 'accountant': $redirect = '../dashboard/accountant/dashboard.php'; break;
                    case 'logistic_coordinator': $redirect = '../dashboard/logistic_operation/dashboard.php'; break;
                    case 'operation_manager': $redirect = '../dashboard/operation_manager/dashboard.php'; break;
                    case 'operation_team': $redirect = '../dashboard/operation_team/dashboard.php'; break;
                    case 'it_operation': $redirect = '../dashboard/information_system/dashboard.php'; break;
                    default: $error = "Invalid user role"; break;
                }
                if (!empty($redirect)) {
                    header("Location: $redirect");
                    exit();
                }
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome back · Asset Management</title>
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

        /* soft, modern card (matching signup style) */
        .login-card {
            background: #ffffff;
            border-radius: 2.5rem;
            padding: 2.8rem 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 440px;
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(2px);
            transition: all 0.2s ease;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 68px;
            height: 68px;
            background: linear-gradient(145deg, #1e2b5e, #2b3b7a);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 12px 18px -8px rgba(30,43,94,0.3);
        }

        .logo-icon i {
            font-size: 2.4rem;
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
        }

        /* form elements – consistent, airy */
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

        /* custom button – deep navy, friendly */
        .btn-login {
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
        }

        .btn-login:hover {
            background: #25377a;
            transform: scale(1.01);
            color: white;
        }

        /* form options row */
        .form-check-input {
            border: 1.5px solid #cbd5e0;
            border-radius: 5px;
        }
        .form-check-input:checked {
            background-color: #1e2b5e;
            border-color: #1e2b5e;
        }
        .form-check-label {
            color: #3f4e6b;
            font-size: 0.9rem;
        }
        .forgot-link {
            color: #1e2b5e;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            border-bottom: 1px dotted #b5c0d0;
        }
        .forgot-link:hover {
            border-bottom: 2px solid #1e2b5e;
        }

        /* alert style – subtle */
        .alert-custom {
            border-radius: 60px;
            border: none;
            background: #fef1f0;
            color: #ab2e2e;
            padding: 0.8rem 1.5rem;
            font-size: 0.9rem;
        }
        .alert-success-custom {
            background: #e1f7ed;
            color: #0d683e;
            border-radius: 60px;
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
        .back-home {
            font-size: 0.9rem;
            color: #4a5b79;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        hr {
            opacity: 0.2;
            margin: 1.5rem 0 0.8rem 0;
        }
        .copyright {
            font-size: 0.75rem;
            color: #8a99b2;
            margin-top: 0.8rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <!-- logo & friendly heading -->
    <div class="logo-area">
        <div class="logo-icon">
            <i class="bi bi-box-seam"></i>
        </div>
        <h2>Welcome back</h2>
        <p class="greeting">Sign in to your account</p>
    </div>

    <!-- dynamic messages (clean & soft) -->
    <?php if ($error): ?>
        <div class="alert alert-custom d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close" style="font-size:0.8rem;"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success-custom d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- login form (method POST) -->
    <form method="POST" action="" id="loginForm">
        <div class="mb-4">
            <label for="username" class="form-label">Staff ID / username</label>
            <input type="text" class="form-control" id="username" name="username" 
                   placeholder="e.g. aisyah2025" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autofocus>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" 
                   placeholder="········" required>
        </div>

        <!-- row: remember + forgot -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Keep me signed in</label>
            </div>
            <a href="forget-password.php" class="forgot-link">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-login" id="loginSubmitBtn">
            <i class="bi bi-box-arrow-in-right me-2"></i> Sign in
        </button>

        <!-- extra links: sign up & home -->
        <div class="footer-links">
            <p class="mb-2">Don't have an account? <a href="signup.php" class="text-decoration-none ms-1">Create one</a></p>
            <a href="../index.php" class="back-home">
                <i class="bi bi-arrow-left"></i> Back to home
            </a>
        </div>
        <hr>
        <div class="copyright text-center">
            Asset Management System · v1.0
        </div>
    </form>
</div>

<!-- Bootstrap JS for alerts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- optional very simple validation: disable submit if fields empty (just UX) -->
<script>
    (function() {
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const submitBtn = document.getElementById('loginSubmitBtn');

        function toggleBtn() {
            if (username.value.trim() !== '' && password.value.trim() !== '') {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // initial check (in case pre-filled)
        setTimeout(toggleBtn, 50);

        username.addEventListener('input', toggleBtn);
        password.addEventListener('input', toggleBtn);
    })();
</script>

</body>
</html>