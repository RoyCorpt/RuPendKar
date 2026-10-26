<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

require "config/db.php";
include "components/header.php";

// --- LOGIKA HAPUS (Khusus Guru) ---
if (isset($_GET['hapus']) && $_SESSION['role'] === 'guru') {
    $id_hapus = $_GET['hapus'];
    // Hapus pertanyaan (jawaban otomatis terhapus jika pakai foreign key cascade, 
    // jika tidak, kita hapus manual jawabannya dulu biar bersih)
    $conn->query("DELETE FROM bantuan_jawaban WHERE pertanyaan_id = $id_hapus");
    $conn->query("DELETE FROM bantuan_pertanyaan WHERE id = $id_hapus");
    
    header("Location: gotong.php");
    exit;
}

// Ambil daftar pertanyaan terbaru
$query_tanya = $conn->query("
    SELECT bp.*, users.nama 
    FROM bantuan_pertanyaan bp
    JOIN users ON bp.user_id = users.id
    ORDER BY bp.dibuat_pada DESC
");
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ruang Gotong Royong</title>
  <link rel="stylesheet" href="assets/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>

<main class="container page-content">
  
  <div style="display:flex; justify-content:space-between; align-items:center;">
      <div>
        <h2>Ruang Gotong Royong</h2>
        <p class="muted">Saling bantu mengerjakan tugas & berbagi materi pelajaran.</p>
      </div>
      <?php if($_SESSION['role']=='guru'): ?>
          <a href="index.php" class="btn ghost">Kembali ke Panel</a>
      <?php endif; ?>
  </div>

  <div class="grid grid-2">

    <div>
        <div class="glass card glossy">
          <h3>Bantuan Tugas</h3>
          <p class="muted">Tanyakan kesulitanmu atau bantu teman menjawab.</p>
          
          <?php if($_SESSION['role'] == 'siswa'): ?>
              <button onclick="document.getElementById('formTanya').style.display='block'" class="btn primary small full">
                + Buat Pertanyaan Baru
              </button>
          <?php endif; ?>

          <div id="formTanya" style="display:none; margin-top:15px; border-top:1px dashed #ccc; padding-top:10px;">
              <form action="tanya-simpan.php" method="POST">
                  <input type="text" name="judul" placeholder="Judul (misal: Rumus Luas Lingkaran)" required style="width:100%; margin-bottom:8px; padding:8px; border-radius:8px; border:1px solid #ddd;">
                  <textarea name="isi" placeholder="Jelaskan pertanyaanmu..." required style="width:100%; height:80px; padding:8px; border-radius:8px; border:1px solid #ddd;"></textarea>
                  <button type="submit" class="btn small primary" style="margin-top:5px;">Kirim</button>
                  <button type="button" onclick="document.getElementById('formTanya').style.display='none'" class="btn small ghost">Batal</button>
              </form>
          </div>

          <ul class="qa-list" style="margin-top:20px;">
            <?php while($t = $query_tanya->fetch_assoc()): ?>
                <li>
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <div>
                            <strong><?= htmlspecialchars($t['nama']) ?></strong> 
                            <span class="muted" style="font-size:12px;">bertanya:</span>
                            <br>
                            <a href="tanya-detail.php?id=<?= $t['id'] ?>" style="font-weight:600; color:var(--primary); text-decoration:none;">
                                <?= htmlspecialchars($t['judul']) ?>
                            </a>
                        </div>
                        <div style="display:flex; gap:5px;">
                            <a href="tanya-detail.php?id=<?= $t['id'] ?>" class="btn small outline">Lihat</a>
                            
                            <?php if($_SESSION['role'] === 'guru'): ?>
                                <a href="gotong.php?hapus=<?= $t['id'] ?>" onclick="return confirm('Hapus diskusi ini?')" class="btn small ghost" style="color:red;">🗑</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            <?php endwhile; ?>
          </ul>
        </div>
    </div>

    <div>
        <div class="glass card glossy">
          <h3>Bank Materi</h3>
          <p class="muted">Kumpulan modul, PDF, dan video pembelajaran.</p>
          
          <div style="text-align:center; padding:20px;">
              <div style="font-size:50px; margin-bottom:10px;">📚</div>
              <p>Akses semua materi yang diupload oleh Guru.</p>
              <a href="materi.php" class="btn primary large full">Buka Halaman Materi</a>
          </div>
        </div>
    </div>

  </div>
</main>

<script src="assets/app.js"></script>
</body>
</html>