<?php
/*
  File: includes/header.php
  Keterangan: Bagian kepala (header) dan menu navigasi atas yang 
  akan muncul di setiap halaman website.
*/

// Ambil nama file halaman yang sedang dibuka saat ini
$halaman_aktif = basename($_SERVER['SCRIPT_NAME']);

// Cek apakah pengguna sudah login atau belum (ambil data dari sesi)
$pengguna = null;
if (isset($_SESSION['user'])) {
    $pengguna = $_SESSION['user'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  
  <!-- Judul halaman bersifat dinamis, jika tidak diatur maka pakai judul default -->
  <title><?php echo isset($judul) ? $judul : 'Kenali Gaya Belajarmu'; ?></title>
  
  <!-- Memuat font dari Google Fonts dan file CSS utama -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&family=Fraunces:opsz,wght@9..144,600;9..144,800&display=swap">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>

<header>
  <div class="wrap">
    <nav aria-label="Navigasi utama">
      
      <!-- Logo Website -->
      <a href="<?= url('/') ?>" class="logo">
        <span class="dots" aria-hidden="true">
          <i style="background:var(--blue)"></i>
          <i style="background:var(--yellow)"></i>
          <i style="background:var(--green)"></i>
        </span>
        Kenali Gaya Belajarmu
      </a>

      <!-- Daftar Menu Navigasi -->
      <div class="links">
        
        <!-- Menu Beranda / Home -->
        <a href="<?= url('/') ?>" <?php if ($halaman_aktif == 'index.php') echo 'aria-current="page"'; ?>>
          Home
        </a>
        
        <!-- Menu Jenis Gaya Belajar -->
        <a href="<?= url('/jenis.php') ?>" <?php if ($halaman_aktif == 'jenis.php') echo 'aria-current="page"'; ?>>
          Jenis
        </a>
        
        <!-- Menu Petunjuk Penggunaan -->
        <a href="<?= url('/petunjuk.php') ?>" <?php if ($halaman_aktif == 'petunjuk.php') echo 'aria-current="page"'; ?>>
          Petunjuk
        </a>
        
        <!-- Menu Kuis (Hanya menyala jika benar-benar di kuis.php atau hasil.php) -->
        <a href="<?= url('/petunjuk.php') ?>" <?php if ($halaman_aktif == 'kuis.php' || $halaman_aktif == 'hasil.php') echo 'aria-current="page"'; ?>>
          Kuis
        </a>
        
        <!-- Menu Riwayat Tes Pengguna -->
        <a href="<?= url('/riwayat.php') ?>" <?php if ($halaman_aktif == 'riwayat.php') echo 'aria-current="page"'; ?>>
          Riwayat
        </a>

        <?php 
          // Jika yang login adalah admin, tampilkan menu Admin
          if ($pengguna && isset($pengguna['role']) && $pengguna['role'] == 'admin') { 
        ?>
          <a href="<?= url('/admin/pertanyaan.php') ?>" <?php if ($halaman_aktif == 'pertanyaan.php' || $halaman_aktif == 'gaya.php') echo 'aria-current="page"'; ?>>
            Admin
          </a>
        <?php } ?>

        <?php 
          // Jika sudah login, tampilkan nama depan pengguna. Jika belum, tampilkan tombol Masuk.
          if ($pengguna) { 
              // Mengambil kata pertama dari nama lengkap pengguna
              $pecah_nama = explode(' ', trim($pengguna['nama']));
              $nama_panggilan = $pecah_nama[0];
        ?>
          <a href="<?= url('/profil.php') ?>" <?php if ($halaman_aktif == 'profil.php') echo 'aria-current="page"'; ?>>
            <?= $nama_panggilan ?>
          </a>
        <?php } else { ?>
          <a href="<?= url('/masuk.php') ?>" <?php if ($halaman_aktif == 'masuk.php' || $halaman_aktif == 'daftar.php') echo 'aria-current="page"'; ?>>
            Masuk
          </a>
        <?php } ?>

      </div>
    </nav>
  </div>
</header>

<main class="wrap">