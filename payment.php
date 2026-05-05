<?php
session_start();
include 'db.php';

if (!isset($_GET['ride_id'])) { header("Location: find_ride.php"); exit(); }
$ride_id = (int)$_GET['ride_id'];

// Get Ride Details (no dependency on driver table)
$sql = "SELECT * FROM ride WHERE ride_id = $ride_id";
$ride = $conn->query($sql)->fetch_assoc();
if (!$ride) {
    die("Ride not found.");
}

// Get customer's current wallet balance
$cust_id = $_SESSION['customer_id'];
$balance_query = $conn->query("SELECT wallet FROM customer WHERE customer_id = '$cust_id'");
$balance_data = $balance_query->fetch_assoc();
$wallet_balance = $balance_data ? $balance_data['wallet'] : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cust_id = $_SESSION['customer_id'];
    $ride_price = $ride['price'];
    
    // 1. Check customer's wallet balance
    $balance_check = $conn->query("SELECT wallet FROM customer WHERE customer_id = '$cust_id'");
    if (!$balance_check || $balance_check->num_rows === 0) {
        die("Error: Customer not found.");
    }
    $customer = $balance_check->fetch_assoc();
    $current_balance = $customer['wallet'];
    
    // 2. Verify sufficient balance
    if ($current_balance < $ride_price) {
        echo "<script>alert('Insufficient Balance! You have RM " . number_format($current_balance, 2) . " but need RM " . number_format($ride_price, 2) . ". Please Top-up.'); window.location='topup.php';</script>";
        exit();
    }
    
    // 3. Deduct ride price from wallet (save to wallet column in database)
    $new_balance = $current_balance - $ride_price;
    $sql_deduct = "UPDATE customer SET wallet = $new_balance WHERE customer_id = '$cust_id'";
    
    // 4. Create Booking (mark as Completed so CO2 stats count immediately)
    $sql_book = "INSERT INTO booking (ride_id, customer_id, status) VALUES ('$ride_id', '$cust_id', 'Completed')";
    
    // 5. Mark ride as no longer available (hide from Find Ride)
    //    Setting available_seats to 0 ensures it won't appear in find_ride.php
    $sql_update = "UPDATE ride SET available_seats = 0 WHERE ride_id = '$ride_id'";

    // 6. Log reward points and update customer's points balance
    $points_earned = 50; // Points earned per ride
    $sql_point_log = "INSERT INTO points (customer_id, ride_id, points_earned, redeemed) VALUES ('$cust_id', '$ride_id', $points_earned, 0)";
    $sql_update_points = "UPDATE customer SET points_balance = points_balance + $points_earned WHERE customer_id = '$cust_id'";

    // Execute all transactions
    if ($conn->query($sql_deduct) && $conn->query($sql_book) && $conn->query($sql_update) && $conn->query($sql_point_log) && $conn->query($sql_update_points)) {
        echo "<script>alert('Payment Successful! RM " . number_format($ride_price, 2) . " deducted from wallet. Ride Booked. You earned $points_earned points!'); window.location='customer.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment | APRide</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">Payment</div>
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
        <div class="glass-card" style="max-width:500px; margin:0 auto;">
            <h2 style="margin-top:0;">Ride Details</h2>
            <p style="margin-bottom:8px;"><strong style="color:#cbd5f5;">To:</strong> <span style="color:#f9fafb;"><?php echo htmlspecialchars($ride['destination']); ?></span></p>
            <p style="margin-bottom:20px;"><strong style="color:#cbd5f5;">Price:</strong> <span style="color:#22c55e; font-size:1.3em; font-weight:bold;">RM <?php echo number_format($ride['price'], 2); ?></span></p>
            <hr style="margin:20px 0; border:none; border-top:1px solid rgba(148, 163, 184, 0.3);">
            <p style="margin-bottom:8px;"><strong style="color:#cbd5f5;">Your Wallet Balance:</strong> <span style="color:#f9fafb; font-size:1.1em; font-weight:bold;">RM <?php echo number_format($wallet_balance, 2); ?></span></p>
            <?php if ($wallet_balance < $ride['price']): ?>
                <p style="color:#f97373; margin-top:10px; font-weight:600;">⚠ Insufficient Balance! Please top-up your wallet.</p>
            <?php else: ?>
                <p style="color:#22c55e; margin-top:10px; font-weight:600;">✓ Sufficient balance available</p>
            <?php endif; ?>
        </div>

        <div class="glass-card" style="max-width:500px; margin:20px auto 0;">
            <h2 style="margin-top:0;">Pay from Wallet</h2>
            <p class="ap-muted" style="margin-bottom:20px;">
                This ride will be paid directly from your AP Ride wallet balance.
            </p>
            <form method="POST">
                <button type="submit" class="ap-button-primary">
                    Pay RM <?php echo number_format($ride['price'], 2); ?> from Wallet
                </button>
            </form>
        </div>
    </div>
</body>
</html>