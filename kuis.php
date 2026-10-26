<?php
session_start();
require "config/db.php";

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (!isset($_GET['modul'])) { header("Location: modul.php"); exit; }

$kode_modul = $_GET['modul'];

// 1. Ambil ID Modul berdasarkan Kode
$stmt_m = $conn->prepare("SELECT id, judul FROM modul WHERE kode = ?");
$stmt_m->bind_param("s", $kode_modul);
$stmt_m->execute();
$modul = $stmt_m->get_result()->fetch_assoc();

if (!$modul) { die("Modul tidak ditemukan."); }

// 2. Ambil Soal Kuis
$modul_id = $modul['id'];
$query_soal = $conn->query("SELECT * FROM kuis WHERE modul_id = $modul_id ORDER BY RAND()"); // Soal diacak
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Kuis: <?= htmlspecialchars($modul['judul']) ?></title>
  <link rel="stylesheet" href="assets/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <style>
      .soal-box { margin-bottom: 30px; border-bottom: 1px dashed #ccc; padding-bottom: 20px; }
      .opsi-label { 
          display: block; padding: 10px 15px; margin: 5px 0; 
          border: 1px solid #ddd; border-radius: 8px; cursor: pointer; transition: .2s; 
      }
      .opsi-label:hover { background: #f0f8ff; border-color: var(--primary); }
      input[type="radio"] { margin-right: 10px; transform: scale(1.2); }
      input[type="radio"]:checked + span { font-weight: bold; color: var(--primary); }
  </style>
</head>
<body>
<?php include "components/header.php"; ?>

<section class="container page-content">
    <div class="glass card glossy" style="max-width: 800px; margin: 0 auto;">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <h2>Evaluasi Pembelajaran</h2>
            <p class="muted">Topik: <strong><?= htmlspecialchars($modul['judul']) ?></strong></p>
        </div>

        <?php if ($query_soal->num_rows == 0): ?>
            <div style="text-align:center; padding:40px;">
                <p>Belum ada soal kuis untuk modul ini.</p>
                <a href="modul-detail.php?modul=<?= $kode_modul ?>" class="btn outline">Kembali ke Materi</a>
            </div>
        <?php else: ?>

            <form action="kuis-nilai.php" method="POST">
                <input type="hidden" name="modul_kode" value="<?= $kode_modul ?>">
                <input type="hidden" name="modul_id" value="<?= $modul_id ?>">

                <?php $no = 1; while($soal = $query_soal->fetch_assoc()): ?>
                    <div class="soal-box">
                        <p style="font-weight: 600; font-size: 1.1rem;">
                            <?= $no++ ?>. <?= htmlspecialchars($soal['pertanyaan']) ?>
                        </p>
                        
                        <label class="opsi-label">
                            <input type="radio" name="jawaban[<?= $soal['id'] ?>]" value="A" required> 
                            <span><?= htmlspecialchars($soal['opsi_a']) ?></span>
                        </label>
                        <label class="opsi-label">
                            <input type="radio" name="jawaban[<?= $soal['id'] ?>]" value="B"> 
                            <span><?= htmlspecialchars($soal['opsi_b']) ?></span>
                        </label>
                        <label class="opsi-label">
                            <input type="radio" name="jawaban[<?= $soal['id'] ?>]" value="C"> 
                            <span><?= htmlspecialchars($soal['opsi_c']) ?></span>
                        </label>
                        <label class="opsi-label">
                            <input type="radio" name="jawaban[<?= $soal['id'] ?>]" value="D"> 
                            <span><?= htmlspecialchars($soal['opsi_d']) ?></span>
                        </label>
                    </div>
                <?php endwhile; ?>

                <button type="submit" class="btn primary full large">Kirim Jawaban & Lihat Nilai</button>
            </form>

        <?php endif; ?>

    </div>
</section>

</body>
</html>