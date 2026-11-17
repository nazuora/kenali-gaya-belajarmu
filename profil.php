<?php
/* 
  File: profil.php
  Keterangan: Halaman untuk menampilkan dan mengelola informasi profil 
  serta pengaturan akun pengguna yang sedang masuk.
*/

// Memuat file koneksi database dan fungsi bantuan lainnya
require __DIR__ . '/config/koneksi.php';

// Memastikan pengguna sudah login sebelum bisa membuka halaman ini
wajib_login();

// Mengambil ID user yang sedang login dari sesi
$id_user = $_SESSION['user']['id'];

// Menentukan folder tempat menyimpan foto profil dan batas ukuran file (2 MB)
$folder_tujuan = __DIR__ . '/uploads/';
$maks_ukuran   = 2 * 1024 * 1024; // 2 MB dalam byte

// Fungsi untuk mengambil data user terbaru dari database
function ambil_data_user($db, $id_user) {
    $query = "SELECT nama, email, password_hash, foto FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id_user);
    $stmt->execute();
    $hasil = $stmt->get_result();
    return $hasil->fetch_assoc();
}

// Ambil data user saat halaman pertama kali dibuka
$user  = ambil_data_user($db, $id_user);
$pesan = '';
$error = '';

// Mengecek apakah form sedang dikirimkan (disubmit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Memeriksa token keamanan CSRF
    csrf_periksa();
    
    // Menangkap jenis aksi yang dikirim dari form (apakah 'simpan' atau 'hapus_akun')
    $aksi = $_POST['aksi'] ?? '';

    // ==========================================
    // 1. PROSES SIMPAN PERUBAHAN (NAMA & FOTO)
    // ==========================================
    if ($aksi === 'simpan') {
        $nama_baru = trim($_POST['nama'] ?? '');
        $foto_baru = $user['foto']; // Defaultnya pakai foto lama

        // Validasi nama minimal 2 karakter
        if (strlen($nama_baru) < 2) {
            $error = 'Nama harus diisi minimal 2 huruf.';
        }

        // Validasi jika ada file foto yang diunggah
        if ($error === '' && isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file_foto = $_FILES['foto'];
            
            // Cek apakah terjadi error saat upload
            if ($file_foto['error'] !== UPLOAD_ERR_OK) {
                $error = 'Terjadi kesalahan saat mengunggah foto.';
            } 
            // Cek ukuran file (maksimal 2 MB)
            elseif ($file_foto['size'] > $maks_ukuran) {
                $error = 'Ukuran foto terlalu besar, maksimal 2 MB.';
            } else {
                // Mendapatkan ekstensi file yang diunggah
                $ekstensi_file = strtolower(pathinfo($file_foto['name'], PATHINFO_EXTENSION));
                $ekstensi_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];

                // Cek apakah ekstensi file diizinkan
                if (!in_array($ekstensi_file, $ekstensi_diizinkan)) {
                    $error = 'Format foto harus berformat JPG, PNG, atau WebP.';
                } else {
                    // Membuat nama file baru agar tidak ada nama file yang sama persis
                    $nama_file_unik = 'foto_' . $id_user . '_' . time() . '.' . $ekstensi_file;
                    
                    // Memindahkan file yang diunggah ke folder tujuan
                    if (move_uploaded_file($file_foto['tmp_name'], $folder_tujuan . $nama_file_unik)) {
                        // Jika user sebelumnya sudah punya foto, hapus foto lama agar tidak menumpuk
                        if (!empty($user['foto']) && file_exists($folder_tujuan . $user['foto'])) {
                            unlink($folder_tujuan . $user['foto']);
                        }
                        $foto_baru = $nama_file_unik;
                    } else {
                        $error = 'Gagal menyimpan foto ke folder uploads.';
                    }
                }
            }
        }

        // Jika tidak ada error sama sekali, simpan ke database
        if ($error === '') {
            $query_update = "UPDATE users SET nama = ?, foto = ? WHERE id = ?";
            $stmt = $db->prepare($query_update);
            $stmt->bind_param('ssi', $nama_baru, $foto_baru, $id_user);
            $stmt->execute();

            // Perbarui nama di sesi login dan ambil ulang data user
            $_SESSION['user']['nama'] = $nama_baru;
            $user = ambil_data_user($db, $id_user);
            
            $pesan = 'Perubahan profil berhasil disimpan!';
        }
    }

    // ==========================================
    // 2. PROSES HAPUS AKUN
    // ==========================================
    if ($aksi === 'hapus_akun') {
        $sandi_input = $_POST['sandi'] ?? '';

        // Memeriksa apakah kata sandi yang dimasukkan sudah benar
        if (!password_verify($sandi_input, $user['password_hash'])) {
            $error = 'Kata sandi salah! Akun batal dihapus.';
        } else {
            // Hapus foto profil dari folder jika ada
            if (!empty($user['foto']) && file_exists($folder_tujuan . $user['foto'])) {
                unlink($folder_tujuan . $user['foto']);
            }

            // Hapus data user dari database (riwayat kuis ikut terhapus otomatis karena ON DELETE CASCADE)
            $query_hapus = "DELETE FROM users WHERE id = ?";
            $stmt = $db->prepare($query_hapus);
            $stmt->bind_param('i', $id_user);
            $stmt->execute();

            // Hancurkan sesi login dan arahkan kembali ke halaman utama
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

  <!-- Menampilkan pesan sukses jika ada -->
  <?php if ($pesan !== ''): ?>
    <div class="msg" role="status"><?= htmlspecialchars($pesan) ?></div>
  <?php endif; ?>

  <!-- Menampilkan pesan error jika ada -->
  <?php if ($error !== ''): ?>
    <div class="err" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Form Edit Profil -->
  <form class="card" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    
    <div class="who">
      <div class="ava" id="ava" aria-hidden="true">
        <?php if (!empty($user['foto'])): ?>
          <img alt="Foto Profil" src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($user['foto']) ?>">
        <?php else: ?>
          <?= htmlspecialchars(strtoupper(substr($user['nama'], 0, 1))) ?>
        <?php endif; ?>
      </div>
      <div>
        <strong><?= htmlspecialchars($user['nama']) ?></strong><br>
        <label class="file">Ganti foto<input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp"></label>
      </div>
    </div>

    <label class="l" for="nama">Nama Lengkap</label>
    <input class="t" id="nama" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" autocomplete="name">
    <div class="err"></div>

    <label class="l" for="email">Email</label>
    <input class="t" id="email" value="<?= htmlspecialchars($user['email']) ?>" readonly aria-describedby="petunjuk">
    
    <div class="hint" id="petunjuk" style="margin-bottom:12px">
      Email tidak bisa diubah. Format foto yang didukung: JPG, PNG, atau WebP, dengan ukuran maksimal 2 MB.
    </div>

    <div class="actions" style="justify-content:flex-start;align-items:center">
      <button class="btn" type="submit">Simpan Perubahan</button>
      <a class="btn ghost" href="<?= htmlspecialchars(url('/keluar.php')) ?>">Keluar</a>
    </div>
  </form>

  <!-- Form Hapus Akun -->
  <form class="card danger" method="post" onsubmit="return confirm('Yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan.')">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="hapus_akun">
    
    <h2>Hapus Akun</h2>
    <p>Semua data akun dan seluruh riwayat kuis kamu akan dihapus secara permanen.</p>
    
    <label class="l" for="sandi">Masukkan kata sandi untuk konfirmasi</label>
    <input class="t" id="sandi" name="sandi" type="password" required autocomplete="current-password" style="margin-bottom:12px">
    
    <button class="btn red" type="submit">Hapus Akun Saya</button>
  </form>
</div>

<script>
  // Script JavaScript sederhana untuk menampilkan pratinjau (preview) foto sebelum disimpan
  document.getElementById('foto').onchange = function(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('ava').innerHTML = '<img alt="Pratinjau Foto" src="' + e.target.result + '">';
    };
    reader.readAsDataURL(file);
  };
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>