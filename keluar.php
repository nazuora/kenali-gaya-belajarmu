<?php
/*
  File: keluar.php
  Keterangan: Mengakhiri sesi login pengguna (logout) dengan menghapus
  data sesi serta cookie sesi, lalu mengarahkan ke halaman masuk.
*/

require __DIR__ . '/config/koneksi.php';

// Kosongkan seluruh data sesi yang sedang aktif
$_SESSION = [];

// Hapus cookie sesi di browser jika memang digunakan
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

// Hancurkan sesi di server lalu arahkan ke halaman masuk
session_destroy();

header('Location: ' . url('/masuk.php'));
exit;