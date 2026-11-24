<?php
/*
  File: profil.php
  Keterangan: Menampilkan dan mengelola informasi profil pengguna (ubah nama,
  unggah foto profil, serta hapus akun) yang sudah login.
*/

require __DIR__ . '/config/koneksi.php';

wajib_login();

$id_user = (int)$_SESSION['user']['id'];
$folder_tujuan = __DIR__ . '/uploads/';
$maks_ukuran = 2 * 1024 * 1024;

// Mengambil data pengguna dari database berdasarkan ID sesi
function ambil_data_user($db, $id_user) {
    $query = 'SELECT nama, email, password_hash, foto FROM users WHERE id = ?';
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id_user);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

$user = ambil_data_user($db, $id_user);
$pesan = '';
$error = '';

// Memproses pengiriman form (simpan perubahan profil atau hapus akun)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();

    if (isset($_POST['aksi'])) {
        $aksi = $_POST['aksi'];
    } else {
        $aksi = '';
    }

    // 1. Simpan perubahan nama dan/atau foto profil
    if ($aksi === 'simpan') {
        if (isset($_POST['nama'])) {
            $nama_baru = trim($_POST['nama']);
        } else {
            $nama_baru = '';
        }

        if (isset($user['foto'])) {
            $foto_baru = $user['foto'];
        } else {
            $foto_baru = '';
        }

        if (mb_strlen($nama_baru) < 2) {
            $error = 'Nama harus diisi minimal 2 huruf.';
        }

        $ada_file_foto = false;
        if (
            isset($_FILES['foto']) &&
            isset($_FILES['foto']['error']) &&
            $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $ada_file_foto = true;
        }

        if ($error === '' && $ada_file_foto) {
            $file_foto = $_FILES['foto'];

            if ($file_foto['error'] !== UPLOAD_ERR_OK) {
                $error = 'Terjadi kesalahan saat mengunggah foto.';
            } elseif ($file_foto['size'] > $maks_ukuran) {
                $error = 'Ukuran foto terlalu besar, maksimal 2 MB.';
            } else {
                $ekstensi_file = strtolower(pathinfo($file_foto['name'], PATHINFO_EXTENSION));
                $ekstensi_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ekstensi_file, $ekstensi_diizinkan, true)) {
                    $error = 'Format foto harus JPG, PNG, atau WebP.';
                } else {
                    $nama_file_unik = 'foto_' . $id_user . '_' . time() . '.' . $ekstensi_file;

                    if (move_uploaded_file($file_foto['tmp_name'], $folder_tujuan . $nama_file_unik)) {
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

        if ($error === '') {
            $query_update = 'UPDATE users SET nama = ?, foto = ? WHERE id = ?';
            $stmt = $db->prepare($query_update);
            $stmt->bind_param('ssi', $nama_baru, $foto_baru, $id_user);
            $stmt->execute();

            $_SESSION['user']['nama'] = $nama_baru;
            $user = ambil_data_user($db, $id_user);

            $pesan = 'Perubahan profil berhasil disimpan!';
        }
    }

    // 2. Hapus akun pengguna setelah verifikasi kata sandi
    if ($aksi === 'hapus_akun') {
        if (isset($_POST['sandi'])) {
            $sandi_input = $_POST['sandi'];
        } else {
            $sandi_input = '';
        }

        if (!password_verify($sandi_input, $user['password_hash'])) {
            $error = 'Kata sandi salah! Akun batal dihapus.';
        } else {
            // Hapus dulu riwayat kuis milik pengguna agar tidak terbentur foreign key
            $query_hapus_riwayat = 'DELETE FROM hasil_kuis WHERE user_id = ?';
            $stmt_riwayat = $db->prepare($query_hapus_riwayat);
            $stmt_riwayat->bind_param('i', $id_user);
            $stmt_riwayat->execute();

            // Hapus file foto profil jika ada
            if (
                !empty($user['foto']) &&
                file_exists($folder_tujuan . $user['foto'])
            ) {
                unlink($folder_tujuan . $user['foto']);
            }

            // Hapus akun pengguna dari tabel users
            $query_hapus = 'DELETE FROM users WHERE id = ?';
            $stmt = $db->prepare($query_hapus);
            $stmt->bind_param('i', $id_user);
            $stmt->execute();

            // Bersihkan sesi login dan arahkan ke halaman utama
            $_SESSION = [];
            session_destroy();

            header('Location: ' . url('/'));
            exit;
        }
    }
}

if (isset($user['nama']) && $user['nama'] !== '') {
    $nama_tampil = $user['nama'];
    $inisial_nama = strtoupper(mb_substr($user['nama'], 0, 1));
} else {
    $nama_tampil = '';
    $inisial_nama = '?';
}

$judul = 'Profil Pengguna';
require __DIR__ . '/includes/header.php';
?>

<div class="narrow" style="max-width:520px">

  <h1 class="s">Profilmu</h1>

  <?php if ($pesan !== ''): ?>
    <div class="msg" role="status">
      <?= e($pesan) ?>
    </div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="err" role="alert">
      <?= e($error) ?>
    </div>
  <?php endif; ?>

  <div class="card">

    <form method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan">

      <div class="who">
        <div class="ava" id="ava" aria-hidden="true">
          <?php if (!empty($user['foto'])): ?>
            <img
              alt="Foto Profil"
              src="<?= e(url('/uploads/' . $user['foto'])) ?>"
            >
          <?php else: ?>
            <?= e($inisial_nama) ?>
          <?php endif; ?>
        </div>

        <div>
          <strong><?= e($nama_tampil) ?></strong><br>

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
        value="<?= e($nama_tampil) ?>"
        autocomplete="name"
      >

      <label class="l" for="email">Email</label>
      <input
        class="t"
        id="email"
        value="<?= e($user['email']) ?>"
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

        <a class="btn ghost" href="<?= htmlspecialchars(url('/keluar.php')) ?>" onclick="return confirm('Apakah kamu yakin ingin keluar dari akun ini?');">
          Keluar
        </a>
      </div>
    </form>

    <div style="border-top:1px solid var(--line);margin:22px 0"></div>

    <div>
      <h2 style="margin:0 0 8px">Hapus Akun</h2>

      <p>
        Semua data akun dan seluruh riwayat kuis kamu akan dihapus secara permanen.
      </p>

      <form
        method="post"
        onsubmit="return confirm('PERINGATAN: Semua data akun dan seluruh riwayat kuis kamu akan dihapus secara permanen. Apakah kamu benar-benar yakin ingin menghapus akun ini?')"
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

    if (!file) {
        return;
    }

    const reader = new FileReader();

    reader.onload = function(e) {
        document.getElementById('ava').innerHTML =
            '<img alt="Pratinjau Foto" src="' + e.target.result + '">';
    };

    reader.readAsDataURL(file);
};
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>