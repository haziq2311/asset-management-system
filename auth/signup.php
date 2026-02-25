<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up · Asset Management</title>
    <!-- Bootstrap 5 + icons (keep consistency) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- modern clean fonts & style -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f2f5fa;  /* soft neutral background */
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #1a2639;
        }

        /* soft card – clean, rounded, elevated subtly */
        .signup-card {
            background: #ffffff;
            border-radius: 2.5rem;
            padding: 2.8rem 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 700px;          /* slightly wider, better for two columns */
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(2px);
            transition: all 0.2s ease;
        }

        /* header area */
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
            margin: 0 auto 1.2rem;
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
        }
        .greeting {
            color: #5b687c;
            font-size: 0.95rem;
        }

        /* form labels & inputs – clean, airy */
        .form-label {
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #4b5a73;
            margin-bottom: 0.3rem;
        }
        .form-control, .form-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 18px;
            padding: 0.7rem 1.2rem;
            font-size: 0.95rem;
            background-color: #fcfdff;
            transition: border 0.15s, box-shadow 0.15s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2b3b7a;
            box-shadow: 0 0 0 4px rgba(43,59,122,0.1);
            outline: none;
            background-color: #ffffff;
        }

        /* floating validation / hint styling */
        .password-strength {
            font-size: 0.8rem;
            margin-top: 0.3rem;
            min-height: 1.4rem;
            color: #5b687c;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .password-match-icon {
            font-size: 1.2rem;
            line-height: 1;
            transition: opacity 0.1s;
        }
        .match-success {
            color: #0f7b5a;
        }
        .match-error {
            color: #c23b3b;
        }
        .hint-text {
            font-size: 0.75rem;
            color: #6a7890;
            margin-top: 0.1rem;
        }
        /* custom button */
        .btn-signup {
            background: #1e2b5e;
            border: none;
            border-radius: 40px;
            padding: 0.9rem 1.2rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: background 0.15s, transform 0.1s;
            box-shadow: 0 10px 18px -8px #1e2b5e80;
            letter-spacing: 0.01em;
        }
        .btn-signup:hover {
            background: #25377a;
            transform: scale(1.01);
        }
        .btn-signup:disabled {
            opacity: 0.5;
            pointer-events: none;
            background: #7c8aa5;
            box-shadow: none;
        }

        /* links */
        .footer-links a {
            color: #1e2b5e;
            font-weight: 500;
            text-decoration: none;
            border-bottom: 1px dotted #b5c0d0;
        }
        .footer-links a:hover {
            color: #2b3b7a;
            border-bottom: 2px solid #1e2b5e;
        }
        .back-home {
            font-size: 0.9rem;
            color: #4a5b79;
        }
        .back-home i {
            font-size: 1rem;
        }

        /* row spacing */
        .row.gap-2 > [class*="col-"] {
            margin-bottom: 0.5rem;
        }

        /* subtle alert design */
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

        /* department id field optional tag */
        .optional-badge {
            font-size: 0.7rem;
            background: #eaeef5;
            color: #44566c;
            border-radius: 40px;
            padding: 0.15rem 0.7rem;
            margin-left: 0.75rem;
            font-weight: 400;
        }

        hr {
            opacity: 0.3;
            margin: 1.5rem 0 0.8rem 0;
        }
    </style>
</head>
<body>

<div class="signup-card">
    <!-- logo & greeting -->
    <div class="logo-area">
        <div class="logo-icon">
            <i class="bi bi-box-seam"></i>
        </div>
        <h2>Create an account</h2>
        <p class="greeting">Join the enterprise asset platform</p>
    </div>

    <!-- dynamic messages (cleaner alerts) -->
    <?php if (isset($error) && $error): ?>
        <div class="alert alert-custom d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close" style="font-size:0.8rem;"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($success) && $success): ?>
        <div class="alert alert-success-custom d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- ===== FORM ===== (method POST, action stays same) -->
    <form method="POST" action="" id="cleanSignupForm">
        <!-- row: full name + staff id (username) -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="full_name" class="form-label">Full name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" 
                       placeholder="e.g. Aisyah binti Ahmad" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
            </div>
            <div class="col-md-6">
                <label for="username" class="form-label">Staff ID</label>
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="e.g. aisyah2025" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                <div class="hint-text">unique identifier</div>
            </div>
        </div>

        <!-- email (full width) -->
        <div class="mb-4">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" 
                   placeholder="you@company.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
        </div>

        <!-- password + confirm (row) with realtime validation -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="········" required>
                <!-- strength & length hints live here -->
                <div class="password-strength" id="passwordLengthHint">
                    <i class="bi bi-info-circle"></i> <span>min. 8 characters</span>
                </div>
                <div class="password-strength" id="passwordStrengthMsg"></div>
            </div>
            <div class="col-md-6">
                <label for="confirm_password" class="form-label">Confirm password</label>
                <div class="position-relative">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="········" required>
                    <span id="confirmIcon" class="password-match-icon position-absolute end-0 top-50 translate-middle-y me-3" style="display: none;"></span>
                </div>
                <div class="password-strength" id="confirmMsg"></div>
            </div>
        </div>

        <!-- role + department (row) -->
        <div class="row g-3 mb-4">
            <div class="col-md-7">
                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                <select class="form-select" id="role" name="role" required>
                    <option value="" selected disabled>– select role –</option>
                    <option value="admin">Admin</option>
                    <option value="accountant">Accountant</option>
                    <option value="logistic_coordinator">Logistic Coordinator</option>
                    <option value="operation_manager">Operation Manager</option>
                    <option value="operation_team">Operation Team</option>
                    <option value="it_operation">IT Operation</option>
                </select>
            </div>
            <div class="col-md-5">
                <label for="department_id" class="form-label">
                    Department <span class="optional-badge">optional</span>
                </label>
                <input type="text" class="form-control" id="department_id" name="department_id" 
                       placeholder="e.g. DPT-210" value="<?php echo isset($_POST['department_id']) ? htmlspecialchars($_POST['department_id']) : ''; ?>">
            </div>
        </div>

        <!-- submit button -->
        <button type="submit" class="btn btn-signup" id="signupSubmitBtn">
            <i class="bi bi-person-plus me-2"></i>Create account
        </button>

        <!-- footer links: sign in + back home -->
        <div class="d-flex flex-wrap align-items-center justify-content-between mt-4 footer-links">
            <span class="back-home">
                <a href="../index.php" class="text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i> Home
                </a>
            </span>
            <span>
                <a href="login.php" class="text-decoration-none">Sign in <i class="bi bi-box-arrow-in-right ms-1"></i></a>
            </span>
        </div>
        <hr>
        <p class="text-center text-muted small mb-0">© 2026 Data Jasa Plus – enterprise ready</p>
    </form>
</div>

<!-- Bootstrap JS (for alert close etc) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Real‑time validation: clean, user-friendly feedback -->
<script>
    (function() {
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        const confirmIcon = document.getElementById('confirmIcon');
        const confirmMsg = document.getElementById('confirmMsg');
        const passwordStrengthMsg = document.getElementById('passwordStrengthMsg');
        const passwordLengthHint = document.getElementById('passwordLengthHint');
        const submitBtn = document.getElementById('signupSubmitBtn');

        // optional: prefill role if validation fails? we keep it as is.

        function updatePasswordStrength() {
            const val = password.value;
            if (val.length === 0) {
                passwordStrengthMsg.innerHTML = '';
                passwordLengthHint.innerHTML = '<i class="bi bi-info-circle"></i> <span>min. 8 characters</span>';
                return;
            }

            let strength = 0;
            if (val.length >= 8) strength++;                     // length ok
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) strength++;  // mixed case
            if (/\d/.test(val)) strength++;                      // has number
            if (/[^A-Za-z0-9]/.test(val)) strength++;            // special char

            let msg = '', color = '#4b5a73';
            if (strength <= 1) { msg = '✗ too weak'; color = '#c23b3b'; }
            else if (strength === 2) { msg = '⚡ weak'; color = '#c07c1b'; }
            else if (strength === 3) { msg = '● good'; color = '#2c7d9c'; }
            else if (strength >= 4) { msg = '★ strong'; color = '#0f7b5a'; }

            passwordStrengthMsg.innerHTML = `<span style="color:${color}; font-weight:500;">${msg}</span>`;
            passwordLengthHint.innerHTML = ''; // hide the static hint
        }

        function matchPasswords() {
            const pass = password.value;
            const conf = confirm.value;

            if (conf === '') {
                confirmIcon.style.display = 'none';
                confirmMsg.innerHTML = '';
                return;
            }

            if (pass === conf) {
                confirmIcon.style.display = 'inline';
                confirmIcon.innerHTML = '<i class="bi bi-check-circle-fill match-success"></i>';
                confirmMsg.innerHTML = '<span class="match-success">✓ passwords match</span>';
            } else {
                confirmIcon.style.display = 'inline';
                confirmIcon.innerHTML = '<i class="bi bi-exclamation-circle-fill match-error"></i>';
                confirmMsg.innerHTML = '<span class="match-error">✗ passwords do not match</span>';
            }
        }

        function toggleSubmit() {
            const pass = password.value;
            const conf = confirm.value;
            const roleSelected = document.getElementById('role').value !== '';

            // enable only if passwords match, both length >=8, and required fields (basic check)
            if (pass === conf && pass.length >= 8 && roleSelected) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // events
        password.addEventListener('input', () => {
            updatePasswordStrength();
            matchPasswords();
            toggleSubmit();
        });
        confirm.addEventListener('input', () => {
            matchPasswords();
            toggleSubmit();
        });
        document.getElementById('role').addEventListener('change', toggleSubmit);

        // also check on page load (in case browser prefills)
        window.addEventListener('load', function() {
            setTimeout(() => {
                updatePasswordStrength();
                matchPasswords();
                toggleSubmit();
            }, 80);
        });

        // manually add bootstrap dismiss to dynamic alerts (they already have data-bs-dismiss)
    })();
</script>

<!-- optionally pre-select role if form posted -->
<?php if (isset($_POST['role']) && !empty($_POST['role'])): ?>
<script>
    document.getElementById('role').value = "<?php echo htmlspecialchars($_POST['role'], ENT_QUOTES); ?>";
    // retrigger validation after setting value
    setTimeout(() => {
        if (typeof toggleSubmit === 'function') toggleSubmit();
    }, 100);
</script>
<?php endif; ?>

</body>
</html>