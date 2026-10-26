<?php
session_start();
require "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil kode modul dari URL
if (!isset($_GET['modul'])) {
    header("Location: modul.php");
    exit;
}

$kode = $_GET['modul'];

// 1. Ambil Data Modul
$stmt = $conn->prepare("SELECT * FROM modul WHERE kode = ?");
$stmt->bind_param("s", $kode);
$stmt->execute();
$modul = $stmt->get_result()->fetch_assoc();

if (!$modul) {
    echo "<h2>Modul tidak ditemukan.</h2>";
    exit;
}

// 2. Ambil Progress User
$stmt2 = $conn->prepare("SELECT * FROM modul_progress WHERE user_id = ? AND modul_kode = ?");
$stmt2->bind_param("is", $_SESSION['user_id'], $kode);
$stmt2->execute();
$progress = $stmt2->get_result()->fetch_assoc();

// 3. Ambil Daftar Konten Modul
$stmt3 = $conn->prepare("SELECT * FROM modul_konten WHERE modul_kode = ? ORDER BY urutan ASC");
$stmt3->bind_param("s", $kode);
$stmt3->execute();
$konten = $stmt3->get_result();

// 4. Hitung Total Materi (LOGIKA BARU UNTUK CEGAH ERROR)
$total_materi = $konten->num_rows;
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($modul['judul']) ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>

<?php include "components/header.php"; ?>

<section class="section container">

    <div class="glass card glossy" style="padding:20px;">
        <img src="<?= $modul['gambar'] ?>" style="width:100%; max-height:300px; object-fit:cover; border-radius:12px; margin-bottom:15px;">

        <h2><?= htmlspecialchars($modul['judul']) ?></h2>
        <p class="muted"><?= htmlspecialchars($modul['deskripsi']) ?></p>

        <div style="margin-top:20px;">
            <strong>Progress Belajar:</strong>
            <?php
                // Hitung persentase progress
                $persen = 0;
                $posisi = 0;
                $status_selesai = false;

                if ($progress) {
                    $posisi = $progress['konten_terakhir'];
                    if ($progress['status'] == 'selesai' || $posisi >= $total_materi) {
                        $persen = 100;
                        $status_selesai = true;
                    } else {
                        // Rumus: (Halaman saat ini / Total) * 100
                        $persen = ($total_materi > 0) ? round(($posisi / $total_materi) * 100) : 0;
                    }
                }
            ?>
            <div class="progress-bar">
                <div class="progress" style="width: <?= $persen ?>%"></div>
            </div>
            <small class="muted"><?= $persen ?>% Selesai</small>
        </div>

        <div style="margin-top:25px;">
            
            <?php if (!$progress): ?>
                <a href="modul-konten.php?modul=<?= $kode ?>&p=1" class="btn primary large full">
                    Mulai Belajar
                </a>

            <?php elseif ($status_selesai): ?>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <button class="btn success large full" disabled style="opacity:0.8; cursor:default;">
                        Materi Sudah Selesai ✓
                    </button>
                    
                    <a href="kuis.php?modul=<?= $kode ?>" class="btn primary large full" style="background: linear-gradient(90deg, #FF9F43, #FFC107); border:none;">
                        📝 Kerjakan Evaluasi / Kuis
                    </a>

                    <a href="modul-konten.php?modul=<?= $kode ?>&p=1" class="btn ghost small full">
                        Baca Ulang Materi
                    </a>
                </div>

            <?php else: ?>
                <?php 
                    $next_page = $posisi + 1;
                    // Cegah link ke halaman yang tidak ada
                    if ($next_page > $total_materi) {
                        $next_page = $total_materi; 
                    }
                ?>
                <a href="modul-konten.php?modul=<?= $kode ?>&p=<?= $next_page ?>" class="btn primary large full">
                    Lanjutkan Belajar (Halaman <?= $next_page ?>)
                </a>

            <?php endif; ?>

        </div>

    </div>

    <h3 style="margin-top:40px;">Daftar Isi Materi</h3>

    <div class="glass card glossy" style="padding:15px;">
        <?php if ($total_materi == 0): ?>
            <p class="muted">Konten modul belum ditambahkan oleh Guru.</p>
        <?php else: ?>
            <ul class="nice-list">
                <?php 
                // Reset pointer data konten ke awal untuk looping
                $konten->data_seek(0); 
                while($k = $konten->fetch_assoc()): 
                    // Tandai materi yang sedang/sudah dibaca
                    $is_active = ($progress && $k['urutan'] == $progress['konten_terakhir']);
                    $is_done = ($progress && $k['urutan'] < $progress['konten_terakhir']) || $status_selesai;
                    $icon = $is_done ? "✅ " : ($is_active ? "👉 " : "📄 ");
                    $style = $is_active ? "color:var(--primary); font-weight:bold;" : "";
                ?>
                    <li>
                        <a href="modul-konten.php?modul=<?= $kode ?>&p=<?= $k['urutan'] ?>" style="<?= $style ?>">
                            <?= $icon ?> <?= $k['urutan'] ?>. <?= htmlspecialchars($k['judul']) ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>
    </div>

</section>

<script src="assets/app.js"></script>
</body>
</html>