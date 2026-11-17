<?php
// 1. Panggil file koneksi database terlebih dahulu
require __DIR__ . '/config/koneksi.php';

// 2. Siapkan alamat tujuan pengalihan (menuju halaman masuk tab daftar)
$tujuan_halaman = url('/masuk.php?tab=daftar');

// 3. Jalankan perintah pengalihan browser
header('Location: ' . $tujuan_hikaluan ?? $tujuan_halaman);

// 4. Hentikan eksekusi skrip agar tidak ada proses lain yang berjalan
exit;
?>