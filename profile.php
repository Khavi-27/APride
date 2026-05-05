<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$uid = (int)$_SESSION['user_id'];

$driverRes = $conn->query("SELECT d.driver_id, d.vehicle_type, d.license_plate, u.name, u.tp_number, u.e_mail, d.status
                            FROM driver d
                            JOIN user u ON d.user_id = u.user_id
                            WHERE d.user_id = $uid");
$driver = $driverRes ? $driverRes->fetch_assoc() : null;
if (!$driver) { die("No driver profile found."); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Profile | APRide</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">Driver Profile</div>
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
        <div class="glass-card" style="max-width:600px; margin:0 auto;">
            <h1 style="margin-top:0;">Driver Profile</h1>
            <div style="display:grid; gap:14px; margin-top:24px;">
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(148, 163, 184, 0.3);">
                    <span class="ap-label" style="margin:0; width:160px;">Name</span>
                    <span style="color:#f9fafb; font-weight:600;"><?php echo htmlspecialchars($driver['name']); ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(148, 163, 184, 0.3);">
                    <span class="ap-label" style="margin:0; width:160px;">TP Number</span>
                    <span style="color:#f9fafb; font-weight:600;"><?php echo htmlspecialchars($driver['tp_number']); ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(148, 163, 184, 0.3);">
                    <span class="ap-label" style="margin:0; width:160px;">Email</span>
                    <span style="color:#f9fafb; font-weight:600;"><?php echo htmlspecialchars($driver['e_mail']); ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(148, 163, 184, 0.3);">
                    <span class="ap-label" style="margin:0; width:160px;">Vehicle Type</span>
                    <span style="color:#f9fafb; font-weight:600;"><?php echo htmlspecialchars($driver['vehicle_type']); ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(148, 163, 184, 0.3);">
                    <span class="ap-label" style="margin:0; width:160px;">License Plate</span>
                    <span style="color:#f9fafb; font-weight:600;"><?php echo htmlspecialchars($driver['license_plate']); ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0; align-items:center;">
                    <span class="ap-label" style="margin:0; width:160px;">Status</span>
                    <?php
                    $status = $driver['status'] ?? 'Pending';
                    $badgeColor = '#fbbf24';
                    if (strcasecmp($status, 'Approved') === 0) $badgeColor = '#22c55e';
                    if (strcasecmp($status, 'Rejected') === 0) $badgeColor = '#f97373';
                    ?>
                    <span style="display:inline-block; padding:6px 12px; border-radius:999px; background:rgba(<?php echo $badgeColor === '#22c55e' ? '34, 197, 94' : ($badgeColor === '#f97373' ? '249, 115, 115' : '251, 191, 36'); ?>, 0.2); color:<?php echo $badgeColor; ?>; font-size:12px; font-weight:600;">
                        <?php echo htmlspecialchars($status); ?>
                    </span>
                </div>
            </div>
            <a href="driver_dashboard.php" class="ap-button-primary" style="text-decoration:none; display:block; text-align:center; margin-top:26px;">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
