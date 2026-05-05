<?php
// Start session to clear any old data
session_start();
include 'db.php';

if(isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    
    // Check if admin
    $checkAdmin = $conn->query("SELECT admin_id FROM admin WHERE user_id = '$uid'");
    $checkRole = $conn->query("SELECT role FROM user WHERE user_id = '$uid' AND role = 'Admin'");
    
    if ($checkAdmin->num_rows > 0 || $checkRole->num_rows > 0) {
        header("Location: admin.php");
        exit();
    }
    
    // Check if driver
    $checkDriver = $conn->query("SELECT driver_id FROM driver WHERE user_id = '$uid'");
    if ($checkDriver->num_rows > 0) {
        header("Location: rider.php");
        exit();
    }
    
    // Default to customer dashboard
    header("Location: customer.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>APRide - Login</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <div class="auth-shell">
        <div class="glass-card">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <img src="logo.png" alt="APRide Logo" class="ap-logo">
            <div>
                <div class="ap-brand">APRide</div>
                <div class="ap-muted">Smart campus carpool</div>
            </div>
        </div>
        <h2 class="auth-title">Welcome back</h2>
        <p class="auth-sub">Sign in to continue your eco‑friendly rides.</p>
        <form action="login_process.php" method="POST">
            <label class="ap-label">TP Number</label>
            <input class="ap-input" type="text" name="tp_number" placeholder="TP012345" required>
            <label class="ap-label">Password</label>
            <input class="ap-input" type="password" name="password" placeholder="••••••••" required>
            <button type="submit" class="ap-button-primary">Login</button>
        </form>
        <p style="margin-top:14px;font-size:13px;color:#94a3b8;text-align:center;">
            New here? <a href="signup.php" class="ap-link">Create an account</a>
        </p>
        </div>
    </div>
</body>
</html>