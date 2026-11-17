<?php
// Memulai sesi untuk mengecek status login pengguna
session_start();

// Memuat file koneksi database
require __DIR__ . '/config/koneksi.php';

// Jika pengguna sudah dalam keadaan login, langsung alihkan ke halaman kuis
if (!empty($_SESSION['user'])) {
    header('Location: ' . url('/kuis.php'));
    exit;
}

// Menentukan tab aktif (pilih antara 'masuk' atau 'daftar')
$tab   = (($_GET['tab'] ?? '') === 'daftar') ? 'daftar' : 'masuk';
$err   = [];
$nilai = ['nama' => '', 'email' => ''];

// Mengecek apakah form sedang dikirimkan (disubmit) menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Memeriksa keamanan CSRF token
    csrf_periksa();
    
    $aksi  = $_POST['aksi'] ?? '';
    $nilai['email'] = trim($_POST['email'] ?? '');
    $sandi = $_POST['sandi'] ?? '';

    // ==========================================
    // 1. PROSES MASUK (LOGIN)
    // ==========================================
    if ($aksi === 'masuk') {
        $tab = 'masuk';

        // Validasi form masuk
        if (!filter_var($nilai['email'], FILTER_VALIDATE_EMAIL)) {
            $err['email'] = 'Format email belum benar, contoh: nama@email.com.';
        }
        if (strlen($sandi) < 8) {
            $err['sandi'] = 'Kata sandi minimal 8 karakter.';
        }

        // Jika tidak ada error validasi, cek ke database
        if (empty($err)) {
            // Menyiapkan query aman untuk mencari data user berdasarkan email
            $stmt = $db->prepare('SELECT id, nama, email, password_hash, role FROM users WHERE email = ?');
            $stmt->bind_param('s', $nilai['email']);
            $stmt->execute();
            $hasil = $stmt->get_result();
            $user = $hasil->fetch_assoc();

            // Memeriksa apakah user ditemukan dan kata sandinya cocok
            if ($user && password_verify($sandi, $user['password_hash'])) {
                // Simpan data user ke dalam sesi PHP
                $_SESSION['user'] = [
                    'id'    => (int)$user['id'],
                    'nama'  => $user['nama'],
                    'email' => $user['email'],
                    'role'  => $user['role']
                ];
                $hasilId = simpan_hasil_tertunda($db, (int)$user['id']);
                
                // Arahkan ke hasil kuis jika ada, atau ke halaman kuis utama
                $tujuan = $hasilId ? '/hasil.php?id=' . $hasilId : '/kuis.php';
                header('Location: ' . url($tujuan));
                exit;
            }
            
            // Pesan error umum agar lebih aman
            $err['sandi'] = 'Email atau kata sandi salah.';
        }
    }

    // ==========================================
    // 2. PROSES DAFTAR AKUN BARU
    // ==========================================
    if ($aksi === 'daftar') {
        $tab = 'daftar';
        $nilai['nama'] = trim($_POST['nama'] ?? '');
        $ulang = $_POST['ulang'] ?? '';

        // Validasi form pendaftaran
        if (mb_strlen($nilai['nama']) < 2) {
            $err['nama'] = 'Isi nama minimal 2 huruf.';
        }
        if (!filter_var($nilai['email'], FILTER_VALIDATE_EMAIL)) {
            $err['email'] = 'Format email belum benar, contoh: nama@email.com.';
        }
        if (strlen($sandi) < 8) {
            $err['sandi'] = 'Kata sandi minimal 8 karakter.';
        }
        if ($ulang !== $sandi) {
            $err['ulang'] = 'Kata sandi tidak sama.';
        }

        // Jika tidak ada error, simpan akun baru ke database
        if (empty($err)) {
            $hash = password_hash($sandi, PASSWORD_DEFAULT);
            
            // Menyimpan data user baru dengan peran default 'user'
            $stmt = $db->prepare("INSERT INTO users (nama, email, password_hash, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param('sss', $nilai['nama'], $nilai['email'], $hash);
            
            if ($stmt->execute()) {
                $idBaru = (int)$db->insert_id;
                
                // Langsung masukkan ke sesi agar otomatis login setelah mendaftar
                $_SESSION['user'] = [
                    'id'    => $idBaru, 
                    'nama'  => $nilai['nama'], 
                    'email' => $nilai['email'], 
                    'role'  => 'user'
                ];
                
                $hasilId = simpan_hasil_tertunda($db, $idBaru);
                $tujuan = $hasilId ? '/hasil.php?id=' . $hasilId : '/kuis.php';
                header('Location: ' . url($tujuan));
                exit;
            } else {
                // Penanganan jika email sudah terdaftar di database
                $err['email'] = 'Email ini sudah terdaftar. Silakan masuk.';
            }
        }
    }
}

$judul = 'Masuk';
require __DIR__ . '/includes/header.php';
?>

<div class="narrow" style="max-width:440px">
  <div class="card">
    <!-- Tombol pilihan Tab (Masuk atau Daftar) -->
    <div class="seg" role="tablist">
      <button type="button" role="tab" id="t-in" aria-selected="<?= $tab === 'masuk' ? 'true' : 'false' ?>">Masuk</button>
      <button type="button" role="tab" id="t-up" aria-selected="<?= $tab === 'daftar' ? 'true' : 'false' ?>">Daftar</button>
    </div>

    <!-- Form Masuk -->
    <div id="p-in" class="<?= $tab === 'masuk' ? '' : 'hidden' ?>">
      <h1 class="s">Selamat datang kembali</h1>
      <p class="sub">Masuk untuk menyimpan dan melihat riwayat hasil kuismu.</p>
      
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="masuk">
        
        <label class="l" for="in-email">Email</label>
        <input class="t" id="in-email" name="email" type="email" autocomplete="email" placeholder="nama@email.com" value="<?= $tab === 'masuk' ? e($nilai['email']) : '' ?>">
        <div class="err"><?= $tab === 'masuk' ? e($err['email'] ?? '') : '' ?></div>
        
        <label class="l" for="in-sandi">Kata sandi</label>
        <input class="t" id="in-sandi" name="sandi" type="password" autocomplete="current-password">
        <div class="err"><?= $tab === 'masuk' ? e($err['sandi'] ?? '') : '' ?></div>
        
        <button class="btn" type="submit" style="width:100%">Masuk</button>
      </form>
    </div>

    <!-- Form Daftar -->
    <div id="p-up" class="<?= $tab === 'daftar' ? '' : 'hidden' ?>">
      <h1 class="s">Buat akun baru</h1>
      <p class="sub">Simpan hasil kuismu dan pantau perkembangannya.</p>
      
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="daftar">
        
        <label class="l" for="up-nama">Nama</label>
        <input class="t" id="up-nama" name="nama" autocomplete="name" value="<?= e($nilai['nama']) ?>">
        <div class="err"><?= e($err['nama'] ?? '') ?></div>
        
        <label class="l" for="up-email">Email</label>
        <input class="t" id="up-email" name="email" type="email" autocomplete="email" placeholder="nama@email.com" value="<?= $tab === 'daftar' ? e($nilai['email']) : '' ?>">
        <div class="err"><?= $tab === 'daftar' ? e($err['email'] ?? '') : '' ?></div>
        
        <label class="l" for="up-sandi">Kata sandi</label>
        <input class="t" id="up-sandi" name="sandi" type="password" autocomplete="new-password">
        <div class="err"><?= $tab === 'daftar' ? e($err['sandi'] ?? '') : '' ?></div>
        
        <label class="l" for="up-ulang">Ulangi kata sandi</label>
        <input class="t" id="up-ulang" name="ulang" type="password" autocomplete="new-password">
        <div class="err"><?= e($err['ulang'] ?? '') ?></div>
        
        <button class="btn" type="submit" style="width:100%">Daftar</button>
      </form>
    </div>
  </div>
</div>

<script>
  // Fungsi JavaScript sederhana untuk beralih antara tab Masuk dan Daftar
  function gantiTab(keDaftar) {
    document.getElementById('t-in').setAttribute('aria-selected', !keDaftar);
    document.getElementById('t-up').setAttribute('aria-selected', keDaftar);
    document.getElementById('p-in').classList.toggle('hidden', keDaftar);
    document.getElementById('p-up').classList.toggle('hidden', !keDaftar);
  }
  
  document.getElementById('t-in').onclick = () => gantiTab(false);
  document.getElementById('t-up').onclick = () => gantiTab(true);
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>