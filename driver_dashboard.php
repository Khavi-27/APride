<?php
session_start();
require_once "db.php";

if ($conn->connect_error) {
    die("Database connection failed");
}

// Require logged-in user
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* Get driver_id for this user */
$driverQuery = $conn->prepare("SELECT driver_id, status FROM driver WHERE user_id = ?");
$driverQuery->bind_param("i", $user_id);
$driverQuery->execute();

$driverResult = $driverQuery->get_result();
$driver = $driverResult->fetch_assoc();

if (!$driver) {
    die("No driver found for this account. Please register as a driver first.");
}

// Allow only approved drivers
if (isset($driver['status']) && strcasecmp($driver['status'], 'Approved') !== 0) {
    die("Your driver account is not approved yet. Please wait for admin approval.");
}

$driver_id = (int)$driver['driver_id'];

/* Dashboard stats */
$sql = "
SELECT
    COUNT(DISTINCT r.ride_id) AS total_rides,
    IFNULL(SUM(CASE WHEN b.status IN ('Confirmed','Completed') THEN 1 ELSE 0 END), 0) AS total_passengers,
    IFNULL(SUM(r.distance_km), 0) AS total_distance
FROM ride r
LEFT JOIN booking b ON r.ride_id = b.ride_id
WHERE r.driver_id = ?
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$co2Saved = $data['total_passengers'] * 2.3;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Driver Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="app.css">
</head>

<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">Driver Control Center</div>
                </div>
            </div>
            <div class="ap-nav-links">
                <a href="driver_dashboard.php" class="ap-nav-primary">Dashboard</a>
                <a href="offer_ride.php">Offer Ride</a>
                <a href="my_rides.php">My Rides</a>
                <a href="earnings.php">Earnings</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php" style="color:#f97373;">Logout</a>
            </div>
        </div>
    </header>

    <div class="shell">
        <div class="glass-card">
            <h1 style="margin-top:0;">Driver Dashboard</h1>
            <p class="ap-muted">At a glance overview of your trips, passengers and CO₂ savings.</p>

            <section class="stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin:28px 0 34px 0;">
                <div class="card" style="background:transparent;border-radius:18px;border:1px solid rgba(148,163,184,0.4);box-shadow:none;">
                    <span class="ap-label" style="text-transform:uppercase;font-size:11px;">Total Rides</span>
                    <h2 style="margin-top:6px;font-size:30px;"><?= $data['total_rides'] ?></h2>
                </div>
                <div class="card" style="background:transparent;border-radius:18px;border:1px solid rgba(148,163,184,0.4);box-shadow:none;">
                    <span class="ap-label" style="text-transform:uppercase;font-size:11px;">Passengers</span>
                    <h2 style="margin-top:6px;font-size:30px;"><?= $data['total_passengers'] ?></h2>
                </div>
                <div class="card" style="background:transparent;border-radius:18px;border:1px solid rgba(148,163,184,0.4);box-shadow:none;">
                    <span class="ap-label" style="text-transform:uppercase;font-size:11px;">Distance (km)</span>
                    <h2 style="margin-top:6px;font-size:30px;"><?= number_format($data['total_distance'], 1) ?></h2>
                </div>
                <div class="card" style="background:transparent;border-radius:18px;border:1px solid rgba(148,163,184,0.4);box-shadow:none;">
                    <span class="ap-label" style="text-transform:uppercase;font-size:11px;">CO₂ Saved (kg)</span>
                    <h2 style="margin-top:6px;font-size:30px;"><?= number_format($co2Saved, 1) ?></h2>
                </div>
            </section>

            <section class="ap-grid">
                <div class="ap-tile" onclick="window.location='offer_ride.php'">
                    <div class="ap-tile-title">Offer New Ride</div>
                    <div class="ap-tile-sub">Publish a new trip for students to join.</div>
                </div>
                <div class="ap-tile" onclick="window.location='my_rides.php'">
                    <div class="ap-tile-title">Manage Rides</div>
                    <div class="ap-tile-sub">Approve passengers and manage upcoming trips.</div>
                </div>
                <div class="ap-tile" onclick="window.location='ride_history.php'">
                    <div class="ap-tile-title">Ride History</div>
                    <div class="ap-tile-sub">Review your completed journeys and eco stats.</div>
                </div>
                <div class="ap-tile" onclick="window.location='earnings.php'">
                    <div class="ap-tile-title">Earnings</div>
                    <div class="ap-tile-sub">See how much you’ve earned from shared rides.</div>
                </div>
                <div class="ap-tile" onclick="window.location='profile.php'">
                    <div class="ap-tile-title">Profile</div>
                    <div class="ap-tile-sub">View your driver and vehicle details.</div>
                </div>
            </section>
        </div>

    </div>
</body>

</html>