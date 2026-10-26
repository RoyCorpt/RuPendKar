<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

require "config/db.php";
include "components/header.php";

$id_user = $_SESSION['user_id'];
$role = $_SESSION['role'];
$kelas_saya = $_SESSION['kelas']; // Guru melihat kelas yang dia ajar, Siswa melihat kelasnya sendiri

// --- LOGIKA UNTUK GURU ---
if ($role == 'guru') {
    // 1. Hitung Total Siswa di kelas ini
    $q_total = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='siswa' AND kelas='$kelas_saya'");
    $total_siswa = $q_total->fetch_assoc()['total'];

    // 2. Ambil daftar siswa
    $q_siswa = $conn->query("
        SELECT id, nama, username, poin, terakhir_aktif 
        FROM users 
        WHERE role='siswa' AND kelas='$kelas_saya' 
        ORDER BY nama ASC
    ");
} 

// --- LOGIKA UNTUK SISWA ---
else {
    // 1. Ambil data diri terbaru (Poin)
    $stmt_me = $conn->prepare("SELECT poin FROM users WHERE id = ?");
    $stmt_me->bind_param("i", $id_user);
    $stmt_me->execute();
    $me = $stmt_me->get_result()->fetch_assoc();
    $poin = $me['poin'];

    // 2. Tentukan Badge berdasarkan Poin
    $badge = "Pemula";
    $badge_color = "#6c757d"; // abu-abu
    if ($poin >= 50) { $badge = "Aktif"; $badge_color = "#1E90FF"; } // biru
    if ($poin >= 100) { $badge = "Bintang Kelas"; $badge_color = "#28C76F"; } // hijau
    if ($poin >= 200) { $badge = "Teladan Digital"; $badge_color = "#FF9F43"; } // oranye

    // 3. Hitung Modul Selesai
    $q_prog = $conn->query("SELECT COUNT(*) as total FROM modul_progress WHERE user_id=$id_user AND status='selesai'");
    $modul_selesai = $q_prog->fetch_assoc()['total'];

    // 4. Ambil Riwayat Aktivitas
    $q_log = $conn->query("SELECT * FROM aktivitas_log WHERE user_id=$id_user ORDER BY waktu DESC LIMIT 5");
}
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard <?= ucfirst($role) ?></title>
  <link rel="stylesheet" href="assets/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
      .stat-card { text-align:center; padding:20px; }
      .stat-num { font-size: 32px; font-weight:700; color:var(--primary); }
      .stat-label { font-size:14px; color:var(--muted); text-transform:uppercase; letter-spacing:1px; margin-top:5px; }
      
      .badge-display {
          display:inline-block; padding:8px 16px; border-radius:50px; 
          color:#fff; font-weight:700; font-size:14px; box-shadow:0 4px 10px rgba(0,0,0,0.1);
      }

      /* Table Styles */
      .table-responsive { overflow-x: auto; }
      .nice-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
      .nice-table th { text-align: left; padding: 12px; color: var(--muted); font-size: 13px; border-bottom: 2px solid #eee; }
      .nice-table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
      .status-dot { height: 10px; width: 10px; background-color: #bbb; border-radius: 50%; display: inline-block; margin-right: 5px; }
      .status-dot.online { background-color: #28C76F; }
  </style>
</head>

<body>

  <main class="container page-content">
  
  <div style="margin-bottom:30px;">
      <h2>Dashboard <?= ucfirst($role) ?></h2>
      <p class="muted">Selamat datang kembali, <strong><?= $_SESSION['nama'] ?></strong> (Kelas <?= $kelas_saya ?>).</p>
  </div>

  <?php if ($role == 'guru'): ?>
    
    <div class="grid grid-2">
        <div class="glass card glossy stat-card">
            <div class="stat-num"><?= $total_siswa ?></div>
            <div class="stat-label">Total Siswa</div>
        </div>
        <div class="glass card glossy stat-card">
            <div class="stat-num"><?= $kelas_saya ?></div>
            <div class="stat-label">Kode Kelas</div>
        </div>
    </div>

    <div class="glass card" style="margin-top:25px;">
        <h3>📋 Daftar Siswa & Aktivitas</h3>
        <div class="table-responsive">
            <table class="nice-table">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Username</th>
                        <th>Total Poin</th>
                        <th>Status Terakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($s = $q_siswa->fetch_assoc()): ?>
                        <?php 
                            // Cek online (aktif < 5 menit lalu)
                            $is_online = false;
                            if ($s['terakhir_aktif']) {
                                $last_active = strtotime($s['terakhir_aktif']);
                                if (time() - $last_active < 300) $is_online = true;
                            }
                        ?>
                        <tr>
                            <td>
                                <span class="status-dot <?= $is_online ? 'online':'' ?>"></span>
                                <strong><?= htmlspecialchars($s['nama']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($s['username']) ?></td>
                            <td>
                                <span style="font-weight:bold; color:var(--primary);"><?= $s['poin'] ?> Poin</span>
                            </td>
                            <td class="muted">
                                <?= $s['terakhir_aktif'] ? date('d M H:i', strtotime($s['terakhir_aktif'])) : 'Belum aktif' ?>
                            </td>
                            <td>
                                <a href="#" class="btn small outline">Detail</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php if ($q_siswa->num_rows == 0) echo "<p class='muted' style='padding:20px; text-align:center;'>Belum ada siswa di kelas ini.</p>"; ?>
        </div>
    </div>

  <?php else: ?>

    <div class="dash-grid fade">
        
        <div class="glass card glossy stat-card">
            <div class="stat-num"><?= $poin ?></div>
            <div class="stat-label">Total Poin</div>
        </div>

        <div class="glass card glossy stat-card">
            <div style="margin-bottom:10px;">
                <span class="badge-display" style="background:<?= $badge_color ?>">
                    <?= $badge ?>
                </span>
            </div>
            <div class="stat-label">Level Karakter</div>
        </div>

        <div class="glass card glossy stat-card">
            <div class="stat-num"><?= $modul_selesai ?></div>
            <div class="stat-label">Modul Selesai</div>
        </div>
    </div>

    <div class="glass card" style="margin-top:30px;">
        <h3>⏳ Riwayat Aktivitas Kamu</h3>
        <ul class="nice-list" style="margin-top:15px;">
            <?php while($log = $q_log->fetch_assoc()): ?>
                <li style="border-bottom:1px solid rgba(0,0,0,0.05); padding-bottom:10px; margin-bottom:10px;">
                    <div style="display:flex; justify-content:space-between;">
                        <strong><?= htmlspecialchars($log['aktivitas']) ?></strong>
                        <span class="muted" style="font-size:12px;"><?= date('d M H:i', strtotime($log['waktu'])) ?></span>
                    </div>
                    <div class="muted" style="font-size:13px; margin-top:4px;">
                        <?= htmlspecialchars($log['detail']) ?>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
        <?php if ($q_log->num_rows == 0): ?>
            <p class="muted" style="text-align:center; padding:20px;">Belum ada aktivitas tercatat.</p>
        <?php endif; ?>
    </div>

  <?php endif; ?>

  </main>

  <footer class="footer glass">
    <div class="container footer-inner">
      <strong>RuPendKar</strong>
      <span class="muted">© 2025</span>
    </div>
  </footer>

  <script src="assets/app.js"></script>
</body>
</html>