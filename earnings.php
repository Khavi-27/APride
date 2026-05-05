<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$uid = (int)$_SESSION['user_id'];

$driverRes = $conn->query("SELECT driver_id, status FROM driver WHERE user_id = $uid");
$driverRow = $driverRes ? $driverRes->fetch_assoc() : null;
if (!$driverRow) { die("No driver profile found."); }
if (strcasecmp($driverRow['status'], 'Approved') !== 0) { die("Driver not approved."); }
$driver_id = (int)$driverRow['driver_id'];

// Sum of accepted/completed bookings times ride price
$sql = "SELECT SUM(r.price) AS earnings, COUNT(b.booking_id) AS trips
        FROM booking b
        JOIN ride r ON b.ride_id = r.ride_id
        WHERE r.driver_id = ? AND b.status IN ('Accepted','Completed')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$earnings = $data['earnings'] ?? 0;
$trips = $data['trips'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Earnings | APRide</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">Earnings</div>
                </div>
            </div>
            <div class="ap-nav-links">
                <a href="driver_dashboard.php" class="ap-nav-primary">Dashboard</a>
                <a href="offer_ride.php">Offer Ride</a>
                <a href="my_rides.php">My Rides</a>
                <a href="earnings.php">Earnings</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php" style="color:#f97373;">Logout</a>
            </div>
        </div>
    </header>

    <div class="shell">
        <div class="glass-card" style="max-width:600px; margin:0 auto; text-align:center;">
            <h1 style="margin-top:0;">Driver Earnings</h1>
            <div style="font-size:3rem; font-weight:700; color:#22c55e; margin:20px 0;">RM <?php echo number_format($earnings, 2); ?></div>
            <p class="ap-muted" style="margin-bottom:26px;"><?php echo (int)$trips; ?> paid/approved trips</p>
            <a href="driver_dashboard.php" class="ap-button-primary" style="text-decoration:none; display:inline-block;">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
