<?php
session_start();
require "config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    
    $judul = htmlspecialchars($_POST['judul']);
    $deskripsi = htmlspecialchars($_POST['deskripsi']);
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    // 1. Buat Kode Unik (Slug) dari Judul
    // Contoh: "Kebersihan Kelas" -> "kebersihan-kelas-123"
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));
    $kode_unik = $slug . '-' . time(); // Tambah waktu biar unik

    // 2. Tentukan Status
    // Guru -> Aktif, Siswa -> Menunggu
    $status = ($role === 'guru') ? 'aktif' : 'menunggu';

    // 3. Simpan ke Database
    $stmt = $conn->prepare("INSERT INTO musyawarah_topik (kode, judul, deskripsi, dibuat_oleh, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $kode_unik, $judul, $deskripsi, $user_id, $status);

    if ($stmt->execute()) {
        header("Location: musyawarah.php?msg=added");
    } else {
        echo "Gagal: " . $conn->error;
    }
} else {
    header("Location: musyawarah.php");
}
?>