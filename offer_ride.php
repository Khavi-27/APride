<?php
session_start();
require_once "db.php";
if ($conn->connect_error)
    die("Database connection failed");

// Require logged-in driver
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* Get driver_id and status */
$stmt = $conn->prepare("SELECT driver_id, status, vehicle_type, license_plate, max_passengers FROM driver WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$driver = $stmt->get_result()->fetch_assoc();

if (!$driver) {
    die("You do not have a driver profile yet.");
}

if (isset($driver['status']) && strcasecmp($driver['status'], 'Approved') !== 0) {
    die("Your driver account is not approved yet. You cannot offer rides.");
}

$driver_id = (int)$driver['driver_id'];
$driver_vehicle_type = $driver['vehicle_type'] ?? 'Unknown';
$driver_license = $driver['license_plate'] ?? '';
$driver_max_seats = isset($driver['max_passengers']) && (int)$driver['max_passengers'] > 0 ? (int)$driver['max_passengers'] : 4;

// Helper to redirect with a message
function redirect_with_msg($msg) {
    header("Location: offer_ride.php?msg=" . urlencode($msg));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dateTime = $_POST['ride_date'] . " " . $_POST['ride_time'];
    $destination = trim($_POST['pickup']) . " → " . trim($_POST['dropoff']);

    // Seats: clamp to driver's max
    $seats = (int)$_POST['available_seats'];
    if ($seats < 1) $seats = 1;
    if ($seats > $driver_max_seats) $seats = $driver_max_seats;

    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $distance = isset($_POST['distance_km']) ? (float)$_POST['distance_km'] : 0;

    if ($price <= 0 || $distance <= 0) {
        redirect_with_msg("Price and distance must be greater than zero.");
    }

    $insert = $conn->prepare("
        INSERT INTO ride 
        (driver_id, destination, date_time, available_seats, price, distance_km)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$insert) {
        die("Error preparing statement: " . $conn->error);
    }

    $insert->bind_param(
        "issidd",
        $driver_id,
        $destination,
        $dateTime,
        $seats,
        $price,
        $distance
    );

    if ($insert->execute()) {
        header("Location: driver_dashboard.php?msg=Ride+published");
    } else {
        redirect_with_msg("Error publishing ride: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Offer Ride | APRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="app.css">
    <style>
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .co2-display {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            color: #22c55e;
            margin-bottom: 20px;
            text-align: center;
        }
        @media (max-width: 768px) {
            .row {
                grid-template-columns: 1fr;
            }
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
                    <div class="ap-muted">Offer a Ride</div>
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
        <div class="glass-card" style="max-width:700px; margin:0 auto;">
            <h2 style="margin-top:0; text-align:center;">🚗 Offer a Ride</h2>
            <p class="ap-muted" style="text-align:center; margin-bottom:26px;">Publish a new trip for students to join.</p>
            <form method="POST" onsubmit="return validateForm()">
                <div class="row">
                    <div>
                        <label class="ap-label">Pick-up Location</label>
                        <select class="ap-select" name="pickup" id="pickup" required>
                            <option value="">Select pick-up location</option>
                            <option>APU Main Campus</option>
                            <option>LRT Bukit Jalil</option>
                            <option>Technology Park Malaysia</option>
                            <option>Pavilion Bukit Jalil</option>
                            <option>KL Sentral</option>
                            <option>Mid Valley Megamall</option>
                        </select>
                    </div>

                    <div>
                        <label class="ap-label">Drop-off Location</label>
                        <select class="ap-select" name="dropoff" id="dropoff" required>
                            <option value="">Select drop-off location</option>
                            <option>APU Main Campus</option>
                            <option>LRT Bukit Jalil</option>
                            <option>Technology Park Malaysia</option>
                            <option>Pavilion Bukit Jalil</option>
                            <option>KL Sentral</option>
                            <option>Mid Valley Megamall</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label class="ap-label">Date</label>
                        <input class="ap-input" type="date" name="ride_date" id="ride_date" required>
                    </div>

                    <div>
                        <label class="ap-label">Time</label>
                        <input class="ap-input" type="time" name="ride_time" required>
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label class="ap-label">Vehicle Type</label>
                        <input class="ap-input" type="text" value="<?php echo htmlspecialchars($driver_vehicle_type); ?>" readonly style="background:rgba(15, 23, 42, 0.5);">
                    </div>

                    <div>
                        <label class="ap-label">Available Seats (max <?php echo $driver_max_seats; ?>)</label>
                        <input class="ap-input" type="number" name="available_seats" id="available_seats" min="1" max="<?php echo $driver_max_seats; ?>" value="<?php echo $driver_max_seats; ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label class="ap-label">Price per Person (RM)</label>
                        <input class="ap-input" type="number" step="0.01" name="price" required min="1">
                    </div>

                    <div>
                        <label class="ap-label">Distance (km)</label>
                        <input class="ap-input" type="number" step="0.1" name="distance_km" id="distance_km" required min="0.1">
                    </div>
                </div>

                <div class="co2-display">
                    🌱 Estimated CO₂ Saved: <span id="co2">0</span> kg
                </div>

                <button type="submit" class="ap-button-primary">🚀 Publish Ride</button>
            </form>
        </div>
    </div>

    <script>
        function updateMaxSeats() {
            const vehicleType = document.getElementById("vehicle_type").value;
            const seatsInput = document.getElementById("available_seats");

            if (vehicleType === "Sedan") {
                seatsInput.max = 4;
                seatsInput.disabled = false;
                seatsInput.placeholder = "Max 4 seats";
            } else if (vehicleType === "Hatchback") {
                seatsInput.max = 4;
                seatsInput.disabled = false;
                seatsInput.placeholder = "Max 4 seats";
            } else if (vehicleType === "Coupe") {
                seatsInput.max = 2;
                seatsInput.disabled = false;
                seatsInput.placeholder = "Max 2 seats";
            } else if (vehicleType === "SUV") {
                seatsInput.max = 6;
                seatsInput.disabled = false;
                seatsInput.placeholder = "Max 6 seats";
            } else {
                seatsInput.disabled = true;
                seatsInput.value = "";
            }

            // Recalculate CO2
            calculateCO2();
        }

        function validateForm() {
            const date = new Date(document.getElementById("ride_date").value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (date < today) {
                alert("⚠️ Date cannot be in the past");
                return false;
            }

            const pickup = document.getElementById("pickup").value;
            const dropoff = document.getElementById("dropoff").value;

            if (pickup === dropoff && pickup !== "") {
                alert("⚠️ Pick-up and drop-off locations cannot be the same");
                return false;
            }

            const seats = parseInt(document.getElementById("available_seats").value);
            return true;
        }

        function calculateCO2() {
            const seats = parseInt(document.getElementById("available_seats").value) || 0;
            const distance = parseFloat(document.getElementById("distance_km").value) || 0;
            // CO2 saved = distance (km) × 0.2 kg/km × available seats
            // This represents CO2 saved by carpooling instead of each person driving separately
            const co2Saved = (distance * 0.2 * seats).toFixed(2);
            document.getElementById("co2").innerText = co2Saved;
        }

        document.getElementById("available_seats").addEventListener("input", calculateCO2);
        document.getElementById("distance_km").addEventListener("input", calculateCO2);
        
        // Calculate CO2 on page load if values exist
        window.addEventListener("load", function() {
            calculateCO2();
        });

        // Prevent same pickup and dropoff selection
        document.getElementById("pickup").addEventListener("change", function () {
            const pickup = this.value;
            const dropoffSelect = document.getElementById("dropoff");

            if (dropoffSelect.value === pickup && pickup !== "") {
                dropoffSelect.value = "";
            }
        });

        document.getElementById("dropoff").addEventListener("change", function () {
            const dropoff = this.value;
            const pickupSelect = document.getElementById("pickup");

            if (pickupSelect.value === dropoff && dropoff !== "") {
                pickupSelect.value = "";
            }
        });
    </script>

</body>

</html>