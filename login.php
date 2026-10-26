<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
include "config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = $conn->prepare("SELECT id, nama, username, password, role, kelas FROM users WHERE username=? LIMIT 1");
    $sql->bind_param("s", $username);
    $sql->execute();
    $result = $sql->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['kelas'] = $user['kelas'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Password salah.";
        }
    } else {
        $error = "Akun tidak ditemukan.";
    }
}
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Masuk - RuPendKar</title> <meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/login.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body class="login-body">

<div class="login-box glass glossy">
    
    <div style="margin-bottom:20px;">
        <h1 style="font-size:32px; font-weight:800; color:#1E90FF; margin:0; letter-spacing:-1px;">RuPendKar</h1>
        <p style="color:#666; margin:5px 0 0 0; font-size:14px;">Ruang Pendidikan Karakter</p>
    </div>

    <?php if ($error): ?>
    <div class="error-box"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" required autofocus>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button class="btn primary full" type="submit">Masuk Sekarang</button>
    </form>

    <div class="login-footer muted">© 2025 RuPendKar Learning System</div>
    <p class="muted" style="margin-top:15px;">
    Belum punya akun?
    <a href="register.php" class="link">Daftar Siswa Baru</a>
    </p>
    <p class="muted" style="margin-top:0;">
        Lupa password? <a href="lupa-password.php" class="link">Pulihkan di sini</a>
    </p>

</div>
</body>
</html>