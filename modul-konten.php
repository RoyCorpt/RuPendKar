<?php
session_start();
require "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Validasi parameter
if (!isset($_GET['modul']) || !isset($_GET['p'])) {
    header("Location: modul.php");
    exit;
}

$kode   = $_GET['modul'];
$urutan = (int) $_GET['p'];

// ============================
// 1. Ambil total jumlah materi
// ============================
$stmt_total = $conn->prepare("SELECT COUNT(*) AS total FROM modul_konten WHERE modul_kode = ?");
$stmt_total->bind_param("s", $kode);
$stmt_total->execute();
$res_total = $stmt_total->get_result()->fetch_assoc();
$total_materi = (int) ($res_total['total'] ?? 0);

// ============================
// 2. LOGIKA PERBAIKAN: Redirect jika materi tidak ada
// ============================
// Jika urutan < 1, kembalikan ke awal
if ($urutan < 1) {
    header("Location: modul-konten.php?modul=$kode&p=1");
    exit;
}

// Jika urutan melebihi total materi, anggap SELESAI dan kembalikan ke menu modul
if ($urutan > $total_materi) {
    header("Location: modul-detail.php?modul=" . urlencode($kode));
    exit;
}

// ============================
// 3. Ambil info modul
// ============================
$stmt = $conn->prepare("SELECT * FROM modul WHERE kode = ?");
$stmt->bind_param("s", $kode);
$stmt->execute();
$modul = $stmt->get_result()->fetch_assoc();

if (!$modul) {
    // Jika modulnya sendiri tidak ada, kembali ke daftar modul
    header("Location: modul.php");
    exit;
}

// ============================
// 4. Ambil materi berdasarkan urutan
// ============================
$stmt2 = $conn->prepare("SELECT * FROM modul_konten WHERE modul_kode = ? AND urutan = ?");
$stmt2->bind_param("si", $kode, $urutan);
$stmt2->execute();
$materi = $stmt2->get_result()->fetch_assoc();

// FAIL-SAFE: Jika data materi corrupt/kosong padahal hitungan total ada
if (!$materi) {
    header("Location: modul-detail.php?modul=" . urlencode($kode));
    exit;
}

// ============================
// 5. Update progres siswa
// ============================
// Cek status: jika ini halaman terakhir, tandai 'selesai', jika tidak 'proses'
$status_baru = ($urutan >= $total_materi) ? 'selesai' : 'proses';

$stmt4 = $conn->prepare("
    INSERT INTO modul_progress (user_id, modul_kode, konten_terakhir, status)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        konten_terakhir = VALUES(konten_terakhir),
        status = IF(status = 'selesai', 'selesai', VALUES(status))
");
// Status lama 'selesai' jangan ditimpa jadi 'proses' kalau siswa mengulang materi awal
$stmt4->bind_param("isis", $_SESSION['user_id'], $kode, $urutan, $status_baru);
$stmt4->execute();

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($materi['judul']) ?> — <?= htmlspecialchars($modul['judul']) ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>

<?php include "components/header.php"; ?>

<section class="section container">

    <h2><?= htmlspecialchars($modul['judul']) ?></h2>
    <p class="muted">Materi <?= $urutan ?> dari <?= $total_materi ?></p>

    <div class="glass card glossy" style="padding:20px; margin-top:20px;">

        <h3 style="margin-top:0;"><?= htmlspecialchars($materi['judul']) ?></h3>
        <p style="line-height:1.7;"><?= nl2br(htmlspecialchars($materi['isi'])) ?></p>

        <?php if (isset($materi['tipe']) && $materi['tipe'] === 'pertanyaan'): ?>

            <form action="simpan-jawaban.php" method="POST" style="margin-top:20px;">
                
                <input type="hidden" name="modul_kode" value="<?= htmlspecialchars($kode) ?>">
                <input type="hidden" name="konten_urutan" value="<?= $urutan ?>">
                <input type="hidden" name="konten_id" value="<?= (int)$materi['id'] ?>">

                <label style="font-weight:600;">Jawaban Anda:</label>
                <textarea 
                    name="jawaban" 
                    required 
                    placeholder="Tuliskan jawabanmu di sini..."
                    style="width:100%; margin-top:10px; padding:12px; border-radius:10px; border:1px solid rgba(0,0,0,0.1); font-size:15px; resize:vertical;"
                ><?php
                    // Tampilkan jawaban lama jika ada
                    $stmtJ = $conn->prepare("SELECT jawaban FROM modul_jawaban WHERE user_id=? AND konten_id=?");
                    $stmtJ->bind_param("ii", $_SESSION['user_id'], $materi['id']);
                    $stmtJ->execute();
                    $rJ = $stmtJ->get_result()->fetch_assoc();
                    echo isset($rJ['jawaban']) ? htmlspecialchars($rJ['jawaban']) : '';
                ?></textarea>

                <button class="btn primary" style="margin-top:12px;">Simpan Jawaban</button>
            </form>

        <?php endif; ?>

    </div>

    <div style="display:flex; justify-content:space-between; margin-top:25px;">

        <?php if ($urutan > 1): ?>
            <a href="modul-konten.php?modul=<?= urlencode($kode) ?>&p=<?= $urutan - 1 ?>" class="btn outline small">
                ← Sebelumnya
            </a>
        <?php else: ?>
            <span></span> <?php endif; ?>

        <?php if ($urutan < $total_materi): ?>
            <a href="modul-konten.php?modul=<?= urlencode($kode) ?>&p=<?= $urutan + 1 ?>" class="btn primary small">
                Berikutnya →
            </a>
        <?php else: ?>
            <a href="modul-detail.php?modul=<?= urlencode($kode) ?>" class="btn success small">
                Selesai & Kembali ✓
            </a>
        <?php endif; ?>

    </div>

</section>

<script src="assets/app.js"></script>
</body>
</html>