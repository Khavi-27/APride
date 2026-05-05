<?php
session_start();
include 'db.php';

// Security Check
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$uid = $_SESSION['user_id'];

// Fetch Customer and Wallet Data based on Data Dictionary 
$sql = "SELECT u.TP_Number, c.wallet as wallet_balance, c.customer_id 
        FROM user u JOIN customer c ON u.user_id = c.user_id 
        WHERE u.user_id = '$uid'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    $_SESSION['customer_id'] = $user_data['customer_id'];
} else {
    echo "Error: Profile not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | AP Ride</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">Passenger Dashboard</div>
                </div>
            </div>
            <div class="ap-nav-links">
                <a href="customer.php" class="ap-nav-primary">Home</a>
                <a href="find_ride.php">Find Ride</a>
                <a href="topup.php">Top‑up Wallet</a>
                <a href="co2_stats.php">CO₂ Stats</a>
                <a href="rewards.php">Rewards</a>
                <a href="logout.php" style="color:#f97373;">Logout</a>
            </div>
        </div>
    </header>

    <div class="shell">
        <?php if (isset($_GET['msg'])): ?>
            <div style="background:#d4edda; color:#155724; padding:10px 15px; border-radius:8px; margin-bottom:20px; border:1px solid #c3e6cb; text-align:center;">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="glass-card">
            <div class="welcome-section" style="text-align:left;">
                <h1 style="margin:0 0 6px 0;">Hi, <?php echo $user_data['TP_Number']; ?></h1>
                <p class="ap-muted">Your smart hub for daily rides and rewards.</p>
            </div>

            <div class="ap-grid">
                <div class="ap-tile" onclick="window.location='find_ride.php'">
                    <div class="ap-tile-title">Find Ride</div>
                    <div class="ap-tile-sub">Search and book eco‑friendly carpools.</div>
                </div>
                <div class="ap-tile" onclick="window.location='topup.php'">
                    <div class="ap-tile-title">Wallet</div>
                    <div class="ap-tile-sub">Balance: <strong>RM <?php echo number_format($user_data['wallet_balance'], 2); ?></strong></div>
                </div>
                <div class="ap-tile" onclick="window.location='co2_stats.php'">
                    <div class="ap-tile-title">CO₂ Impact</div>
                    <div class="ap-tile-sub">Track how much carbon you’ve saved.</div>
                </div>
                <div class="ap-tile" onclick="window.location='rewards.php'">
                    <div class="ap-tile-title">Rewards</div>
                    <div class="ap-tile-sub">Redeem points for exclusive perks.</div>
                </div>
            </div>
        </div>

        <?php
        // Upcoming / recent bookings for this customer, grouped by status
        $custId = (int)$user_data['customer_id'];
        $bookSql = "SELECT b.booking_id, b.status, r.destination, r.date_time, r.price
                    FROM booking b
                    JOIN ride r ON b.ride_id = r.ride_id
                    WHERE b.customer_id = $custId
                    ORDER BY r.date_time DESC";
        $bookRes = $conn->query($bookSql);
        $groups = ['Pending' => [], 'Completed' => [], 'Other' => []];
        if ($bookRes && $bookRes->num_rows > 0) {
            while ($b = $bookRes->fetch_assoc()) {
                $st = $b['status'];
                if ($st === 'Pending') $groups['Pending'][] = $b;
                elseif ($st === 'Completed') $groups['Completed'][] = $b;
                else $groups['Other'][] = $b;
            }
        }

        function renderBookingTable($title, $rows) {
            if (empty($rows)) return;
            echo "<div class='glass-card' style='margin-top:20px;'>";
            echo "<h3 style='margin:0 0 20px 0; color:#f9fafb;'>$title</h3>";
            echo "<table class='ap-table'>";
            echo "<thead>
                    <tr>
                        <th>Destination</th>
                        <th>Date & Time</th>
                        <th>Price</th>
                        <th>Status</th>
                    </tr>
                </thead><tbody>";
            foreach ($rows as $b) {
                $statusBadge = '';
                if ($b['status'] === 'Pending') {
                    $statusBadge = "<span class='ap-pill ap-pill-amber'>Pending</span>";
                } elseif ($b['status'] === 'Completed' || $b['status'] === 'Confirmed') {
                    $statusBadge = "<span class='ap-pill ap-pill-green'>" . ($b['status'] === 'Completed' ? 'Paid' : 'Confirmed') . "</span>";
                } else {
                    $statusBadge = "<span class='ap-pill'>" . htmlspecialchars($b['status']) . "</span>";
                }
                echo "<tr>
                        <td>" . htmlspecialchars($b['destination']) . "</td>
                        <td>" . date('d M Y, h:i A', strtotime($b['date_time'])) . "</td>
                        <td style='color:#22c55e; font-weight:600;'>RM " . number_format($b['price'], 2) . "</td>
                        <td>$statusBadge</td>
                      </tr>";
            }
            echo "</tbody></table>";
            echo "</div>";
        }
        ?>

        <div style="margin-top:40px;">
            <h2 style="margin-bottom:20px; color:#f9fafb;">Your Bookings</h2>
            <?php
                if (!$bookRes || $bookRes->num_rows === 0) {
                    echo "<div class='glass-card'><p class='ap-muted' style='margin:0; text-align:center; padding:20px;'>You have no bookings yet.</p></div>";
                } else {
                    renderBookingTable('Pending Approval', $groups['Pending']);
                    renderBookingTable('Confirmed & Paid', $groups['Completed']);
                    renderBookingTable('Other', $groups['Other']);
                }
            ?>
        </div>
    </div>
</body>
</html>