<?php
session_start();
include 'db.php';

$ride_id = isset($_GET['ride_id']) ? (int)$_GET['ride_id'] : 0;
if ($ride_id <= 0) {
    die("Ride not found.");
}

// Get ride + driver info, only for approved drivers
$stmt = $conn->prepare("SELECT r.*, d.vehicle_type, d.license_plate, u.tp_number 
                        FROM ride r
                        JOIN driver d ON r.driver_id = d.driver_id
                        JOIN user u ON d.user_id = u.user_id
                        WHERE r.ride_id = ? AND d.status = 'Approved'");
$stmt->bind_param("i", $ride_id);
$stmt->execute();
$ride = $stmt->get_result()->fetch_assoc();
if (!$ride) {
    die("Ride not found or driver not approved.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ride Details | APRide</title>
    <link rel="stylesheet" href="app.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map { width:100%; height:450px; border-radius:16px; border:1px solid rgba(148, 163, 184, 0.3); overflow:hidden; }
        .ride-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        @media (max-width: 768px) {
            .ride-info-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">Ride Details</div>
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
            <h2 style="margin-top:0;">Ride Details</h2>
            <div style="background:rgba(15, 23, 42, 0.6); border:1px solid rgba(148, 163, 184, 0.3); border-left:4px solid #22c55e; padding:20px; border-radius:12px; margin:20px 0;">
                <p style="margin:8px 0;"><strong style="color:#cbd5f5;">Destination:</strong> <span style="color:#f9fafb;"><?php echo htmlspecialchars($ride['destination']); ?></span></p>
                <p style="margin:8px 0;"><strong style="color:#cbd5f5;">Driver TP:</strong> <span style="color:#f9fafb;"><?php echo htmlspecialchars($ride['tp_number']); ?></span></p>
                <p style="margin:8px 0;"><strong style="color:#cbd5f5;">Vehicle:</strong> <span style="color:#f9fafb;"><?php echo htmlspecialchars($ride['vehicle_type']); ?> (<?php echo htmlspecialchars($ride['license_plate']); ?>)</span></p>
                <p style="margin:8px 0;"><strong style="color:#cbd5f5;">Price:</strong> <span style="color:#22c55e; font-weight:bold; font-size:1.1em;">RM <?php echo number_format($ride['price'], 2); ?></span></p>
                <p style="margin:8px 0;"><strong style="color:#cbd5f5;">Seats Available:</strong> <span style="color:#f9fafb;"><?php echo $ride['available_seats']; ?></span></p>
                <p style="margin:8px 0;"><strong style="color:#cbd5f5;">Date/Time:</strong> <span style="color:#f9fafb;"><?php echo date('d M Y, h:i A', strtotime($ride['date_time'])); ?></span></p>
                <p style="margin:8px 0;"><strong style="color:#cbd5f5;">Estimated Arrival:</strong> <span style="color:#f9fafb;">10 Min</span></p>
            </div>
            <a href="payment.php?ride_id=<?php echo $ride_id; ?>" class="ap-button-primary" style="text-decoration:none; display:block; text-align:center; margin-top:20px;">Proceed to Payment</a>
        </div>

        <div class="glass-card" style="margin-top:20px;">
            <h3 style="margin-top:0;">Location Map</h3>
            <div id="map"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Set view to APU Bukit Jalil coordinates [3.0485, 101.6911]
        var map = L.map('map').setView([3.0485, 101.6911], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Add a marker to show driver's current real-time location
        L.marker([3.0485, 101.6911]).addTo(map)
            .bindPopup('Driver is currently here.')
            .openPopup();
    </script>
</body>
</html>