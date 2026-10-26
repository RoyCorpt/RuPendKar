<?php
session_start();
require "config/db.php";
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "ERROR", "msg" => "Sesi habis, silakan login kembali."]);
    exit;
}

if (!isset($_POST['topik_kode']) || !isset($_POST['pesan'])) {
    echo json_encode(["status" => "ERROR", "msg" => "Data tidak lengkap"]);
    exit;
}

$topik = $_POST['topik_kode'];
$pesan = trim($_POST['pesan']);
$user  = $_SESSION['user_id'];

// Validasi pesan kosong
if ($pesan === "") {
    echo json_encode(["status" => "ERROR", "msg" => "Pesan tidak boleh kosong"]);
    exit;
}

// Validasi panjang pesan (Opsional, misalnya max 500 karakter)
if (strlen($pesan) > 500) {
    echo json_encode(["status" => "ERROR", "msg" => "Pesan terlalu panjang"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO musyawarah_chat (topik_kode, user_id, pesan)
    VALUES (?, ?, ?)
");

$stmt->bind_param("sis", $topik, $user, $pesan);

if ($stmt->execute()) {
    echo json_encode(["status" => "OK"]);
} else {
    // Jangan tampilkan $stmt->error asli ke user (berbahaya), cukup log error umum
    echo json_encode(["status" => "ERROR", "msg" => "Gagal menyimpan pesan."]);
}
?>