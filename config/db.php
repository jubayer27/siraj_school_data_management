<?php
$host = "localhost";
$user = "root";          // default user for XAMPP
$pass = "";              // default password = empty
$dbname = "school";      // your database name

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
