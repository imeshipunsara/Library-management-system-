<?php
$host = "localhost"; // Your database host
$user = "root";      // Your MySQL username
$pass = "";          // Your MySQL password
$dbname = "librarydb"; // Your database name

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>