<?php
$servername = "localhost";
$username = "root";
$password = "admin"; 
$dbname = "coding_platform";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("MySQL Connection failed: " . $conn->connect_error);
}
?>
