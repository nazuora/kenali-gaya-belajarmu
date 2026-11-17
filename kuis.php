<?php
session_start();

// Aktifkan laporan error untuk debugging (opsional)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Cek apakah user sudah melewati halaman petunjuk
if (!isset($_SESSION['sudah_baca_petunjuk']) || $_SESSION['sudah_baca_petunjuk'] !== true) {
    header("Location: petunjuk.php");
    exit;
}

// 2. Panggil koneksi database
require __DIR__ . '/config/koneksi.php';
wajib_login();

// READ: semua pertanyaan beserta tiga jawabannya (urutan jawaban: Visual, Auditori, Kinestetik)
$sql = 'SELECT p.id AS pid, p.teks AS pteks, j.gaya_id, j.teks AS jteks
        FROM pertanyaan p
        JOIN jawaban j ON j.pertanyaan_id = p.id
        ORDER BY p.urutan, p.id, j.gaya_id';
$soal = [];
foreach ($db->query($sql) as $r) {
    $pid = (int)$r['pid'];
    $soal[$pid]['id']   = $pid;
    $soal[$pid]['teks'] = $r['pteks'];
    $soal[$pid]['jawaban'][] = ['gaya' => (int)$r['gaya_id'], 'teks' => $r['jteks']];
}
$soal = array_values($soal);

$judul = 'Kuis Gaya Belajar';
require __DIR__ . '/includes/header.php';
?>
<div class="narrow">
<?php if (!$soal): ?>
  <div class="empty"><strong>Belum ada pertanyaan.</strong><p>Admin perlu menambahkannya dulu.</p></div>
<?php else: ?>
  <div class="qtop"><span>Kuis</span><span id="count"></span></div>
  <div class="bar" role="progressbar" aria-label="Kemajuan kuis" aria-valuemin="0" aria-valuemax="100" id="pb"><div id="fill"></div></div>

  <section class="card" style="margin-top:18px">
    <h1 class="s" id="q"></h1>
    <div id="opts"></div>
    <div class="actions">
      <button type="button" class="btn ghost" id="prev">Kembali</button>
      <button type="button" class="btn" id="next" disabled>Lanjut</button>
    </div>
  </section>

  <!-- PERUBAHAN UTAMA: action diarahkan ke proses-kuis.php -->
  <form id="kirim" method="post" action="<?= e(url('/proses-kuis.php')) ?>" hidden><?= csrf_field() ?></form>

  <script>
    const Q = <?= json_encode($soal, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    let i = 0;
    const ans = Array(Q.length).fill(null);
    const $ = id => document.getElementById(id);

    function renderQ() {
      $('q').textContent = Q[i].teks;
      $('count').textContent = (i + 1) + '/' + Q.length;
      const p = (i / Q.length) * 100;
      $('fill').style.width = p + '%';
      
      $('pb').setAttribute('aria-valuenow', Math.round(p)); 

      $('opts').innerHTML = '';
      Q[i].jawaban.forEach(j => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'opt';
        b.textContent = j.teks;
        b.setAttribute('aria-pressed', ans[i] === j.gaya);
        b.onclick = () => { ans[i] = j.gaya; renderQ(); };
        $('opts').appendChild(b);
      });
      $('prev').style.visibility = i ? 'visible' : 'hidden';
      $('next').disabled = ans[i] === null;
      $('next').textContent = i === Q.length - 1 ? 'Lihat hasil' : 'Lanjut';
    }
    $('prev').onclick = () => { i--; renderQ(); };
    $('next').onclick = () => {
      if (i < Q.length - 1) { i++; renderQ(); return; }
      const f = $('kirim');
      f.innerHTML = '<?= csrf_field() ?>'; 
      Q.forEach((q, n) => {
        const h = document.createElement('input');
        h.type = 'hidden'; 
        h.name = 'jawaban[' + q.id + ']'; 
        h.value = ans[n];
        f.appendChild(h);
      });
      f.submit();
    };
    renderQ();
  </script>
<?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>