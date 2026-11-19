
<?php
/*
  File: profil.php
  Keterangan: Halaman untuk menampilkan dan mengelola informasi profil
  serta pengaturan akun pengguna yang sedang masuk.
*/

require __DIR__ . '/config/koneksi.php';

wajib_login();

$id_user = (int)$_SESSION['user']['id'];

$folder_tujuan = __DIR__ . '/uploads/';
$maks_ukuran = 2 * 1024 * 1024;

// Mengambil data pengguna
function ambil_data_user($db, $id_user) {
    $query = "SELECT nama, email, password_hash, foto FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id_user);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

$user = ambil_data_user($db, $id_user);
$pesan = '';
$error = '';

// Memproses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();

    $aksi = $_POST['aksi'] ?? '';

    // SIMPAN PERUBAHAN PROFIL
    if ($aksi === 'simpan') {
        $nama_baru = trim($_POST['nama'] ?? '');
        $foto_baru = $user['foto'];

        if (strlen($nama_baru) < 2) {
            $error = 'Nama harus diisi minimal 2 huruf.';
        }

        // Memproses foto profil
        if (
            $error === '' &&
            isset($_FILES['foto']) &&
            $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $file_foto = $_FILES['foto'];

            if ($file_foto['error'] !== UPLOAD_ERR_OK) {
                $error = 'Terjadi kesalahan saat mengunggah foto.';
            } elseif ($file_foto['size'] > $maks_ukuran) {
                $error = 'Ukuran foto terlalu besar, maksimal 2 MB.';
            } else {
                $ekstensi_file = strtolower(
                    pathinfo($file_foto['name'], PATHINFO_EXTENSION)
                );

                $ekstensi_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ekstensi_file, $ekstensi_diizinkan, true)) {
                    $error = 'Format foto harus JPG, PNG, atau WebP.';
                } else {
                    $nama_file_unik = 'foto_' . $id_user . '_' . time() . '.' . $ekstensi_file;

                    if (move_uploaded_file(
                        $file_foto['tmp_name'],
                        $folder_tujuan . $nama_file_unik
                    )) {
                        if (
                            !empty($user['foto']) &&
                            file_exists($folder_tujuan . $user['foto'])
                        ) {
                            unlink($folder_tujuan . $user['foto']);
                        }

                        $foto_baru = $nama_file_unik;
                    } else {
                        $error = 'Gagal menyimpan foto ke folder uploads.';
                    }
                }
            }
        }

        // Menyimpan perubahan ke database
        if ($error === '') {
            $query_update = "UPDATE users SET nama = ?, foto = ? WHERE id = ?";
            $stmt = $db->prepare($query_update);
            $stmt->bind_param('ssi', $nama_baru, $foto_baru, $id_user);
            $stmt->execute();

            $_SESSION['user']['nama'] = $nama_baru;
            $user = ambil_data_user($db, $id_user);

            $pesan = 'Perubahan profil berhasil disimpan!';
        }
    }

    // HAPUS AKUN
    if ($aksi === 'hapus_akun') {
        $sandi_input = $_POST['sandi'] ?? '';

        if (!password_verify($sandi_input, $user['password_hash'])) {
            $error = 'Kata sandi salah! Akun batal dihapus.';
        } else {
            // Menghapus foto profil
            if (
                !empty($user['foto']) &&
                file_exists($folder_tujuan . $user['foto'])
            ) {
                unlink($folder_tujuan . $user['foto']);
            }

            // Menghapus akun pengguna
            $query_hapus = "DELETE FROM users WHERE id = ?";
            $stmt = $db->prepare($query_hapus);
            $stmt->bind_param('i', $id_user);
            $stmt->execute();

            // Mengakhiri sesi login
            $_SESSION = [];
            session_destroy();

            header('Location: ' . url('/'));
            exit;
        }
    }
}

$judul = 'Profil Pengguna';
require __DIR__ . '/includes/header.php';
?>

<div class="narrow" style="max-width:520px">

  <h1 class="s">Profilmu</h1>

  <?php if ($pesan !== ''): ?>
    <div class="msg" role="status">
      <?= htmlspecialchars($pesan) ?>
    </div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="err" role="alert">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- KARTU PROFIL -->
  <div class="card">

    <!-- FORM EDIT PROFIL -->
    <form method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan">

      <div class="who">
        <div class="ava" id="ava" aria-hidden="true">
          <?php if (!empty($user['foto'])): ?>
            <img
              alt="Foto Profil"
              src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($user['foto']) ?>"
            >
          <?php else: ?>
            <?= htmlspecialchars(strtoupper(substr($user['nama'], 0, 1))) ?>
          <?php endif; ?>
        </div>

        <div>
          <strong><?= htmlspecialchars($user['nama']) ?></strong><br>

          <label class="file">
            Ganti foto
            <input
              type="file"
              id="foto"
              name="foto"
              accept="image/jpeg,image/png,image/webp"
            >
          </label>
        </div>
      </div>

      <label class="l" for="nama">Nama Lengkap</label>
      <input
        class="t"
        id="nama"
        name="nama"
        value="<?= htmlspecialchars($user['nama']) ?>"
        autocomplete="name"
      >

      <label class="l" for="email">Email</label>
      <input
        class="t"
        id="email"
        value="<?= htmlspecialchars($user['email']) ?>"
        readonly
        aria-describedby="petunjuk"
      >

      <div class="hint" id="petunjuk" style="margin-bottom:12px">
        Email tidak bisa diubah. Format foto yang didukung: JPG, PNG, atau WebP, dengan ukuran maksimal 2 MB.
      </div>

      <div class="actions" style="justify-content:flex-start;align-items:center">
        <button class="btn" type="submit">
          Simpan Perubahan
        </button>

        <a class="btn ghost" href="<?= htmlspecialchars(url('/keluar.php')) ?>">
          Keluar
        </a>
      </div>
    </form>

    <!-- PEMISAH BAGIAN -->
    <div style="border-top:1px solid var(--line);margin:22px 0"></div>

    <!-- BAGIAN HAPUS AKUN DALAM KARTU YANG SAMA -->
    <div>
      <h2 style="margin:0 0 8px">Hapus Akun</h2>

      <p>
        Semua data akun dan seluruh riwayat kuis kamu akan dihapus secara permanen.
      </p>

      <form
        method="post"
        onsubmit="return confirm('Yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan.')"
      >
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="hapus_akun">

        <label class="l" for="sandi">
          Masukkan kata sandi untuk konfirmasi
        </label>

        <input
          class="t"
          id="sandi"
          name="sandi"
          type="password"
          required
          autocomplete="current-password"
          style="margin-bottom:12px"
        >

        <button class="btn red" type="submit">
          Hapus Akun Saya
        </button>
      </form>
    </div>

  </div>
</div>

<script>
// Pratinjau foto sebelum disimpan
document.getElementById('foto').onchange = function(event) {
    const file = event.target.files[0];

    if (!file) return;

    const reader = new FileReader();

    reader.onload = function(e) {
        document.getElementById('ava').innerHTML =
            '<img alt="Pratinjau Foto" src="' + e.target.result + '">';
    };

    reader.readAsDataURL(file);
};
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
