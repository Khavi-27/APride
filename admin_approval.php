<?php
session_start();
include 'db.php';

// Optional: basic admin check (similar to admin.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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
    header("Location: login.php");
    exit();
}

// Show only drivers that still need admin attention (Pending or Rejected)
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
        WHERE d.status IN ('Pending', 'Rejected')
        ORDER BY d.driver_id ASC";

$result = $conn->query($sql);

// Collect rows and status counts for this view
$drivers = [];
$counts = ['Pending' => 0, 'Rejected' => 0];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'] ?? 'Pending';
        if (isset($counts[$status])) {
            $counts[$status]++;
        } else {
            $counts['Pending']++;
        }
        $drivers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Requests | APRide</title>
    <link rel="stylesheet" href="app.css">
    <style>
        .legend { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin: 12px 0 16px; }
        .pill { padding: 6px 12px; border-radius: 16px; font-weight: 600; font-size: 0.85em; }
        .pill.pending { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .pill.rejected { background: rgba(249, 115, 115, 0.2); color: #f97373; }
        .filter-bar { margin: 16px 0; display: flex; flex-wrap: wrap; gap: 10px; }
        .filter-btn { padding: 8px 16px; border-radius: 999px; border: 1px solid rgba(148, 163, 184, 0.4); background: rgba(15, 23, 42, 0.6); color: #cbd5f5; cursor: pointer; font-size: 13px; transition: all 0.15s; }
        .filter-btn:hover { background: rgba(15, 23, 42, 0.8); color: #f9fafb; }
        .filter-btn.active { background: linear-gradient(135deg, #22c55e, #0ea5e9); color: #020617; border-color: transparent; }
        .action-btn { background: linear-gradient(135deg, #22c55e, #0ea5e9); color: #020617; border: none; padding: 6px 14px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 12px; margin-right: 6px; transition: transform 0.1s; }
        .action-btn:hover { transform: translateY(-1px); }
        .action-btn.reject { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .action-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
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
                    <div class="ap-muted">Driver Requests</div>
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
            <h2 style="margin-top:0;">Driver Requests (Pending / Rejected)</h2>
            <div class="legend">
                <span class="ap-muted">This page shows new driver sign‑ups waiting for your decision.</span>
                <span class="pill pending">Pending: waiting for approval</span>
                <span class="pill rejected">Rejected: previously rejected, still blocked</span>
                <span class="ap-muted">
                    Counts — 
                    Pending: <span id="count-pending" style="color:#fbbf24; font-weight:600;"><?php echo $counts['Pending']; ?></span> | 
                    Rejected: <span id="count-rejected" style="color:#f97373; font-weight:600;"><?php echo $counts['Rejected']; ?></span>
                </span>
            </div>
            <div class="filter-bar">
                <button class="filter-btn active" data-filter="all" onclick="filterStatus('all', this)">All requests</button>
                <button class="filter-btn" data-filter="pending" onclick="filterStatus('pending', this)">Pending only</button>
                <button class="filter-btn" data-filter="rejected" onclick="filterStatus('rejected', this)">Rejected only</button>
            </div>

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
                            $status   = $row['status'] ?? 'Pending';

                            $badgeColor = '#fbbf24';
                            if (strcasecmp($status, 'Rejected') === 0) {
                                $badgeColor = '#f97373';
                            }

                            $approveDisabled = strcasecmp($status, 'Approved') === 0 ? 'disabled' : '';
                            $rejectDisabled  = strcasecmp($status, 'Rejected') === 0 ? 'disabled' : '';

                            $statusDataAttr = strtolower($status);

                            echo "<tr id='row-" . $userId . "' data-status='" . $statusDataAttr . "'>
                                    <td>#DRV-" . $driverId . "</td>
                                    <td>" . htmlspecialchars($row["tp_number"]) . "</td>
                                    <td><strong>" . htmlspecialchars($row["license_plate"]) . "</strong></td>
                                    <td>" . htmlspecialchars($row["e_mail"]) . "</td>
                                    <td>" . htmlspecialchars($row["vehicle_type"]) . "</td>
                                    <td><span class='ap-pill' style='background:rgba(" . ($badgeColor === '#f97373' ? '249, 115, 115' : '251, 191, 36') . ", 0.2); color:" . $badgeColor . ";' id='status-" . $driverId . "'>" . htmlspecialchars($status) . "</span></td>
                                    <td>
                                        <button class='action-btn' {$approveDisabled} onclick=\"updateStatus(" . $driverId . ", 'Approved')\">Approve</button>
                                        <button class='action-btn reject' {$rejectDisabled} onclick=\"updateStatus(" . $driverId . ", 'Rejected')\">Reject</button>
                                        <button class='delete-btn' onclick='deleteDriver(" . $userId . ")'>Delete</button>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; padding:30px; color:#9ca3af;'>No driver requests at the moment.</td></tr>";
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
                recalcCounts();
            } else {
                alert("Error: " + data);
            }
        });
    }
}

function updateStatus(driverId, status) {
    const formData = new FormData();
    formData.append('driver_id', driverId);
    formData.append('status', status);

    fetch('update_driver_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data === "success") {
            const badge = document.getElementById('status-' + driverId);
            const row = document.querySelector('tr[data-status][id^="row-"]:has(#status-' + driverId + ')');

            if (status === 'Approved') {
                // Once approved, remove from the requests list
                if (row) {
                    row.remove();
                }
            } else {
                // Update badge styling/text for rejected
                if (badge) {
                    badge.textContent = status;
                    badge.classList.remove('status-pending', 'status-rejected');
                    if (status === 'Rejected') {
                        badge.classList.add('status-rejected');
                    } else {
                        badge.classList.add('status-pending');
                    }
                }

                if (row) {
                    row.dataset.status = status.toLowerCase();
                }
            }

            recalcCounts();
        } else {
            alert("Error updating status: " + data);
        }
    })
    .catch(err => {
        alert("Network error: " + err);
    });
}

function filterStatus(filter, buttonEl) {
    const rows = document.querySelectorAll('tbody tr[data-status]');

    rows.forEach(row => {
        const rowStatus = (row.dataset.status || '').toLowerCase();
        if (filter === 'all' || rowStatus === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    if (buttonEl) {
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        buttonEl.classList.add('active');
    }
}

function recalcCounts() {
    const rows = document.querySelectorAll('tbody tr[data-status]');
    let pending = 0, rejected = 0;

    rows.forEach(row => {
        const s = (row.dataset.status || '').toLowerCase();
        if (s === 'rejected') rejected++;
        else pending++;
    });

    const pEl = document.getElementById('count-pending');
    const rEl = document.getElementById('count-rejected');

    if (pEl) pEl.textContent = pending;
    if (rEl) rEl.textContent = rejected;
}
</script>

</body>
</html>