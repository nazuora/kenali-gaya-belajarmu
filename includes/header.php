<?php
/*
  File: includes/header.php
  Keterangan: Bagian kepala (header) dan menu navigasi atas yang
  muncul di setiap halaman website.
*/

if (isset($_SERVER['SCRIPT_NAME'])) {
    $halaman_aktif = basename($_SERVER['SCRIPT_NAME']);
} else {
    $halaman_aktif = '';
}

$pengguna = null;
if (isset($_SESSION['user'])) {
    $pengguna = $_SESSION['user'];
}

if (isset($judul) && $judul !== '') {
    $judul_halaman = $judul;
} else {
    $judul_halaman = 'Kenali Gaya Belajarmu';
}

$aktif_home = '';
if ($halaman_aktif === 'index.php') {
    $aktif_home = ' aria-current="page"';
}

$aktif_jenis = '';
if ($halaman_aktif === 'jenis.php') {
    $aktif_jenis = ' aria-current="page"';
}

$aktif_kuis = '';
if ($halaman_aktif === 'petunjuk.php' || $halaman_aktif === 'kuis.php' || $halaman_aktif === 'hasil.php') {
    $aktif_kuis = ' aria-current="page"';
}

$aktif_riwayat = '';
if ($halaman_aktif === 'riwayat.php') {
    $aktif_riwayat = ' aria-current="page"';
}

$aktif_admin = '';
if ($halaman_aktif === 'pertanyaan.php' || $halaman_aktif === 'gaya.php') {
    $aktif_admin = ' aria-current="page"';
}

$aktif_akun = '';
if ($halaman_aktif === 'profil.php' || $halaman_aktif === 'masuk.php' || $halaman_aktif === 'daftar.php') {
    $aktif_akun = ' aria-current="page"';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

  <title><?= e($judul_halaman) ?></title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&family=Fraunces:opsz,wght@9..144,600;9..144,800&display=swap">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css">
</head>
<body>

<header>
  <div class="wrap">
    <nav aria-label="Navigasi utama">

      <a href="<?= e(url('/')) ?>" class="logo">
        Kenali Gaya Belajarmu
      </a>

      <div class="links">

        <a href="<?= e(url('/')) ?>"<?= $aktif_home ?>>
          Home
        </a>

        <a href="<?= e(url('/jenis.php')) ?>"<?= $aktif_jenis ?>>
          Jenis
        </a>

        <a href="<?= e(url('/petunjuk.php')) ?>"<?= $aktif_kuis ?>>
          Kuis
        </a>

        <a href="<?= e(url('/riwayat.php')) ?>"<?= $aktif_riwayat ?>>
          Riwayat
        </a>

        <a href="<?= e(url('/admin/pertanyaan.php')) ?>"<?= $aktif_admin ?>>
          Admin
        </a>

        <?php
        if ($pengguna) {
            if (isset($pengguna['nama'])) {
                $pecah_nama = explode(' ', trim((string)$pengguna['nama']));
                $nama_panggilan = $pecah_nama[0];
            } else {
                $nama_panggilan = 'Akun';
            }
        ?>
          <a href="<?= e(url('/profil.php')) ?>"<?= $aktif_akun ?>>
            <?= e($nama_panggilan) ?>
          </a>
        <?php } else { ?>
          <a href="<?= e(url('/masuk.php')) ?>"<?= $aktif_akun ?>>
            Masuk
          </a>
        <?php } ?>

        <span id="jam"></span>

      </div>
    </nav>
  </div>
</header>

<main class="wrap">