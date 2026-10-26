<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

require "config/db.php";
include "components/header.php";

$role = $_SESSION['role'];
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>RuPendKar - Beranda</title>
  <link rel="stylesheet" href="assets/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php if($role === 'guru'): ?>

    <section class="hero" style="padding-bottom:10px;">
      <div class="container hero-inner">
        <div class="hero-left glass" style="min-height: auto; padding: 30px;">
          <h2>Panel Guru</h2>
          <p class="lead" style="margin-bottom:0;">
            Selamat datang, Bapak/Ibu <strong><?= $_SESSION['nama'] ?></strong>. 
            Silakan kelola pembelajaran melalui menu di bawah ini.
          </p>
        </div>
      </div>
    </section>

    <section class="section container">
        <div class="grid grid-2">
            
            <div class="glass card glossy">
                <h3 style="color:var(--primary);">📝 Koreksi Essay</h3>
                <p class="muted">Periksa jawaban essay siswa dari modul pembelajaran.</p>
                <?php
                    $cek_nilai = $conn->query("SELECT COUNT(*) as total FROM modul_jawaban WHERE status_nilai='belum'");
                    $count_nilai = $cek_nilai->fetch_assoc()['total'];
                ?>
                <?php if($count_nilai > 0): ?>
                    <div style="margin-bottom:10px; color:#EA5455; font-weight:bold;">
                        <?= $count_nilai ?> Jawaban menunggu dinilai!
                    </div>
                <?php else: ?>
                    <div style="margin-bottom:10px; color:#28C76F;">Semua sudah dinilai.</div>
                <?php endif; ?>
                <a href="penilaian.php" class="btn primary full">Buka Penilaian</a>
            </div>

            <div class="glass card glossy">
                <h3 style="color:var(--primary);">⚖️ Musyawarah</h3>
                <p class="muted">Setujui topik diskusi siswa dan pantau jalannya debat.</p>
                <?php
                    // Cek topik yang menunggu persetujuan
                    $cek_topik = $conn->query("SELECT COUNT(*) as total FROM musyawarah_topik WHERE status='menunggu'");
                    $count_topik = $cek_topik->fetch_assoc()['total'];
                ?>
                <?php if($count_topik > 0): ?>
                    <div style="margin-bottom:10px; color:#EA5455; font-weight:bold;">
                        <?= $count_topik ?> Topik menunggu persetujuan!
                    </div>
                <?php else: ?>
                    <div style="margin-bottom:10px; color:#28C76F;">Tidak ada topik tertunda.</div>
                <?php endif; ?>
                <a href="musyawarah.php" class="btn primary full">Kelola Topik</a>
            </div>

            <div class="glass card glossy">
                <h3 style="color:var(--primary);">📤 Bank Materi</h3>
                <p class="muted">Upload video, PDF, atau artikel untuk bahan ajar siswa.</p>
                <a href="materi.php" class="btn outline full">Kelola Materi</a>
            </div>

            <div class="glass card glossy">
                <h3 style="color:var(--primary);">📢 Pantau Forum</h3>
                <p class="muted">Moderasi diskusi siswa di ruang Gotong Royong.</p>
                <a href="gotong.php" class="btn outline full">Buka Forum</a>
            </div>

            <div class="glass card glossy">
                <h3 style="color:var(--primary);">📚 Kelola Modul</h3>
                <p class="muted">Hapus materi atau soal essay bawaan yang tidak terpakai.</p>
                <a href="kelola-modul.php" class="btn outline full">Atur Isi Modul</a>
            </div>

            <div class="glass card glossy">
                <h3 style="color:var(--primary);">👥 Data Siswa</h3>
                <p class="muted">Lihat statistik poin, aktivitas login, dan perkembangan kelas.</p>
                <a href="dashboard.php" class="btn outline full">Lihat Dashboard</a>
            </div>

        </div>
    </section>

<?php else: ?>

    <section class="hero">
      <div class="container hero-inner">
        <div class="hero-left glass">
            <h2>Latih Karakter Pancasila</h2>
            <p class="lead">Belajar gotong royong, musyawarah, dan etika digital.</p>
            <div class="actions">
                <a href="modul.php" class="btn primary large">Mulai Belajar</a>
                <a href="musyawarah.php" class="btn outline large">Masuk Musyawarah</a>
            </div>
        </div>
        <div class="hero-right">
             <div class="card glass glossy" style="width:100%;">
                <div class="card-title">Statistik Kamu</div>
                <?php
                   $id_me = $_SESSION['user_id'];
                   $me = $conn->query("SELECT poin FROM users WHERE id=$id_me")->fetch_assoc();
                   $prog = $conn->query("SELECT COUNT(*) as t FROM modul_progress WHERE user_id=$id_me AND status='selesai'")->fetch_assoc();
                ?>
                <div class="small-stats">
                  <div><div class="stat-num"><?= $me['poin'] ?></div><div class="stat-label">Poin</div></div>
                  <div><div class="stat-num"><?= $prog['t'] ?></div><div class="stat-label">Modul</div></div>
                </div>
                <div class="card-cta" style="margin-top:15px;">
                  <a href="dashboard.php" class="btn small full">Lihat Detail</a>
                </div>
            </div>
        </div>
      </div>
    </section>

    <section class="section container">
        <div class="grid grid-3">
            <a href="modul.php" style="text-decoration:none;">
                <div class="module-card glass glossy" style="padding:20px; text-align:center;">
                    <i class="fa-solid fa-book" style="font-size:40px; color:var(--primary); margin-bottom:10px;"></i>
                    <h4>Modul Belajar</h4>
                    <p class="muted">Baca materi & kerjakan kuis.</p>
                </div>
            </a>
            <a href="musyawarah.php" style="text-decoration:none;">
                <div class="module-card glass glossy" style="padding:20px; text-align:center;">
                    <i class="fa-solid fa-comments" style="font-size:40px; color:#28C76F; margin-bottom:10px;"></i>
                    <h4>Musyawarah</h4>
                    <p class="muted">Diskusi real-time.</p>
                </div>
            </a>
            <a href="gotong.php" style="text-decoration:none;">
                <div class="module-card glass glossy" style="padding:20px; text-align:center;">
                    <i class="fa-solid fa-handshake" style="font-size:40px; color:#FF9F43; margin-bottom:10px;"></i>
                    <h4>Gotong Royong</h4>
                    <p class="muted">Forum tanya jawab.</p>
                </div>
            </a>
        </div>
    </section>

<?php endif; ?>

<footer class="footer glass">
  <div class="container footer-inner">
    <strong>RuPendKar</strong>
    <span class="muted">© 2025</span>
  </div>
</footer>

<script src="assets/app.js"></script>
</body>
</html>