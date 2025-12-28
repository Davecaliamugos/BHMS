<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BHMS | Modern Boarding House Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --accent-color: #06b6d4;
            --dark-bg: #0f172a;
            --text-main: #1e293b;
            --glass-bg: rgba(255, 255, 255, 0.8);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            overflow-x: hidden;
            background-color: #ffffff;
        }

        /* Unique Navbar */
        .navbar {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.4s ease;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.6rem;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Hero Section Enhancements */
        .hero-section {
            padding: 160px 0 100px;
            background: radial-gradient(circle at 90% 10%, rgba(79, 70, 229, 0.05) 0%, rgba(255, 255, 255, 0) 50%),
                        radial-gradient(circle at 10% 90%, rgba(6, 182, 212, 0.05) 0%, rgba(255, 255, 255, 0) 50%);
        }

        .hero-title {
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -2px;
            color: var(--dark-bg);
        }

        .hero-img-container {
            position: relative;
            z-index: 1;
        }

        .hero-img-container::before {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 100%;
            height: 100%;
            border: 2px solid var(--primary-color);
            border-radius: 40px;
            z-index: -1;
            opacity: 0.2;
        }

        /* Feature Card Overhaul */
        .feature-card {
            border: 1px solid #f1f5f9;
            border-radius: 30px;
            padding: 45px;
            background: #fff;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 30px 60px -12px rgba(50, 50, 93, 0.1), 0 18px 36px -18px rgba(0, 0, 0, 0.1);
            border-color: transparent;
        }

        .feature-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .feature-card:hover::after {
            transform: scaleX(1);
        }

        .icon-box {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
            color: var(--primary-color);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
            transition: all 0.3s ease;
        }

        .feature-card:hover .icon-box {
            background: var(--primary-color);
            color: #fff;
            transform: rotate(-10deg);
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), #6366f1);
            border: none;
            padding: 14px 35px;
            border-radius: 14px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

        .btn-outline-custom {
            border: 2px solid #e2e8f0;
            padding: 14px 35px;
            border-radius: 14px;
            font-weight: 600;
            color: var(--text-main);
            transition: all 0.3s;
        }

        .btn-outline-custom:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        /* Section Stats */
        .stats-bar {
            background: var(--dark-bg);
            border-radius: 40px;
            padding: 50px;
            margin-top: -80px;
            z-index: 10;
            position: relative;
            color: white;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }

        footer {
            background: #f8fafc;
            color: var(--text-main);
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg py-3 sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-layer-group me-2"></i>BHMS
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link mx-3 fw-semibold text-dark" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link mx-3 fw-semibold text-dark" href="#features">Features</a></li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-primary" href="auth/login.php">Portal Access</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <span class="px-3 py-1 bg-primary text-white rounded-pill small fw-bold">New v2.0</span>
                        <span class="text-muted small fw-semibold">Next-Gen Property Management</span>
                    </div>
                    <h1 class="display-3 hero-title mb-4">Your Property,<br><span class="text-primary">Simplified.</span></h1>
                    <p class="lead text-muted mb-5 fs-5">Elevate your boarding house management. From tenant lifecycle to automated financial reporting, everything is now in one elegant dashboard.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="auth/login.php" class="btn btn-primary btn-lg px-5 shadow-lg">Start Free Trial</a>
                        <a href="#features" class="btn btn-outline-custom btn-lg px-5">Watch Demo</a>
                    </div>
                </div>
                <div class="col-lg-6 mt-5 mt-lg-0" data-aos="zoom-in" data-aos-delay="200">
                    <div class="hero-img-container">
                        <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=1000" alt="Platform" class="img-fluid rounded-5 shadow-2xl">
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container mb-5">
        <div class="stats-bar" data-aos="fade-up">
            <div class="row text-center g-4">
                <div class="col-md-4">
                    <h2 class="fw-800 mb-1">99.9%</h2>
                    <p class="text-white-50 mb-0">Uptime Reliability</p>
                </div>
                <div class="col-md-4">
                    <h2 class="fw-800 mb-1">10k+</h2>
                    <p class="text-white-50 mb-0">Units Managed</p>
                </div>
                <div class="col-md-4">
                    <h2 class="fw-800 mb-1">24/7</h2>
                    <p class="text-white-50 mb-0">Expert Support</p>
                </div>
            </div>
        </div>
    </div>

    <section id="features" class="py-5 my-5">
        <div class="container">
            <div class="text-center mb-5 pb-4" data-aos="fade-up">
                <h2 class="fw-800 display-5">Engineered for Excellence</h2>
                <p class="text-muted fs-5">Advanced tools to give you total control over your business.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card h-100">
                        <div class="icon-box">
                            <i class="fas fa-fingerprint"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Secure Tenant Vault</h4>
                        <p class="text-muted leading-relaxed">Encrypted digital storage for contracts, IDs, and tenant behavior history. Always accessible, always secure.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card h-100">
                        <div class="icon-box">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Smart Billing</h4>
                        <p class="text-muted leading-relaxed">AI-driven invoice generation. Automatically send payment reminders and calculate utility splits with one click.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card h-100">
                        <div class="icon-box">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Live Insights</h4>
                        <p class="text-muted leading-relaxed">Visualize your ROI, occupancy rates, and revenue trends through beautiful, real-time analytics charts.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-5 mt-5">
        <div class="container text-center">
            <div class="mb-4">
                <a class="navbar-brand mb-3 d-block" href="#">BHMS</a>
                <div class="d-flex justify-content-center gap-4 text-muted">
                    <a href="#" class="text-decoration-none text-muted">Terms</a>
                    <a href="#" class="text-decoration-none text-muted">Privacy</a>
                    <a href="#" class="text-decoration-none text-muted">Support</a>
                </div>
            </div>
            <p class="mb-0 opacity-50">&copy; 2025 PropCore BHMS. Powering modern living spaces.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true,
            easing: 'ease-out'
        });
    </script>
</body>
</html>