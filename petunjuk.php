<?php
/*
  File: petunjuk.php
  Keterangan: Halaman petunjuk aturan dan cara mengerjakan
  kuis gaya belajar sebelum pengguna mulai menjawab soal.
*/

session_start(); // memulai session atau menyimpan informasi sementara
  $_SESSION['sudah_baca_petunjuk'] = true; // menyimpan informasi pengguna udah lewat hal petunjuk

require_once __DIR__ . '/config/koneksi.php'; // memanggil fine koneksi 

$judul = 'Petunjuk Kuis'; //

require_once __DIR__ . '/includes/header.php'; // include header

?>

<div class="narrow" style="max-width: 700px;">

    <h1 class="s">Petunjuk Pengerjaan Kuis</h1>

    <p class="sub">
    Bacalah petunjuk berikut sebelum memulai kuis gaya belajar
    </p>

    <ol class="steps" style="margin: 30px 0; padding-left: 20px;">
        <li>
            <strong>Baca setiap pertanyaan</strong>
            <span>Pahami pertanyaan dengan cermat sebelum memilih jawaban</span>
        </li>
        <li>
             <strong>Pilih satu jawaban</strong>
             <span>Pilih jawaban yang paling sesuai dengan kebiasaanmu sehari-hari</span>
        </li>
        <li>
            <strong>Jawab dengan jujur</strong>
            <span>Tidak ada jawaban benar atau salah dalam kuis ini</span>
        </li>
        <li>
            <strong>Jawab semua pertanyaan</strong>
            <span>Pastikan tidak ada pertanyaan yang terlewat</span>
        </li>
        <li>
             <strong>Lihat hasil kuis</strong>
             <span>Dapatkan hasil gaya belajar beserta rekomendasi tips belajar</span>
        </li>
    </ol>  

<div style="display: flex; justify-content: flex-end; margin-top: 30px;"> 
    <a href="<?= e(url('/kuis.php')) ?>" class="btn"> 
    Mulai Kuis 
    </a> 
</div>

<?php
require_once __DIR__ . '/includes/footer.php'; //include footer
?>