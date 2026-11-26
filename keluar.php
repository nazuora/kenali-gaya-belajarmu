<?php
/*
  File: keluar.php
  Keterangan: Mengakhiri sesi login pengguna (logout) dengan menghapus
  data sesi serta cookie sesi, lalu mengarahkan ke halaman masuk.
*/

// Memuat konfigurasi koneksi, helper URL, dan inisialisasi sesi
require __DIR__ . '/config/koneksi.php';

// Mengosongkan seluruh data sesi yang aktif
$_SESSION = [];

// Menghapus cookie sesi di browser jika sesi menggunakan cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Menghancurkan sesi di server lalu mengalihkan pengguna ke halaman masuk
session_destroy();

header('Location: ' . url('/masuk.php'));
exit;