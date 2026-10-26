<?php
session_start();
require "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_SESSION['user_id'];
$msg = "";
$msg_type = ""; // success atau error

// --- PROSES UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nama_baru = trim($_POST['nama']);
    $pass_lama = $_POST['password_lama'];
    $pass_baru = $_POST['password_baru'];

    // 1. Ambil data user saat ini untuk cek password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();

    // 2. Validasi Password Lama (Wajib diisi untuk perubahan apapun demi keamanan)
    if (password_verify($pass_lama, $current['password'])) {
        
        // Update Nama
        if (!empty($nama_baru)) {
            $upd = $conn->prepare("UPDATE users SET nama = ? WHERE id = ?");
            $upd->bind_param("si", $nama_baru, $id);
            $upd->execute();
            $_SESSION['nama'] = $nama_baru; // Update session juga
        }

        // Update Password Baru (Jika diisi)
        if (!empty($pass_baru)) {
            if (strlen($pass_baru) < 6) {
                $msg = "Password baru minimal 6 karakter.";
                $msg_type = "error";
            } else {
                $hash_baru = password_hash($pass_baru, PASSWORD_DEFAULT);
                $upd_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd_pass->bind_param("si", $hash_baru, $id);
                $upd_pass->execute();
                $msg = "Profil dan Password berhasil diperbarui!";
                $msg_type = "success";
            }
        } else {
            $msg = "Profil berhasil diperbarui!";
            $msg_type = "success";
        }

    } else {
        $msg = "Password lama salah. Perubahan ditolak.";
        $msg_type = "error";
    }
}

// Ambil data terbaru untuk ditampilkan di form
$q = $conn->query("SELECT * FROM users WHERE id = $id");
$u = $q->fetch_assoc();
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Pengaturan Akun</title>
  <link rel="stylesheet" href="assets/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <style>
      .form-group { margin-bottom: 15px; }
      .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
      .form-group input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; }
      .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
      .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
      .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
  </style>
</head>
<body>

<?php include "components/header.php"; ?>

<section class="container page-content">
    
    <div style="max-width: 500px; margin: 0 auto;">
        <h2 style="text-align: center;">Pengaturan Akun</h2>
        <p class="muted" style="text-align: center;">Perbarui informasi profil dan keamananmu.</p>

        <div class="glass card glossy" style="margin-top: 25px;">
            
            <?php if($msg): ?>
                <div class="alert <?= $msg_type ?>"><?= $msg ?></div>
            <?php endif; ?>

            <form method="POST">
                
                <div class="form-group">
                    <label>Username (Tidak bisa diubah)</label>
                    <input type="text" value="<?= htmlspecialchars($u['username']) ?>" disabled style="background: #f0f0f0; color: #888;">
                </div>

                <div class="form-group">
                    <label>Kelas</label>
                    <input type="text" value="<?= htmlspecialchars($u['kelas']) ?>" disabled style="background: #f0f0f0; color: #888;">
                </div>

                <hr style="border: 0; border-top: 1px dashed #ddd; margin: 20px 0;">

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($u['nama']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Ganti Password Baru (Opsional)</label>
                    <input type="password" name="password_baru" placeholder="Kosongkan jika tidak ingin mengganti">
                    <small class="muted">Minimal 6 karakter.</small>
                </div>

                <div class="form-group" style="margin-top: 20px; background: #fff3cd; padding: 10px; border-radius: 8px;">
                    <label style="color: #856404;">Konfirmasi Password Lama</label>
                    <input type="password" name="password_lama" required placeholder="Masukkan password saat ini untuk menyimpan">
                </div>

                <button class="btn primary full large">Simpan Perubahan</button>
            </form>

        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" class="btn ghost">Kembali ke Dashboard</a>
        </div>

    </div>

</section>

</body>
</html>