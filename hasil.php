<?php
// hasil.php
require __DIR__ . '/config/koneksi.php';
wajib_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . url('/riwayat.php'));
    exit;
}

// Ambil data hasil kuis dari database beserta relasi kodenya
$stmt = $db->prepare('
    SELECT h.*, g.kode, g.nama AS nama_gaya 
    FROM hasil_kuis h 
    JOIN gaya_belajar g ON h.gaya_utama_id = g.id 
    WHERE h.id = ? AND h.user_id = ?
');
$stmt->bind_param('ii', $id, $_SESSION['user']['id']);
$stmt->execute();
$hasil = $stmt->get_result()->fetch_assoc();

if (!$hasil) {
    http_response_code(404);
    exit('Hasil kuis tidak ditemukan.');
}

// Data teks hasil dan tips diatur langsung di sini (tanpa database)
$dataGaya = [
    'V' => [
        'kalimat_hasil' => 'Kamu paling cepat paham lewat gambar dan struktur yang jelas.',
        'tips'          => [
            'Buat mind map dan diagram alur untuk merangkum materi.',
            'Gunakan stabilo dengan kode warna berbeda untuk menandai poin penting.',
            'Tonton video penjelasan atau tutorial visual.',
            'Ubah deretan angka dan teks panjang menjadi bentuk tabel atau grafik.'
        ]
    ],
    'A' => [
        'kalimat_hasil' => 'Kamu paling cepat paham lewat suara dan percakapan.',
        'tips'          => [
            'Jelaskan ulang materi yang dipelajari dengan suara nyaring.',
            'Rekam suara rangkuman materi, lalu dengarkan kembali saat beristirahat.',
            'Bentuk kelompok belajar untuk berdiskusi dan saling tanya jawab.',
            'Dengarkan podcast edukatif atau materi audio terkait pelajaran.'
        ]
    ],
    'K' => [
        'kalimat_hasil' => 'Kamu paling cepat paham lewat praktik dan gerakan.',
        'tips'          => [
            'Perbanyak latihan soal mandiri dan lakukan eksperimen langsung.',
            'Lakukan aktivitas belajar sambil berjalan santai atau berdiri.',
            'Gunakan objek atau alat peraga nyata untuk memahami konsep abstrak.',
            'Ambil istirahat singkat untuk bergerak setiap 25 menit sesi belajar.'
        ]
    ]
];

$kode = strtoupper($hasil['kode']);
$infoGaya = $dataGaya[$kode] ?? [
    'kalimat_hasil' => 'Kamu memiliki gaya belajar yang unik.',
    'tips'          => ['Eksplorasi berbagai metode belajar yang paling nyaman bagimu.']
];

$judul = 'Hasil Kuis Gaya Belajar';
require __DIR__ . '/includes/header.php';
?>

<div class="narrow">
  <div class="badge" style="background: var(--<?= $kode === 'V' ? 'blue' : ($kode === 'A' ? 'yellow' : 'green') ?>-soft); border: 2px solid var(--<?= $kode === 'V' ? 'blue' : ($kode === 'A' ? 'yellow' : 'green') ?>);">
    <h1>Hasil Kuis Kamu</h1>
    <p style="font-size: 1.1rem; font-weight: 700;"><?= e($infoGaya['kalimat_hasil']) ?></p>
  </div>

  <div class="card">
    <h2 style="margin-top:0">Dominasi Gaya Belajar</h2>
    <p class="sub">Berikut persentase kecenderungan gaya belajarmu berdasarkan jawaban kuis:</p>

    <div class="row">
      <div class="lab"><span>Visual (V)</span><span><?= (int)$hasil['persen_visual'] ?>%</span></div>
      <div class="track"><div style="width: <?= (int)$hasil['persen_visual'] ?>%; background: var(--blue);"></div></div>
    </div>

    <div class="row">
      <div class="lab"><span>Auditori (A)</span><span><?= (int)$hasil['persen_auditori'] ?>%</span></div>
      <div class="track"><div style="width: <?= (int)$hasil['persen_auditori'] ?>%; background: var(--yellow);"></div></div>
    </div>

    <div class="row" style="margin-bottom: 0;">
      <div class="lab"><span>Kinestetik (K)</span><span><?= (int)$hasil['persen_kinestetik'] ?>%</span></div>
      <div class="track"><div style="width: <?= (int)$hasil['persen_kinestetik'] ?>%; background: var(--green);"></div></div>
    </div>
  </div>

  <div class="card">
    <h2 style="margin-top:0">Tips Belajar untuk <?= e($hasil['nama_gaya']) ?></h2>
    <ul class="tips">
      <?php foreach ($infoGaya['tips'] as $t): ?>
        <li><?= e($t) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div style="display: flex; gap: 12px; margin-top: 20px;">
    <a href="<?= url('/kuis.php') ?>" class="btn">Ulangi Kuis</a>
    <a href="<?= url('/riwayat.php') ?>" class="btn ghost">Lihat Riwayat</a>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>