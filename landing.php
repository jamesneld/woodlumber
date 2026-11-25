<?php
/**
 * Wood Lumber System - Landing Page
 * Features: Live time & date, centered Wood Lumber System title, Get Started button, and dynamic current year footer
 */
date_default_timezone_set('Asia/Manila');
$currentDate = date('l, F d, Y');
$currentTime = date('h:i:s A');
$currentYear = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wood Lumber System - Welcome</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="landing-body">

    <!-- Top Bar with Date & Live Time -->
    <header class="landing-topbar">
        <div class="brand-badge">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
            </svg>
            <span>Wood Lumber System</span>
        </div>

        <div class="landing-clock-widget">
            <div class="clock-item">
                <!-- Calendar Icon -->
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span class="live-date-display"><?php echo $currentDate; ?></span>
            </div>

            <div class="clock-divider"></div>

            <div class="clock-item">
                <!-- Clock Icon -->
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span class="live-time-display"><?php echo $currentTime; ?></span>
            </div>
        </div>
    </header>

    <!-- Center Middle Hero Section -->
    <main class="landing-main">
        <div class="landing-hero-card">
            <div class="hero-pill">
                <span>🌲 Sawmill &amp; Timber Management</span>
            </div>

            <h1 class="landing-title">Wood Lumber System</h1>

            <p class="landing-subtitle">
                A comprehensive management system for lumber inventory, timber scaling, standardized wood dimensions, customer purchases, and credit tracking.
            </p>

            <div class="hero-btn-container">
                <a href="dashboard.php" class="btn-get-started">
                    <span>Get Started</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        </div>
    </main>

    <!-- Landing Footer with Current Year -->
    <footer class="landing-footer">
        <p>&copy; <?php echo $currentYear; ?> Wood Lumber System. All Rights Reserved.</p>
    </footer>

    <script src="assets/js/app.js"></script>
</body>
</html>
