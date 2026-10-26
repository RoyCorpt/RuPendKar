<?php
session_start();
require "config/db.php";

if (!isset($_GET['id'])) { header("Location: gotong.php"); exit; }
$id_tanya = (int)$_GET['id'];
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];

// --- LOGIKA HAPUS JAWABAN/KOMENTAR ---
if (isset($_GET['hapus_jawaban'])) {
    $id_hapus = (int)$_GET['hapus_jawaban'];
    
    // Guru bisa menghapus semua, Siswa hanya bisa menghapus miliknya sendiri
    if ($role === 'guru') {
        $stmt_del = $conn->prepare("DELETE FROM bantuan_jawaban WHERE id = ?");
        $stmt_del->bind_param("i", $id_hapus);
    } else {
        $stmt_del = $conn->prepare("DELETE FROM bantuan_jawaban WHERE id = ? AND user_id = ?");
        $stmt_del->bind_param("ii", $id_hapus, $uid);
    }
    
    $stmt_del->execute();
    header("Location: tanya-detail.php?id=$id_tanya");
    exit;
}

// --- LOGIKA EDIT JAWABAN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $id_edit = (int)$_POST['edit_id'];
    $jawaban_baru = htmlspecialchars($_POST['jawaban_edit']);
    
    // Hanya pemilik komentar yang bisa mengedit (baik itu guru atau siswa)
    $stmt_edit = $conn->prepare("UPDATE bantuan_jawaban SET jawaban = ? WHERE id = ? AND user_id = ?");
    $stmt_edit->bind_param("sii", $jawaban_baru, $id_edit, $uid);
    $stmt_edit->execute();
    
    header("Location: tanya-detail.php?id=$id_tanya");
    exit;
}

// --- LOGIKA SIMPAN JAWABAN BARU ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['edit_id'])) {
    $jawaban = htmlspecialchars($_POST['jawaban']);
    
    $stmt = $conn->prepare("INSERT INTO bantuan_jawaban (pertanyaan_id, user_id, jawaban) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $id_tanya, $uid, $jawaban);
    
    if($stmt->execute()) {
        // Beri poin HANYA saat pertama kali menjawab
        if ($role == 'siswa') {
            $conn->query("UPDATE users SET poin = poin + 10 WHERE id = $uid");
            $conn->query("INSERT INTO aktivitas_log (user_id, aktivitas, detail) VALUES ($uid, 'Membantu Teman', 'Menjawab pertanyaan di Gotong Royong (+10 Poin)')");
        }
        header("Location: tanya-detail.php?id=$id_tanya");
        exit;
    }
}

// Ambil Data Pertanyaan
$q = $conn->prepare("SELECT bp.*, users.nama, users.role FROM bantuan_pertanyaan bp JOIN users ON bp.user_id = users.id WHERE bp.id = ?");
$q->bind_param("i", $id_tanya);
$q->execute();
$data = $q->get_result()->fetch_assoc();

// Ambil List Jawaban 
$ans = $conn->prepare("
    SELECT bj.*, users.nama, users.role 
    FROM bantuan_jawaban bj 
    JOIN users ON bj.user_id = users.id 
    WHERE pertanyaan_id = ? 
    ORDER BY 
        CASE WHEN users.role = 'guru' THEN 0 ELSE 1 END,
        bj.dibuat_pada ASC
");
$ans->bind_param("i", $id_tanya);
$ans->execute();
$list_jawaban = $ans->get_result();
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Detail Diskusi</title>
  <link rel="stylesheet" href="assets/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <style>
      .badge-guru { background: #1565c0; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; margin-left: 8px; vertical-align: middle; }
      .card-guru { border: 1px solid #90caf9; background: #e3f2fd !important; }
      
      /* Style Tombol Aksi */
      .btn-aksi-komentar { font-size: 12px; text-decoration: none; padding: 3px 10px; border-radius: 6px; transition: 0.2s; cursor: pointer; border: 1px solid transparent; font-weight: 600; font-family: inherit;}
      .btn-hapus { color: #d9534f; border-color: #d9534f; background: transparent; }
      .btn-hapus:hover { background: #d9534f; color: white; }
      .btn-edit { color: #1E90FF; border-color: #1E90FF; background: transparent; }
      .btn-edit:hover { background: #1E90FF; color: white; }
  </style>
</head>
<body>
<?php include "components/header.php"; ?>

<section class="container page-content">
    <a href="gotong.php" class="btn small ghost">← Kembali ke Forum</a>

    <div class="glass card glossy" style="margin-top:15px; border-left: 5px solid var(--primary);">
        <h2 style="margin-bottom:5px; margin-top:0;"><?= htmlspecialchars($data['judul']) ?></h2>
        <p class="muted" style="margin-bottom:15px;">
            Ditanya oleh <strong><?= htmlspecialchars($data['nama']) ?></strong>
            <?php if($data['role']=='guru') echo '<span class="badge-guru">GURU</span>'; ?>
        </p>
        <p style="font-size:1.1rem; line-height:1.6;"><?= nl2br(htmlspecialchars($data['isi'])) ?></p>
    </div>

    <h3 style="margin-top:30px;">Jawaban (<?= $list_jawaban->num_rows ?>)</h3>
    
    <?php while($j = $list_jawaban->fetch_assoc()): ?>
        <?php 
            $is_guru_comment = ($j['role'] === 'guru'); 
            $is_owner = ($j['user_id'] == $uid);
            $can_delete = ($is_owner || $role === 'guru'); // Guru bisa hapus semua, Siswa hapus miliknya sendiri
        ?>
        
        <div class="glass card <?= $is_guru_comment ? 'card-guru' : '' ?>" style="margin-bottom:15px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px; align-items:center;">
                <div>
                    <strong><?= htmlspecialchars($j['nama']) ?></strong>
                    <?php if($is_guru_comment): ?>
                        <span class="badge-guru">GURU</span>
                    <?php endif; ?>
                </div>
                
                <div style="display:flex; gap:8px; align-items:center;">
                    <small class="muted" style="margin-right:5px;"><?= date('d M H:i', strtotime($j['dibuat_pada'])) ?></small>
                    
                    <?php if($is_owner): ?>
                        <button onclick="toggleEdit(<?= $j['id'] ?>)" class="btn-aksi-komentar btn-edit">✎ Edit</button>
                    <?php endif; ?>

                    <?php if($can_delete): ?>
                        <a href="tanya-detail.php?id=<?= $id_tanya ?>&hapus_jawaban=<?= $j['id'] ?>" 
                           onclick="return confirm('Apakah Anda yakin ingin menghapus komentar ini?')" 
                           class="btn-aksi-komentar btn-hapus">
                           🗑 Hapus
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div id="teks_<?= $j['id'] ?>">
                <p style="margin:0; line-height:1.5;"><?= nl2br(htmlspecialchars($j['jawaban'])) ?></p>
            </div>

            <?php if($is_owner): ?>
            <div id="form_edit_<?= $j['id'] ?>" style="display:none; margin-top:10px; background:#fff; padding:10px; border-radius:8px;">
                <form method="POST">
                    <input type="hidden" name="edit_id" value="<?= $j['id'] ?>">
                    <textarea name="jawaban_edit" required style="width:100%; height:80px; padding:10px; border-radius:8px; border:1px solid #1E90FF; font-family:inherit;"><?= htmlspecialchars($j['jawaban']) ?></textarea>
                    
                    <div style="display:flex; gap:8px; margin-top:8px;">
                        <button type="submit" class="btn small primary">Simpan Pembaruan</button>
                        <button type="button" onclick="toggleEdit(<?= $j['id'] ?>)" class="btn small ghost">Batal</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

        </div>
    <?php endwhile; ?>

    <?php if($list_jawaban->num_rows == 0): ?>
        <p class="muted">Belum ada jawaban.</p>
    <?php endif; ?>

    <div class="glass card glossy" style="margin-top:30px; background: #f0f8ff;">
        <?php if($_SESSION['role'] === 'guru'): ?>
            <h4 style="margin-top:0; color:#1565c0;">Berikan Jawaban / Koreksi (Sebagai Guru)</h4>
        <?php else: ?>
            <h4 style="margin-top:0;">Bantu Jawab (+10 Poin)</h4>
        <?php endif; ?>

        <form method="POST">
            <textarea name="jawaban" required style="width:100%; height:100px; padding:12px; border-radius:10px; border:1px solid #ccc;" placeholder="Tulis jawabanmu di sini..."></textarea>
            <button class="btn primary" style="margin-top:10px;">Kirim Jawaban</button>
        </form>
    </div>

</section>

<script>
function toggleEdit(id) {
    var teks = document.getElementById('teks_' + id);
    var form = document.getElementById('form_edit_' + id);
    
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        teks.style.display = 'none';
    } else {
        form.style.display = 'none';
        teks.style.display = 'block';
    }
}
</script>

</body>
</html>