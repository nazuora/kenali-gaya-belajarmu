<?php
// Sambungkan ke database, session, dan fungsi bantu
require __DIR__ . '/config/koneksi.php';

// Cek apakah pengguna sudah login dan ambil ID-nya
$sudah_login = !empty($_SESSION['user']);
$id_user     = $sudah_login ? (int)$_SESSION['user']['id'] : 0;

// ==========================================
// 1. HAPUS RIWAYAT (DELETE)
// ==========================================
if ($sudah_login && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    csrf_periksa(); // Cek token keamanan form
    $id_hasil = (int)($_POST['id'] ?? 0);

    // Hapus hanya jika id hasil cocok dan milik user yang sedang login
    $stmt = mysqli_prepare($db, 'DELETE FROM hasil_kuis WHERE id = ? AND user_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $id_hasil, $id_user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Redirect agar tidak kirim ulang form saat di-refresh
    header('Location: ' . url('/riwayat.php'));
    exit;
}

// ==========================================
// 2. AMBIL RIWAYAT (READ) MILIK USER INI
// ==========================================
$daftar_riwayat = [];
if ($sudah_login) {
    $sql = 'SELECT h.id, h.persen_visual, h.persen_auditori, h.persen_kinestetik, h.dibuat_pada,
                   g.kode, g.nama AS gaya, g.julukan, g.pengertian, g.tips
            FROM hasil_kuis h
            JOIN gaya_belajar g ON g.id = h.gaya_utama_id
            WHERE h.user_id = ?
            ORDER BY h.dibuat_pada DESC, h.id DESC';

    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    mysqli_stmt_execute($stmt);
    $hasil_query = mysqli_stmt_get_result($stmt);

    while ($baris = mysqli_fetch_assoc($hasil_query)) {
        $daftar_riwayat[] = $baris;
    }
    mysqli_stmt_close($stmt);
}

$judul = 'Riwayat Kuis';
require __DIR__ . '/includes/header.php';
?>

<style>
  /* Hanya tambahan kecil di luar style.css */
  .kotak-tips-riwayat {
    margin-top: 14px;
    padding: 12px 14px;
    border: 1px solid var(--line);
    border-radius: 16px;
    background: var(--bg);
  }
</style>

<div class="narrow" style="max-width:720px">
  <h1 class="s">Riwayat kuismu</h1>
  <p class="sub">Lihat hasil kuis yang pernah kamu kerjakan sebelumnya.</p>

  <?php if (!$sudah_login): ?>
    <div class="empty">
      <strong>Kamu belum masuk ke akun.</strong>
      <p>Silakan masuk terlebih dahulu agar riwayat kuis pribadimu bisa ditampilkan.</p>
      <a class="btn" href="<?= e(url('/masuk.php')) ?>">Masuk</a>
    </div>

  <?php elseif (count($daftar_riwayat) === 0): ?>
    <div class="empty">
      <strong>Belum ada riwayat kuis.</strong>
      <p>Akun ini belum pernah mengerjakan kuis. Yuk coba kerjakan kuis pertamamu!</p>
      <a class="btn" href="<?= e(url('/kuis.php')) ?>">Mulai kuis</a>
    </div>

  <?php else: ?>
    <?php foreach ($daftar_riwayat as $item): ?>
      <div class="item">
        <!-- Klik tombol ini untuk membuka/menutup detail hasil -->
        <button type="button" class="head" aria-expanded="false">
          <span class="pill"><?= e($item['gaya']) ?> - <?= e($item['julukan']) ?></span>
          <span class="date"><?= e(tanggal_id($item['dibuat_pada'])) ?></span>
          <span class="chev" aria-hidden="true">&#9662;</span>
        </button>

        <!-- Detail hasil kuis (tersembunyi sampai tombol diklik) -->
        <div class="body hidden">
          <p><strong>Gaya belajar utamamu:</strong> <?= e($item['gaya']) ?> (<?= e($item['julukan']) ?>)</p>
          <p><?= e($item['pengertian']) ?></p>

          <div class="row">
            <div class="lab">
              <span>Visual</span>
              <span><?= (int)$item['persen_visual'] ?>%</span>
            </div>
            <div class="track">
              <div style="width:<?= (int)$item['persen_visual'] ?>%"></div>
            </div>
          </div>

          <div class="row">
            <div class="lab">
              <span>Auditori</span>
              <span><?= (int)$item['persen_auditori'] ?>%</span>
            </div>
            <div class="track">
              <div style="width:<?= (int)$item['persen_auditori'] ?>%"></div>
            </div>
          </div>

          <div class="row">
            <div class="lab">
              <span>Kinestetik</span>
              <span><?= (int)$item['persen_kinestetik'] ?>%</span>
            </div>
            <div class="track">
              <div style="width:<?= (int)$item['persen_kinestetik'] ?>%"></div>
            </div>
          </div>

          <div class="kotak-tips-riwayat">
            <strong>Tips belajar untukmu:</strong>
            <p style="margin:6px 0 0;"><?= nl2br(e($item['tips'])) ?></p>
          </div>

          <div class="actions" style="justify-content:flex-start; align-items:center;">
            <a class="btn ghost sm" href="<?= e(url('/hasil.php?id=' . (int)$item['id'])) ?>">Lihat halaman hasil</a>

            <form method="post" style="margin:0;">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
              <button type="button" class="btn red sm tombol-hapus">Hapus hasil</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
  // Buka atau tutup detail hasil saat tombol panah/header diklik
  document.querySelectorAll('.head').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
      const item = tombol.closest('.item');
      const detail = item.querySelector('.body');
      const sedangBuka = tombol.getAttribute('aria-expanded') === 'true';

      tombol.setAttribute('aria-expanded', String(!sedangBuka));
      detail.classList.toggle('hidden', sedangBuka);
    });
  });

  // Minta klik dua kali sebelum benar-benar mengirim form hapus
  document.querySelectorAll('.tombol-hapus').forEach(function (tombolHapus) {
    tombolHapus.addEventListener('click', function () {
      if (tombolHapus.dataset.yakin === '1') {
        tombolHapus.closest('form').submit();
      } else {
        tombolHapus.dataset.yakin = '1';
        tombolHapus.textContent = 'Klik lagi untuk hapus';
      }
    });
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
