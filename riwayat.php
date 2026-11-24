<?php
/*
  File: riwayat.php
  Keterangan: Menampilkan daftar riwayat pengerjaan kuis pengguna,
  tautan detail hasil, serta fitur hapus riwayat tanpa grafik.
*/

require __DIR__ . '/config/koneksi.php';

$login = false;
if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    $login = true;
}

if ($login) {
    $uid = (int)$_SESSION['user']['id'];
} else {
    $uid = 0;
}

// Proses hapus satu catatan riwayat milik pengguna yang sedang login
if ($login && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
        csrf_periksa();

        if (isset($_POST['id'])) {
            $id_riwayat = (int)$_POST['id'];
        } else {
            $id_riwayat = 0;
        }

        if ($id_riwayat > 0) {
            $stmt = $db->prepare('DELETE FROM hasil_kuis WHERE id = ? AND user_id = ?');
            $stmt->bind_param('ii', $id_riwayat, $uid);
            $stmt->execute();
        }

        header('Location: ' . url('/riwayat.php'));
        exit;
    }
}

// Ambil seluruh riwayat hasil kuis pengguna dari database
$rows = [];

if ($login) {
    $stmt = $db->prepare('SELECT h.id, h.persen_visual, h.persen_auditori, h.persen_kinestetik, h.dibuat_pada, g.kode, g.nama AS gaya
                          FROM hasil_kuis h
                          JOIN gaya_belajar g ON g.id = h.gaya_utama_id
                          WHERE h.user_id = ?
                          ORDER BY h.dibuat_pada ASC, h.id ASC');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

$jumlah_data = count($rows);

$judul = 'Riwayat Kuis';
require __DIR__ . '/includes/header.php';
?>

<div class="narrow" style="max-width: 720px;">
  <h1 class="s">Riwayat kuismu</h1>
  <p class="sub">Lihat riwayat hasil gaya belajarmu dari waktu ke waktu.</p>

  <?php if (!$login): ?>
    <div class="empty">
      <strong>Masuk dulu untuk melihat riwayat.</strong>
      <p>Hasil kuis akan tersimpan dengan aman di akunmu.</p>
      <a class="btn" href="<?= e(url('/masuk.php')) ?>">Masuk Sekarang</a>
    </div>
  <?php elseif ($jumlah_data === 0): ?>
    <div class="empty">
      <strong>Belum ada hasil kuis.</strong>
      <p>Ayo kerjakan kuis pertamamu untuk mulai melacak gaya belajar.</p>
      <a class="btn" href="<?= e(url('/petunjuk.php')) ?>">Mulai Kuis</a>
    </div>
  <?php else: ?>
    <!-- Daftar riwayat pengerjaan kuis -->
    <?php foreach (array_reverse($rows) as $r): ?>
      <div class="item">
        <button type="button" class="head" aria-expanded="false">
          <span class="pill"><?= e($r['gaya']) ?></span>
          <span class="date"><?= e(tanggal_id($r['dibuat_pada'])) ?></span>
          <span class="chev" aria-hidden="true">▾</span>
        </button>

        <div class="body hidden">
          <div class="row">
            <div class="lab">
              <span>Visual</span>
              <span><?= (int)$r['persen_visual'] ?>%</span>
            </div>
            <div class="track">
              <div style="width: <?= (int)$r['persen_visual'] ?>%;"></div>
            </div>
          </div>

          <div class="row">
            <div class="lab">
              <span>Auditori</span>
              <span><?= (int)$r['persen_auditori'] ?>%</span>
            </div>
            <div class="track">
              <div style="width: <?= (int)$r['persen_auditori'] ?>%;"></div>
            </div>
          </div>

          <div class="row">
            <div class="lab">
              <span>Kinestetik</span>
              <span><?= (int)$r['persen_kinestetik'] ?>%</span>
            </div>
            <div class="track">
              <div style="width: <?= (int)$r['persen_kinestetik'] ?>%;"></div>
            </div>
          </div>

          <div class="actions" style="justify-content: flex-start; align-items: center;">
            <a class="btn ghost" style="padding: 8px 18px;" href="<?= e(url('/hasil.php?id=' . (int)$r['id'])) ?>">
              Lihat detail
            </a>

            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button type="button" class="btn red del" style="padding: 8px 18px;">
                Hapus hasil ini
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
  // Toggle buka/tutup detail item dan konfirmasi dua kali klik sebelum hapus
  document.querySelectorAll('.item').forEach(function(item) {
    const head = item.querySelector('.head');
    const body = item.querySelector('.body');
    const delBtn = item.querySelector('.del');

    head.onclick = function() {
      const isOpen = head.getAttribute('aria-expanded') === 'true';
      head.setAttribute('aria-expanded', !isOpen);
      body.classList.toggle('hidden', isOpen);
    };

    delBtn.onclick = function() {
      if (delBtn.dataset.sure) {
        delBtn.closest('form').submit();
      } else {
        delBtn.dataset.sure = '1';
        delBtn.textContent = 'Klik lagi untuk konfirmasi hapus';
      }
    };
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>