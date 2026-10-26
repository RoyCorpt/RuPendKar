<?php
session_start();
require "config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: modul.php"); exit; }

$user_id = $_SESSION['user_id'];
$modul_id = $_POST['modul_id'];
$modul_kode = $_POST['modul_kode'];
$jawaban_user = $_POST['jawaban']; // Array [soal_id => jawaban]

// Variabel Nilai
$jumlah_benar = 0;
$total_soal = count($jawaban_user);
$detail_hasil = [];

// Loop setiap jawaban user
foreach ($jawaban_user as $soal_id => $jawab) {
    // Ambil Kunci Jawaban
    $cek = $conn->query("SELECT jawaban_benar, pertanyaan FROM kuis WHERE id = $soal_id");
    $data_soal = $cek->fetch_assoc();
    
    $kunci = $data_soal['jawaban_benar'];
    $is_benar = ($jawab == $kunci);

    if ($is_benar) {
        $jumlah_benar++;
    }

    // Simpan ke database kuis_jawaban (Riwayat)
    // Cek dulu apakah sudah pernah jawab, kalau sudah update, kalau belum insert
    // (Untuk simplifikasi di sini kita Insert baru saja sebagai history)
    $benar_int = $is_benar ? 1 : 0;
    $conn->query("INSERT INTO kuis_jawaban (user_id, kuis_id, jawaban, benar) VALUES ($user_id, $soal_id, '$jawab', $benar_int)");
}

// Hitung Skor Akhir (Skala 100)
$skor_akhir = ($total_soal > 0) ? round(($jumlah_benar / $total_soal) * 100) : 0;

// --- GAMIFIKASI: BERI POIN ---
// Logika: Poin = Skor / 10. Misal dapat 100 nilai, dapat 10 poin.
$poin_dapat = floor($skor_akhir / 10);

if ($poin_dapat > 0) {
    $conn->query("UPDATE users SET poin = poin + $poin_dapat WHERE id = $user_id");
    
    // Catat Log
    $log_msg = "Mengerjakan Kuis $modul_kode (Nilai: $skor_akhir)";
    $conn->query("INSERT INTO aktivitas_log (user_id, aktivitas, detail) VALUES ($user_id, 'Kuis', '$log_msg')");
}

?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Hasil Evaluasi</title>
  <link rel="stylesheet" href="assets/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include "components/header.php"; ?>

<section class="container page-content">
    <div class="glass card glossy" style="max-width: 600px; margin: 40px auto; text-align: center; padding: 40px;">
        
        <?php if ($skor_akhir >= 70): ?>
            <h1 style="font-size: 4rem; margin: 0; color: #28C76F;">LULUS</h1>
            <p class="muted">Kerja bagus! Kamu memahami materi ini dengan baik.</p>
        <?php else: ?>
            <h1 style="font-size: 4rem; margin: 0; color: #EA5455;">BELAJAR LAGI</h1>
            <p class="muted">Nilaimu masih di bawah standar. Jangan menyerah!</p>
        <?php endif; ?>

        <div style="margin: 30px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; padding: 20px 0;">
            <div style="font-size: 1.2rem;">Skor Kamu</div>
            <div style="font-size: 3.5rem; font-weight: 800; color: var(--primary);"><?= $skor_akhir ?></div>
            <div style="margin-top: 10px;">
                <span style="background: #FFF3CD; padding: 5px 10px; border-radius: 20px; font-weight: bold; color: #FF9F43;">
                    + <?= $poin_dapat ?> Poin Karakter
                </span>
            </div>
        </div>

        <div class="grid grid-2">
            <div>
                <strong>Benar</strong><br>
                <span style="color: #28C76F; font-size: 1.5rem; font-weight: bold;"><?= $jumlah_benar ?></span>
            </div>
            <div>
                <strong>Salah</strong><br>
                <span style="color: #EA5455; font-size: 1.5rem; font-weight: bold;"><?= $total_soal - $jumlah_benar ?></span>
            </div>
        </div>

        <div style="margin-top: 40px;">
            <a href="modul-detail.php?modul=<?= $modul_kode ?>" class="btn outline full">Kembali ke Materi</a>
            <a href="dashboard.php" class="btn primary full" style="margin-top: 10px;">Lihat Poin di Dashboard</a>
        </div>

    </div>
</section>
</body>
</html>