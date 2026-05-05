<?php
$servername = "localhost";
$username = "root";
$password = ""; // Default WAMP password is usually empty
$dbname = "apride";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>