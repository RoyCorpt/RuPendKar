<?php
session_start();
require "config/db.php";
if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// --- FUNGSI BANTUAN ---
function getYoutubeId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : false;
}

// --- LOGIKA HAPUS (Khusus Guru) ---
if (isset($_GET['hapus']) && $_SESSION['role'] === 'guru') {
    $id_hapus = $_GET['hapus'];
    $cek = $conn->query("SELECT file_path FROM bank_materi WHERE id = $id_hapus");
    if($cek->num_rows > 0){
        $data = $cek->fetch_assoc();
        if(!filter_var($data['file_path'], FILTER_VALIDATE_URL) && file_exists($data['file_path'])) {
            unlink($data['file_path']);
        }
        $conn->query("DELETE FROM bank_materi WHERE id = $id_hapus");
        header("Location: materi.php");
        exit;
    }
}

// --- LOGIKA SIMPAN (Khusus Guru) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_SESSION['role'] !== 'guru') {
        echo "<script>alert('Hanya guru yang boleh upload!');</script>";
    } else {
        $judul = htmlspecialchars($_POST['judul']);
        $deskripsi = htmlspecialchars($_POST['deskripsi']);
        $tipe_input = $_POST['tipe_input'];
        $path_to_save = "";

        if ($tipe_input === 'link') {
            $url = $_POST['link_url'];
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $path_to_save = $url;
            } else {
                echo "<script>alert('Link tidak valid!');</script>";
            }
        } else {
            if(isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] == 0){
                $target_dir = "uploads/";
                if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
                
                $file_name = time() . "_" . basename($_FILES["file_materi"]["name"]);
                $target_file = $target_dir . $file_name;
                $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                $allowed = array('pdf', 'mp4', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'png', 'jpeg');

                if(in_array($fileType, $allowed)) {
                    if (move_uploaded_file($_FILES["file_materi"]["tmp_name"], $target_file)) {
                        $path_to_save = $target_file;
                    } else {
                        echo "<script>alert('Gagal upload file.');</script>";
                    }
                } else {
                    echo "<script>alert('Format file tidak diizinkan.');</script>";
                }
            }
        }

        if ($path_to_save != "") {
            $stmt = $conn->prepare("INSERT INTO bank_materi (user_id, judul, deskripsi, file_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $_SESSION['user_id'], $judul, $deskripsi, $path_to_save);
            $stmt->execute();
            header("Location: materi.php");
            exit;
        }
    }
}

$materi = $conn->query("SELECT bank_materi.*, users.nama FROM bank_materi JOIN users ON bank_materi.user_id = users.id ORDER BY id DESC");
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Bank Materi</title>
  <link rel="stylesheet" href="assets/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <style>
    .preview-box {
        width: 100%; height: 180px; background: #eef2f7;
        display: flex; align-items: center; justify-content: center;
        border-radius: 8px; overflow: hidden; margin-bottom: 12px; position: relative;
        border: 1px solid #dee2e6;
    }
    .preview-box video, .preview-box img, .preview-box iframe {
        width: 100%; height: 100%; object-fit: cover; border:0;
    }
    
    /* Ganti icon emoji dengan text label yang rapi */
    .file-type-label {
        font-weight: 700;
        color: #6c757d;
        font-size: 18px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .toggle-container { display: flex; gap: 10px; margin-bottom: 15px; }
    .toggle-btn {
        flex: 1; padding: 10px; text-align: center; cursor: pointer;
        border: 1px solid var(--primary); border-radius: 8px; font-weight: 600;
        transition: .2s;
        font-size: 14px;
    }
    .toggle-btn.active { background: var(--primary); color: #fff; }
    .input-wrapper { display: none; }
    .input-wrapper.show { display: block; }
  </style>
</head>
<body>
<?php include "components/header.php"; ?>

<section class="container page-content">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h2>Bank Materi & Video</h2>
            <p class="muted">Akses materi pelajaran dari guru dan sumber eksternal.</p>
        </div>
        <a href="gotong.php" class="btn small ghost">Kembali</a>
    </div>

    <?php if($_SESSION['role'] === 'guru'): ?>
    <div class="glass card glossy" style="border: 2px dashed var(--primary); margin-bottom:40px;">
        <h3 style="color:var(--primary); margin-top:0;">Tambah Materi Baru</h3>
        
        <form method="POST" enctype="multipart/form-data">
            
            <div style="margin-bottom:15px;">
                <label style="font-weight:600;">Judul Materi</label>
                <input type="text" name="judul" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc; margin-top:5px;">
            </div>

            <div style="margin-bottom:15px;">
                <label style="font-weight:600;">Tipe Materi</label>
                <div class="toggle-container" style="margin-top:5px;">
                    <div class="toggle-btn active" onclick="switchInput('file')">Upload File</div>
                    <div class="toggle-btn" onclick="switchInput('link')">Link / YouTube</div>
                </div>
                
                <input type="hidden" name="tipe_input" id="tipeInput" value="file">

                <div id="boxFile" class="input-wrapper show">
                    <input type="file" name="file_materi" style="width:100%; padding:10px; background:#fff; border-radius:8px;">
                    <small class="muted">Format: PDF, Word, Video (MP4), Gambar.</small>
                </div>

                <div id="boxLink" class="input-wrapper">
                    <input type="url" name="link_url" placeholder="https://..." style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc;">
                    <small class="muted">Masukkan link YouTube atau link website materi.</small>
                </div>
            </div>

            <div style="margin-bottom:15px;">
                <label style="font-weight:600;">Deskripsi</label>
                <textarea name="deskripsi" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc; margin-top:5px;"></textarea>
            </div>

            <button class="btn primary full">Simpan Materi</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="grid grid-3">
        <?php while($m = $materi->fetch_assoc()): ?>
            <?php 
                $path = $m['file_path'];
                $isUrl = filter_var($path, FILTER_VALIDATE_URL);
                $ytId = $isUrl ? getYoutubeId($path) : false;
                $ext = $isUrl ? 'LINK' : strtolower(pathinfo($path, PATHINFO_EXTENSION));
            ?>
            
            <div class="glass card" style="display:flex; flex-direction:column;">
                
                <div class="preview-box">
                    
                    <?php if ($ytId): ?>
                        <iframe src="https://www.youtube.com/embed/<?= $ytId ?>" allowfullscreen></iframe>
                    
                    <?php elseif ($isUrl): ?>
                        <div style="text-align:center;">
                            <span class="file-type-label">LINK EKSTERNAL</span>
                        </div>

                    <?php elseif ($ext == 'mp4'): ?>
                        <video controls preload="metadata"><source src="<?= $path ?>" type="video/mp4"></video>
                    
                    <?php elseif (in_array($ext, ['jpg','jpeg','png'])): ?>
                        <img src="<?= $path ?>">
                    
                    <?php else: ?>
                        <span class="file-type-label"><?= strtoupper($ext) ?></span>
                    <?php endif; ?>

                </div>

                <div style="flex:1;">
                    <h4 style="margin:0 0 5px 0;"><?= htmlspecialchars($m['judul']) ?></h4>
                    <p class="muted" style="font-size:12px; margin-bottom:10px;">
                        <?= htmlspecialchars($m['nama']) ?> • 
                        <span style="background:#e3f2fd; color:#1565c0; padding:2px 6px; border-radius:4px; font-weight:600; font-size:11px;">
                            <?= $ytId ? 'YOUTUBE' : strtoupper($ext) ?>
                        </span>
                    </p>
                    <p style="font-size:13px; line-height:1.4; margin-bottom:15px;">
                        <?= htmlspecialchars($m['deskripsi']) ?>
                    </p>
                </div>

                <div style="display:flex; gap:8px; margin-top:auto;">
                    <?php if ($isUrl): ?>
                        <a href="<?= $path ?>" target="_blank" class="btn small primary full" style="text-align:center;">Buka Link</a>
                    <?php else: ?>
                        <a href="<?= $path ?>" download class="btn small outline full" style="text-align:center;">Unduh</a>
                    <?php endif; ?>

                    <?php if($_SESSION['role'] === 'guru'): ?>
                        <a href="materi.php?hapus=<?= $m['id'] ?>" onclick="return confirm('Hapus materi ini?')" class="btn small ghost" style="color:#d9534f; border-color:#d9534f;">Hapus</a>
                    <?php endif; ?>
                </div>

            </div>
        <?php endwhile; ?>
    </div>

</section>

<script>
    function switchInput(type) {
        document.getElementById('tipeInput').value = type;
        
        document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        document.querySelectorAll('.input-wrapper').forEach(box => box.classList.remove('show'));
        if(type === 'file') {
            document.getElementById('boxFile').classList.add('show');
            document.querySelector('input[name="file_materi"]').required = true;
            document.querySelector('input[name="link_url"]').required = false;
        } else {
            document.getElementById('boxLink').classList.add('show');
            document.querySelector('input[name="file_materi"]').required = false;
            document.querySelector('input[name="link_url"]').required = true;
        }
    }
</script>

</body>
</html>