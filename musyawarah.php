<?php
session_start();
require "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$role = $_SESSION['role'];

// LOGIKA QUERY:
// 1. Guru: Lihat SEMUA topik (baik aktif maupun menunggu).
// 2. Siswa: Lihat topik AKTIF + topik buatan SENDIRI (meski masih menunggu).
if ($role === 'guru') {
    $query = $conn->query("
        SELECT mt.*, users.nama as pembuat 
        FROM musyawarah_topik mt
        LEFT JOIN users ON mt.dibuat_oleh = users.id
        ORDER BY mt.status DESC, mt.dibuat_pada DESC
    ");
} else {
    $query = $conn->query("
        SELECT mt.*, users.nama as pembuat 
        FROM musyawarah_topik mt
        LEFT JOIN users ON mt.dibuat_oleh = users.id
        WHERE mt.status = 'aktif' OR mt.dibuat_oleh = $id_user
        ORDER BY mt.status DESC, mt.dibuat_pada DESC
    ");
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Lobi Musyawarah</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        /* Style untuk Badge Status */
        .badge-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-active { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        /* Modal Style */
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; padding: 25px; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: slideDown 0.3s; }
        @keyframes slideDown { from {transform: translateY(-50px); opacity: 0;} to {transform: translateY(0); opacity: 1;} }
    </style>
</head>

<body>

<?php include "components/header.php"; ?>

<section class="section container">

    <div class="glass card glossy" style="padding:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <div>
                <h2>Lobi Musyawarah</h2>
                <p class="muted">Pilih topik diskusi atau ajukan topik baru.</p>
            </div>
            <button onclick="openModal()" class="btn primary">
                + Topik Baru
            </button>
        </div>

        <div style="margin-top:20px;">
            <?php if ($query->num_rows == 0): ?>
                <p class="muted" style="text-align:center; padding:20px;">Belum ada topik musyawarah.</p>
            <?php else: ?>
                <ul class="nice-list">
                    <?php while($topik = $query->fetch_assoc()): ?>
                        <?php 
                            $is_pending = ($topik['status'] === 'menunggu');
                            // Tentukan link: Jika pending, tidak bisa diklik (kecuali guru mau ngecek, tapi di sini kita disable dulu buat siswa)
                            $link = "simulasi.php?topik=" . $topik['kode'];
                            if ($is_pending && $role !== 'guru') $link = "#"; 
                        ?>
                        
                        <li style="margin-bottom: 15px; border-bottom: 1px dashed rgba(0,0,0,0.1); padding-bottom: 15px; display:block;">
                            
                            <div style="display:flex; justify-content:space-between; align-items:start;">
                                <div style="flex:1;">
                                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:5px;">
                                        <?php if($is_pending): ?>
                                            <span class="badge-status badge-pending">Menunggu Persetujuan</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-active">Aktif</span>
                                        <?php endif; ?>
                                        <small class="muted">Oleh: <?= htmlspecialchars($topik['pembuat'] ?? 'Guru') ?></small>
                                    </div>

                                    <a href="<?= $link ?>" style="text-decoration: none; color: inherit;">
                                        <strong style="font-size: 1.2rem; color: var(--primary);"><?= htmlspecialchars($topik['judul']) ?></strong>
                                    </a>
                                    
                                    <p class="muted" style="margin: 5px 0 0 0; font-size: 0.95rem;">
                                        <?= htmlspecialchars($topik['deskripsi']) ?>
                                    </p>
                                </div>

                                <?php if($role === 'guru' && $is_pending): ?>
                                    <div style="display:flex; gap:5px; flex-direction:column;">
                                        <a href="musyawarah-aksi.php?act=approve&id=<?= $topik['id'] ?>" class="btn small success" style="background:#28C76F; border:none; color:white; text-align:center;">Setujui ✓</a>
                                        <a href="musyawarah-aksi.php?act=reject&id=<?= $topik['id'] ?>" class="btn small ghost" style="color:red; border-color:red; text-align:center;">Tolak ✕</a>
                                    </div>
                                <?php elseif(!$is_pending): ?>
                                    <a href="<?= $link ?>" class="btn small outline">Masuk →</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

</section>

<div id="modalTopik" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Ajukan Topik Baru</h3>
        <p class="muted">
            <?= ($role == 'siswa') ? 'Topikmu akan diperiksa Guru sebelum muncul.' : 'Topik akan langsung aktif.' ?>
        </p>
        
        <form action="musyawarah-tambah.php" method="POST">
            <div style="margin-bottom:15px;">
                <label style="font-weight:600;">Judul Topik</label>
                <input type="text" name="judul" required placeholder="Contoh: Kebersihan Kelas" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc; margin-top:5px;">
            </div>
            
            <div style="margin-bottom:15px;">
                <label style="font-weight:600;">Deskripsi Singkat</label>
                <textarea name="deskripsi" required rows="3" placeholder="Apa yang akan dibahas?" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc; margin-top:5px;"></textarea>
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" onclick="closeModal()" class="btn ghost">Batal</button>
                <button type="submit" class="btn primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() { document.getElementById('modalTopik').style.display = 'flex'; }
    function closeModal() { document.getElementById('modalTopik').style.display = 'none'; }
    
    // Tutup modal jika klik di luar area
    window.onclick = function(event) {
        if (event.target == document.getElementById('modalTopik')) {
            closeModal();
        }
    }
</script>

<script src="assets/app.js"></script>
</body>
</html>