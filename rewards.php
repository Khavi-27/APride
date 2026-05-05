<?php
session_start();
include 'db.php';
$cust_id = $_SESSION['customer_id'];

// Handle Redemption
if (isset($_POST['redeem_id'])) {
    $rid = $_POST['redeem_id'];
    $cost = $_POST['cost'];
    
    // Check balance
    $check = $conn->query("SELECT points_balance FROM customer WHERE customer_id='$cust_id'")->fetch_assoc();
    if ($check['points_balance'] >= $cost) {
        // Record redemption
        $conn->query("INSERT INTO redemption (customer_id, reward_id, date_redeemed) VALUES ('$cust_id', '$rid', NOW())");
        // Deduct points
        $conn->query("UPDATE customer SET points_balance = points_balance - $cost WHERE customer_id='$cust_id'");
        echo "<script>alert('Redemption Successful!'); window.location='rewards.php';</script>";
    } else {
        echo "<script>alert('Not enough points!');</script>";
    }
}

// Get Data
$user = $conn->query("SELECT * FROM customer WHERE customer_id='$cust_id'")->fetch_assoc();
$rewards = $conn->query("SELECT * FROM reward");
$history = $conn->query("SELECT * FROM points WHERE customer_id='$cust_id' ORDER BY points_id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rewards | APRide</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">Rewards Center</div>
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
            <h1 style="margin:0; font-size:3.5rem; color:#22c55e;"><?php echo $user['points_balance']; ?> pts</h1>
            <p class="ap-muted" style="margin-top:8px;">Your current reward points</p>
            <div style="display:inline-block; margin-top:12px; padding:6px 14px; border-radius:999px; background:rgba(34, 197, 94, 0.2); font-size:13px; color:#4ade80;">
                Earn points by completing rides and redeem them for rewards
            </div>
        </div>

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:22px;">
            <div>
                <div class="glass-card" style="margin-bottom:14px;">
                    <div style="display:flex; justify-content:space-between; align-items:baseline;">
                        <h3 style="margin:0;">Available Rewards</h3>
                        <span class="ap-muted" style="font-size:13px;">Tap Redeem to convert your points</span>
                    </div>
                </div>
                <?php while($r = $rewards->fetch_assoc()): ?>
                    <div class="glass-card" style="margin-bottom:14px; display:flex; justify-content:space-between; align-items:center; gap:14px;">
                        <div style="flex:1;">
                            <div style="font-weight:600; color:#f9fafb; margin-bottom:4px;"><?php echo $r['product_title']; ?></div>
                            <div class="ap-muted" style="font-size:13px; margin-bottom:4px;"><?php echo $r['description']; ?></div>
                            <div style="font-size:12px; color:#94a3b8; margin-top:6px;">
                                You have <?php echo $user['points_balance']; ?> / <?php echo $r['points_required']; ?> pts
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="display:inline-block; padding:4px 12px; border-radius:999px; background:rgba(34, 197, 94, 0.2); color:#4ade80; font-size:12px; margin-bottom:8px;">
                                <?php echo $r['points_required']; ?> pts
                            </div>
                            <form method="POST" style="margin-top:8px;">
                                <input type="hidden" name="redeem_id" value="<?php echo $r['reward_id']; ?>">
                                <input type="hidden" name="cost" value="<?php echo $r['points_required']; ?>">
                                <button
                                    type="submit"
                                    class="ap-button-primary"
                                    style="padding:8px 18px; font-size:13px; white-space:nowrap;"
                                    <?php if($user['points_balance'] < $r['points_required']) echo 'disabled style="opacity:0.5; cursor:not-allowed;"'; ?>
                                >
                                    <?php echo ($user['points_balance'] >= $r['points_required']) ? 'Redeem now' : 'Not enough points'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
                <p class="ap-muted" style="font-size:13px; margin-top:14px;">Tip: each completed ride gives you extra points that you can redeem here.</p>
            </div>

            <div>
                <div class="glass-card">
                    <h3 style="margin-top:0;">Recent Points Activity</h3>
                    <?php if($history->num_rows > 0): ?>
                        <?php while($h = $history->fetch_assoc()): ?>
                            <div style="border-bottom:1px solid rgba(148, 163, 184, 0.2); padding:10px 0; display:flex; justify-content:space-between;">
                                <span class="ap-muted" style="font-size:13px;">Ride #<?php echo $h['ride_id']; ?></span>
                                <span style="color:#22c55e; font-weight:600; font-size:13px;">+ <?php echo $h['points_earned']; ?> pts</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="ap-muted" style="font-size:13px; margin:0;">No points activity yet. Complete a ride to start earning!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>