<?php
session_start();
include 'db.php';

if (isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];

    // 1. Delete from Customer table first (because it depends on User)
    $delCustomer = "DELETE FROM Customer WHERE user_id = ?";
    $stmt1 = $conn->prepare($delCustomer);
    $stmt1->bind_param("i", $userId);
    $stmt1->execute();

    // 2. Delete from User table to wipe them from existence
    $delUser = "DELETE FROM User WHERE user_id = ?";
    $stmt2 = $conn->prepare($delUser);
    $stmt2->bind_param("i", $userId);

    if ($stmt2->execute()) {
        echo "success";
    } else {
        echo "Error deleting user: " . $conn->error;
    }

    $stmt1->close();
    $stmt2->close();
    $conn->close();
}
?>