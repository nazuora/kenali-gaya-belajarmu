<?php
/* 
  File: jenis.php
  Keterangan: Halaman untuk menampilkan informasi jenis gaya belajar 
  (Visual, Auditori, Kinestetik) beserta ciri-ciri dan tips belajarnya 
  dalam bentuk tab interaktif yang ramah pengguna.
*/

// Memanggil file koneksi database (jika diperlukan) dan fungsi ikon
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/includes/ikon.php';

// Data konten halaman Jenis Gaya Belajar dibuat manual di dalam array agar mudah dibaca
$daftar_gaya = [
    [
        'kode' => 'V',
        'nama' => 'Visual',
        'pengertian' => 'Orang visual menyerap informasi paling baik lewat penglihatan: gambar, warna, diagram, dan tata letak yang rapi.',
        'ciri' => [
            'Mudah mengingat wajah, bentuk, dan warna',
            'Suka membuat catatan berwarna dan bergambar',
            'Lebih paham saat melihat peta, grafik, atau slide',
            'Sulit fokus kalau hanya mendengar ceramah panjang'
        ],
        'tips' => [
            'Buat mind map dan diagram alur',
            'Pakai stabilo dengan kode warna',
            'Tonton video penjelasan',
            'Ubah angka dan teks menjadi tabel atau grafik'
        ],
        'bidang' => 'Desainer, arsitek, dan pekerjaan yang banyak memakai visual.'
    ],
    [
        'kode' => 'A',
        'nama' => 'Auditori',
        'pengertian' => 'Orang auditori menyerap informasi paling baik lewat pendengaran: penjelasan lisan, diskusi, dan irama.',
        'ciri' => [
            'Mudah mengingat percakapan dan penjelasan',
            'Suka berdiskusi dan bertanya',
            'Sering membaca sambil bersuara',
            'Mudah terganggu oleh suara bising'
        ],
        'tips' => [
            'Jelaskan ulang materi dengan suara',
            'Rekam rangkuman, lalu dengarkan ulang',
            'Belajar dalam kelompok diskusi',
            'Dengarkan podcast atau materi audio'
        ],
        'bidang' => 'Pembicara, pengajar, dan pekerjaan yang banyak berkomunikasi lisan.'
    ],
    [
        'kode' => 'K',
        'nama' => 'Kinestetik',
        'pengertian' => 'Orang kinestetik menyerap informasi paling baik lewat gerakan dan pengalaman langsung.',
        'ciri' => [
            'Sulit duduk diam dalam waktu lama',
            'Lebih paham setelah mencoba sendiri',
            'Suka praktik, eksperimen, dan simulasi',
            'Sering bergerak atau memegang benda saat berpikir'
        ],
        'tips' => [
            'Perbanyak latihan soal dan praktik',
            'Belajar sambil berjalan atau berdiri',
            'Pakai benda nyata sebagai alat peraga',
            'Istirahat singkat untuk bergerak tiap 25 menit'
        ],
        'bidang' => 'Teknisi, atlet, dan pekerjaan yang banyak praktik lapangan.'
    ]
];

$judul = 'Jenis Gaya Belajar';
require __DIR__ . '/includes/header.php';
?>

<!-- Bagian Judul Halaman -->
<h1 class="s">Jenis gaya belajar</h1>
<p class="sub">Gaya belajar adalah cara yang paling nyaman bagi seseorang untuk menerima dan mengingat informasi. Pilih salah satu untuk mengenalnya.</p>

<!-- Bagian Tombol Tab Pilihan Gaya Belajar -->
<div class="tabs" role="tablist">
  <?php foreach ($daftar_gaya as $i => $g): ?>
    <?php 
        $kodeKecil = strtolower($g['kode']);
        // Menentukan apakah tab ini aktif di awal (untuk tab pertama)
        $statusAktif = ($i === 0) ? 'true' : 'false';
    ?>
    <button type="button" role="tab" class="<?= $kodeKecil ?>" data-kode="<?= $g['kode'] ?>" data-k="<?= $kodeKecil ?>" aria-selected="<?= $statusAktif ?>">
      <?= ikon($g['kode']) ?><?= htmlspecialchars($g['nama']) ?>
    </button>
  <?php endforeach; ?>
</div>

<!-- Bagian Panel Konten untuk Setiap Jenis Gaya Belajar -->
<?php foreach ($daftar_gaya as $i => $g): ?>
  <?php 
      $kodeKecil = strtolower($g['kode']);
      // Menyembunyikan panel selain panel pertama secara default
      $atributHidden = ($i === 0) ? '' : 'hidden';
  ?>
  <article class="panel <?= $kodeKecil ?>" id="p-<?= $g['kode'] ?>" role="tabpanel" <?= $atributHidden ?>>
    
    <div class="bubble">
      <h2><?= htmlspecialchars($g['nama']) ?></h2>
      <p><?= htmlspecialchars($g['pengertian']) ?></p>
    </div>

    <h3 class="sec">Ciri-cirimu</h3>
    <div class="chips">
      <?php foreach ($g['ciri'] as $ciri_item): ?>
        <span><?= htmlspecialchars($ciri_item) ?></span>
      <?php endforeach; ?>
    </div>

    <h3 class="sec">Coba cara ini</h3>
    <div class="notes">
      <?php foreach ($g['tips'] as $index => $tips_item): ?>
        <div class="note n<?= $index % 3 ?>"><?= htmlspecialchars($tips_item) ?></div>
      <?php endforeach; ?>
    </div>

    <?php if (!empty($g['bidang'])): ?>
      <p class="bd">Cocok untuk: <?= htmlspecialchars($g['bidang']) ?></p>
    <?php endif; ?>

  </article>
<?php endforeach; ?>

<!-- Tombol Navigasi ke Halaman Petunjuk -->
<p style="margin-top:18px">
    <a class="btn" href="<?= url('/petunjuk.php') ?>">Cari tahu gaya belajarmu</a>
</p>

<!-- Skrip JavaScript Sederhana untuk Mengatur Perpindahan Tab -->
<script>
  // Mengambil semua tombol tab yang ada
  const semuaTombolTab = document.querySelectorAll('.tabs button');
  const semuaPanelKonten = document.querySelectorAll('.panel');

  semuaTombolTab.forEach(function(tombol) {
    tombol.onclick = function() {
      
      // 1. Ubah status aria-selected pada semua tombol menjadi false, lalu jadikan true pada yang diklik
      semuaTombolTab.forEach(function(t) {
        t.setAttribute('aria-selected', 'false');
      });
      tombol.setAttribute('aria-selected', 'true');

      // 2. Sembunyikan semua panel, lalu tampilkan panel yang kodenya cocok dengan tombol
      const kodePilihan = tombol.getAttribute('data-kode');
      semuaPanelKonten.forEach(function(panel) {
        if (panel.id === 'p-' + kodePilihan) {
          panel.removeAttribute('hidden');
        } else {
          panel.setAttribute('hidden', 'true');
        }
      });

    };
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>