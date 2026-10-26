<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
include "config/db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $tanggal_buat = $_POST['tanggal_buat']; // Mengambil format YYYY-MM-DD dari input
    $password_baru = trim($_POST['password_baru']);
    $confirm_password = trim($_POST['confirm_password']);

    if (strlen($password_baru) < 6) {
        $error = "Password baru minimal 6 karakter.";
    } elseif ($password_baru !== $confirm_password) {
        $error = "Konfirmasi password baru tidak sama!";
    } else {
        // Mencocokkan username dan tanggal pembuatan akun (mengabaikan waktu jam:menit:detik)
        $sql = $conn->prepare("SELECT id FROM users WHERE username = ? AND DATE(dibuat_pada) = ? LIMIT 1");
        $sql->bind_param("ss", $username, $tanggal_buat);
        $sql->execute();
        $result = $sql->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $id_user = $user['id'];

            // Hash password baru sebelum disimpan
            $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);

            // Update password baru ke database
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $hashed_password, $id_user);

            if ($update->execute()) {
                $success = "Password berhasil diperbarui! Silakan masuk.";
            } else {
                $error = "Gagal memperbarui password, silakan coba lagi.";
            }
        } else {
            $error = "Username atau Tanggal Pembuatan Akun salah.";
        }
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pulihkan Sandi - RuPendKar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body class="login-body">

<div class="login-box glass glossy">
    
    <div style="margin-bottom:20px;">
        <h1 style="font-size:28px; font-weight:800; color:#1E90FF; margin:0; letter-spacing:-1px;">Pulihkan Sandi</h1>
        <p style="color:#666; margin:5px 0 0 0; font-size:13px;">Gunakan detail pendaftaran untuk mereset password</p>
    </div>

    <?php if ($error): ?>
        <div class="error-box" style="background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-box"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label>Username Akun</label>
            <input type="text" name="username" required autofocus placeholder="Masukkan username">
        </div>

        <div class="input-group">
            <label>Tanggal Membuat Akun</label>
            <input type="date" name="tanggal_buat" required style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(0,0,0,0.1); margin-top:6px; font-size:15px;">
        </div>

        <div class="input-group">
            <label>Password Baru</label>
            <input type="password" name="password_baru" required placeholder="Minimal 6 karakter">
        </div>

        <div class="input-group">
            <label>Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" required placeholder="Ulangi password baru">
        </div>

        <button class="btn primary full" type="submit">Ubah Password</button>
    </form>

    <p class="muted" style="margin-top:20px;">
        Ingat password Anda? 
        <a href="login.php" class="link">Masuk di sini</a>
    </p>

</div>
</body>
</html>