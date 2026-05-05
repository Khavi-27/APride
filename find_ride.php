<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$sql = "SELECT r.* FROM ride r
        JOIN driver d ON r.driver_id = d.driver_id
        WHERE r.available_seats > 0
          AND d.status = 'Approved'
        ORDER BY r.date_time DESC";
$search_results = $conn->query($sql);

if (isset($_POST['search'])) {
    $origin = $_POST['origin']; // kept for future use
    $destination = $_POST['destination'];
    
    // Find rides matching destination text with seats
    $sql = "SELECT r.* FROM ride r
            JOIN driver d ON r.driver_id = d.driver_id
            WHERE r.destination LIKE '%$destination%' 
              AND r.available_seats > 0
              AND d.status = 'Approved'
            ORDER BY r.date_time DESC";
    $search_results = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Find Ride | APRide</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">Find a Ride</div>
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
        <div class="glass-card">
            <h2 style="margin-top:0;">Where to?</h2>
            <p class="ap-muted">Search for available rides to your destination.</p>
            <form method="POST" style="margin-top:20px;">
                <label class="ap-label">Origin</label>
                <input class="ap-input" type="text" name="origin" placeholder="e.g. Taman Paramount" required>
                
                <label class="ap-label">Destination</label>
                <input class="ap-input" type="text" name="destination" placeholder="Any destination (e.g. APU, KLCC, Sunway)" />

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div>
                        <label class="ap-label">Date</label>
                        <input class="ap-input" type="date" name="date" required>
                    </div>
                    <div>
                        <label class="ap-label">Pax</label>
                        <input class="ap-input" type="number" name="pax" min="1" max="4" value="1">
                    </div>
                </div>
                <button type="submit" name="search" class="ap-button-primary" style="margin-top:10px;">Find Drivers</button>
            </form>
        </div>

        <?php if ($search_results && $search_results->num_rows > 0): ?>
            <div class="glass-card" style="margin-top:20px;">
                <h3 style="margin-top:0;">Available Drivers</h3>
                <?php while($row = $search_results->fetch_assoc()): ?>
                    <div style="background:rgba(15, 23, 42, 0.6); border:1px solid rgba(148, 163, 184, 0.3); border-radius:16px; padding:18px; margin-bottom:14px; transition:transform 0.15s;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                            <div>
                                <div style="font-weight:600; color:#f9fafb; margin-bottom:6px;">Ride ID: <?php echo $row['ride_id']; ?></div>
                                <div style="color:#94a3b8; font-size:13px; margin-bottom:4px;">
                                    Destination: <?php echo htmlspecialchars($row['destination']); ?><br>
                                    Time: <?php echo date('d M Y, h:i A', strtotime($row['date_time'])); ?>
                                </div>
                                <div style="color:#22c55e; font-weight:bold; font-size:16px; margin-top:6px;">RM <?php echo number_format($row['price'], 2); ?></div>
                            </div>
                        </div>
                        <div style="text-align:center;">
                            <a href="ride_details.php?ride_id=<?php echo $row['ride_id']; ?>" class="ap-button-primary" style="text-decoration:none; padding:10px 20px; white-space:nowrap; display:inline-block;">Select</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php elseif(isset($_POST['search'])): ?>
            <div class="glass-card" style="margin-top:20px; text-align:center;">
                <p style="color:#f97373; margin:0;">No drivers found for this route.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>