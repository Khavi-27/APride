<?php
session_start();
include 'db.php';
$cust_id = $_SESSION['customer_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];
    // Update wallet balance in database (saves to wallet column)
    $sql = "UPDATE customer SET wallet = wallet + $amount WHERE customer_id = '$cust_id'";
    if ($conn->query($sql)) {
        header("Location: customer.php?msg=Top-up Successful");
    } else {
        echo "<script>alert('Error updating wallet. Please make sure the wallet column exists in the customer table.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Top-up Wallet | APRide</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">Top-up Wallet</div>
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
        <div class="glass-card" style="max-width:450px; margin:0 auto;">
            <h2 style="margin-top:0; text-align:center;">Top-up Wallet</h2>
            <p class="ap-muted" style="text-align:center; margin-bottom:22px;">Add funds to your wallet for seamless ride payments.</p>
            <form method="POST">
                <label class="ap-label">Reload Amount (RM)</label>
                <input class="ap-input" type="number" name="amount" placeholder="e.g. 50" required>
                <label class="ap-label">Card Number</label>
                <input class="ap-input" type="text" placeholder="XXXX XXXX XXXX XXXX">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div>
                        <label class="ap-label">MM/YY</label>
                        <input class="ap-input" type="text" placeholder="MM/YY">
                    </div>
                    <div>
                        <label class="ap-label">CVV</label>
                        <input class="ap-input" type="text" placeholder="CVV">
                    </div>
                </div>
                <button type="submit" class="ap-button-primary" style="margin-top:10px;">Confirm Reload</button>
            </form>
        </div>
    </div>
</body>
</html>