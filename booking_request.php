<?php
session_start();
include 'db.php';

if (!isset($_SESSION['customer_id']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: find_ride.php");
    exit();
}

$ride_id = (int)($_POST['ride_id'] ?? 0);
$customer_id = (int)$_SESSION['customer_id'];

if ($ride_id <= 0) {
    die("Invalid ride.");
}

// Ensure ride exists and has seats
$rideRes = $conn->query("SELECT available_seats FROM ride WHERE ride_id = $ride_id");
if (!$rideRes || $rideRes->num_rows === 0) {
    die("Ride not found.");
}
$ride = $rideRes->fetch_assoc();

if ((int)$ride['available_seats'] <= 0) {
    echo "<script>alert('No seats left for this ride.'); window.location='find_ride.php';</script>";
    exit();
}

// Insert booking with Pending status (driver must approve)
$stmt = $conn->prepare("INSERT INTO booking (ride_id, customer_id, status) VALUES (?, ?, 'Pending')");
$stmt->bind_param("ii", $ride_id, $customer_id);

if ($stmt->execute()) {
    echo "<script>alert('Request sent! Waiting for driver approval.'); window.location='customer.php';</script>";
} else {
    echo "Error: " . $conn->error;
}

$stmt->close();
$conn->close();
