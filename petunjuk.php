<?php
/* 
  File: petunjuk.php
  Keterangan: Halaman petunjuk pengerjaan kuis yang memberikan panduan 
  kepada pengguna sebelum mereka mulai menjawab pertanyaan gaya belajar.
*/

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

    <h1 class="s">Petunjuk Pengerjaan</h1>

    <p class="sub">
        Bacalah petunjuk berikut sebelum memulai kuis gaya belajar.
    </p>

    <ol class="steps" style="margin: 30px 0; padding-left: 20px;">

        <li>
            <strong>Baca setiap pertanyaan</strong>
            <span>Pahami pertanyaan dengan cermat sebelum memilih jawaban.</span>
        </li>

        <li>
            <strong>Pilih satu jawaban</strong>
            <span>Pilih jawaban yang paling sesuai dengan kebiasaanmu sehari-hari.</span>
        </li>

        <li>
            <strong>Jawab dengan jujur</strong>
            <span>Tidak ada jawaban benar atau salah dalam kuis ini.</span>
        </li>

        <li>
            <strong>Jawab semua pertanyaan</strong>
            <span>Pastikan tidak ada pertanyaan yang terlewat.</span>
        </li>

        <li>
            <strong>Lihat hasil kuis</strong>
            <span>Dapatkan hasil gaya belajar beserta rekomendasi tips belajar.</span>
        </li>

    </ol>

    <div style="display: flex; justify-content: flex-end; margin-top: 30px;">
        <a href="<?= e(url('/kuis.php')) ?>" class="btn">
            Mulai Kuis 
        </a>
    </div>

</div>

  <!-- Tombol untuk mulai masuk ke halaman soal kuis -->
  

<?php 
// Memuat bagian bawah halaman (footer dan penutup HTML)
require_once __DIR__ . '/includes/footer.php'; 
?>