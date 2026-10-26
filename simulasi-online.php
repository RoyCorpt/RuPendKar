<?php
session_start();
require "config/db.php";
header("Content-Type: application/json");

// 1. Cek Login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['kelas'])) {
    echo json_encode([]);
    exit;
}

$id_saya = $_SESSION['user_id'];
$kelas_saya = $_SESSION['kelas'];

// 2. Update waktu aktif user saat ini (Heartbeat)
$stmt_update = $conn->prepare("UPDATE users SET terakhir_aktif = NOW() WHERE id = ?");
$stmt_update->bind_param("i", $id_saya);
$stmt_update->execute();

// 3. Ambil teman sekelas yang aktif dalam 5 menit terakhir
// Kecualikan diri sendiri agar nama kita tidak muncul ganda
$stmt_get = $conn->prepare("
    SELECT nama 
    FROM users 
    WHERE kelas = ? 
    AND id != ? 
    AND terakhir_aktif > (NOW() - INTERVAL 5 MINUTE)
    ORDER BY nama ASC
");

$stmt_get->bind_param("si", $kelas_saya, $id_saya);
$stmt_get->execute();
$result = $stmt_get->get_result();

$online_users = [];
while ($row = $result->fetch_assoc()) {
    $online_users[] = $row;
}

// Kirim data JSON ke frontend
echo json_encode($online_users);
?>