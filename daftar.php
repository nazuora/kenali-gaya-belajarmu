<?php
/*
  File: daftar.php
  Keterangan: Mengalihkan pengunjung secara otomatis ke halaman masuk
  pada tab daftar karena form pendaftaran sudah digabung ke dalam masuk.php.
*/

// Memuat file koneksi dan fungsi helper url()
require __DIR__ . '/config/koneksi.php';

// Menyiapkan URL tujuan ke tab daftar pada halaman masuk.php
$tujuan_halaman = url('/masuk.php?tab=daftar');

// Mengecek apakah URL tujuan tersedia dan tidak kosong sebelum redirect
if (isset($tujuan_halaman) && $tujuan_halaman !== '') {
    header('Location: ' . $tujuan_halaman);
} else {
    header('Location: ' . url('/masuk.php'));
}

// Menghentikan eksekusi skrip setelah perintah redirect dikirim
exit;