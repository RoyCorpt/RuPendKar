<?php
session_start();
require "config/db.php";

header("Content-Type: application/json");

// 1. Keamanan: Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // Akses ditolak
    echo json_encode(["error" => "Akses ditolak"]);
    exit;
}

if (!isset($_GET['topik'])) {
    echo json_encode([]);
    exit;
}

$topik = $_GET['topik'];

$stmt = $conn->prepare("
    SELECT musyawarah_chat.*, users.nama
    FROM musyawarah_chat
    JOIN users ON users.id = musyawarah_chat.user_id
    WHERE topik_kode = ?
    ORDER BY waktu ASC
");
$stmt->bind_param("s", $topik);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    // 2. Keamanan: Sanitasi output (PENTING!)
    // Mengubah karakter berbahaya < > menjadi entitas HTML aman
    $row['pesan'] = htmlspecialchars($row['pesan']); 
    $row['nama']  = htmlspecialchars($row['nama']);
    
    $data[] = $row;
}

echo json_encode($data);
?>