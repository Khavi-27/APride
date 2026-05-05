<?php
session_start();
include 'db.php';

// Optional: Only allow admins
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

// Basic admin check similar to admin.php
$uid = $_SESSION['user_id'];
$isAdmin = false;

$checkAdmin = $conn->query("SELECT admin_id FROM admin WHERE user_id = '$uid'");
if ($checkAdmin && $checkAdmin->num_rows > 0) {
    $isAdmin = true;
}

if (!$isAdmin) {
    $checkRole = $conn->query("SELECT role FROM user WHERE user_id = '$uid' AND role = 'Admin'");
    if ($checkRole && $checkRole->num_rows > 0) {
        $isAdmin = true;
    }
}

if (!$isAdmin) {
    http_response_code(403);
    echo "Forbidden";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driverId = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;
    $status   = isset($_POST['status']) ? $_POST['status'] : '';

    // Allow only specific statuses
    $allowed = ['Pending', 'Approved', 'Rejected'];
    if ($driverId <= 0 || !in_array($status, $allowed)) {
        http_response_code(400);
        echo "Invalid input";
        exit();
    }

    $stmt = $conn->prepare("UPDATE Driver SET status = ? WHERE driver_id = ?");
    if (!$stmt) {
        http_response_code(500);
        echo "DB error: " . $conn->error;
        exit();
    }

    $stmt->bind_param("si", $status, $driverId);

    if ($stmt->execute()) {
        echo "success";
    } else {
        http_response_code(500);
        echo "Error updating status: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo "Method not allowed";
}

