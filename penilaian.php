<?php
session_start();
require "config/db.php";

// Hanya Guru yang boleh akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru') {
    header("Location: index.php");
    exit;
}

// --- PROSES SIMPAN NILAI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_jawaban = $_POST['id_jawaban'];
    $nilai = (int) $_POST['nilai'];
    $id_siswa = $_POST['id_siswa'];

    // 1. Update Tabel Jawaban
    $stmt = $conn->prepare("UPDATE modul_jawaban SET nilai = ?, status_nilai = 'sudah' WHERE id = ?");
    $stmt->bind_param("ii", $nilai, $id_jawaban);
    $stmt->execute();

    // 2. Beri Poin ke Siswa (Misal: Nilai / 10)
    $poin_dapat = floor($nilai / 10);
    if ($poin_dapat > 0) {
        $conn->query("UPDATE users SET poin = poin + $poin_dapat WHERE id = $id_siswa");
        
        // 3. Catat Log
        $conn->query("INSERT INTO aktivitas_log (user_id, aktivitas, detail) VALUES ($id_siswa, 'Penilaian Essay', 'Guru memberi nilai $nilai ($poin_dapat Poin)')");
    }

    $msg = "Nilai berhasil disimpan!";
}

// Ambil jawaban yang BELUM dinilai
$query = $conn->query("
    SELECT mj.*, users.nama, mk.judul as judul_soal, mk.isi as soal 
    FROM modul_jawaban mj
    JOIN users ON mj.user_id = users.id
    JOIN modul_konten mk ON mj.konten_id = mk.id
    WHERE mj.status_nilai = 'belum'
    ORDER BY mj.waktu ASC
");
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Koreksi Jawaban</title>
  <link rel="stylesheet" href="assets/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include "components/header.php"; ?>

<section class="container page-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>📝 Koreksi Jawaban Essay</h2>
        <a href="index.php" class="btn ghost">Kembali</a>
    </div>

    <?php if(isset($msg)) echo "<div class='success-box'>$msg</div>"; ?>

    <?php if($query->num_rows == 0): ?>
        <div class="glass card glossy" style="text-align:center; padding:40px;">
            <h3>Kerja Bagus! 🎉</h3>
            <p class="muted">Semua jawaban essay sudah dinilai.</p>
        </div>
    <?php else: ?>

        <div class="grid">
            <?php while($row = $query->fetch_assoc()): ?>
                <div class="glass card">
                    <div style="margin-bottom:10px; border-bottom:1px solid #eee; padding-bottom:10px;">
                        <strong><?= htmlspecialchars($row['nama']) ?></strong>
                        <span class="muted" style="font-size:12px;"> • <?= date('d M H:i', strtotime($row['waktu'])) ?></span>
                    </div>

                    <div style="background:#f9f9f9; padding:10px; border-radius:8px; margin-bottom:10px;">
                        <small class="muted">Soal:</small><br>
                        <strong><?= htmlspecialchars($row['judul_soal']) ?></strong>
                        <p style="margin:5px 0 0 0; font-size:13px;"><?= nl2br(htmlspecialchars($row['soal'])) ?></p>
                    </div>

                    <div style="margin-bottom:15px;">
                        <small class="muted">Jawaban Siswa:</small>
                        <p style="background:#e3f2fd; padding:10px; border-radius:8px; margin-top:5px;">
                            <?= nl2br(htmlspecialchars($row['jawaban'])) ?>
                        </p>
                    </div>

                    <form method="POST" style="display:flex; gap:10px; align-items:center;">
                        <input type="hidden" name="id_jawaban" value="<?= $row['id'] ?>">
                        <input type="hidden" name="id_siswa" value="<?= $row['user_id'] ?>">
                        
                        <input type="number" name="nilai" min="0" max="100" placeholder="Skor (0-100)" required 
                               style="width:120px; padding:10px; border-radius:8px; border:1px solid #ccc;">
                        
                        <button class="btn primary">Simpan Nilai</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>

    <?php endif; ?>

</section>
</body>
</html>