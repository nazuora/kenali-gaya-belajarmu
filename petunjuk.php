<?php
session_start();
// Menandai bahwa user sudah melewati halaman petunjuk
$_SESSION['sudah_baca_petunjuk'] = true;

require_once __DIR__ . '/config/koneksi.php';
$judul = 'Petunjuk Kuis';
require_once __DIR__ . '/includes/header.php';
?>

<div class="narrow" style="max-width: 700px;">
  <h1 class="s">Sebelum Memulai Kuis</h1>
  <p class="sub">
    Kuis ini bertujuan untuk mengetahui kecenderungan gaya belajarmu (Visual, Auditori, atau Kinestetik).
  </p>

  <ol class="steps" style="margin: 30px 0; padding-left: 20px;">
    <li><strong>Baca teliti</strong><span>Baca setiap pertanyaan dengan cermat.</span></li>
    <li><strong>Pilih yang jujur</strong><span>Pilih jawaban yang paling sesuai dengan kebiasaanmu sehari-hari.</span></li>
    <li><strong>Jawab semua</strong><span>Pastikan tidak ada pertanyaan yang terlewat agar hasil akurat.</span></li>
    <li><strong>Lihat hasil</strong><span>Dapatkan profil gaya belajar dan rekomendasi tipsnya.</span></li>
  </ol>

  <div style="display: flex; justify-content: flex-end; margin-top: 30px;">
    <a href="<?= e(url('/kuis.php')) ?>" class="btn">Mulai Kuis Sekarang</a>
  </div>
</div>

<?php 
require_once __DIR__ . '/includes/footer.php'; 
?>