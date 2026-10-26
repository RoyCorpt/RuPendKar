<?php
session_start();
require "config/db.php";

// Cek akses guru
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru') {
    header("Location: index.php");
    exit;
}

$msg = "";
if(isset($_GET['msg'])){
    if($_GET['msg'] == 'added') $msg = "Konten berhasil ditambahkan!";
    if($_GET['msg'] == 'deleted') $msg = "Konten berhasil dihapus!";
    if($_GET['msg'] == 'updated') $msg = "Konten berhasil diperbarui!";
}

// --- LOGIKA HAPUS KONTEN ---
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    // Hapus konten (jawaban siswa terkait juga akan terhapus jika setting DB cascade)
    $conn->query("DELETE FROM modul_jawaban WHERE konten_id = $id_hapus");
    $conn->query("DELETE FROM modul_konten WHERE id = $id_hapus");
    
    $modul_aktif = $_GET['modul'];
    header("Location: kelola-modul.php?modul=$modul_aktif&msg=deleted");
    exit;
}

// --- LOGIKA SIMPAN (TAMBAH / EDIT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modul_kode = $_POST['modul_kode'];
    $judul      = htmlspecialchars($_POST['judul']);
    $isi        = htmlspecialchars($_POST['isi']);
    $tipe       = $_POST['tipe']; // 'materi' atau 'pertanyaan'
    $urutan     = (int)$_POST['urutan'];

    if (!empty($_POST['id_edit'])) {
        // PROSES UPDATE (EDIT)
        $id_edit = (int)$_POST['id_edit'];
        $stmt = $conn->prepare("UPDATE modul_konten SET judul = ?, isi = ?, tipe = ?, urutan = ? WHERE id = ?");
        $stmt->bind_param("sssii", $judul, $isi, $tipe, $urutan, $id_edit);
        
        if ($stmt->execute()) {
            header("Location: kelola-modul.php?modul=$modul_kode&msg=updated");
            exit;
        }
    } else {
        // PROSES INSERT (TAMBAH BARU)
        $stmt = $conn->prepare("INSERT INTO modul_konten (modul_kode, judul, isi, tipe, urutan) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $modul_kode, $judul, $isi, $tipe, $urutan);
        
        if ($stmt->execute()) {
            header("Location: kelola-modul.php?modul=$modul_kode&msg=added");
            exit;
        }
    }
}

// Ambil Daftar Modul untuk Dropdown
$list_modul = $conn->query("SELECT * FROM modul");

// Ambil Konten jika ada modul yang dipilih
$konten = null;
$selected_modul = isset($_GET['modul']) ? $_GET['modul'] : '';

if ($selected_modul) {
    $stmt = $conn->prepare("SELECT * FROM modul_konten WHERE modul_kode = ? ORDER BY urutan ASC");
    $stmt->bind_param("s", $selected_modul);
    $stmt->execute();
    $konten = $stmt->get_result();
}

// --- AMBIL DATA UNTUK FORM EDIT (Jika tombol edit diklik) ---
$data_edit = null;
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $stmt_edit = $conn->prepare("SELECT * FROM modul_konten WHERE id = ?");
    $stmt_edit->bind_param("i", $id_edit);
    $stmt_edit->execute();
    $data_edit = $stmt_edit->get_result()->fetch_assoc();
}
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Kelola Isi Modul</title>
  <link rel="stylesheet" href="assets/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <style>
      .item-row { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
      .item-row:last-child { border-bottom: none; }
      .item-row.highlight { background-color: #f8f9fc; border-left: 4px solid var(--primary); }
      .badge-type { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
      .badge-materi { background: #e3f2fd; color: #1565c0; }
      .badge-tanya { background: #fff3cd; color: #856404; }
  </style>
</head>
<body>
<?php include "components/header.php"; ?>

<section class="container page-content">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>⚙️ Kelola Isi Modul</h2>
        <a href="index.php" class="btn ghost">Kembali ke Panel</a>
    </div>

    <?php if($msg): ?>
        <div class="success-box" style="margin-bottom:20px;"><?= $msg ?></div>
    <?php endif; ?>

    <div class="glass card glossy" style="margin-bottom: 30px;">
        <label style="font-weight:bold;">Pilih Modul yang ingin dikelola:</label>
        <form method="GET" style="display:flex; gap:10px; margin-top:10px;">
            <select name="modul" required style="flex:1; padding:10px; border-radius:8px; border:1px solid #ccc;">
                <option value="">-- Pilih Modul --</option>
                <?php while($m = $list_modul->fetch_assoc()): ?>
                    <option value="<?= $m['kode'] ?>" <?= ($selected_modul == $m['kode']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['judul']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button class="btn primary">Buka Modul</button>
        </form>
    </div>

    <?php if ($selected_modul && $konten): ?>
        
        <div class="grid grid-2">
            
            <div>
                <h3 style="margin-top:0;">Daftar Isi Saat Ini</h3>
                <div class="glass card" style="padding:0; overflow:hidden;">
                    <?php if ($konten->num_rows == 0): ?>
                        <p class="muted" style="text-align:center; padding:20px;">Modul ini masih kosong.</p>
                    <?php else: ?>
                        <?php while($row = $konten->fetch_assoc()): ?>
                            <?php $is_editing = ($data_edit && $data_edit['id'] == $row['id']); ?>
                            
                            <div class="item-row <?= $is_editing ? 'highlight' : '' ?>">
                                <div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span style="font-weight:bold; color:#888;">#<?= $row['urutan'] ?></span>
                                        <span class="badge-type <?= ($row['tipe']=='materi')?'badge-materi':'badge-tanya' ?>">
                                            <?= $row['tipe'] ?>
                                        </span>
                                    </div>
                                    <div style="font-weight:600; margin-top:5px;"><?= htmlspecialchars($row['judul']) ?></div>
                                </div>
                                <div style="display:flex; gap:8px;">
                                    
                                    <a href="kelola-modul.php?modul=<?= $selected_modul ?>&edit=<?= $row['id'] ?>" 
                                       class="btn small outline">
                                       ✎ Edit
                                    </a>

                                    <a href="kelola-modul.php?modul=<?= $selected_modul ?>&hapus=<?= $row['id'] ?>" 
                                       onclick="return confirm('Hapus item ini? Jawaban siswa terkait soal ini juga akan terhapus.')" 
                                       class="btn small ghost" style="color:red; border-color:red;">
                                       🗑 Hapus
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <h3 style="margin-top:0; color: <?= $data_edit ? '#1E90FF' : 'inherit' ?>;">
                    <?= $data_edit ? '✎ Edit Konten' : '➕ Tambah Konten Baru' ?>
                </h3>
                
                <div class="glass card glossy" style="<?= $data_edit ? 'border: 2px solid #1E90FF;' : '' ?>">
                    <form method="POST">
                        <input type="hidden" name="modul_kode" value="<?= $selected_modul ?>">
                        
                        <input type="hidden" name="id_edit" value="<?= $data_edit ? $data_edit['id'] : '' ?>">
                        
                        <div style="margin-bottom:10px;">
                            <label style="font-size:13px; font-weight:600;">Urutan Halaman</label>
                            <input type="number" name="urutan" required placeholder="Contoh: 1, 2, 3..." 
                                   value="<?= $data_edit ? $data_edit['urutan'] : '' ?>"
                                   style="width:100%; padding:8px; border-radius:6px; border:1px solid #ddd;">
                        </div>

                        <div style="margin-bottom:10px;">
                            <label style="font-size:13px; font-weight:600;">Tipe Konten</label>
                            <select name="tipe" style="width:100%; padding:8px; border-radius:6px; border:1px solid #ddd;">
                                <option value="materi" <?= ($data_edit && $data_edit['tipe'] == 'materi') ? 'selected' : '' ?>>Materi Bacaan</option>
                                <option value="pertanyaan" <?= ($data_edit && $data_edit['tipe'] == 'pertanyaan') ? 'selected' : '' ?>>Soal Essay</option>
                            </select>
                        </div>

                        <div style="margin-bottom:10px;">
                            <label style="font-size:13px; font-weight:600;">Judul</label>
                            <input type="text" name="judul" required placeholder="Judul Bab / Pertanyaan" 
                                   value="<?= $data_edit ? htmlspecialchars($data_edit['judul']) : '' ?>"
                                   style="width:100%; padding:8px; border-radius:6px; border:1px solid #ddd;">
                        </div>

                        <div style="margin-bottom:15px;">
                            <label style="font-size:13px; font-weight:600;">Isi Materi / Pertanyaan</label>
                            <textarea name="isi" required rows="7" placeholder="Tuliskan materi atau soal di sini..." 
                                      style="width:100%; padding:8px; border-radius:6px; border:1px solid #ddd; resize:vertical;"><?= $data_edit ? htmlspecialchars($data_edit['isi']) : '' ?></textarea>
                        </div>

                        <div style="display:flex; gap:10px;">
                            <?php if($data_edit): ?>
                                <a href="kelola-modul.php?modul=<?= $selected_modul ?>" class="btn ghost full" style="text-align:center;">Batal</a>
                            <?php endif; ?>
                            
                            <button class="btn primary full">
                                <?= $data_edit ? 'Simpan Perubahan' : 'Simpan Konten' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    <?php endif; ?>

</section>
</body>
</html>