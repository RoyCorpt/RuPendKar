<?php
session_start();
require "config/db.php";

if (!isset($_SESSION['user_id'])) {
    exit("Tidak ada akses.");
}

$user = $_SESSION['user_id'];
$modul = $_POST['modul_kode'];
$konten = $_POST['konten_id'];
$urutan = $_POST['konten_urutan'];
$jawaban = $_POST['jawaban'];

$stmt = $conn->prepare("
    INSERT INTO modul_jawaban (user_id, konten_id, jawaban, waktu)
    VALUES (?,?,?,NOW())
");
$stmt->bind_param("iis", $user, $konten, $jawaban);
$stmt->execute();

header("Location: modul-konten.php?modul=$modul&p=" . ($urutan + 1));
exit;
?>
