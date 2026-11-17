<?php
// Memuat file koneksi database dan fungsi bantuan (seperti fungsi url())
require __DIR__ . '/config/koneksi.php';

// 1. Mengosongkan seluruh data sesi (session) yang sedang aktif
$_SESSION = [];

// 2. Menghancurkan sesi pengguna di server agar benar-benar keluar
session_destroy();

// 3. Mengarahkan kembali pengguna ke halaman masuk (login)
header('Location: ' . url('/masuk.php'));
exit;