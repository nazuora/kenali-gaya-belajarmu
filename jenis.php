<?php
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/includes/ikon.php';

// Data konten halaman Jenis Gaya Belajar diatur langsung di sini (tanpa ambil dari database)
$gaya = [
    [
        'kode'       => 'V',
        'nama'       => 'Visual',
        'pengertian' => 'Orang visual menyerap informasi paling baik lewat penglihatan: gambar, warna, diagram, dan tata letak yang rapi.',
        'ciri'       => [
            'Mudah mengingat wajah, bentuk, dan warna',
            'Suka membuat catatan berwarna dan bergambar',
            'Lebih paham saat melihat peta, grafik, atau slide',
            'Sulit fokus kalau hanya mendengar ceramah panjang',
        ],
        'tips'       => [
            'Buat mind map dan diagram alur',
            'Pakai stabilo dengan kode warna',
            'Tonton video penjelasan',
            'Ubah angka dan teks menjadi tabel atau grafik',
        ],
        'bidang'     => 'Desainer, arsitek, dan pekerjaan yang banyak memakai visual.',
    ],
    [
        'kode'       => 'A',
        'nama'       => 'Auditori',
        'pengertian' => 'Orang auditori menyerap informasi paling baik lewat pendengaran: penjelasan lisan, diskusi, dan irama.',
        'ciri'       => [
            'Mudah mengingat percakapan dan penjelasan',
            'Suka berdiskusi dan bertanya',
            'Sering membaca sambil bersuara',
            'Mudah terganggu oleh suara bising',
        ],
        'tips'       => [
            'Jelaskan ulang materi dengan suara',
            'Rekam rangkuman, lalu dengarkan ulang',
            'Belajar dalam kelompok diskusi',
            'Dengarkan podcast atau materi audio',
        ],
        'bidang'     => 'Pembicara, pengajar, dan pekerjaan yang banyak berkomunikasi lisan.',
    ],
    [
        'kode'       => 'K',
        'nama'       => 'Kinestetik',
        'pengertian' => 'Orang kinestetik menyerap informasi paling baik lewat gerakan dan pengalaman langsung.',
        'ciri'       => [
            'Sulit duduk diam dalam waktu lama',
            'Lebih paham setelah mencoba sendiri',
            'Suka praktik, eksperimen, dan simulasi',
            'Sering bergerak atau memegang benda saat berpikir',
        ],
        'tips'       => [
            'Perbanyak latihan soal dan praktik',
            'Belajar sambil berjalan atau berdiri',
            'Pakai benda nyata sebagai alat peraga',
            'Istirahat singkat untuk bergerak tiap 25 menit',
        ],
        'bidang'     => 'Teknisi, atlet, dan pekerjaan yang banyak praktik lapangan.',
    ],
];

$judul = 'Jenis Gaya Belajar';
require __DIR__ . '/includes/header.php';
?>
<h1 class="s">Jenis gaya belajar</h1>
<p class="sub">Gaya belajar adalah cara yang paling nyaman bagi seseorang untuk menerima dan mengingat informasi. Pilih salah satu untuk mengenalnya.</p>

<div class="tabs" role="tablist">
  <?php foreach ($gaya as $i => $g): ?>
    <button type="button" role="tab" class="<?= e(strtolower($g['kode'])) ?>" data-kode="<?= e($g['kode']) ?>" data-k="<?= e(strtolower($g['kode'])) ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
      <?= ikon($g['kode']) ?><?= e($g['nama']) ?>
    </button>
  <?php endforeach; ?>
</div>

<?php foreach ($gaya as $i => $g): ?>
  <article class="panel <?= e(strtolower($g['kode'])) ?>" id="p-<?= e($g['kode']) ?>" role="tabpanel" <?= $i === 0 ? '' : 'hidden' ?>>
    <div class="bubble">
      <h2><?= e($g['nama']) ?></h2>
      <p><?= e($g['pengertian']) ?></p>
    </div>

    <h3 class="sec">Ciri-cirimu</h3>
    <div class="chips">
      <?php foreach ($g['ciri'] as $c): ?><span><?= e($c) ?></span><?php endforeach; ?>
    </div>

    <h3 class="sec">Coba cara ini</h3>
    <div class="notes">
      <?php foreach ($g['tips'] as $n => $t): ?><div class="note n<?= $n % 3 ?>"><?= e($t) ?></div><?php endforeach; ?>
    </div>

    <?php if ($g['bidang']): ?><p class="bd">Cocok untuk: <?= e($g['bidang']) ?></p><?php endif; ?>
  </article>
<?php endforeach; ?>

<p style="margin-top:18px"><a class="btn" href="<?= url('/kuis.php') ?>">Cari tahu gaya belajarmu</a></p>

<script>
  document.querySelectorAll('.tabs button').forEach(b => b.onclick = () => {
    document.querySelectorAll('.tabs button').forEach(x => x.setAttribute('aria-selected', x === b));
    document.querySelectorAll('.panel').forEach(p => p.hidden = p.id !== 'p-' + b.dataset.kode);
  });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>