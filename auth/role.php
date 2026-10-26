<?php
// Pastikan session sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika user tidak login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

function onlyGuru() {
    if ($_SESSION['role'] !== 'guru' && $_SESSION['role'] !== 'admin') {
        header("Location: ../index.php?denied=guru");
        exit;
    }
}

function onlyAdmin() {
    if ($_SESSION['role'] !== 'admin') {
        header("Location: ../index.php?denied=admin");
        exit;
    }
}

function onlySiswa() {
    if ($_SESSION['role'] !== 'siswa') {
        header("Location: ../index.php?denied=siswa");
        exit;
    }
}
?>
