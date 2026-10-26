# RuPendKar (Ruang Pendidikan Karakter)

RuPendKar adalah platform edukasi berbasis web (Learning Management System) yang difokuskan pada pendidikan karakter (Pancasila, gotong royong, etika digital). Sistem ini menggabungkan modul pembelajaran, evaluasi, interaksi sosial, dan mekanisme gamifikasi (poin & badge) dalam satu platform.

## Daftar Isi

- [Arsitektur](#arsitektur)
- [Fitur Utama](#fitur-utama)
- [Struktur Direktori](#struktur-direktori)
- [Skema Database](#skema-database)
- [Keamanan](#keamanan)
- [Instalasi](#instalasi)
- [Catatan Pengujian](#catatan-pengujian)

## Arsitektur

- **Backend:** PHP Native (gaya prosedural dan Object-Oriented), menggunakan ekstensi `MySQLi` untuk koneksi database.
- **Frontend:** HTML5, CSS3 (konsep desain *Glassmorphism* — kelas `glass`, `card glossy`), Vanilla JavaScript.
- **Database:** MySQL (relasional).
- **Lingkungan:** Server lokal (XAMPP), didesain untuk berjalan tanpa bergantung pada koneksi internet eksternal.
- **Hak akses:** Role-Based Access Control (RBAC) dengan dua peran — **Guru** (administrator/moderator) dan **Siswa** (pengguna akhir).

## Fitur Utama

### Autentikasi dan Manajemen Pengguna
- Registrasi mandiri (peran default: `siswa`), dengan validasi panjang password minimal 6 karakter, kecocokan konfirmasi password, dan pengecekan duplikasi `username`.
- Login menggunakan `password_hash` / `password_verify`; sesi menyimpan `user_id`, `nama`, `role`, `kelas`.
- Pemulihan sandi mandiri tanpa email, memakai kecocokan `username` dan tanggal pembuatan akun (`DATE(dibuat_pada)`).
- Pengaturan profil (ubah nama dan password baru dengan validasi password lama); `username` dan `kelas` bersifat statis.

### Dasbor dan Gamifikasi
- Dasbor siswa: total poin, jumlah modul selesai, 5 aktivitas terakhir, dan badge karakter:
  - Pemula (abu-abu, < 50 poin)
  - Aktif (biru, ≥ 50 poin)
  - Bintang Kelas (hijau, ≥ 100 poin)
  - Teladan Digital (oranye, ≥ 200 poin)
- Dasbor guru: akses cepat koreksi esai tertunda dan persetujuan topik musyawarah, rekap siswa per kelas, serta indikator *online* (aktif dalam 5 menit terakhir).
- Log aktivitas mencatat setiap penambahan poin beserta detail peristiwa.

### Modul Pembelajaran (CMS)
- Navigasi modul dengan pelacakan progres: `(halaman saat ini / total materi) * 100`, status `proses` / `selesai`.
- Guru dapat menambah, mengedit, menghapus isi modul, dan menentukan tipe konten (`materi` atau `pertanyaan`).
- Penghapusan bab memicu *cascade* manual (menghapus riwayat jawaban siswa terkait).

### Evaluasi
- Kuis pilihan ganda dengan urutan soal acak (`ORDER BY RAND()`); kelulusan pada skor ≥ 70; poin dihitung `floor(skor/10)`.
- Esai dinilai guru pada skala 0–100, dikonversi ke poin dengan rumus yang sama.

### Interaksi Sosial
- **Simulasi Musyawarah** (chat real-time via AJAX polling, bukan WebSocket): pembaruan pesan tiap 2 detik, heartbeat status online tiap 5 detik. Topik buatan guru langsung aktif; topik buatan siswa menunggu persetujuan guru (reward +15 poin).
- **Ruang Gotong Royong**: forum tanya-jawab antar siswa; guru dapat menghapus pertanyaan beserta seluruh jawabannya.

### Bank Materi
- Unggah berkas (pdf, mp4, doc, docx, ppt, pptx, jpg, png, jpeg) khusus peran guru; berkas dinamai ulang dengan stempel waktu.
- Integrasi tautan YouTube otomatis via regex kustom `getYoutubeId()`.
- Penghapusan materi berkas fisik memicu `unlink()` untuk membersihkan storage.

## Struktur Direktori

```
Website/
├── .git/
├── assets/            # CSS, JS (styles.css, login.css, app.js)
├── auth/              # Skrip sesi akses (logout.php, dll.)
├── components/        # Komponen UI reusable (header.php, dll.)
├── config/            # Konfigurasi koneksi database (db.php)
├── uploads/           # Berkas materi yang diunggah guru (dibuat otomatis)
├── dashboard.php
├── gotong.php
├── index.php
├── kelola-modul.php
├── kuis.php
├── kuis-nilai.php
├── login.php
├── lupa-Password.php
├── materi.php
├── modul.php
├── modul-detail.php
├── modul-konten.php
├── musyawarah.php
├── musyawarah-aksi.php
├── musyawarah-tambah.php
├── penilaian.php
├── profil.php
├── register.php
├── simpan-jawaban.php
├── simulasi.php
├── simulasi-load.php
├── simulasi-online.php
├── simulasi-save.php
├── tanya-detail.php
└── tanya-simpan.php
```

## Skema Database

| Tabel | Kolom Utama |
| --- | --- |
| `users` | id, nama, username, password, kelas, role, poin, dibuat_pada, terakhir_aktif |
| `modul` | id, kode, judul, deskripsi, gambar |
| `modul_konten` | id, modul_kode, urutan, tipe (materi/pertanyaan), judul, isi |
| `modul_progress` | user_id, modul_kode, konten_terakhir, status (proses/selesai) |
| `modul_jawaban` | id, user_id, konten_id, jawaban, waktu, nilai, status_nilai (belum/sudah) |
| `kuis` | id, modul_id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar |
| `kuis_jawaban` | user_id, kuis_id, jawaban, benar (0/1) |
| `musyawarah_topik` | id, kode (slug), judul, deskripsi, dibuat_oleh, dibuat_pada, status (aktif/menunggu) |
| `bantuan_pertanyaan` | id, user_id, judul, isi, dibuat_pada |
| `bantuan_jawaban` | id, pertanyaan_id, user_id, jawaban |
| `bank_materi` | id, user_id, judul, deskripsi, file_path |
| `aktivitas_log` | id, user_id, aktivitas, detail, waktu |

> Catatan: dokumen sumber menyebut "10 tabel" namun mendaftarkan 12 entri bernomor 1–12. Mohon dicek kembali jumlah tabel yang sebenarnya di skema database Anda sebelum README ini difinalisasi.

## Keamanan

- Seluruh halaman operasional diproteksi dengan `session_start()`; halaman khusus guru memblokir sesi siswa dengan pengalihan otomatis.
- Input dan query dilindungi dengan *Prepared Statements* (`bind_param`) untuk mencegah injeksi SQL.
- Output ke UI dirender dengan `htmlspecialchars()` untuk mencegah XSS.
- Direktori root memerlukan izin baca/tulis (`0777`) agar folder `uploads/` dapat dibuat dan diisi otomatis saat instalasi di *hosting*.

## Instalasi

1. Clone atau salin seluruh folder proyek ke direktori `htdocs` XAMPP.
2. Buat database MySQL dan impor skema sesuai [Skema Database](#skema-database).
3. Atur kredensial koneksi database di `config/db.php`.
4. Pastikan direktori `uploads/` memiliki izin tulis.
5. Jalankan Apache dan MySQL melalui XAMPP Control Panel.
6. Akses sistem melalui `http://localhost/<nama-folder-project>/`.

> Langkah di atas adalah alur instalasi umum untuk proyek PHP + XAMPP berdasarkan struktur yang ada. Sesuaikan dengan konfigurasi spesifik proyek Anda (misalnya nama file konfigurasi atau skrip impor skema) jika berbeda.

## Catatan Pengujian

Pengujian fungsional (*black-box testing*) telah dilakukan secara internal terhadap seluruh modul aplikasi. Pengujian non-fungsional — termasuk keamanan (SQL Injection, XSS), kapasitas pengguna simultan, dan responsivitas antarmuka lintas perangkat — belum diverifikasi melalui pengujian khusus dan menjadi agenda pengembangan lanjutan.
"# RuPendKar" 
