<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require "config/db.php";
include "components/header.php";
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Simulasi Musyawarah</title>

<link rel="stylesheet" href="assets/styles.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>

<main class="container page-content">
    <h2>Simulasi Musyawarah</h2>
    <p class="muted">Latih diskusi dan mufakat secara digital.</p>

    <div class="sim-row">

        <!-- CHAT -->
        <div class="sim-left glass">
            <div class="chat-window">
                <div class="chat-header">Topik: Pilih Ketua Kelas</div>

                <div class="chat-body" id="chatBody"></div>

                <div class="chat-form">
                    <input id="chatInput" placeholder="Tulis pendapat..." autocomplete="off">
                    <button 
                        type="button" 
                        class="btn small"
                        onclick="sendChat()">
                        Kirim
                    </button>
                </div>
            </div>
        </div>

        <!-- SIDEBAR -->
        <aside class="sim-right glass">
    <h4>Anggota Online</h4>
    <p class="muted" style="font-size:12px; margin-bottom:10px;">Kelas <?= htmlspecialchars($_SESSION['kelas']) ?></p>
    
    <ul class="member-list" id="onlineList">
        <li><span class="dot online"></span> Kamu</li> 
    </ul>
</aside>

    </div>
</main>

<script>
// MENGAMBIL TOPIK DARI URL (PHP -> JS)
// Jika tidak ada topik di URL, default ke 'ketua'
let currentTopik = "<?= isset($_GET['topik']) ? htmlspecialchars($_GET['topik']) : 'ketua' ?>";

// Tambahkan judul topik ke Header Chat agar user tahu sedang dimana
document.addEventListener("DOMContentLoaded", function() {
    // Ubah judul header chat sesuai topik (Opsional, biar keren)
    const judulTopik = currentTopik.replace(/_/g, ' ').toUpperCase();
    document.querySelector('.chat-header').innerText = "Topik: " + judulTopik;
    
    loadChat();
    checkOnlineUsers();
    setInterval(loadChat, 2000);
    setInterval(checkOnlineUsers, 5000);
});

// ... sisa kode fungsi loadChat dan sendChat biarkan sama ...

document.addEventListener("DOMContentLoaded", function() {
    loadChat();
    setInterval(loadChat, 2000);
});

function loadChat() {
    fetch("simulasi-load.php?topik=" + currentTopik)
        .then(res => res.json())
        .then(data => {
            const box = document.getElementById("chatBody");
            box.innerHTML = "";

            data.forEach(msg => {
                let side = (msg.user_id == <?= $_SESSION['user_id'] ?> ? "right" : "left");

                box.innerHTML += `
                    <div class="message ${side}">
                        <strong>${msg.nama}</strong><br>
                        ${msg.pesan}
                    </div>
                `;
            });

            box.scrollTop = box.scrollHeight;
        })
        .catch(err => console.error("ERR loadChat:", err));
}

function sendChat() {
    let text = document.getElementById("chatInput").value.trim();
    if (text === "") return;

    let form = new FormData();
    form.append("topik_kode", currentTopik);
    form.append("pesan", text);

    fetch("simulasi-save.php", {
        method: "POST",
        body: form
    })
    .then(res => res.json())
    .then(data => {
        console.log("SEND RESULT:", data);

        if (data.status === "OK") {
            document.getElementById("chatInput").value = "";
            loadChat();
        } else {
            alert("Gagal mengirim pesan: " + data.msg);
        }
    })
    .catch(err => console.error("ERR sendChat:", err));
}

// ... kode loadChat dan sendChat yang sudah ada ...

// FUNGSI BARU: Cek User Online
function checkOnlineUsers() {
    fetch("simulasi-online.php")
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById("onlineList");
            
            // Reset list, tapi biarkan "Kamu" tetap ada di paling atas
            let html = '<li><span class="dot online"></span> Kamu</li>';

            if (data.length === 0) {
                // Jika tidak ada teman lain
                html += '<li class="muted" style="font-size:13px; font-style:italic;">Belum ada teman lain.</li>';
            } else {
                // Loop data teman
                data.forEach(user => {
                    html += `
                        <li>
                            <span class="dot online"></span> ${user.nama}
                        </li>
                    `;
                });
            }

            list.innerHTML = html;
        })
        .catch(err => console.error("Gagal cek online:", err));
}

// PANGGIL FUNGSI
document.addEventListener("DOMContentLoaded", function() {
    loadChat();
    checkOnlineUsers(); // Panggil sekali saat loading
    
    // Update chat setiap 2 detik
    setInterval(loadChat, 2000);
    
    // Update status online setiap 5 detik (Heartbeat)
    setInterval(checkOnlineUsers, 5000); 
});
</script>



<footer class="footer glass">
  <div class="container footer-inner">
    <strong>RuPendKar</strong>
    <span class="muted">© 2025</span>
  </div>
</footer>

</body>
</html>
