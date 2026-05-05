<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['tp_number'];
    $pass = $_POST['password'];

    // 1. Check the base User table
    $sql = "SELECT user_id FROM User WHERE tp_number = '$user' AND password = '$pass'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $uid = $row['user_id'];
        $_SESSION['user_id'] = $uid;

        // 2. Check if the user is an Admin (check both Admin table and user role)
        $checkAdmin = $conn->query("SELECT admin_id FROM admin WHERE user_id = '$uid'");
        if ($checkAdmin->num_rows > 0) {
            $adminData = $checkAdmin->fetch_assoc();
            $_SESSION['admin_id'] = $adminData['admin_id'];
            $_SESSION['user_role'] = 'admin';
            header("Location: admin.php");
            exit();
        }
        
        // Also check if user role is 'Admin' in the user table
        $checkRole = $conn->query("SELECT role FROM user WHERE user_id = '$uid' AND role = 'Admin'");
        if ($checkRole->num_rows > 0) {
            $_SESSION['user_role'] = 'admin';
            header("Location: admin.php");
            exit();
        }

        // 3. Check if the user is a Driver (Rider) and only allow if approved
        $checkDriver = $conn->query("SELECT driver_id, status FROM Driver WHERE user_id = '$uid'");
        if ($checkDriver->num_rows > 0) {
            $driverData = $checkDriver->fetch_assoc();
            $driverStatus = $driverData['status'] ?? 'Pending';

            if (strcasecmp($driverStatus, 'Approved') === 0) {
                header("Location: driver_dashboard.php");
                exit();
            } elseif (strcasecmp($driverStatus, 'Rejected') === 0) {
                echo "<script>alert('Your driver registration was rejected by admin.'); window.location='login.php';</script>";
                exit();
            } else {
                echo "<script>alert('Your driver registration is pending admin approval. Please wait until it is approved.'); window.location='login.php';</script>";
                exit();
            }
        }

        // 4. Check if the user is a Customer
        $checkCustomer = $conn->query("SELECT customer_id FROM Customer WHERE user_id = '$uid'");
        if ($checkCustomer->num_rows > 0) {
            header("Location: customer.php");
            exit();
        }

        echo "User found, but no role assigned.";
    } else {
        echo "<script>alert('Invalid credentials!'); window.location='login.php';</script>";
    }
}
?>