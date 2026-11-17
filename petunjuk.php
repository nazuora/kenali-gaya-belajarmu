<?php
// Memulai sesi PHP untuk menyimpan status aktivitas pengguna
session_start();

// Menandai di dalam sesi bahwa user sudah melewati halaman petunjuk
$_SESSION['sudah_baca_petunjuk'] = true;

// Memuat file koneksi database dan fungsi bantuan lainnya
require_once __DIR__ . '/config/koneksi.php';

// Menentukan judul halaman web
$judul = 'Petunjuk Kuis';

// Memuat bagian atas halaman (header dan menu navigasi)
require_once __DIR__ . '/includes/header.php';
?>

<div class="narrow" style="max-width: 700px;">
  <h1 class="s">Sebelum Memulai Kuis</h1>
  <p class="sub">
    Kuis ini bertujuan untuk mengetahui kecenderungan gaya belajarmu (Visual, Auditori, atau Kinestetik).
  </p>

  <!-- Daftar langkah-langkah yang harus diperhatikan sebelum mengerjakan kuis -->
  <ol class="steps" style="margin: 30px 0; padding-left: 20px;">
    <li><strong>Baca teliti</strong><span>Baca setiap pertanyaan dengan cermat.</span></li>
    <li><strong>Pilih yang jujur</strong><span>Pilih jawaban yang paling sesuai dengan kebiasaanmu sehari-hari.</span></li>
    <li><strong>Jawab semua</strong><span>Pastikan tidak ada pertanyaan yang terlewat agar hasil akurat.</span></li>
    <li><strong>Lihat hasil</strong><span>Dapatkan profil gaya belajar dan rekomendasi tipsnya.</span></li>
  </ol>

  <!-- Tombol untuk mulai masuk ke halaman soal kuis -->
  <div style="display: flex; justify-content: flex-end; margin-top: 30px;">
    <a href="<?= e(url('/kuis.php')) ?>" class="btn">Mulai Kuis Sekarang</a>
  </div>
</div>

<?php 
// Memuat bagian bawah halaman (footer dan penutup HTML)
require_once __DIR__ . '/includes/footer.php'; 
?>