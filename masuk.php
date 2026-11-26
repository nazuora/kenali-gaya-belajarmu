<?php
/*
  File: masuk.php
  Keterangan: Menangani proses masuk (login) dan pendaftaran akun
  pengguna dalam satu halaman menggunakan sistem tab.
*/

require __DIR__ . '/config/koneksi.php';

// Jika sudah login, langsung arahkan ke halaman kuis
if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    header('Location: ' . url('/kuis.php'));
    exit;
}

// Tentukan tab aktif ('masuk' atau 'daftar')
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

// Proses pengiriman form masuk maupun daftar (POST)
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

    // 1. Proses masuk akun
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

    // 2. Proses daftar akun baru
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

        if (count($err) === 0) {
            $hash = password_hash($sandi, PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO users (nama, email, password_hash, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param('sss', $nilai['nama'], $nilai['email'], $hash);

            try {
                // Untuk PHP di bawah 8.1, gagal simpan tidak melempar error, jadi dilempar manual
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
        <input class="t" id="in-sandi" name="sandi" type="password" autocomplete="current-password">
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
        <input class="t" id="up-sandi" name="sandi" type="password" autocomplete="new-password">
        <div class="err"><?= e($errDaftarSandi) ?></div>

        <label class="l" for="up-ulang">Ulangi kata sandi</label>
        <input class="t" id="up-ulang" name="ulang" type="password" autocomplete="new-password">
        <div class="err"><?= e($errDaftarUlang) ?></div>

        <button class="btn" type="submit" style="width:100%">Daftar</button>
      </form>
    </div>
  </div>
</div>

<script>
  // Beralih antara tab Masuk dan Daftar
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
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>