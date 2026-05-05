<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - APRide</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="AP Ride Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">Admin Control Center</div>
                </div>
            </div>
            <div class="ap-nav-links">
                <a href="admin.php" class="ap-nav-primary">Dashboard</a>
                <a href="co2_report.php">CO₂ Report</a>
                <a href="ride_history.php">Ride History</a>
                <a href="transaction_report.php">Bookings & Payments</a>
                <a href="customer_list.php">Customers</a>
                <a href="view_drivers.php">Drivers</a>
                <a href="admin_approval.php">Requests</a>
                <a href="logout.php" style="color:#f97373;">Logout</a>
            </div>
        </div>
    </header>

    <main class="shell">
        <div class="glass-card" style="text-align:left;">
            <h1 style="margin:0 0 6px 0;">Welcome, Administrator</h1>
            <p class="ap-muted">Select a report or management task to view.</p>

            <div class="ap-grid" style="margin-top:22px;">
                <div class="ap-tile" onclick="window.location='co2_report.php'">
                    <div class="ap-tile-title">📊 CO₂ Report</div>
                    <div class="ap-tile-sub">View emissions and ride impact.</div>
                </div>
                <div class="ap-tile" onclick="window.location='ride_history.php'">
                    <div class="ap-tile-title">🚗 Ride History</div>
                    <div class="ap-tile-sub">Monitor recent trips and usage.</div>
                </div>
                <div class="ap-tile" onclick="window.location='transaction_report.php'">
                    <div class="ap-tile-title">💰 Bookings & Payments</div>
                    <div class="ap-tile-sub">Review revenue and transactions.</div>
                </div>
                <div class="ap-tile" onclick="window.location='customer_list.php'">
                    <div class="ap-tile-title">👥 Customers</div>
                    <div class="ap-tile-sub">Manage registered passengers.</div>
                </div>
                <div class="ap-tile" onclick="window.location='view_drivers.php'">
                    <div class="ap-tile-title">🏎️ Drivers</div>
                    <div class="ap-tile-sub">View approved drivers.</div>
                </div>
                <div class="ap-tile" onclick="window.location='admin_approval.php'">
                    <div class="ap-tile-title">📩 Requests</div>
                    <div class="ap-tile-sub">Approve or reject new drivers.</div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>