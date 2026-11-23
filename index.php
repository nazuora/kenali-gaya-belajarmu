<?php
/* 
  File: index.php
  Keterangan: Halaman utama (beranda) aplikasi "Kenali Gaya Belajarmu". 
  Berisi sambutan, informasi singkat mengenai jenis gaya belajar, 
  serta tombol navigasi untuk memulai kuis atau masuk ke akun.
*/

// Memanggil file koneksi database 
require __DIR__ . '/config/koneksi.php';

$judul = 'Kenali Gaya Belajarmu';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div>
    <h1>Temukan cara belajar yang paling pas untukmu.</h1>
    <p class="lead">Jawab pertanyaan seputar situasi sehari-hari. Kamu akan dapat profil gaya belajar dan tips yang bisa langsung dipraktikkan.</p>
    <div class="links" style="gap:12px">
      <a href="<?= e(url('/petunjuk.php')) ?>" class="btn">Mulai Kuis Sekarang</a>
    </div>
  </div>

  <?php
    // Gambar di sisi kanan beranda (assets/foto-home.png).
    // Mau diganti gambar lain? Cukup ubah nama file di bawah ini.
    $gambar_home = 'assets/foto-home.png';
    if (file_exists(__DIR__ . '/' . $gambar_home)):
  ?>
    <div class="hero-gambar">
      <img src="<?= e(url('/' . $gambar_home)) ?>" alt="Anak tersenyum mengacungkan jempol di depan laptop">
    </div>
  <?php endif; ?>
</section>

<h2 style="margin-top:72px;font-size:1.5rem;text-align:center">Cara kerjanya</h2>
<ol class="steps">
  <li><strong>Buat akun</strong><span>Supaya hasilmu tersimpan.</span></li>
  <li><strong>Jawab kuis</strong><span>Pilih jawaban yang paling mirip kebiasaanmu.</span></li>
  <li><strong>Lihat hasil</strong><span>Dapatkan persentase dan rekomendasi.</span></li>
  <li><strong>Pantau riwayat</strong><span>Bandingkan hasilmu dari waktu ke waktu.</span></li>
</ol>


<?php require __DIR__ . '/includes/footer.php'; ?>