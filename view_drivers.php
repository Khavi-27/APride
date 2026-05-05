<?php
session_start();
include 'db.php';

// SQL Query joining User and Driver, showing only APPROVED drivers
$sql = "SELECT 
            d.driver_id, 
            u.user_id,
            u.tp_number, 
            u.e_mail,
            d.vehicle_type,
            d.license_plate,
            d.status
        FROM Driver d
        JOIN User u ON d.user_id = u.user_id
        WHERE d.status = 'Approved'
        ORDER BY d.driver_id ASC";

$result = $conn->query($sql);

// Collect rows for display
$drivers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $drivers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Drivers | APRide</title>
    <link rel="stylesheet" href="app.css">
    <style>
        .delete-btn { background-color: rgba(249, 115, 115, 0.2); color: #f97373; border: none; padding: 6px 14px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 12px; transition: transform 0.1s; }
        .delete-btn:hover { background-color: rgba(249, 115, 115, 0.3); transform: translateY(-1px); }
    </style>
</head>
<body>
    <header class="ap-nav">
        <div class="ap-nav-inner">
            <div class="ap-nav-left">
                <img src="logo.png" alt="Logo" class="ap-logo">
                <div>
                    <div class="ap-brand">APRide</div>
                    <div class="ap-muted">Approved Drivers</div>
                </div>
            </div>
            <div class="ap-nav-links">
                <a href="admin.php" class="ap-nav-primary">Dashboard</a>
                <a href="co2_report.php">CO₂ Report</a>
                <a href="ride_history.php">Ride History</a>
                <a href="transaction_report.php">Bookings & Payments</a>
                <a href="customer_list.php">Customers</a>
                <a href="view_drivers.php">Drivers</a>
                <a href="admin_approval.php">Requests</a>
                <a href="logout.php" style="color:#f97373;">Logout</a>
            </div>
        </div>
    </header>

    <div class="shell">
        <div class="glass-card">
            <h2 style="margin-top:0;">Approved Drivers</h2>
            <p class="ap-muted" style="margin-bottom:20px;">This list shows only drivers that have been approved. New or rejected applications appear under <strong>REQUESTS</strong> on the admin dashboard.</p>

            <table class="ap-table">
                <thead>
                    <tr>
                        <th>Driver ID</th>
                        <th>TP Number</th>
                        <th>License Plate</th>
                        <th>Email</th>
                        <th>Vehicle Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($drivers)) {
                        foreach ($drivers as $row) {
                            $userId   = $row['user_id'];
                            $driverId = $row['driver_id'];
                            $status   = $row['status'] ?? 'Approved';

                            echo "<tr id='row-" . $userId . "'>
                                    <td>#DRV-" . $driverId . "</td>
                                    <td>" . htmlspecialchars($row["tp_number"]) . "</td>
                                    <td><strong>" . htmlspecialchars($row["license_plate"]) . "</strong></td>
                                    <td>" . htmlspecialchars($row["e_mail"]) . "</td>
                                    <td>" . htmlspecialchars($row["vehicle_type"]) . "</td>
                                    <td><span class='ap-pill ap-pill-green'>" . htmlspecialchars($status) . "</span></td>
                                    <td>
                                        <button class='delete-btn' onclick='deleteDriver(" . $userId . ")'>Delete</button>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; padding:30px; color:#9ca3af;'>No approved drivers found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

<script>
function deleteDriver(userId) {
    if (confirm("Permanently remove this driver?")) {
        const formData = new FormData();
        formData.append('user_id', userId);

        fetch('delete_driver.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data === "success") {
                const rowEl = document.getElementById('row-' + userId);
                if (rowEl) rowEl.remove();
            } else {
                alert("Error: " + data);
            }
        });
    }
}
</script>

</body>
</html>