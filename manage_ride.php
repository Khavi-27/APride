<?php
include('db.php'); 
session_start();

// Require logged-in driver
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch driver id for this user
$uid = (int)$_SESSION['user_id'];
$driverRes = mysqli_query($conn, "SELECT driver_id, status FROM driver WHERE user_id = $uid");
$driverRow = $driverRes ? mysqli_fetch_assoc($driverRes) : null;
if (!$driverRow) {
    die("No driver profile found.");
}
if (strcasecmp($driverRow['status'], 'Approved') !== 0) {
    die("Your driver account is not approved yet.");
}
$driver_id = (int)$driverRow['driver_id'];

// --- LOGIC: ACCEPT PASSENGER ---
if (isset($_POST['accept_pax'])) {
    $booking_id = (int)$_POST['booking_id'];
    $ride_id = (int)$_POST['ride_id'];

    // Get booking + ride + customer wallet
    $info_sql = "SELECT b.customer_id, r.price, c.wallet 
                 FROM booking b 
                 JOIN ride r ON b.ride_id = r.ride_id 
                 JOIN customer c ON b.customer_id = c.customer_id 
                 WHERE b.booking_id = $booking_id AND b.ride_id = $ride_id";
    $info_res = mysqli_query($conn, $info_sql);
    if (!$info_res || mysqli_num_rows($info_res) === 0) {
        header("Location: manage_ride.php?msg=Error");
        exit();
    }
    $info = mysqli_fetch_assoc($info_res);
    $cust_id = (int)$info['customer_id'];
    $price = (float)$info['price'];
    $wallet = (float)$info['wallet'];

    if ($wallet < $price) {
        header("Location: manage_ride.php?msg=NoBalance");
        exit();
    }

    $new_balance = $wallet - $price;

    // Deduct from customer's wallet
    mysqli_query($conn, "UPDATE customer SET wallet = $new_balance WHERE customer_id = $cust_id");
    // Mark booking as Completed (approved + paid)
    mysqli_query($conn, "UPDATE booking SET status = 'Completed' WHERE booking_id = $booking_id");
    // Reduce available seats
    mysqli_query($conn, "UPDATE ride SET available_seats = available_seats - 1 WHERE ride_id = $ride_id AND available_seats > 0 AND driver_id = $driver_id");
    // Log points and update customer's points balance
    $points_earned = 50;
    mysqli_query($conn, "INSERT INTO points (customer_id, ride_id, points_earned, redeemed) VALUES ($cust_id, $ride_id, $points_earned, 0)");
    mysqli_query($conn, "UPDATE customer SET points_balance = points_balance + $points_earned WHERE customer_id = $cust_id");

    header("Location: manage_ride.php?msg=Accepted");
    exit();
}

// --- LOGIC: DELETE RIDE ---
if (isset($_POST['delete_ride'])) {
    $ride_id = (int)$_POST['ride_id'];
    mysqli_query($conn, "DELETE FROM ride WHERE ride_id = $ride_id AND driver_id = $driver_id");
    header("Location: manage_ride.php?msg=Deleted");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Rides</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
<header class="ap-nav">
    <div class="ap-nav-inner">
        <div class="ap-nav-left">
            <img src="logo.png" alt="Logo" class="ap-logo">
            <div>
                <div class="ap-brand">APRide</div>
                <div class="ap-muted">Manage Rides & Requests</div>
            </div>
        </div>
        <div class="ap-nav-links">
            <a href="driver_dashboard.php">Dashboard</a>
            <a href="offer_ride.php">Offer Ride</a>
            <a href="my_rides.php" class="ap-nav-primary">My Rides</a>
            <a href="earnings.php">Earnings</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" style="color:#f97373;">Logout</a>
        </div>
    </div>
</header>

<div class="shell">
    <div class="glass-card">
        <h1 style="margin-top:0;">Manage Rides</h1>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg-success" style="background:rgba(34,197,94,0.12); border:1px solid rgba(34,197,94,0.35); color:#bbf7d0; padding:12px 14px; border-radius:12px; margin-bottom:16px;">
                <?php 
                    if ($_GET['msg'] == 'Accepted') {
                        echo "✓ Passenger accepted, fare deducted and booking confirmed.";
                    } elseif ($_GET['msg'] == 'NoBalance') {
                        echo "⚠ Passenger does not have enough wallet balance. Request not accepted.";
                    } elseif ($_GET['msg'] == 'Deleted') {
                        echo "✓ Ride removed from system.";
                    } else {
                        echo "⚠ An error occurred. Please try again.";
                    }
                ?>
            </div>
        <?php endif; ?>

        <table class="ap-table">
            <thead>
                <tr>
                    <th>Destination</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Price</th>
                    <th>Distance</th>
                    <th>Seats</th>
                    <th>Requests (TP)</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT * FROM ride WHERE driver_id = $driver_id ORDER BY date_time ASC";
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $rid = $row['ride_id'];
                    // Split DateTime into Date and Time
                    $dt = new DateTime($row['date_time']);
                    $date = $dt->format('d M Y');
                    $time = $dt->format('h:i A');
            ?>
            <tr>
                <td class="col-dest"><?php echo htmlspecialchars($row['destination']); ?></td>
                <td><?php echo $date; ?></td>
                <td><?php echo $time; ?></td>
                <td class="col-price">RM <?php echo number_format($row['price'], 2); ?></td>
                <td class="col-dist"><?php echo $row['distance_km']; ?> km</td>
                <td><span class="badge-seats"><?php echo $row['available_seats']; ?> Left</span></td>
                <td>
                    <?php
                    $pax_sql = "SELECT b.booking_id, u.tp_number 
                                FROM booking b 
                                JOIN customer c ON b.customer_id = c.customer_id 
                                JOIN `user` u ON c.user_id = u.user_id 
                                WHERE b.ride_id = $rid AND b.status = 'Pending'";
                    $pax_res = mysqli_query($conn, $pax_sql);

                    if ($pax_res && mysqli_num_rows($pax_res) > 0) {
                        while ($pax = mysqli_fetch_assoc($pax_res)) {
                            echo "<div class='pax-box'>";
                            echo "<span class='tp-number'>" . htmlspecialchars($pax['tp_number']) . "</span>";
                            echo "<form method='POST' style='margin:0;'>
                                    <input type='hidden' name='booking_id' value='".$pax['booking_id']."'>
                                    <input type='hidden' name='ride_id' value='".$rid."'>
                                    <button type='submit' name='accept_pax' class='btn btn-acc'>Accept</button>
                                  </form></div>";
                        }
                    } else {
                        echo "<span style='color:#bbb; font-size:12px;'>None</span>";
                    }
                    ?>
                </td>
                <td style="text-align: right;">
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this ride?');">
                        <input type="hidden" name="ride_id" value="<?php echo $rid; ?>">
                        <button type="submit" name="delete_ride" class="btn btn-del">Delete</button>
                    </form>
                </td>
            </tr>
            <?php 
                } 
            } else {
                echo "<tr><td colspan='8' style='text-align:center; padding: 50px; color: #999;'>No rides scheduled in the database.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>