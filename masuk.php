<?php
/*
  File: masuk.php
  Keterangan: Menangani proses masuk (login) dan pendaftaran akun
  pengguna dalam satu halaman menggunakan sistem tab.
*/

// Memuat konfigurasi koneksi, helper, dan sesi
require __DIR__ . '/config/koneksi.php';

// Mengalihkan pengguna yang sudah login langsung ke halaman kuis
if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    header('Location: ' . url('/kuis.php'));
    exit;
}

// Menentukan tab aktif awal dari parameter URL (?tab=daftar)
if (isset($_GET['tab']) && $_GET['tab'] === 'daftar') {
    $tab = 'daftar';
} else {
    $tab = 'masuk';
}

$err = [];
$nilai = [
    'nama'  => '',
    'email' => '',
];

// Memproses pengiriman form masuk maupun daftar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();

    if (isset($_POST['aksi'])) {
        $aksi = $_POST['aksi'];
    } else {
        $aksi = '';
    }

    if (isset($_POST['email'])) {
        $nilai['email'] = trim($_POST['email']);
    } else {
        $nilai['email'] = '';
    }

    if (isset($_POST['sandi'])) {
        $sandi = $_POST['sandi'];
    } else {
        $sandi = '';
    }

    // Proses autentikasi login pengguna
    if ($aksi === 'masuk') {
        $tab = 'masuk';

        if (!filter_var($nilai['email'], FILTER_VALIDATE_EMAIL)) {
            $err['email'] = 'Format email belum benar, contoh: nama@email.com.';
        }

        if (strlen($sandi) < 8) {
            $err['sandi'] = 'Kata sandi minimal 8 karakter.';
        }

        if (count($err) === 0) {
            $stmt = $db->prepare('SELECT id, nama, email, password_hash, role FROM users WHERE email = ?');
            $stmt->bind_param('s', $nilai['email']);
            $stmt->execute();
            $hasil = $stmt->get_result();
            $user = $hasil->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($sandi, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'    => (int)$user['id'],
                    'nama'  => $user['nama'],
                    'email' => $user['email'],
                    'role'  => $user['role'],
                ];

                header('Location: ' . url('/kuis.php'));
                exit;
            }

            $err['sandi'] = 'Email atau kata sandi salah.';
        }
    }

    // Proses registrasi akun baru dengan role default 'user'
    if ($aksi === 'daftar') {
        $tab = 'daftar';

        if (isset($_POST['nama'])) {
            $nilai['nama'] = trim($_POST['nama']);
        } else {
            $nilai['nama'] = '';
        }

        if (isset($_POST['ulang'])) {
            $ulang = $_POST['ulang'];
        } else {
            $ulang = '';
        }

        if (mb_strlen($nilai['nama']) < 4) {
            $err['nama'] = 'Isi nama minimal 4 huruf.';
        } elseif (mb_strlen($nilai['nama']) > 60) {
            $err['nama'] = 'Nama maksimal 60 karakter.';
        }

        if (!filter_var($nilai['email'], FILTER_VALIDATE_EMAIL)) {
            $err['email'] = 'Format email belum benar, contoh: nama@email.com.';
        } elseif (strlen($nilai['email']) > 100) {
            $err['email'] = 'Email maksimal 100 karakter.';
        }

        if (strlen($sandi) < 8) {
            $err['sandi'] = 'Kata sandi minimal 8 karakter.';
        }

        if ($ulang !== $sandi) {
            $err['ulang'] = 'Kata sandi tidak sama.';
        }

        if (count($err) === 0) {
            $hash = password_hash($sandi, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO users (nama, email, password_hash, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param('sss', $nilai['nama'], $nilai['email'], $hash);

            try {
               // Melempar mysqli_sql_exception saat query gagal agar error bisa ditangani
                $stmt->execute();
                $idBaru = (int)$db->insert_id;
                $stmt->close();

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'    => $idBaru,
                    'nama'  => $nilai['nama'],
                    'email' => $nilai['email'],
                    'role'  => 'user',
                ];

                header('Location: ' . url('/kuis.php'));
                exit;
            } catch (mysqli_sql_exception $ex) {
                $stmt->close();
                if ($ex->getCode() === 1062) {
                    $err['email'] = 'Email ini sudah terdaftar. Silakan masuk.';
                } else {
                    $err['email'] = 'Gagal mendaftarkan akun. Silakan coba lagi.';
                }
            }
        }
    }
}

// Menyiapkan status aktif tab dan visibilitas panel form
if ($tab === 'masuk') {
    $selectedMasuk = 'true';
    $selectedDaftar = 'false';
    $kelasPanelMasuk = '';
    $kelasPanelDaftar = 'hidden';
} else {
    $selectedMasuk = 'false';
    $selectedDaftar = 'true';
    $kelasPanelMasuk = 'hidden';
    $kelasPanelDaftar = '';
}

// Memetakan pesan error per field sesuai tab aktif
$errMasukEmail = '';
if (isset($err['email']) && $tab === 'masuk') {
    $errMasukEmail = $err['email'];
}

$errMasukSandi = '';
if (isset($err['sandi']) && $tab === 'masuk') {
    $errMasukSandi = $err['sandi'];
}

$errDaftarNama = '';
if (isset($err['nama']) && $tab === 'daftar') {
    $errDaftarNama = $err['nama'];
}

$errDaftarEmail = '';
if (isset($err['email']) && $tab === 'daftar') {
    $errDaftarEmail = $err['email'];
}

$errDaftarSandi = '';
if (isset($err['sandi']) && $tab === 'daftar') {
    $errDaftarSandi = $err['sandi'];
}

$errDaftarUlang = '';
if (isset($err['ulang']) && $tab === 'daftar') {
    $errDaftarUlang = $err['ulang'];
}

// Mempertahankan nilai email sesuai tab yang sedang aktif
$nilaiEmailMasuk = '';
if ($tab === 'masuk') {
    $nilaiEmailMasuk = $nilai['email'];
}

$nilaiEmailDaftar = '';
if ($tab === 'daftar') {
    $nilaiEmailDaftar = $nilai['email'];
}

$judul = 'Masuk';
require __DIR__ . '/includes/header.php';
?>

<div class="narrow" style="max-width:440px">
  <div class="card">
    <div class="seg" role="tablist">
      <button type="button" role="tab" id="t-in" aria-selected="<?= $selectedMasuk ?>">Masuk</button>
      <button type="button" role="tab" id="t-up" aria-selected="<?= $selectedDaftar ?>">Daftar</button>
    </div>

    <div id="p-in" class="<?= $kelasPanelMasuk ?>">
      <h1 class="s">Selamat datang kembali</h1>
      <p class="sub">Masuk untuk menyimpan dan melihat riwayat hasil kuismu.</p>

      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="masuk">

        <label class="l" for="in-email">Email</label>
        <input class="t" id="in-email" name="email" type="email" autocomplete="email" placeholder="nama@email.com" value="<?= e($nilaiEmailMasuk) ?>">
        <div class="err"><?= e($errMasukEmail) ?></div>

        <label class="l" for="in-sandi">Kata sandi</label>
        <div class="kotak-sandi">
  <input class="t" id="in-sandi" name="sandi" type="password" autocomplete="current-password">
  <button type="button" class="tombol-mata" aria-label="Tampilkan kata sandi" aria-pressed="false">
    <svg class="ikon-mata" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
      <circle cx="12" cy="12" r="3"></circle>
    </svg>
    <svg class="ikon-mata-tutup hidden" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
      <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-5.94"></path>
      <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a21.85 21.85 0 0 1-2.16 3.19"></path>
      <line x1="1" y1="1" x2="23" y2="23"></line>
    </svg>
  </button>
</div>
        <div class="err"><?= e($errMasukSandi) ?></div>

        <button class="btn" type="submit" style="width:100%">Masuk</button>
      </form>
    </div>

    <div id="p-up" class="<?= $kelasPanelDaftar ?>">
      <h1 class="s">Buat akun baru</h1>
      <p class="sub">Simpan hasil kuismu dan pantau perkembangannya.</p>

      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="daftar">

        <label class="l" for="up-nama">Nama</label>
        <input class="t" id="up-nama" name="nama" autocomplete="name" value="<?= e($nilai['nama']) ?>">
        <div class="err"><?= e($errDaftarNama) ?></div>

        <label class="l" for="up-email">Email</label>
        <input class="t" id="up-email" name="email" type="email" autocomplete="email" placeholder="nama@email.com" value="<?= e($nilaiEmailDaftar) ?>">
        <div class="err"><?= e($errDaftarEmail) ?></div>

        <label class="l" for="up-sandi">Kata sandi</label>
        <div class="kotak-sandi">
          <input class="t" id="up-sandi" name="sandi" type="password" autocomplete="new-password">
          <button type="button" class="tombol-mata" aria-label="Tampilkan kata sandi" aria-pressed="false">
            <svg class="ikon-mata" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg class="ikon-mata-tutup hidden" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-5.94"></path>
              <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a21.85 21.85 0 0 1-2.16 3.19"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          </button>
        </div>
        <div class="err"><?= e($errDaftarSandi) ?></div>

        <label class="l" for="up-ulang">Ulangi kata sandi</label>
        <div class="kotak-sandi">
          <input class="t" id="up-ulang" name="ulang" type="password" autocomplete="new-password">
          <button type="button" class="tombol-mata" aria-label="Tampilkan kata sandi" aria-pressed="false">
            <svg class="ikon-mata" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg class="ikon-mata-tutup hidden" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-5.94"></path>
              <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a21.85 21.85 0 0 1-2.16 3.19"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          </button>
        </div>
        <div class="err"><?= e($errDaftarUlang) ?></div>

        <button class="btn" type="submit" style="width:100%">Daftar</button>
      </form>
    </div>
  </div>
</div>

<script>
  // Mengalihkan tab aktif dan panel form tanpa memuat ulang halaman
  function gantiTab(keDaftar) {
    document.getElementById('t-in').setAttribute('aria-selected', !keDaftar);
    document.getElementById('t-up').setAttribute('aria-selected', keDaftar);
    document.getElementById('p-in').classList.toggle('hidden', keDaftar);
    document.getElementById('p-up').classList.toggle('hidden', !keDaftar);
  }

  document.getElementById('t-in').onclick = function() {
    gantiTab(false);
  };

  document.getElementById('t-up').onclick = function() {
    gantiTab(true);
  };

  // Menampilkan atau menyembunyikan teks sandi saat tombol ikon mata diklik
  document.querySelectorAll('.tombol-mata').forEach(function(tombol) {
    tombol.addEventListener('click', function() {
      const wadah = tombol.closest('.kotak-sandi');
      const inputSandi = wadah ? wadah.querySelector('input') : null;
      if (!inputSandi) return;

      const sedangTerlihat = inputSandi.type === 'text';
      inputSandi.type = sedangTerlihat ? 'password' : 'text';

      tombol.setAttribute('aria-pressed', String(!sedangTerlihat));
      tombol.setAttribute(
        'aria-label',
        sedangTerlihat ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi'
      );

      const ikonBuka = tombol.querySelector('.ikon-mata');
      const ikonTutup = tombol.querySelector('.ikon-mata-tutup');
      if (ikonBuka && ikonTutup) {
        ikonBuka.classList.toggle('hidden', !sedangTerlihat);
        ikonTutup.classList.toggle('hidden', sedangTerlihat);
      }
    });
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>