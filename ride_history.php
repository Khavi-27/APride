<?php
include('db.php'); 
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$uid = (int)$_SESSION['user_id'];

// Check if admin
$isAdmin = false;
$checkAdmin = $conn->query("SELECT admin_id FROM admin WHERE user_id = '$uid'");
if ($checkAdmin && $checkAdmin->num_rows > 0) {
    $isAdmin = true;
}
if (!$isAdmin) {
    $checkRole = $conn->query("SELECT role FROM user WHERE user_id = '$uid' AND role = 'Admin'");
    if ($checkRole && $checkRole->num_rows > 0) {
        $isAdmin = true;
    }
}

// If admin, show all rides. Otherwise, check if driver
if (!$isAdmin) {
    $driverRes = $conn->query("SELECT driver_id, status FROM driver WHERE user_id = $uid");
    $driverRow = $driverRes ? $driverRes->fetch_assoc() : null;
    if (!$driverRow) { die("No driver profile found."); }
    if (strcasecmp($driverRow['status'], 'Approved') !== 0) { die("Driver not approved."); }
    $driver_id = (int)$driverRow['driver_id'];
}

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 1. FETCH ALL RIDES FOR DISPLAY (both past and future)
if ($isAdmin) {
    // Admin view: Show all rides
    $history_sql = "SELECT r.*, 
                           COUNT(CASE WHEN b.status IN ('Accepted', 'Confirmed', 'Completed') THEN b.booking_id END) as pax_count,
                           u.tp_number as driver_tp
                    FROM ride r
                    LEFT JOIN booking b ON r.ride_id = b.ride_id
                    LEFT JOIN driver d ON r.driver_id = d.driver_id
                    LEFT JOIN user u ON d.user_id = u.user_id
                    GROUP BY r.ride_id
                    ORDER BY r.date_time DESC";
    $history_res = $conn->query($history_sql);
} else {
    // Driver view: Show only their rides
    $history_sql = "SELECT r.*, 
                           COUNT(CASE WHEN b.status IN ('Accepted', 'Confirmed', 'Completed') THEN b.booking_id END) as pax_count
                    FROM ride r
                    LEFT JOIN booking b ON r.ride_id = b.ride_id
                    WHERE r.driver_id = ?
                    GROUP BY r.ride_id
                    ORDER BY r.date_time DESC";
    $stmt = $conn->prepare($history_sql);
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $history_res = $stmt->get_result();
}

$history_rows = [];

if ($history_res) {
    while ($row = $history_res->fetch_assoc()) {
        $pax = $row['pax_count'];
        $dist = $row['distance_km'];
        
        // Determine ride status
        $ride_datetime = strtotime($row['date_time']);
        $current_time = time();
        $is_completed = ($ride_datetime < $current_time);
        
        if ($is_completed) {
            $row['ride_status'] = 'Completed';
        } else {
            $row['ride_status'] = 'Upcoming';
        }
        
        // Calculate points and CO2 for display (if has passengers, regardless of completion status)
        if ($pax > 0) {
            $points = 10 + ($pax * 10);
            $co2 = $dist * 0.12 * $pax;
            $row['calculated_points'] = $points;
            $row['calculated_co2'] = $co2;
        } else {
            $row['calculated_points'] = 0;
            $row['calculated_co2'] = 0;
        }
        
        $history_rows[] = $row;
    }
}

// 2. CALCULATE STATS from history_rows - Only for drivers, not admins
$total_points = 0;
$total_co2 = 0;
$trees = 0;
$progress = 0;

if (!$isAdmin) {
    foreach ($history_rows as $ride) {
        // Count all rides with passengers (both completed and upcoming)
        if ($ride['pax_count'] > 0 && isset($ride['calculated_points']) && isset($ride['calculated_co2'])) {
            $total_points += $ride['calculated_points'];
            $total_co2 += $ride['calculated_co2'];
        }
    }
    
    // 1 tree per 6kg CO2 saved
    $trees = ($total_co2 > 0) ? $total_co2 / 6 : 0; 
    $progress = ($total_points % 1000) / 10;
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Eco Performance</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>

<header class="ap-nav">
    <div class="ap-nav-inner">
        <div class="ap-nav-left">
            <img src="logo.png" alt="Logo" class="ap-logo">
            <div>
                <div class="ap-brand">APRide</div>
                <div class="ap-muted"><?php echo $isAdmin ? 'Ride History' : 'Eco Performance'; ?></div>
            </div>
        </div>
        <div class="ap-nav-links">
            <?php if ($isAdmin): ?>
                <a href="admin.php" class="ap-nav-primary">Dashboard</a>
                <a href="co2_report.php">CO₂ Report</a>
                <a href="ride_history.php">Ride History</a>
                <a href="transaction_report.php">Bookings & Payments</a>
                <a href="customer_list.php">Customers</a>
                <a href="view_drivers.php">Drivers</a>
                <a href="admin_approval.php">Requests</a>
            <?php else: ?>
                <a href="driver_dashboard.php">Dashboard</a>
                <a href="offer_ride.php">Offer Ride</a>
                <a href="my_rides.php">My Rides</a>
                <a href="ride_history.php" class="ap-nav-primary">Ride History</a>
                <a href="earnings.php">Earnings</a>
                <a href="profile.php">Profile</a>
            <?php endif; ?>
            <a href="logout.php" style="color:#f97373;">Logout</a>
        </div>
    </div>
</header>

<div class="shell">
    <div class="glass-card">
        <?php if ($isAdmin): ?>
            <h1 style="margin-top:0;">🚗 Ride History</h1>
            <p class="ap-muted" style="margin-bottom:24px;">Monitor recent trips and usage across all drivers.</p>
        <?php else: ?>
            <h1 style="margin-top:0;">🌱 Eco Performance</h1>

            <div class="stat-container" style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
                <div class="card" style="flex:1;min-width:220px;background:transparent;border:1px solid rgba(148,163,184,0.4);border-radius:16px;box-shadow:none;">
                    <span class="ap-label" style="text-transform:uppercase;font-size:11px;">Total Eco Points</span>
                    <h2 style="margin:6px 0 0 0;color:#22c55e;"><?php echo number_format($total_points); ?></h2>
                </div>
                <div class="card" style="flex:1;min-width:220px;background:transparent;border:1px solid rgba(148,163,184,0.4);border-radius:16px;box-shadow:none;">
                    <span class="ap-label" style="text-transform:uppercase;font-size:11px;">Carbon Reduction</span>
                    <h2 style="margin:6px 0 0 0;color:#38bdf8;"><?php echo number_format($total_co2, 1); ?> kg CO₂</h2>
                </div>
                <div class="card" style="flex:1;min-width:220px;background:transparent;border:1px solid rgba(148,163,184,0.4);border-radius:16px;box-shadow:none;">
                    <span class="ap-label" style="text-transform:uppercase;font-size:11px;">Environmental Impact</span>
                    <h2 style="margin:6px 0 0 0;color:#fbbf24;"><?php echo number_format($trees, 1); ?> Trees</h2>
                </div>
            </div>

            <div class="progress-section" style="background:rgba(15,23,42,0.85);border:1px solid rgba(148,163,184,0.35);padding:18px;border-radius:16px;margin-bottom:28px;">
                <strong style="color:#e5e7eb;">ECO LEVEL PROGRESS</strong><br>
                <span class="ap-muted">Current Level: </span><span style="font-weight:bold;color:#22c55e;">Eco-Warrior</span><br>
                <span class="ap-muted">Next Level: Eco-Legend</span>
                
                <div class="progress-bar" style="border:1px solid rgba(148,163,184,0.4);background:rgba(15,23,42,0.7);height:20px;margin:10px 0;border-radius:10px;overflow:hidden;">
                    <div class="progress-fill" style="background:linear-gradient(90deg,#22c55e,#0ea5e9);height:100%;width: <?php echo $progress; ?>%;"></div>
                    <div class="progress-empty" style="flex-grow:1;background:transparent;"></div>
                </div> 
                <span style="font-weight:bold;color:#22c55e;"><?php echo round($progress); ?>%</span>
                <p class="ap-muted" style="margin-top:6px;">Just <?php echo (1000 - ($total_points % 1000)); ?> more points to reach 🌿 Eco-Legend status!</p>
            </div>

            <h2 style="margin:0 0 10px 0;">📋 Ride History</h2>
        <?php endif; ?>

        <table class="ap-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <?php if ($isAdmin): ?><th>Driver</th><?php endif; ?>
                    <th>Route</th>
                    <th>Passengers</th>
                    <?php if (!$isAdmin): ?><th>Points</th><?php endif; ?>
                    <th>CO₂ Saved</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($history_rows)): ?>
                    <?php foreach($history_rows as $ride): ?>
                    <tr>
                        <td><?php echo date('d M Y, h:i A', strtotime($ride['date_time'])); ?></td>
                        <?php if ($isAdmin): ?>
                            <td><?php echo htmlspecialchars($ride['driver_tp'] ?? 'N/A'); ?></td>
                        <?php endif; ?>
                        <td><?php echo htmlspecialchars($ride['destination']); ?></td>
                        <td class="pax-count" style="color:#fbbf24;font-weight:700;"><?php echo $ride['pax_count']; ?> pax</td>
                        <?php if (!$isAdmin): ?>
                            <td class="points-add" style="color:#22c55e;font-weight:700;">+<?php echo $ride['calculated_points']; ?></td>
                        <?php endif; ?>
                        <td><?php echo number_format($ride['calculated_co2'], 1); ?> kg</td>
                        <td>
                            <?php
                                $cls = $ride['ride_status'] == 'Completed' ? 'ap-pill-green' : 'ap-pill-amber';
                                echo "<span class='ap-pill $cls'>".htmlspecialchars($ride['ride_status'])."</span>";
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?php echo $isAdmin ? '7' : '6'; ?>" style="text-align:center; padding: 30px; color: #9ca3af;">No rides found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>