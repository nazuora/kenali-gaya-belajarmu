<?php
// admin/pertanyaan.php
require __DIR__ . '/../config/koneksi.php';
wajib_admin();

// Menyiapkan variabel untuk menampung pesan sukses atau pesan error (galat)
$pesan = '';
$galat = '';

// Cek apakah ada data yang dikirim lewat form (metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $aksi = $_POST['aksi'] ?? '';

    // Aksi untuk Menyimpan (Tambah baru atau Edit data)
    if ($aksi === 'simpan') {
        $id      = (int)($_POST['id'] ?? 0);
        $teks    = trim($_POST['teks'] ?? '');
        $urutan  = (int)($_POST['urutan'] ?? 0);
        $jawaban = $_POST['jawaban'] ?? []; // Array pilihan jawaban

        // Validasi sederhana: pastikan teks pertanyaan tidak kosong
        if ($teks === '') {
            $galat = 'Teks pertanyaan tidak boleh kosong.';
        } else {
            // Memulai transaksi database secara manual
            $db->begin_transaction();
            try {
                if ($id > 0) {
                    // Jika ID ada, berarti ini proses Edit/Ubah pertanyaan lama
                    $stmt = $db->prepare('UPDATE pertanyaan SET teks = ?, urutan = ? WHERE id = ?');
                    $stmt->bind_param('sii', $teks, $urutan, $id);
                    $stmt->execute();

                    // Hapus dulu jawaban lama, nanti dimasukkan ulang yang baru
                    $db->query("DELETE FROM jawaban WHERE pertanyaan_id = $id");
                } else {
                    // Jika ID 0, berarti ini Tambah pertanyaan baru
                    $stmt = $db->prepare('INSERT INTO pertanyaan (teks, urutan) VALUES (?, ?)');
                    $stmt->bind_param('si', $teks, $urutan);
                    $stmt->execute();
                    $id = $db->insert_id; // Ambil ID pertanyaan yang baru saja dibuat
                }

                // Masukkan pilihan jawaban untuk tiap gaya belajar (Visual, Auditoris, Kinestetik)
                $stmtJ = $db->prepare('INSERT INTO jawaban (pertanyaan_id, gaya_id, teks) VALUES (?, ?, ?)');
                foreach ($jawaban as $gaya_id => $teks_jawaban) {
                    $tJ  = trim($teks_jawaban);
                    $gId = (int)$gaya_id;
                    
                    // Simpan hanya jika teks jawaban tidak kosong dan ID gaya valid (1, 2, atau 3)
                    if ($tJ !== '' && ($gId == 1 || $gId == 2 || $gId == 3)) {
                        $stmtJ->bind_param('iis', $id, $gId, $tJ);
                        $stmtJ->execute();
                    }
                }

                // Jika semua proses di atas berhasil, simpan permanen ke database
                $db->commit();
                $pesan = 'Pertanyaan berhasil disimpan.';
            } catch (Exception $e) {
                // Jika ada error di tengah jalan, batalkan semua perubahan
                $db->rollback();
                $galat = 'Gagal menyimpan data: ' . $e->getMessage();
            }
        }
    } 
    // Aksi untuk Menghapus Pertanyaan
    elseif ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare('DELETE FROM pertanyaan WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $pesan = 'Pertanyaan berhasil dihapus.';
        }
    }
}

// Mengambil daftar gaya belajar dari database untuk referensi form jawaban
$gayaList = [];
$resGaya = $db->query("SELECT * FROM gaya_belajar ORDER BY id ASC");
while ($row = $resGaya->fetch_assoc()) {
    $gayaList[$row['id']] = $row;
}

// Cek apakah halaman sedang dalam mode Edit (ada parameter ?edit=...)
$editId = (int)($_GET['edit'] ?? 0);
$editData = null;
$editJawaban = [];

if ($editId > 0) {
    // Ambil data pertanyaan berdasarkan ID yang dipilih untuk diedit
    $stmt = $db->prepare("SELECT * FROM pertanyaan WHERE id = ?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();

    // Jika pertanyaannya ketemu, ambil juga pilihan jawaban lamanya
    if ($editData) {
        $resJ = $db->prepare("SELECT * FROM jawaban WHERE pertanyaan_id = ?");
        $resJ->bind_param('i', $editId);
        $resJ->execute();
        $resJData = $resJ->get_result();
        while ($j = $resJData->fetch_assoc()) {
            $editJawaban[$j['gaya_id']] = $j['teks'];
        }
    }
}

// Mengambil seluruh daftar pertanyaan untuk ditampilkan ke dalam tabel/list di bawah
$pertanyaanList = [];
$resP = $db->query("SELECT * FROM pertanyaan ORDER BY urutan ASC, id ASC");
while ($row = $resP->fetch_assoc()) {
    $pId = $row['id'];
    // Ambil pilihan jawaban untuk masing-masing pertanyaan
    $resJSub = $db->query("SELECT j.*, g.kode FROM jawaban j JOIN gaya_belajar g ON j.gaya_id = g.id WHERE j.pertanyaan_id = $pId");
    $row['pilihan'] = [];
    while ($jSub = $resJSub->fetch_assoc()) {
        $row['pilihan'][$jSub['gaya_id']] = $jSub;
    }
    $pertanyaanList[] = $row;
}

$judul = 'Kelola Pertanyaan Kuis';
require __DIR__ . '/../includes/header.php';
?>

<!-- Tab navigasi khusus Admin -->
<div class="seg" style="grid-template-columns: 1fr; margin-bottom: 24px;">
  <a href="<?= e(url('/admin/pertanyaan.php')) ?>" aria-selected="true">Kelola Pertanyaan Kuis</a>
</div>

<h1>Kelola Pertanyaan Kuis</h1>
<p class="sub">Tambah, ubah, atau hapus daftar pertanyaan serta pilihan jawaban kuis gaya belajar.</p>

<!-- Menampilkan pesan berhasil jika ada -->
<?php if ($pesan): ?>
  <div class="card" style="background: var(--green-soft); border-color: var(--ok); color: var(--ok); padding: 12px 18px; margin-bottom: 16px;">
    <strong><?= e($pesan) ?></strong>
  </div>
<?php endif; ?>

<!-- Menampilkan pesan error jika ada -->
<?php if ($galat): ?>
  <div class="err" style="margin-bottom: 16px;"><?= e($galat) ?></div>
<?php endif; ?>

<!-- Form untuk Tambah atau Edit Pertanyaan -->
<div class="card" style="margin-bottom: 28px;">
  <h2 style="margin-top:0"><?= $editData ? 'Ubah Pertanyaan #' . $editData['id'] : 'Tambah Pertanyaan Baru' ?></h2>
  
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="id" value="<?= $editData ? (int)$editData['id'] : 0 ?>">

    <div style="margin-bottom: 14px;">
      <label class="l" for="teks">Teks Pertanyaan</label>
      <textarea class="t" name="teks" id="teks" rows="2" required placeholder="Tuliskan pertanyaan kuis di sini..."><?= e($editData['teks'] ?? '') ?></textarea>
    </div>

    <div style="margin-bottom: 18px; max-width: 200px;">
      <label class="l" for="urutan">Nomor Urutan</label>
      <input class="t" type="number" name="urutan" id="urutan" value="<?= e($editData['urutan'] ?? count($pertanyaanList) + 1) ?>" required>
    </div>

    <h3 style="font-size: 1.1rem; margin-bottom: 10px;">Pilihan Jawaban Berdasarkan Gaya Belajar</h3>
    <p class="hint" style="margin-bottom: 14px;">Isi opsi jawaban yang mencerminkan masing-masing kategori gaya belajar di bawah ini:</p>

    <?php 
    // Perulangan untuk membuat form input jawaban untuk setiap gaya belajar
    foreach ($gayaList as $gId => $gInfo): 
        // Menentukan warna background pill berdasarkan ID gaya belajar secara manual agar mudah dibaca
        $warnaBg = 'var(--blue-soft)';
        if ($gId == 2) {
            $warnaBg = 'var(--yellow-soft)';
        } elseif ($gId == 3) {
            $warnaBg = 'var(--green-soft)';
        }
    ?>
      <div style="margin-bottom: 12px;">
        <label class="l" style="display: flex; align-items: center; gap: 6px;">
          <span class="pill" style="background: <?= $warnaBg ?>; font-size: 0.8rem;">
            <?= e($gInfo['nama']) ?> (<?= e($gInfo['kode']) ?>)
          </span>
        </label>
        <input class="t" type="text" name="jawaban[<?= $gId ?>]" value="<?= e($editJawaban[$gId] ?? '') ?>" placeholder="Tulis pilihan jawaban untuk gaya <?= e($gInfo['nama']) ?>..." required>
      </div>
    <?php endforeach; ?>

    <div style="margin-top: 18px; display: flex; gap: 10px;">
      <button type="submit" class="btn">Simpan Pertanyaan</button>
      <?php if ($editData): ?>
        <a href="<?= url('/admin/pertanyaan.php') ?>" class="btn ghost">Batal Edit</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Daftar Pertanyaan yang Sudah Tersimpan -->
<h2>Daftar Pertanyaan (<?= count($pertanyaanList) ?>)</h2>

<?php if (empty($pertanyaanList)): ?>
  <div class="empty">Belum ada pertanyaan yang ditambahkan.</div>
<?php else: ?>
  <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 12px;">
    <?php foreach ($pertanyaanList as $p): ?>
      <div class="card" style="margin-bottom: 0; padding: 18px 22px;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 8px;">
          <div>
            <span style="font-weight: 900; color: var(--blue); margin-right: 6px;">#<?= (int)$p['urutan'] ?></span>
            <strong style="font-size: 1.05rem;"><?= e($p['teks']) ?></strong>
          </div>
          
          <!-- Tombol Aksi (Ubah & Hapus) -->
          <div style="display: flex; gap: 6px; flex: none;">
            <a href="<?= url('/admin/pertanyaan.php?edit=' . (int)$p['id']) ?>" class="btn sm ghost">Ubah</a>
            
            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus pertanyaan ini?');" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button type="submit" class="btn sm red">Hapus</button>
            </form>
          </div>
        </div>

        <!-- Menampilkan pilihan jawaban di bawah pertanyaan -->
        <ul style="margin: 8px 0 0 20px; padding: 0; font-size: 0.92rem; color: var(--muted);">
          <?php foreach ($p['pilihan'] as $pil): ?>
            <li>
              <strong style="color: var(--text);"><?= e($pil['kode']) ?>:</strong> <?= e($pil['teks']) ?>
            </li>
          <?php endforeach; ?>
        </ul>

      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>