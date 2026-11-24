<?php
/*
  File: daftar.php
  Keterangan: Mengalihkan pengunjung secara otomatis ke halaman masuk
  pada tab daftar karena form pendaftaran sudah digabung ke dalam masuk.php.
*/

require __DIR__ . '/config/koneksi.php';

// Siapkan alamat tujuan pengalihan ke halaman masuk tab daftar
$tujuan_halaman = url('/masuk.php?tab=daftar');

if (isset($tujuan_halaman) && $tujuan_halaman !== '') {
    header('Location: ' . $tujuan_halaman);
} else {
    header('Location: ' . url('/masuk.php'));
}

exit;