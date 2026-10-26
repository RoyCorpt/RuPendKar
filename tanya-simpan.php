<?php
session_start();
require "config/db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $judul = htmlspecialchars($_POST['judul']);
    $isi = htmlspecialchars($_POST['isi']);
    $user_id = $_SESSION['user_id'];

    // 1. Simpan Pertanyaan
    $stmt = $conn->prepare("INSERT INTO bantuan_pertanyaan (user_id, judul, isi) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $judul, $isi);
    
    if ($stmt->execute()) {
        // 2. BERI POIN (5 Poin untuk Bertanya)
        $conn->query("UPDATE users SET poin = poin + 5 WHERE id = $user_id");

        // 3. Catat Log
        $conn->query("INSERT INTO aktivitas_log (user_id, aktivitas, detail) VALUES ($user_id, 'Bertanya', 'Membuat pertanyaan baru di Gotong Royong (+5 Poin)')");

        header("Location: gotong.php?msg=sukses");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>