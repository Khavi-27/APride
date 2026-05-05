<?php
session_start();
include 'db.php';

// Admin authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
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

if (!$isAdmin) {
    header("Location: login.php");
    exit();
}

// Calculate CO2 saved from all rides with bookings
// CO2 saved = distance * 0.2 kg per km (standard calculation)
$sql = "SELECT 
            r.ride_id,
            r.destination,
            r.distance_km,
            r.date_time,
            r.price,
            COUNT(b.booking_id) as passenger_count,
            GROUP_CONCAT(u.tp_number SEPARATOR ', ') AS passengers,
            (r.distance_km * 0.2 * COUNT(b.booking_id)) as co2_saved
        FROM ride r
        LEFT JOIN booking b ON r.ride_id = b.ride_id AND b.status IN ('Completed', 'Confirmed', 'Accepted')
        LEFT JOIN customer c ON b.customer_id = c.customer_id
        LEFT JOIN user u ON c.user_id = u.user_id
        GROUP BY r.ride_id
        HAVING passenger_count > 0
        ORDER BY r.date_time DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CO₂ Report | APRide</title>
    <link rel="stylesheet" href="app.css">
    <style>
        .passenger-list { font-style: italic; color: #94a3b8; font-size: 0.9em; }
    </style>
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">CO₂ Report</div>
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

    <div class="shell">
        <div class="glass-card">
            <h2 style="margin-top:0;">CO₂ Impact Report</h2>
            <p class="ap-muted" style="margin-bottom:20px;">View CO₂ saved across all rides with passengers. CO₂ saved = distance × 0.2 kg/km × passenger count.</p>

            <?php
            // Calculate total CO2 saved
            $total_co2 = 0;
            $total_rides = 0;
            $rows_data = [];
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $total_co2 += $row['co2_saved'];
                    $total_rides++;
                    $rows_data[] = $row;
                }
            }
            ?>

            <?php if ($total_rides > 0): ?>
            <div style="background:rgba(34, 197, 94, 0.15); border:1px solid rgba(34, 197, 94, 0.3); padding:20px; border-radius:16px; margin-bottom:24px; text-align:center;">
                <div style="font-size:2.5rem; font-weight:700; color:#22c55e; margin-bottom:8px;"><?php echo number_format($total_co2, 2); ?> kg</div>
                <div class="ap-muted">Total CO₂ saved across <?php echo $total_rides; ?> ride<?php echo $total_rides > 1 ? 's' : ''; ?></div>
            </div>
            <?php endif; ?>

            <table class="ap-table">
                <thead>
                    <tr>
                        <th>Ride ID</th>
                        <th>Destination</th>
                        <th>Date & Time</th>
                        <th>Distance</th>
                        <th>Passengers</th>
                        <th>CO₂ Saved</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($rows_data)) {
                        foreach($rows_data as $row) {
                            $passengers = !empty($row["passengers"]) ? $row["passengers"] : "N/A";
                            
                            echo "<tr>
                                    <td>#RIDE-" . htmlspecialchars($row["ride_id"]) . "</td>
                                    <td>" . htmlspecialchars($row["destination"]) . "</td>
                                    <td>" . date('d M Y, h:i A', strtotime($row["date_time"])) . "</td>
                                    <td>" . number_format($row["distance_km"], 1) . " km</td>
                                    <td class='passenger-list'>" . htmlspecialchars($passengers) . " (" . $row["passenger_count"] . ")</td>
                                    <td><strong style='color:#22c55e;'>" . number_format($row["co2_saved"], 2) . " kg</strong></td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding:30px; color:#9ca3af;'>No rides with passengers found. CO₂ is saved when passengers book rides.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>