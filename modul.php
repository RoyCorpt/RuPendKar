<?php
session_start();
require "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$modul = $conn->query("SELECT * FROM modul ORDER BY id ASC");
?>

<?php include "components/header.php"; ?>
<link rel="stylesheet" href="assets/styles.css">

<section class="section container">
    <h2>Modul Pembelajaran</h2>
    <p class="muted">Pilih modul untuk mulai belajar nilai karakter.</p>

    <div class="grid grid-3">

        <?php while($m = $modul->fetch_assoc()): ?>
        <div class="module-card glass glossy">

            <img src="<?= $m['gambar'] ?>">

            <div class="module-body">
                <a href="modul-detail.php?modul=<?= $m['kode'] ?>" class="module-title-link">
                    <?= $m['judul'] ?>
                </a>
                <p class="muted"><?= $m['deskripsi'] ?></p>
            </div>

        </div>
        <?php endwhile; ?>

    </div>
</section>
