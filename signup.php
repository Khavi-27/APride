<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Common User Data
    $tp = $_POST['tp_number'];
    $pass = $_POST['password']; 
    $email = $_POST['email'];
    $name = $_POST['name'];
    $role = $_POST['role'];

    // 1. Insert into USER table
    $sql_user = "INSERT INTO user (TP_Number, password, e_mail, name, role) VALUES ('$tp', '$pass', '$email', '$name', '$role')";
    
    if ($conn->query($sql_user) === TRUE) {
        $last_id = $conn->insert_id; // Get the ID of the new user

        // 2. Role Specific Insertion
        if ($role == 'Customer') {
            // Insert into CUSTOMER table
            $sql_specific = "INSERT INTO customer (user_id, points_balance, membership_status) VALUES ('$last_id', 0, 'Silver')";
        
        } else if ($role == 'Driver') {
            // Get Driver Specific Data from the form
            $v_type = $_POST['vehicle_type'];
            $l_plate = $_POST['license_plate'];
            $max_p = $_POST['max_passengers'];

            // Insert into DRIVER table
            // NOTE: Ensure your database columns match these names exactly: vehicle_type, license_plate, max_passengers
            $sql_specific = "INSERT INTO driver (user_id, status, vehicle_type, license_plate, max_passengers) 
                             VALUES ('$last_id', 'Pending', '$v_type', '$l_plate', '$max_p')"; 
        }

        // Execute the specific insertion
        if ($conn->query($sql_specific) === TRUE) {
            echo "<script>alert('Account created successfully as $role! Please login.'); window.location='login.php';</script>";
        } else {
            echo "Error creating $role profile: " . $conn->error;
        }

    } else {
        echo "Error creating User: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="app.css">

    <script>
        function toggleDriverFields() {
            var roleSelect = document.getElementById("roleSelect");
            var driverSection = document.getElementById("driver-fields");
            
            // Get the inputs inside the driver section
            var vehicleType = document.getElementById("vehicle_type");
            var licensePlate = document.getElementById("license_plate");
            var maxPax = document.getElementById("max_passengers");

            if (roleSelect.value === "Driver") {
                // Show fields and make them REQUIRED
                driverSection.style.display = "block";
                vehicleType.required = true;
                licensePlate.required = true;
                maxPax.required = true;
            } else {
                // Hide fields and make them NOT REQUIRED (so form can submit)
                driverSection.style.display = "none";
                vehicleType.required = false;
                licensePlate.required = false;
                maxPax.required = false;
            }
        }
    </script>
</head>
<body>
    <div class="auth-shell">
        <div class="glass-card">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <img src="logo.png" style="height:40px;" class="ap-logo">
            <div>
                <div class="ap-brand">APRide</div>
                <div class="ap-muted">Create your account</div>
            </div>
        </div>
        <h2 class="auth-title">Join APRide</h2>
        <form method="POST">
            <label class="ap-label">TP Number</label>
            <input class="ap-input" type="text" name="tp_number" placeholder="TP012345" required>
            <label class="ap-label">Full Name</label>
            <input class="ap-input" type="text" name="name" placeholder="Your name" required>
            <label class="ap-label">Email</label>
            <input class="ap-input" type="email" name="email" placeholder="Student/Staff email" required>
            <label class="ap-label">Password</label>
            <input class="ap-input" type="password" name="password" placeholder="Create a password" required>
            
            <label class="ap-label">Account type</label>
            <select class="ap-select" name="role" id="roleSelect" onchange="toggleDriverFields()" required>
                <option value="" disabled selected>Select your Role</option>
                <option value="Customer">Customer (Passenger)</option>
                <option value="Driver">Driver (Car Owner)</option>
            </select>

            <div id="driver-fields">
                <p style="margin:5px 0 10px 0; font-size:0.9rem; color:#4ade80; font-weight:bold;">Driver Details</p>
                
                <label class="ap-label" style="margin-top:0;">Vehicle Type</label>
                <select class="ap-select" name="vehicle_type" id="vehicle_type">
                    <option value="" disabled selected>Vehicle Type</option>
                    <option value="Sedan">Sedan (4 Seater)</option>
                    <option value="SUV">SUV (6 Seater)</option>
                    <option value="MPV">MPV (7 Seater)</option>
                    <option value="Hatchback">Hatchback</option>
                </select>

                <label class="ap-label">License Plate</label>
                <input class="ap-input" type="text" name="license_plate" id="license_plate" placeholder="e.g. VAB 1234">
                
                <label class="ap-label">Max Passengers</label>
                <input class="ap-input" type="number" name="max_passengers" id="max_passengers" min="1" max="6" value="4">
            </div>

            <button type="submit" class="ap-button-primary">Create account</button>
        </form>
        <p style="margin-top:14px;font-size:13px;color:#94a3b8;text-align:center;">
            Already have an account? <a href="login.php" class="ap-link">Back to Login</a>
        </p>
    </div>
</body>
</html>