<?php
session_start();
include 'db.php';

$sql = "SELECT 
            c.customer_id, 
            u.user_id,
            u.tp_number, 
            u.e_mail,
            c.points_balance 
        FROM Customer c
        JOIN User u ON c.user_id = u.user_id
        ORDER BY c.customer_id ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer List | APRide</title>
    <link rel="stylesheet" href="app.css">
    <style>
        .points-badge { background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: 5px 12px; border-radius: 999px; font-weight: 600; font-size: 0.85em; }
        .e_mail-link { color: #38bdf8; text-decoration: none; }
        .e_mail-link:hover { text-decoration: underline; }
        .delete-btn { 
            background-color: rgba(249, 115, 115, 0.2); 
            color: #f97373; 
            border: none; 
            padding: 8px 14px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600;
            font-size: 12px;
            transition: transform 0.1s;
        }
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
                    <div class="ap-muted">Customers</div>
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
            <h2 style="margin-top:0;">Registered Customers (Students)</h2>
            <p class="ap-muted" style="margin-bottom:20px;">Manage registered passengers and their account details.</p>

            <table class="ap-table">
                <thead>
                    <tr>
                        <th>Customer ID</th>
                        <th>TP Number</th>
                        <th>Email Address</th>
                        <th>Current Points</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $email = !empty($row["e_mail"]) ? $row["e_mail"] : "N/A";
                            // We use the user_id to identify the person across all tables
                            $userId = $row['user_id']; 

                            echo "<tr id='row-" . $userId . "'>
                                    <td>#CUST-" . htmlspecialchars($row["customer_id"]) . "</td>
                                    <td>" . htmlspecialchars($row["tp_number"]) . "</td>
                                    <td><a href='mailto:" . htmlspecialchars($email) . "' class='e_mail-link'>" . htmlspecialchars($email) . "</a></td>
                                    <td><span class='points-badge'>" . htmlspecialchars($row["points_balance"]) . " pts</span></td>
                                    <td>
                                        <button class='delete-btn' onclick='deleteCustomer(" . $userId . ")'>Delete</button>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#9ca3af;'>No customers found in the system.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

<script>
function deleteCustomer(userId) {
    if (confirm("Are you sure? This will permanently delete this customer from the entire system.")) {
        
        // Create the form data to send
        const formData = new FormData();
        formData.append('user_id', userId);

        fetch('delete_customer.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data === "success") {
                // Remove the row from the table UI instantly
                document.getElementById('row-' + userId).remove();
            } else {
                alert("Error: " + data);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}
</script>

</body>
</html>