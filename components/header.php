<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="header glass">
  <div class="container header-inner">

      <div class="logo">
        <div class="logo-mark" style="background: linear-gradient(135deg, #FF9F43, #FFC107);">RPK</div>
        <div>
          <h1 style="font-size:18px; margin:0; font-weight:800; color:var(--primary); letter-spacing:-0.5px;">RuPendKar</h1>
          <p style="font-size:10px; margin:0; color:#666; font-weight:600; text-transform:uppercase; letter-spacing:1px;">Ruang Pendidikan Karakter</p>
        </div>
      </div>

      <button class="hamburger" onclick="document.getElementById('mobileNav').classList.toggle('open')">
        <span></span><span></span><span></span>
      </button>

      <nav class="nav">
        <a href="index.php">Beranda</a>
        <a href="dashboard.php">Dashboard</a>

        <?php if(isset($_SESSION['user_id'])): ?>
            
            <a href="profil.php" class="btn small ghost" style="display:flex; align-items:center; gap:8px;">
                <span class="dot online" style="margin:0;"></span>
                <?= htmlspecialchars(explode(' ', $_SESSION['nama'])[0]) ?>
            </a>

            <a href="auth/logout.php" class="btn small primary">Keluar</a>

        <?php else: ?>
            <a href="login.php" class="btn primary small">Masuk</a>
        <?php endif; ?>
      </nav>

  </div>
</header>