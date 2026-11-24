<?php
/*
  File: hasil.php
  Keterangan: Menampilkan gaya belajar utama, persentase Visual, Auditori,
  dan Kinestetik, serta tips belajar berdasarkan hasil kuis pengguna.
*/

require __DIR__ . '/config/koneksi.php';

wajib_login();   // hanya yang sudah masuk yang boleh melihat hasil

$uid = (int)$_SESSION['user']['id'];
$id  = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . url('/riwayat.php'));
    exit;
}

$stmt = $db->prepare('SELECT h.persen_visual, h.persen_auditori, h.persen_kinestetik,
                             g.id AS gaya_id, g.kode, g.nama, g.julukan, g.kalimat_hasil, g.tips
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

$utama = (int)$h['gaya_id'];

// Daftar teks dan tips per gaya belajar (1 = Visual, 2 = Auditori, 3 = Kinestetik)
$daftar_hasil_gaya = [
    1 => [
        'julukan'       => 'Sang Pengamat Visual',
        'nama'          => 'Visual',
        'kalimat_hasil' => 'Orang visual menyerap informasi paling baik lewat penglihatan: gambar, warna, diagram, dan tata letak yang rapi.',
        'tips'          => [
            'Buat mind map dan diagram alur',
            'Pakai stabilo dengan kode warna',
            'Tonton video penjelasan',
            'Ubah angka dan teks menjadi tabel atau grafik',
        ],
    ],
    2 => [
        'julukan'       => 'Sang Pendengar Ulung',
        'nama'          => 'Auditori',
        'kalimat_hasil' => 'Orang auditori menyerap informasi paling baik lewat pendengaran: penjelasan lisan, diskusi, dan irama.',
        'tips'          => [
            'Baca teks atau materi pelajaran dengan bersuara keras',
            'Dengarkan rekaman penjelasan atau podcast materi terkait',
            'Bentuk kelompok diskusi untuk membahas bahan pelajaran bersama',
        ],
    ],
    3 => [
        'julukan'       => 'Sang Pembelajar Aktif',
        'nama'          => 'Kinestetik',
        'kalimat_hasil' => 'Orang kinestetik menyerap informasi paling baik lewat gerakan dan pengalaman langsung.',
        'tips'          => [
            'Perbanyak latihan soal dan praktik',
            'Belajar sambil berjalan atau berdiri',
            'Pakai benda nyata sebagai alat peraga',
            'Istirahat singkat untuk bergerak tiap 25 menit',
        ],
    ],
];

if (isset($h['gaya_id'])) {
    $id_gaya = (int)$h['gaya_id'];
} else {
    $id_gaya = (int)$utama;
}

$tips = [];

if (isset($daftar_hasil_gaya[$id_gaya])) {
    $h['julukan']       = $daftar_hasil_gaya[$id_gaya]['julukan'];
    $h['nama']          = $daftar_hasil_gaya[$id_gaya]['nama'];
    $h['kalimat_hasil'] = $daftar_hasil_gaya[$id_gaya]['kalimat_hasil'];
    $tips               = $daftar_hasil_gaya[$id_gaya]['tips'];
} else {
    if (isset($h['tips'])) {
        $teks_tips = (string)$h['tips'];
    } else {
        $teks_tips = '';
    }

    $baris_tips = explode("\n", $teks_tips);
    foreach ($baris_tips as $t) {
        $t_bersih = trim($t);
        if ($t_bersih !== '') {
            $tips[] = $t_bersih;
        }
    }
}

$bar = [
    ['Visual',     (int)$h['persen_visual']],
    ['Auditori',   (int)$h['persen_auditori']],
    ['Kinestetik', (int)$h['persen_kinestetik']],
];

$judul = 'Hasil Kuis';
require __DIR__ . '/includes/header.php';
?>

<div class="narrow">
  <section class="card" id="result" style="margin-top: 18px;">
    <div class="badge">
      <p>Gaya belajar utamamu</p>
      <h1><?= e($h['julukan']) ?> (<?= e($h['nama']) ?>)</h1>
      <p><?= e($h['kalimat_hasil']) ?></p>
    </div>

    <?php foreach ($bar as $item): ?>
      <?php
        $nama_kategori = $item[0];
        $persen = (int)$item[1];
      ?>
      <div class="row">
        <div class="lab">
          <span><?= e($nama_kategori) ?></span>
          <span><?= $persen ?>%</span>
        </div>
        <div class="track">
          <div style="width: <?= $persen ?>%;"></div>
        </div>
      </div>
    <?php endforeach; ?>

    <h2>Tips belajar untukmu</h2>
    <ul class="tips">
      <?php foreach ($tips as $t): ?>
        <li><?= e($t) ?></li>
      <?php endforeach; ?>
    </ul>

    <div class="msg">Hasil tersimpan di Riwayat.</div>

    <div class="actions">
      <a class="btn ghost" href="<?= e(url('/petunjuk.php')) ?>">Ulangi kuis</a>
    </div>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>