<?php
// admin/pertanyaan.php
require __DIR__ . '/../config/koneksi.php';
wajib_admin();

$pesan = '';
$galat = '';

// Proses Tambah / Ubah / Hapus Pertanyaan & Jawaban
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $id      = (int)($_POST['id'] ?? 0);
        $teks    = trim($_POST['teks'] ?? '');
        $urutan  = (int)($_POST['urutan'] ?? 0);
        $jawaban = $_POST['jawaban'] ?? []; // Array [gaya_id => teks_jawaban]

        if ($teks === '') {
            $galat = 'Teks pertanyaan tidak boleh kosong.';
        } else {
            $db->begin_transaction();
            try {
                if ($id > 0) {
                    // Update pertanyaan
                    $stmt = $db->prepare('UPDATE pertanyaan SET teks = ?, urutan = ? WHERE id = ?');
                    $stmt->bind_param('sii', $teks, $urutan, $id);
                    $stmt->execute();

                    // Hapus jawaban lama lalu masukkan yang baru
                    $db->query("DELETE FROM jawaban WHERE pertanyaan_id = $id");
                } else {
                    // Insert pertanyaan baru
                    $stmt = $db->prepare('INSERT INTO pertanyaan (teks, urutan) VALUES (?, ?)');
                    $stmt->bind_param('si', $teks, $urutan);
                    $stmt->execute();
                    $id = $db->insert_id;
                }

                // Masukkan pilihan jawaban untuk tiap gaya (V, A, K)
                $stmtJ = $db->prepare('INSERT INTO jawaban (pertanyaan_id, gaya_id, teks) VALUES (?, ?, ?)');
                foreach ($jawaban as $gaya_id => $teks_jawaban) {
                    $tJ = trim($teks_jawaban);
                    $gId = (int)$gaya_id;
                    if ($tJ !== '' && in_array($gId, [1, 2, 3], true)) {
                        $stmtJ->bind_param('iis', $id, $gId, $tJ);
                        $stmtJ->execute();
                    }
                }

                $db->commit();
                $pesan = 'Pertanyaan berhasil disimpan.';
            } catch (Exception $e) {
                $db->rollback();
                $galat = 'Gagal menyimpan data: ' . $e->getMessage();
            }
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare('DELETE FROM pertanyaan WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $pesan = 'Pertanyaan berhasil dihapus.';
        }
    }
}

// Ambil semua daftar gaya belajar untuk referensi form jawaban
$gayaList = [];
$resGaya = $db->query("SELECT * FROM gaya_belajar ORDER BY id ASC");
while ($row = $resGaya->fetch_assoc()) {
    $gayaList[$row['id']] = $row;
}

// Ambil data jika sedang mode edit
$editId = (int)($_GET['edit'] ?? 0);
$editData = null;
$editJawaban = [];
if ($editId > 0) {
    $stmt = $db->prepare("SELECT * FROM pertanyaan WHERE id = ?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();

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

// Ambil semua daftar pertanyaan untuk ditampilkan di tabel
$pertanyaanList = [];
$resP = $db->query("SELECT * FROM pertanyaan ORDER BY urutan ASC, id ASC");
while ($row = $resP->fetch_assoc()) {
    // Ambil pilihan jawaban tiap pertanyaan
    $pId = $row['id'];
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

<!-- Tab navigasi khusus Admin (Hanya Kelola Pertanyaan) -->
<div class="seg" style="grid-template-columns: 1fr; margin-bottom: 24px;">
  <a href="<?= e(url('/admin/pertanyaan.php')) ?>" aria-selected="true">Kelola Pertanyaan Kuis</a>
</div>

<h1>Kelola Pertanyaan Kuis</h1>
<p class="sub">Tambah, ubah, atau hapus daftar pertanyaan serta pilihan jawaban kuis gaya belajar.</p>

<?php if ($pesan): ?>
  <div class="card" style="background: var(--green-soft); border-color: var(--ok); color: var(--ok); padding: 12px 18px; margin-bottom: 16px;">
    <strong><?= e($pesan) ?></strong>
  </div>
<?php endif; ?>

<?php if ($galat): ?>
  <div class="err" style="margin-bottom: 16px;"><?= e($galat) ?></div>
<?php endif; ?>

<!-- Form Tambah / Edit Pertanyaan -->
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

    <?php foreach ($gayaList as $gId => $gInfo): ?>
      <div style="margin-bottom: 12px;">
        <label class="l" style="display: flex; align-items: center; gap: 6px;">
          <span class="pill" style="background: <?= $gId == 1 ? 'var(--blue-soft)' : ($gId == 2 ? 'var(--yellow-soft)' : 'var(--green-soft)') ?>; font-size: 0.8rem;">
            <?= e($gInfo['nama']) ?> (<?= e($gInfo['kode']) ?>)
          </span>
        </label>
        <input class="t" type="text" name="jawaban[<?= $gId ?>]" value="<?= e($editJawaban[$gId] ?? '') ?>" placeholder="Opsi jawaban untuk gaya <?= e($gInfo['nama']) ?>..." required>
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

<!-- Daftar Pertanyaan yang Sudah Ada -->
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