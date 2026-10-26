<?php
session_start();
include "config/db.php";

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $kelas = trim($_POST['kelas']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm']);

    if ($password !== $confirm) {
        $error = "Password konfirmasi tidak sama!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "Username sudah digunakan.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = $conn->prepare("INSERT INTO users (nama, username, password, kelas, role) VALUES (?, ?, ?, ?, 'siswa')");
            $sql->bind_param("ssss", $nama, $username, $hashed, $kelas);

            if ($sql->execute()) {
                $success = "Pendaftaran berhasil! Silakan login.";
            } else {
                $error = "Terjadi kesalahan: " . $conn->error;
            }
        }
    }
}
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Daftar - RuPendKar</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/login.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body class="login-body">

<div class="login-box glass glossy">

    <h2 class="login-title">Daftar RuPendKar</h2>
    <p class="muted" style="margin-top:-10px; margin-bottom:20px;">Bergabung ke Ruang Pendidikan Karakter</p>

    <?php if ($error): ?>
    <div class="error-box"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="success-box"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" required>
        </div>
        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="input-group">
            <label>Kelas</label>
            <input type="text" name="kelas" required>
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="input-group">
            <label>Konfirmasi Password</label>
            <input type="password" name="confirm" required>
        </div>
        <button class="btn primary full" type="submit">Daftar</button>
    </form>

    <p class="muted" style="margin-top:15px;">
        Sudah punya akun?
        <a href="login.php" class="link">Masuk di sini</a>
    </p>

</div>
</body>
</html>