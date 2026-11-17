<?php
<?php
/* 
  File: index.php
  Keterangan: Halaman utama (beranda) aplikasi "Kenali Gaya Belajarmu". 
  Berisi sambutan, informasi singkat mengenai jenis gaya belajar, 
  serta tombol navigasi untuk memulai kuis atau masuk ke akun.
*/

// Memanggil file koneksi database dan ikon
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/includes/ikon.php';

// Mengambil data gaya belajar dari database
$query_gaya = $db->query('SELECT kode, nama, kalimat_hasil FROM gaya_belajar ORDER BY id');
$gaya = [];
while ($row = $query_gaya->fetch_assoc()) {
    $gaya[] = $row;
}

// Cek apakah user sudah login atau belum
$login = false;
if (!empty($_SESSION['user'])) {
    $login = true;
}

// Ambil pertanyaan pertama untuk fitur "Coba satu pertanyaan" di halaman utama
$coba = null;
$query_pertanyaan = $db->query('SELECT id, teks FROM pertanyaan ORDER BY urutan, id LIMIT 1');
$q = $query_pertanyaan->fetch_assoc();

if ($q) {
    $stmt = $db->prepare('SELECT j.teks, g.kode FROM jawaban j JOIN gaya_belajar g ON g.id = j.gaya_id WHERE j.pertanyaan_id = ? ORDER BY j.gaya_id');
    $stmt->bind_param('i', $q['id']);
    $stmt->execute();
    $result_jawaban = $stmt->get_result();
    
    $jawaban_list = [];
    while ($j_row = $result_jawaban->fetch_assoc()) {
        $jawaban_list[] = $j_row;
    }
    
    $coba = [
        'teks' => $q['teks'], 
        'jawaban' => $jawaban_list
    ];
}

// Menyimpan info penjelasan untuk JavaScript
$cobaInfo = [];
foreach ($gaya as $g) {
    $cobaInfo[$g['kode']] = [
        'nama' => $g['nama'], 
        'kalimat' => $g['kalimat_hasil']
    ];
}

$judul = 'Kenali Gaya Belajarmu';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div>
    <h1>Temukan cara belajar yang paling pas untukmu.</h1>
    <p class="lead">Jawab pertanyaan seputar situasi sehari-hari. Kamu akan dapat profil gaya belajar dan tips yang bisa langsung dipraktikkan.</p>
    <div class="links" style="gap:12px">
      <a href="<?= e(url('/petunjuk.php')) ?>" class="btn">Mulai Kuis Sekarang</a>
      <a href="<?= e(url('/jenis.php')) ?>" class="btn ghost">Lihat jenis gaya belajar</a>
    </div>
  </div>

  <?php if ($coba): ?>
    <div class="cobacard" aria-labelledby="judulcoba">
      <h2 id="judulcoba">Coba satu pertanyaan</h2>
      <p><?= e($coba['teks']) ?></p>
      
      <!-- Pilihan jawaban -->
      <div class="coba-opsi-container">
        <?php foreach ($coba['jawaban'] as $j): ?>
          <button type="button" class="opt <?= e(strtolower($j['kode'])) ?>" data-kode="<?= e($j['kode']) ?>" aria-pressed="false"><?= e($j['teks']) ?></button>
        <?php endforeach; ?>
      </div>

      <div id="dres" aria-live="polite"></div>
    </div>
  <?php endif; ?>
</section>

<h2 style="margin-top:48px;text-align:center">Kenalan dengan tiga maskot</h2>
<div class="trio">
  <?php foreach ($gaya as $g): ?>
    <button type="button" class="mas <?= e(strtolower($g['kode'])) ?>" data-kode="<?= e($g['kode']) ?>" aria-pressed="false">
      <span class="mi"><?= ikon($g['kode']) ?></span><?= e($g['nama']) ?>
    </button>
  <?php endforeach; ?>
</div>
<div class="trioNote" id="trioNote" aria-live="polite">Ketuk satu maskot untuk berkenalan.</div>

<h2 style="margin-top:36px;font-size:1.5rem">Cara kerjanya</h2>
<ol class="steps">
  <li><strong>Buat akun</strong><span>Supaya hasilmu tersimpan.</span></li>
  <li><strong>Jawab kuis</strong><span>Pilih jawaban yang paling mirip kebiasaanmu.</span></li>
  <li><strong>Lihat hasil</strong><span>Dapatkan persentase dan rekomendasi.</span></li>
  <li><strong>Pantau riwayat</strong><span>Bandingkan hasilmu dari waktu ke waktu.</span></li>
</ol>

<script>
  // Mengambil data dari PHP ke JavaScript
  const infoData = <?= json_encode($cobaInfo, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  
  // Script untuk tombol pilihan pertanyaan uji coba
  const tombolOpsi = document.querySelectorAll('.opt');
  tombolOpsi.forEach(function(b) {
    b.onclick = function() {
      tombolOpsi.forEach(function(o) {
        o.setAttribute('aria-pressed', 'false');
      });
      b.setAttribute('aria-pressed', 'true');
      
      const kode = b.getAttribute('data-kode');
      const dataGaya = infoData[kode];
      const kotakHasil = document.getElementById('dres');
      
      kotakHasil.innerHTML = '';
      
      const tagB = document.createElement('b');
      tagB.className = kode.toLowerCase();
      tagB.textContent = dataGaya.nama;
      
      kotakHasil.appendChild(tagB);
      kotakHasil.append(' ' + dataGaya.kalimat + ' Kuis lengkap akan mengungkap profilmu.');
    };
  });

  // Script untuk tombol maskot
  const tombolMaskot = document.querySelectorAll('.mas');
  tombolMaskot.forEach(function(b) {
    b.onclick = function() {
      tombolMaskot.forEach(function(x) {
        x.setAttribute('aria-pressed', 'false');
      });
      b.setAttribute('aria-pressed', 'true');
      
      const kode = b.getAttribute('data-kode');
      const catatan = document.getElementById('trioNote');
      
      catatan.className = 'trioNote ' + kode.toLowerCase();
      catatan.textContent = infoData[kode].kalimat;
    };
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>