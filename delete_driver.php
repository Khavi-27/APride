
<?php
session_start();
include 'db.php';

if (isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];

    // 1. Delete from Driver table first
    $delDriver = "DELETE FROM Driver WHERE user_id = ?";
    $stmt1 = $conn->prepare($delDriver);
    $stmt1->bind_param("i", $userId);
    $stmt1->execute();

    // 2. Delete from User table
    $delUser = "DELETE FROM User WHERE user_id = ?";
    $stmt2 = $conn->prepare($delUser);
    $stmt2->bind_param("i", $userId);

    if ($stmt2->execute()) {
        echo "success";
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt1->close();
    $stmt2->close();
    $conn->close();
}
?>
