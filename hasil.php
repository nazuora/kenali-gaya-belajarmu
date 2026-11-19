<?php
// Panggil koneksi database
require __DIR__ . '/config/koneksi.php';

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

// Data persentase untuk bar progres
$bar = [
    ['Visual',     (int)$h['persen_visual']],
    ['Auditori',   (int)$h['persen_auditori']],
    ['Kinestetik', (int)$h['persen_kinestetik']],
];

$judul = 'Hasil Kuis';
require __DIR__ . '/includes/header.php';
?>

<div class="narrow">

  <section class="card" id="result" style="margin-top:18px">
    <div class="badge">
      <p>Gaya belajar utamamu</p>
      <h1><?= e($h['julukan']) ?> (<?= e($h['nama']) ?>)</h1>
      <p><?= e($h['kalimat_hasil']) ?></p>
    </div>

    <?php foreach ($bar as $item): ?>
      <?php 
        $nama_kategori = $item[0];
        $persen = $item[1];
      ?>
      <div class="row">
        <div class="lab"><span><?= e($nama_kategori) ?></span><span><?= $persen ?>%</span></div>
                <div class="track">
          <div style="width:<?= $persen ?>%; background:#333;"></div>
        </div>
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
      <a class="btn ghost" href="<?= e(url('/petunjuk.php')) ?>">
        Ulangi kuis
      </a>
    </div>
  </section>

</div>



<?php require __DIR__ . '/includes/footer.php'; ?>