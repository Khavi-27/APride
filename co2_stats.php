<?php
session_start();
include 'db.php';
$cust_id = $_SESSION['customer_id'];

// Calculate Saved CO2
$sql = "SELECT SUM(r.distance_km) as total_km 
        FROM booking b 
        JOIN ride r ON b.ride_id = r.ride_id 
        WHERE b.customer_id = '$cust_id' AND b.status = 'Completed'"; 
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_km = $row['total_km'] ? $row['total_km'] : 0;
$co2_saved = round($total_km * 0.2, 2); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CO₂ Stats | APRide</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">CO₂ Impact</div>
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
        <div class="glass-card" style="text-align:center; margin-bottom:26px;">
            <h2 style="margin-top:0;">Your Green Impact</h2>
            <p class="ap-muted">See how much CO₂ you've helped save by sharing rides</p>
        </div>

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:22px;">
            <div class="glass-card">
                <div style="display:inline-block; padding:4px 12px; border-radius:999px; background:rgba(34, 197, 94, 0.2); color:#4ade80; font-size:11px; font-weight:600; margin-bottom:20px;">
                    LIFETIME STATS
                </div>
                <div style="width:220px; height:220px; background:conic-gradient(#22c55e <?php echo min($co2_saved, 100); ?>%, rgba(148, 163, 184, 0.3) 0); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:15px auto 20px; position:relative;">
                    <div style="position:absolute; width:75%; height:75%; background:radial-gradient(circle at top left, rgba(148, 163, 184, 0.08), rgba(15, 23, 42, 0.96)); border-radius:50%; border:1px solid rgba(148, 163, 184, 0.35);"></div>
                    <div style="text-align:center; position:relative; z-index:1;">
                        <div style="font-size:2.4rem; color:#22c55e; font-weight:bold;"><?php echo $co2_saved; ?></div>
                        <div style="font-size:1rem; color:#94a3b8;">kg CO₂ saved</div>
                    </div>
                </div>
                <ul style="list-style:none; padding:0; margin:15px 0 0;">
                    <li style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px dashed rgba(148, 163, 184, 0.3); font-size:14px;">
                        <span class="ap-muted">Total shared distance</span>
                        <strong style="color:#f9fafb;"><?php echo $total_km; ?> km</strong>
                    </li>
                    <li style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px dashed rgba(148, 163, 184, 0.3); font-size:14px;">
                        <span class="ap-muted">Equivalent trees planted*</span>
                        <strong style="color:#f9fafb;"><?php echo round($co2_saved / 21, 2); ?></strong>
                    </li>
                    <li style="display:flex; justify-content:space-between; padding:10px 0; font-size:14px;">
                        <span class="ap-muted">CO₂ saved per km (average)</span>
                        <strong style="color:#f9fafb;">0.20 kg/km</strong>
                    </li>
                </ul>
                <p class="ap-muted" style="font-size:12px; margin-top:14px;">
                    *Approximation: one mature tree absorbs ~21 kg CO₂ per year.
                </p>
            </div>

            <div class="glass-card">
                <h3 style="margin-top:0;">Keep Going Green</h3>
                <ul style="padding-left:18px; margin:8px 0 0; color:#94a3b8; font-size:13px;">
                    <li style="margin-bottom:8px;">Share rides to campus instead of driving alone.</li>
                    <li style="margin-bottom:8px;">Choose drivers that already match your usual route.</li>
                    <li style="margin-bottom:8px;">Invite friends to carpool and multiply the impact.</li>
                    <li style="margin-bottom:8px;">Check this page regularly to see your progress grow.</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>