<?php
session_start();
include 'db.php';

// SQL Query using your existing ERD tables:
// Booking -> Customer -> User (to get tp_number)
// Booking -> Ride (to get destination and distance)
$sql = "SELECT 
            b.booking_id, 
            u.tp_number AS student_name, 
            r.destination, 
            r.distance_km,
            r.ride_id
        FROM Booking b
        JOIN Customer c ON b.customer_id = c.customer_id
        JOIN User u ON c.user_id = u.user_id
        JOIN Ride r ON b.ride_id = r.ride_id";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Transactions | APRide</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">Bookings & Payments</div>
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
            <h2 style="margin-top:0;">Ride Booking Transaction Report</h2>
            <p class="ap-muted" style="margin-bottom:20px;">Review revenue and transactions across all bookings.</p>

            <table class="ap-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Student (TP Number)</th>
                        <th>Ride Destination</th>
                        <th>Distance (km)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>#BKN-" . htmlspecialchars($row["booking_id"]) . "</td>
                                    <td>" . htmlspecialchars($row["student_name"]) . "</td>
                                    <td>" . htmlspecialchars($row["destination"]) . "</td>
                                    <td>" . htmlspecialchars($row["distance_km"]) . " km</td>
                                    <td><span class='ap-pill ap-pill-green'>Confirmed</span></td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#9ca3af;'>No bookings found in the database.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>