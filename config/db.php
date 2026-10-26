<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "rupendkar";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
