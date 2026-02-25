<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS · clean asset management</title>
    <!-- Bootstrap 5 + icons (keeping the same links, just refined) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- subtle style extension for cleaner spacing, softer shadows, consistent rounding -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background: #fafbfc;
            color: #1e293b;
        }
        /* clean, airy hero */
        .hero-section {
            background: linear-gradient(135deg, #1e2b5e 0%, #2b3b7a 100%);
            color: white;
            padding: 6rem 1.5rem;
            margin-bottom: 2rem;
            border-bottom-left-radius: 2.5rem;
            border-bottom-right-radius: 2.5rem;
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }
        /* floating login button — refined */
        .login-btn {
            position: absolute;
            top: 1.5rem;
            right: 2rem;
            z-index: 10;
            padding: 0.6rem 1.5rem;
            border-radius: 40px;
            font-weight: 500;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            backdrop-filter: blur(4px);
            transition: all 0.2s;
        }
        .login-btn:hover {
            background: white;
            color: #1e2b5e;
            border-color: white;
        }
        /* modern card style */
        .feature-card {
            border: none;
            background: white;
            border-radius: 28px;
            padding: 1.2rem 0.8rem;
            transition: all 0.25s ease;
            box-shadow: 0 8px 18px rgba(0,0,0,0.02), 0 1px 3px rgba(0,0,0,0.03);
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 30px -8px rgba(30, 43, 94, 0.12), 0 8px 16px -6px rgba(0,0,0,0.02);
            background: #ffffff;
        }
        /* icon circle – clean gradient, consistent size */
        .icon-flat-circle {
            width: 72px;
            height: 72px;
            border-radius: 24px;   /* soft square-round — more modern than full circle */
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
            transition: border-radius 0.2s, transform 0.2s;
        }
        .feature-card:hover .icon-flat-circle {
            border-radius: 20px;    
            transform: scale(0.96);
        }
        /* custom background tints (soft) */
        .bg-soft-primary { background: #2545b8; }
        .bg-soft-success { background: #1f8b6c; }
        .bg-soft-warning { background: #c07c1b; }
        .bg-soft-info { background: #2c7d9c; }
        .bg-soft-danger { background: #bc3f4e; }
        .bg-soft-secondary { background: #5f6c84; }

        h2 {
            font-weight: 600;
            letter-spacing: -0.02em;
        }
        .card-title {
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 0.75rem;
            color: #0f1829;
        }
        .text-muted {
            color: #5c6a81 !important;
            font-size: 0.95rem;
        }
        footer {
            background: #121826;
            color: #d3dcec;
            border-top: 1px solid #242f40;
        }
        .footer-small {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .btn-light-custom {
            background: white;
            border: none;
            color: #1e2b5e;
            border-radius: 40px;
            padding: 0.8rem 2.2rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        .btn-light-custom:hover {
            background: #f0f3ff;
            transform: scale(1.02);
            box-shadow: 0 8px 18px rgba(0,0,0,0.15);
        }
        /* spacing & clean micro-details */
        .feature-grid {
            margin-top: 2rem;
            margin-bottom: 3rem;
        }
        hr {
            width: 5rem;
            margin: 1.8rem auto;
            border: 1px solid rgba(0,0,0,0.05);
        }
        a {
            text-decoration: none;
        }

        /* --- footer enhancements for functionality --- */
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .footer-links li {
            margin-bottom: 0.5rem;
        }
        .footer-links a {
            color: #b0c0d0;
            text-decoration: none;
            transition: color 0.2s, padding-left 0.2s;
            display: inline-block;
        }
        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }
        .social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 40px;
            background: rgba(255,255,255,0.06);
            color: #cbd5e1;
            transition: all 0.2s;
            margin-right: 0.5rem;
        }
        .social-icon:hover {
            background: #2b3b7a;
            color: white;
            transform: translateY(-3px);
        }
        .newsletter-input {
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 60px;
            padding: 0.6rem 1.2rem;
            color: white;
            width: 100%;
        }
        .newsletter-input:focus {
            outline: none;
            border-color: #4f6eb3;
            box-shadow: 0 0 0 3px rgba(79,110,179,0.3);
        }
        .newsletter-btn {
            background: #2b3b7a;
            border: none;
            border-radius: 40px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            color: white;
            transition: background 0.15s;
            white-space: nowrap;
        }
        .newsletter-btn:hover {
            background: #3b4e99;
        }
        .footer-bottom {
            border-top: 1px solid #253040;
            padding-top: 1.5rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>

    <!-- cleaner login button (blend with hero, more subtle) -->
    <a href="auth/login.php" class="btn login-btn">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign in
    </a>

    <!-- Hero - cleaner, slightly softer -->
    <section class="hero-section text-center position-relative">
        <div class="container" style="max-width: 800px;">
            <!-- subtle icon -->
            <div class="d-inline-flex bg-white bg-opacity-10 p-3 rounded-4 mb-4 backdrop-blur">
                <i class="bi bi-grid-3x3-gap-fill fs-1 text-white"></i>
            </div>
            <h1 class="display-5 fw-semibold mb-3">Asset Management System</h1>
            <p class="lead fs-5 fw-light mb-4 opacity-90">Track, manage, and optimise your enterprise assets —<br>one clean dashboard for every role.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="auth/signup.php" class="btn btn-light-custom px-5 py-3">
                    Get started <i class="bi bi-arrow-right ms-2"></i>
                </a>
                <a href="#features" class="btn btn-outline-light border-2 rounded-pill px-4 py-3">
                    Explore features
                </a>
            </div>
        </div>
    </section>

    <!-- features section (clean + consistent) -->
    <section id="features" class="py-5">
        <div class="container">
            <!-- subtle heading -->
            <div class="text-center mb-5">
                <span class="badge bg-light text-dark px-4 py-2 rounded-pill mb-3">everything you need</span>
                <h2 class="display-6 fw-semibold">One system, four perspectives</h2>
                <p class="text-secondary col-lg-6 mx-auto">Designed for admins, finance, maintenance and regular users — all with crystal-clear access.</p>
            </div>

            <div class="row g-4 feature-grid">
                <!-- Asset Tracking -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card p-4">
                        <div class="icon-flat-circle bg-soft-primary">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <h4 class="card-title">Asset tracking</h4>
                        <p class="text-muted">Unique IDs, barcode integration, serial number registry — always know where everything lives.</p>
                    </div>
                </div>
                <!-- Role‑based access -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card p-4">
                        <div class="icon-flat-circle bg-soft-success">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h4 class="card-title">Role‑based access</h4>
                        <p class="text-muted">Four distinct dashboards (admin, manager, finance, employee) with fine-grained permissions.</p>
                    </div>
                </div>
                <!-- Financial Management -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card p-4">
                        <div class="icon-flat-circle bg-soft-warning">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <h4 class="card-title">Financial management</h4>
                        <p class="text-muted">Purchase cost, depreciation curves, expense tracking — stay on top of asset value.</p>
                    </div>
                </div>
                <!-- Check In/Out -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card p-4">
                        <div class="icon-flat-circle bg-soft-info">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                        <h4 class="card-title">Check in / out</h4>
                        <p class="text-muted">Seamless movement logging with timestamps and audit trails — never lose history.</p>
                    </div>
                </div>
                <!-- Maintenance Tracking -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card p-4">
                        <div class="icon-flat-circle bg-soft-danger">
                            <i class="bi bi-wrench-adjustable"></i>
                        </div>
                        <h4 class="card-title">Maintenance tracking</h4>
                        <p class="text-muted">Schedule repairs, set status updates, log interventions — keep assets healthy.</p>
                    </div>
                </div>
                <!-- Comprehensive Reporting -->
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card p-4">
                        <div class="icon-flat-circle bg-soft-secondary">
                            <i class="bi bi-bar-chart-fill"></i>
                        </div>
                        <h4 class="card-title">Comprehensive reporting</h4>
                        <p class="text-muted">Ready‑to‑use reports for audits, budget reviews, and strategic decisions.</p>
                    </div>
                </div>
            </div>

            <!-- subtle extra confidence line -->
            <div class="text-center mt-5 pt-3">
                <p class="text-muted mb-0">
                    <i class="bi bi-database-check me-2"></i> Built following enterprise data dictionary standards
                </p>
            </div>
        </div>
    </section>

    <!-- quick separator / stat panel (clean & friendly) -->
    <div class="container">
        <div class="bg-white p-5 rounded-5 shadow-sm mx-2 mb-5 border">
            <div class="row align-items-center g-4">
                <div class="col-md-8">
                    <h3 class="fw-semibold mb-3">Start managing assets in minutes</h3>
                    <p class="text-secondary fs-5">No complex setup — just log in and see your inventory. From IT equipment to office furniture.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="auth/signup.php" class="btn btn-primary rounded-pill py-3 px-5" style="background:#1e2b5e; border: none;">
                        Create free account <i class="bi bi-arrow-right-short ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== FOOTER (enhanced functionality) ========== -->
    <footer class="py-5 mt-4">
        <div class="container">
            <!-- Main footer row with navigation, contact, social, newsletter -->
            <div class="row gy-5">
                <!-- Brand column -->
                <div class="col-lg-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-box-seam fs-1 me-3 text-primary" style="color:#4f6eb3 !important;"></i>
                        <h4 class="fw-light text-white mb-0">AMS</h4>
                    </div>
                    <p class="text-secondary mb-4" style="max-width: 260px;">Comprehensive asset lifecycle management for forward-thinking enterprises.</p>
                    <div class="d-flex">
                        <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-github"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-envelope-fill"></i></a>
                    </div>
                </div>

                <!-- Quick links -->
                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Product</h6>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="auth/signup.php">Sign up</a></li>
                        <li><a href="auth/login.php">Login</a></li>
                        <li><a href="#">Pricing</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Resources</h6>
                    <ul class="footer-links">
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">API status</a></li>
                        <li><a href="#">Support</a></li>
                        <li><a href="#">Compliance</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Company</h6>
                    <ul class="footer-links">
                        <li><a href="#">About us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Legal</a></li>
                    </ul>
                </div>

                <!-- Newsletter / stay in touch -->
                <div class="col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Stay updated</h6>
                    <form action="#" method="post" class="newsletter-form">
                        <div class="d-flex flex-column gap-2">
                            <input type="email" class="newsletter-input" placeholder="Your email" required>
                            <button type="submit" class="newsletter-btn">
                                <i class="bi bi-send me-2"></i>Subscribe
                            </button>
                        </div>
                        <p class="text-secondary small mt-2">No spam, just updates.</p>
                    </form>
                </div>
            </div>

            <!-- Footer bottom with copyright, terms and dynamic links -->
            <div class="footer-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="footer-small text-secondary mb-0">
                            &copy; 2026 Data Jasa Plus Sdn Bhd. All rights reserved.
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="#" class="text-secondary text-decoration-none me-3 footer-small">Privacy</a>
                        <a href="#" class="text-secondary text-decoration-none me-3 footer-small">Terms</a>
                        <a href="#" class="text-secondary text-decoration-none me-3 footer-small">Cookies</a>
                        <a href="#" class="text-secondary text-decoration-none footer-small">Sitemap</a>
                    </div>
                </div>
                <!-- additional certifications / badges (functional trust signals) -->
                <div class="row mt-3">
                    <div class="col-12">
                        <hr class="bg-white bg-opacity-10 my-3">
                        <div class="d-flex flex-wrap gap-4 justify-content-between align-items-center">
                            <span class="text-muted footer-small"><i class="bi bi-shield-lock-check me-1"></i> ISO 27001 certified</span>
                            <span class="text-muted footer-small"><i class="bi bi bi-cloud-check me-1"></i> 99.9% uptime SLA</span>
                            <span class="text-muted footer-small"><i class="bi bi-geo-alt me-1"></i> Kuala Lumpur · Singapore</span>
                            <span class="text-muted footer-small"><i class="bi bi-database me-1"></i> Data dictionary compliant</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap bundle (same) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- smooth scroll for "Explore features" (just a tiny UX touch) -->
    <script>
        (function() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        })();
    </script>
    <!-- optional newsletter dummy prevent default (just UX) -->
    <script>
        document.querySelector('.newsletter-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('📬 Thank you! (Demo mode — newsletter subscription simulated)');
            this.reset();
        });
    </script>
</body>
</html>