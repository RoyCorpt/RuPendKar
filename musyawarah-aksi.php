<?php
session_start();
require "config/db.php";

// Hanya Guru yang boleh akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru') {
    header("Location: index.php");
    exit;
}

if (isset($_GET['act']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $act = $_GET['act'];

    if ($act === 'approve') {
        // Setujui Topik
        $conn->query("UPDATE musyawarah_topik SET status = 'aktif' WHERE id = $id");
        
        // Opsional: Beri poin ke siswa yang membuat topik (Reward keaktifan)
        // Ambil ID pembuat topik
        $q = $conn->query("SELECT dibuat_oleh FROM musyawarah_topik WHERE id = $id");
        if ($row = $q->fetch_assoc()) {
            $siswa_id = $row['dibuat_oleh'];
            if ($siswa_id) {
                $conn->query("UPDATE users SET poin = poin + 15 WHERE id = $siswa_id");
                $conn->query("INSERT INTO aktivitas_log (user_id, aktivitas, detail) VALUES ($siswa_id, 'Topik Disetujui', 'Mengajukan topik musyawarah yang diterima Guru (+15 Poin)')");
            }
        }

    } elseif ($act === 'reject') {
        // Tolak (Hapus Topik)
        $conn->query("DELETE FROM musyawarah_topik WHERE id = $id");
    }

    header("Location: musyawarah.php");
    exit;
}
?>