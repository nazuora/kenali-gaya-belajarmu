<?php
/*
  File: admin/pertanyaan.php
  Keterangan: Halaman panel admin untuk menambah, mengubah, dan menghapus
  pertanyaan kuis serta pilihan jawaban berdasarkan gaya belajar.
*/

require __DIR__ . '/../config/koneksi.php';
wajib_admin();

$pesan = '';
$galat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();

    if (isset($_POST['aksi'])) {
        $aksi = $_POST['aksi'];
    } else {
        $aksi = '';
    }

    if ($aksi === 'simpan') {
        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
        } else {
            $id = 0;
        }

        if (isset($_POST['teks'])) {
            $teks = trim($_POST['teks']);
        } else {
            $teks = '';
        }

        if (isset($_POST['urutan'])) {
            $urutan = (int)$_POST['urutan'];
        } else {
            $urutan = 0;
        }

        if (isset($_POST['jawaban']) && is_array($_POST['jawaban'])) {
            $jawaban = $_POST['jawaban'];
        } else {
            $jawaban = [];
        }

        $jawaban_bersih = [];
        foreach ($jawaban as $gaya_id => $teks_jawaban) {
            $gId = (int)$gaya_id;
            $tJ  = trim((string)$teks_jawaban);
            if ($tJ !== '' && ($gId === 1 || $gId === 2 || $gId === 3)) {
                $jawaban_bersih[$gId] = $tJ;
            }
        }

        $jawabanPanjang = false;
        foreach ($jawaban_bersih as $tJ) {
            if (mb_strlen($tJ) > 255) {
                $jawabanPanjang = true;
            }
        }

        if ($teks === '') {
            $galat = 'Teks pertanyaan tidak boleh kosong.';
        } elseif ($urutan < 1) {
            $galat = 'Nomor urutan minimal 1.';
        } elseif (mb_strlen($teks) > 255 || $jawabanPanjang) {
            $galat = 'Teks pertanyaan dan tiap jawaban maksimal 255 karakter.';
        } elseif (count($jawaban_bersih) < 3) {
            $galat = 'Semua pilihan jawaban untuk Visual, Auditori, dan Kinestetik harus diisi.';
        } else {
            $db->begin_transaction();
            try {
                if ($id > 0) {
                    $stmt = $db->prepare('UPDATE pertanyaan SET teks = ?, urutan = ? WHERE id = ?');
                    $stmt->bind_param('sii', $teks, $urutan, $id);
                    $stmt->execute();
                    $stmt->close();

                    $stmtHapusJawaban = $db->prepare('DELETE FROM jawaban WHERE pertanyaan_id = ?');
                    $stmtHapusJawaban->bind_param('i', $id);
                    $stmtHapusJawaban->execute();
                    $stmtHapusJawaban->close();
                } else {
                    $stmt = $db->prepare('INSERT INTO pertanyaan (teks, urutan) VALUES (?, ?)');
                    $stmt->bind_param('si', $teks, $urutan);
                    $stmt->execute();
                    $id = (int)$db->insert_id;
                    $stmt->close();
                }

                $stmtJ = $db->prepare('INSERT INTO jawaban (pertanyaan_id, gaya_id, teks) VALUES (?, ?, ?)');
                foreach ($jawaban_bersih as $gId => $tJ) {
                    $stmtJ->bind_param('iis', $id, $gId, $tJ);
                    $stmtJ->execute();
                }
                $stmtJ->close();

                $db->commit();
                $pesan = 'Pertanyaan berhasil disimpan.';
            } catch (Exception $e) {
                $db->rollback();
                $galat = 'Gagal menyimpan data. Coba lagi.';
            }
        }
    } elseif ($aksi === 'hapus') {
        if (isset($_POST['id'])) {
            $id = (int)$_POST['id'];
        } else {
            $id = 0;
        }

        if ($id > 0) {
            $stmt = $db->prepare('DELETE FROM pertanyaan WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $pesan = 'Pertanyaan berhasil dihapus.';
        }
    }
}

$gayaList = [];
$resGaya = $db->query('SELECT * FROM gaya_belajar ORDER BY id ASC');
while ($row = $resGaya->fetch_assoc()) {
    $id_gaya = (int)$row['id'];
    $gayaList[$id_gaya] = $row;
}
if ($resGaya) {
    $resGaya->free();
}

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
} else {
    $editId = 0;
}

$editData = null;
$editJawaban = [];

if ($editId > 0) {
    $stmt = $db->prepare('SELECT * FROM pertanyaan WHERE id = ?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($editData) {
        $resJ = $db->prepare('SELECT * FROM jawaban WHERE pertanyaan_id = ?');
        $resJ->bind_param('i', $editId);
        $resJ->execute();
        $resJData = $resJ->get_result();
        while ($j = $resJData->fetch_assoc()) {
            $id_gaya_j = (int)$j['gaya_id'];
            $editJawaban[$id_gaya_j] = $j['teks'];
        }
        $resJ->close();
    }
}

$pertanyaanList = [];
$resP = $db->query('SELECT * FROM pertanyaan ORDER BY urutan ASC, id ASC');
$stmtPilihan = $db->prepare('SELECT j.*, g.kode FROM jawaban j JOIN gaya_belajar g ON j.gaya_id = g.id WHERE j.pertanyaan_id = ?');

while ($row = $resP->fetch_assoc()) {
    $pId = (int)$row['id'];
    $stmtPilihan->bind_param('i', $pId);
    $stmtPilihan->execute();
    $resJSub = $stmtPilihan->get_result();

    $row['pilihan'] = [];
    while ($jSub = $resJSub->fetch_assoc()) {
        $id_gaya_sub = (int)$jSub['gaya_id'];
        $row['pilihan'][$id_gaya_sub] = $jSub;
    }
    $pertanyaanList[] = $row;
}
$stmtPilihan->close();
if ($resP) {
    $resP->free();
}

if ($editData) {
    $judulForm = 'Ubah Pertanyaan #' . (int)$editData['urutan'];
    $nilaiIdForm = (int)$editData['id'];
    $nilaiTeksForm = $editData['teks'];
    $nilaiUrutanForm = (int)$editData['urutan'];
} else {
    $judulForm = 'Tambah Pertanyaan Baru';
    $nilaiIdForm = 0;
    $nilaiTeksForm = '';
    $nilaiUrutanForm = count($pertanyaanList) + 1;
}

$judul = 'Kelola Pertanyaan Kuis';
require __DIR__ . '/../includes/header.php';
?>

<h1>Kelola Pertanyaan Kuis</h1>
<p class="sub">Tambah, ubah, atau hapus daftar pertanyaan serta pilihan jawaban kuis gaya belajar.</p>

<?php if ($pesan !== '') { ?>
  <div class="card" style="background: var(--surface); border-color: var(--ok); color: var(--ok); padding: 12px 18px; margin-bottom: 16px;">
    <strong><?= e($pesan) ?></strong>
  </div>
<?php } ?>

<?php if ($galat !== '') { ?>
  <div class="err" style="margin-bottom: 16px;"><?= e($galat) ?></div>
<?php } ?>

<div class="card" style="margin-bottom: 28px;">
  <h2 style="margin-top: 0;"><?= e($judulForm) ?></h2>

  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="id" value="<?= e($nilaiIdForm) ?>">

    <div style="margin-bottom: 14px;">
      <label class="l" for="teks">Teks Pertanyaan</label>
      <textarea class="t" name="teks" id="teks" rows="2" required placeholder="Tuliskan pertanyaan kuis di sini..."><?= e($nilaiTeksForm) ?></textarea>
    </div>

    <div style="margin-bottom: 18px; max-width: 200px;">
      <label class="l" for="urutan">Nomor Urutan</label>
      <input class="t" type="number" name="urutan" id="urutan" value="<?= e($nilaiUrutanForm) ?>" required>
    </div>

    <h3 style="font-size: 1.1rem; margin-bottom: 10px;">Pilihan Jawaban Berdasarkan Gaya Belajar</h3>
    <p class="hint" style="margin-bottom: 14px;">Isi opsi jawaban yang mencerminkan masing-masing kategori gaya belajar di bawah ini:</p>

    <?php foreach ($gayaList as $gId => $gInfo) { ?>
      <?php
        if (isset($editJawaban[$gId])) {
            $nilaiJawabanInput = $editJawaban[$gId];
        } else {
            $nilaiJawabanInput = '';
        }
      ?>
      <div style="margin-bottom: 12px;">
        <label class="l" style="display: flex; align-items: center; gap: 6px;">
          <span class="pill" style="background: var(--bg); font-size: 0.85rem;">
            <?= e($gInfo['nama']) ?> (<?= e($gInfo['kode']) ?>)
          </span>
        </label>
        <input class="t" type="text" name="jawaban[<?= (int)$gId ?>]" value="<?= e($nilaiJawabanInput) ?>" placeholder="Tulis pilihan jawaban untuk gaya <?= e($gInfo['nama']) ?>..." required>
      </div>
    <?php } ?>

    <div style="margin-top: 18px; display: flex; gap: 10px;">
      <button type="submit" class="btn">Simpan Pertanyaan</button>
      <?php if ($editData) { ?>
        <a href="<?= e(url('/admin/pertanyaan.php')) ?>" class="btn ghost">Batal Edit</a>
      <?php } ?>
    </div>
  </form>
</div>

<h2>Daftar Pertanyaan (<?= e(count($pertanyaanList)) ?>)</h2>

<?php if (count($pertanyaanList) === 0) { ?>
  <div class="empty">Belum ada pertanyaan yang ditambahkan.</div>
<?php } else { ?>
  <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 12px;">
    <?php foreach ($pertanyaanList as $p) { ?>
      <div class="card" style="margin-bottom: 0; padding: 18px 22px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 8px;">
          <div>
            <span style="font-weight: 900; color: var(--text); margin-right: 6px;">#<?= e((int)$p['urutan']) ?></span>
            <strong style="font-size: 1.05rem;"><?= e($p['teks']) ?></strong>
          </div>

          <div style="display: flex; gap: 6px; flex: none;">
            <a href="<?= e(url('/admin/pertanyaan.php?edit=' . (int)$p['id'])) ?>" class="btn sm ghost">Ubah</a>

            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus pertanyaan ini?');" style="display: inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= e((int)$p['id']) ?>">
              <button type="submit" class="btn sm red">Hapus</button>
            </form>
          </div>
        </div>

        <ul style="margin: 8px 0 0 20px; padding: 0; font-size: 0.92rem; color: var(--muted);">
          <?php foreach ($p['pilihan'] as $pil) { ?>
            <li>
              <strong style="color: var(--text);"><?= e($pil['kode']) ?>:</strong> <?= e($pil['teks']) ?>
            </li>
          <?php } ?>
        </ul>
      </div>
    <?php } ?>
  </div>
<?php } ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>