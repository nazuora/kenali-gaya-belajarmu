<?php
// Panggil koneksi database
require __DIR__ . '/config/koneksi.php';

// Definisi warna konsisten untuk gaya belajar
if (!defined('WARNA_GAYA')) {
    define('WARNA_GAYA', [
        'V' => ['#8b7cf6', '#ede9fe'], // Visual (Ungu / Biru lembut)
        'A' => ['#f97316', '#ffedd5'], // Auditori (Oranye / Peach)
        'K' => ['#10b981', '#d1fae5']  // Kinestetik (Hijau Toska / Mint)
    ]);
}

$login = !empty($_SESSION['user']);
$uid   = $login ? (int)$_SESSION['user']['id'] : 0;
$h     = null;

// Cek apakah halaman diakses lewat tombol submit form kuis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $jawab = $_POST['q'] ?? [];
    
    // Ambil daftar ID pertanyaan dari database
    $query_pertanyaan = $db->query('SELECT id FROM pertanyaan');
    $ids = [];
    while ($row = $query_pertanyaan->fetch_assoc()) {
        $ids[] = $row['id'];
    }
    
    $total = count($ids);
    if ($total === 0) {
        header('Location: ' . url('/kuis.php'));
        exit;
    }

    // Hitung jawaban per kategori gaya belajar
    // 1 = Visual, 2 = Auditori, 3 = Kinestetik
    $hitung = [1 => 0, 2 => 0, 3 => 0];
    foreach ($ids as $pid) {
        $g = isset($jawab[$pid]) ? (int)$jawab[$pid] : 0;
        if ($g < 1 || $g > 3) {
            // Kalau ada soal yang belum dijawab, lempar balik ke halaman kuis
            header('Location: ' . url('/kuis.php'));
            exit;
        }
        $hitung[$g]++;
    }

    // Hitung persentase masing-masing gaya belajar
    $pv = round(($hitung[1] / $total) * 100);
    $pa = round(($hitung[2] / $total) * 100);
    $pk = round(($hitung[3] / $total) * 100);
    
    // Cari nilai yang paling tinggi untuk menentukan gaya utama
    $nilai_maksimal = max($hitung);
    $pilihan_utama = array_keys($hitung, $nilai_maksimal);
    $utama = $pilihan_utama[0]; // Jika seri, ambil ID terkecil

    // Jika belum login, simpan sementara di session
    if (!$login) {
        $_SESSION['hasil_tertunda'] = [
            'v' => $pv, 
            'a' => $pa, 
            'k' => $pk, 
            'g' => (int)$utama, 
            't' => time()
        ];
    }

    // Jika sudah login, simpan permanen ke tabel hasil_kuis
    if ($login) {
        $stmt = $db->prepare('INSERT INTO hasil_kuis (user_id, persen_visual, persen_auditori, persen_kinestetik, gaya_utama_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iiiii', $uid, $pv, $pa, $pk, $utama);
        $stmt->execute();
        
        $id_baru = $db->insert_id;
        header('Location: ' . url('/hasil.php?id=' . $id_baru));
        exit;
    }

    // Ambil keterangan hasil untuk tamu
    $stmt = $db->prepare('SELECT kode, nama, julukan, kalimat_hasil, tips FROM gaya_belajar WHERE id = ?');
    $stmt->bind_param('i', $utama);
    $stmt->execute();
    $h = $stmt->get_result()->fetch_assoc();
    
    if (!$h) {
        http_response_code(500);
        exit('Data gaya belajar belum ada.');
    }
    
    $h['persen_visual'] = $pv;
    $h['persen_auditori'] = $pa;
    $h['persen_kinestetik'] = $pk;

} else {
    // Jika diakses melalui URL biasa (bukan POST), tampilkan hasil berdasarkan ID di URL
    if (!$login) {
        header('Location: ' . url('/masuk.php'));
        exit;
    }
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $stmt = $db->prepare('SELECT h.persen_visual, h.persen_auditori, h.persen_kinestetik,
                                 g.kode, g.nama, g.julukan, g.kalimat_hasil, g.tips
                          FROM hasil_kuis h
                          JOIN gaya_belajar g ON g.id = h.gaya_utama_id
                          WHERE h.id = ? AND h.user_id = ?');
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    $h = $stmt->get_result()->fetch_assoc();
    
    if (!$h) {
        http_response_code(404);
        exit('Hasil tidak ditemukan.');
    }
}

// Memecah tips belajar per baris
$tips = [];
$baris_tips = explode("\n", (string)$h['tips']);
foreach ($baris_tips as $t) {
    $t_bersih = trim($t);
    if ($t_bersih !== '') {
        $tips[] = $t_bersih;
    }
}

$kode  = $h['kode'];

// Menentukan warna latar belakang kartu berdasarkan kode gaya belajar
$latar = 'var(--blue-soft)';
if ($kode === 'A') {
    $latar = 'var(--yellow-soft)';
} elseif ($kode === 'K') {
    $latar = 'var(--green-soft)';
}

// Data persentase untuk bar progres
$bar = [
    ['Visual',     (int)$h['persen_visual'],     WARNA_GAYA['V'][0]],
    ['Auditori',   (int)$h['persen_auditori'],   WARNA_GAYA['A'][0]],
    ['Kinestetik', (int)$h['persen_kinestetik'], WARNA_GAYA['K'][0]],
];

$dataKartu = [
    'kode'    => $kode,
    'julukan' => $h['julukan'],
    'warna'   => WARNA_GAYA[$kode][0],
    'latar'   => WARNA_GAYA[$kode][1],
    'pct'     => [
        'V' => (int)$h['persen_visual'], 
        'A' => (int)$h['persen_auditori'], 
        'K' => (int)$h['persen_kinestetik']
    ],
    'tips'    => array_slice($tips, 0, 2),
];

$namaDepan = $login ? nama_depan($_SESSION['user']['nama']) : '';

$judul = 'Hasil Kuis';
require __DIR__ . '/includes/header.php';
?>

<div class="narrow">
  <div class="qtop"><span>Kuis</span><span>Selesai</span></div>
  <div class="bar"><div style="width:100%"></div></div>

  <section class="card" id="result" style="margin-top:18px">
    <div class="badge" style="background:<?= $latar ?>">
      <p>Gaya belajar utamamu</p>
      <h1><?= e($h['julukan']) ?> (<?= e($h['nama']) ?>)</h1>
      <p><?= e($h['kalimat_hasil']) ?></p>
    </div>

    <?php foreach ($bar as $item): ?>
      <?php 
        $nama_kategori = $item[0];
        $persen = $item[1];
        $warna_bar = $item[2];
      ?>
      <div class="row">
        <div class="lab"><span><?= e($nama_kategori) ?></span><span><?= $persen ?>%</span></div>
        <div class="track"><div style="width:<?= $persen ?>%;background:<?= $warna_bar ?>"></div></div>
      </div>
    <?php endforeach; ?>

    <h2>Tips belajar untukmu</h2>
    <ul class="tips">
      <?php foreach ($tips as $t): ?>
        <li><?= e($t) ?></li>
      <?php endforeach; ?>
    </ul>

    <?php if ($login): ?>
      <div class="msg">Hasil tersimpan di Riwayat.</div>
    <?php else: ?>
      <div class="msg">
        <span style="color:var(--muted)">Masuk agar hasil tersimpan di Riwayat. 
          <a href="<?= e(url('/masuk.php')) ?>" style="color:var(--blue);font-weight:900">Masuk sekarang</a>
        </span>
      </div>
    <?php endif; ?>

    <div class="actions">
      <a class="btn ghost" href="<?= e(url('/kuis.php')) ?>">Ulangi kuis</a>
      <button type="button" class="btn" id="mk">Buat kartu</button>
    </div>
  </section>

  <!-- Bagian untuk pratinjau kartu hasil -->
  <section class="card hidden" id="cardv" style="margin-top:18px">
    <h1 class="s">Kartu hasilmu</h1>
    <label class="l" for="nm">Nama di kartu</label>
    <input class="t" id="nm" maxlength="20" autocomplete="off" placeholder="Nama kamu" value="<?= e($namaDepan) ?>" style="margin-bottom:14px">
    <img id="out" alt="Kartu hasil gaya belajar">
    <p class="hint">Tekan lama gambar (atau klik kanan) untuk menyimpan.</p>
    <div class="actions">
      <button type="button" class="btn ghost" id="back">Kembali ke hasil</button>
      <a class="btn" href="<?= e(url('/kuis.php')) ?>">Ulangi kuis</a>
    </div>
  </section>
</div>

<!-- Canvas tersembunyi untuk merender gambar kartu -->
<canvas id="cv" width="1080" height="1350" hidden></canvas>

<script>
  // Menerima data dari PHP ke JavaScript
  const dataKartu = <?= json_encode($dataKartu, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const namaKategoriMap = { V: 'Visual', A: 'Auditori', K: 'Kinestetik' };
  const warnaMap = { V: '#8B7CF0', A: '#FF9F7A', K: '#5CCB9C' };
  const daftarKunci = ['V', 'A', 'K'];
  const warnaInk = '#3A2F52';
  const jenisFont = "'Nunito', system-ui, sans-serif";

  // Fungsi bantu untuk mengambil elemen berdasarkan ID
  function ambilElemen(id) {
    return document.getElementById(id);
  }

  const canvas = ambilElemen('cv');
  const context = canvas.getContext('2d');

  // Fungsi menggambar ikon ke dalam canvas
  function gambarIkon(kodeGaya, pusatX, pusatY) {
    context.save(); 
    context.translate(pusatX - 128, pusatY - 128); 
    context.scale(4, 4);
    context.lineWidth = 3; 
    context.lineCap = 'round'; 
    context.lineJoin = 'round'; 
    context.strokeStyle = warnaInk; 
    context.fillStyle = '#fff';

    if (kodeGaya === 'V') {
      context.beginPath(); 
      context.ellipse(32, 32, 28, 18, 0, 0, 7); 
      context.fill();
      context.fillStyle = warnaInk; 
      context.beginPath(); 
      context.arc(32, 32, 12, 0, 7); 
      context.fill();
      context.fillStyle = '#fff'; 
      context.beginPath(); 
      context.arc(36, 28, 3.5, 0, 7); 
      context.fill();
    } else if (kodeGaya === 'A') {
      const jalurAudio = new Path2D('M20 30c0-9 5-16 13-16s13 7 13 15c0 9-8 10-8 17a7 7 0 0 1-14 0');
      context.fill(jalurAudio); 
      context.stroke(jalurAudio); 
      context.stroke(new Path2D('M29 30c0-4 2-6 5-6'));
    } else {
      const jalurKines = new Path2D('M18 34V18a4 4 0 0 1 8 0v10m0-12a4 4 0 0 1 8 0v12m0-10a4 4 0 0 1 8 0v14c0 10-7 16-16 16-10 0-16-6-20-14l-4-8a4 4 0 0 1 7-3z');
      context.fill(jalurKines); 
      context.stroke(jalurKines);
    }
    context.restore();
  }

  // Fungsi utama untuk menggambar kartu hasil belajar
  function buatKartu() {
    const inputNama = ambilElemen('nm').value.trim();
    
    context.clearRect(0, 0, 1080, 1350);
    
    // Latar belakang kartu
    context.fillStyle = dataKartu.latar; 
    context.fillRect(0, 0, 1080, 1350);
    
    // Judul Header
    context.textAlign = 'left'; 
    context.fillStyle = warnaInk; 
    context.font = '900 34px ' + jenisFont; 
    context.fillText('Kenali Gaya Belajarmu', 80, 100);
    
    // Lingkaran hiasan kecil di pojok atas
    const hiasan = [['#8B7CF0', 900], ['#FF9F7A', 936], ['#5CCB9C', 972]];
    for (let i = 0; i < hiasan.length; i++) {
      context.fillStyle = hiasan[i][0]; 
      context.beginPath(); 
      context.arc(hiasan[i][1] + 10, 88, 13, 0, 7); 
      context.fill();
    }
    
    // Lingkaran ikon utama di tengah atas
    context.fillStyle = dataKartu.warna; 
    context.beginPath(); 
    context.arc(540, 330, 170, 0, 7); 
    context.fill(); 
    gambarIkon(dataKartu.kode, 540, 330);
    
    // Teks Julukan Gaya Belajar
    context.textAlign = 'center'; 
    context.fillStyle = warnaInk;
    context.font = '700 34px ' + jenisFont; 
    context.fillText('Gaya belajar utama' + (inputNama ? ' ' + inputNama : ''), 540, 590);
    
    context.font = '900 74px ' + jenisFont; 
    context.fillText(dataKartu.julukan, 540, 680);
    
    // Bar persentase kategori
    context.textAlign = 'left';
    for (let i = 0; i < daftarKunci.length; i++) {
      let kunci = daftarKunci[i];
      let posisiY = 760 + (i * 84);
      
      context.fillStyle = warnaInk; 
      context.font = '700 30px ' + jenisFont; 
      context.fillText(namaKategoriMap[kunci], 80, posisiY);
      
      context.textAlign = 'right'; 
      context.fillText(dataKartu.pct[kunci] + '%', 1000, posisiY); 
      context.textAlign = 'left';
      
      // Jalur bar kosong
      context.fillStyle = 'rgba(45,49,66,.12)'; 
      context.beginPath();
      context.roundRect(80, posisiY + 14, 920, 22, 11);
      context.fill();
      
      // Bar isi persentase
      context.fillStyle = warnaMap[kunci]; 
      context.beginPath();
      let lebarBar = Math.max(22, 920 * dataKartu.pct[kunci] / 100);
      context.roundRect(80, posisiY + 14, lebarBar, 22, 11);
      context.fill();
    }
    
    // Bagian Tips Singkat
    context.fillStyle = warnaInk; 
    context.font = '900 34px ' + jenisFont; 
    context.fillText('Coba ini:', 80, 1068);
    
    context.font = '700 32px ' + jenisFont;
    for (let i = 0; i < dataKartu.tips.length; i++) {
      context.fillText('\u2022 ' + dataKartu.tips[i], 80, 1120 + (i * 50));
    }
    
    // Footer / Catatan Kaki Canvas
    context.textAlign = 'center'; 
    context.fillStyle = 'rgba(45,49,66,.65)'; 
    context.font = '700 28px ' + jenisFont; 
    context.fillText('Cari tahu gaya belajarmu juga!', 540, 1290);
    
    // Masukkan hasil render ke elemen gambar (img)
    ambilElemen('out').src = canvas.toDataURL('image/png');
  }

  // Event listener untuk tombol dan input
  ambilElemen('mk').onclick = function() {
    ambilElemen('result').classList.add('hidden'); 
    ambilElemen('cardv').classList.remove('hidden'); 
    buatKartu();
  };

  ambilElemen('back').onclick = function() {
    ambilElemen('cardv').classList.add('hidden'); 
    ambilElemen('result').classList.remove('hidden');
  };

  ambilElemen('nm').oninput = function() {
    buatKartu();
  };
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>