<?php
require __DIR__ . '/config/koneksi.php';

// Definisi warna disesuaikan persis dengan 3 titik logo header (Visual, Auditori, Kinestetik)
if (!defined('WARNA_GAYA')) {
    define('WARNA_GAYA', [
        'V' => ['#8b7cf6', '#ede9fe'], // Visual (Ungu / Biru lembut)
        'A' => ['#f97316', '#ffedd5'], // Auditori (Oranye / Peach)
        'K' => ['#10b981', '#d1fae5']  // Kinestetik (Hijau Toska / Mint)
    ]);
}

// Cek apakah pengguna sudah login atau belum
$login = !empty($_SESSION['user']);
$uid   = $login ? (int)$_SESSION['user']['id'] : 0;

// Proses jika tombol Hapus diklik
if ($login && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
    csrf_periksa();
    $id_riwayat = (int)($_POST['id'] ?? 0);
    
    // Hapus data dari database (pastikan milik user yang bersangkutan)
    $stmt = $db->prepare('DELETE FROM hasil_kuis WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id_riwayat, $uid);
    $stmt->execute();
    
    header('Location: ' . url('/riwayat.php'));
    exit;
}

// Ambil semua data riwayat kuis milik pengguna dari database
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

// Pengaturan ukuran untuk grafik SVG sederhana
$lebar_svg = 600;
$tinggi_svg = 220;
$padding = 34;

$judul = 'Riwayat Kuis';
require __DIR__ . '/includes/header.php';
?>

<div class="narrow" style="max-width:720px">
  <h1 class="s">Riwayat kuismu</h1>
  <p class="sub">Lihat perkembangan hasil gaya belajarmu dari waktu ke waktu.</p>

  <?php if (!$login): ?>
    <!-- Tampilan jika belum masuk -->
    <div class="empty">
      <strong>Masuk dulu untuk melihat riwayat.</strong>
      <p>Hasil kuis akan tersimpan dengan aman di akunmu.</p>
      <a class="btn" href="<?= e(url('/masuk.php')) ?>">Masuk Sekarang</a>
    </div>

  <?php elseif ($jumlah_data === 0): ?>
    <!-- Tampilan jika sudah masuk tapi belum pernah kuis -->
    <div class="empty">
      <strong>Belum ada hasil kuis.</strong>
      <p>Ayo kerjakan kuis pertamamu untuk mulai melacak gaya belajar.</p>
      <a class="btn" href="<?= e(url('/petunjuk.php')) ?>">Mulai Kuis</a>
    </div>

  <?php else: ?>
    <!-- Grafik Perkembangan (Muncul jika data minimal ada 2) -->
    <?php if ($jumlah_data >= 2): ?>
      <section class="card" aria-label="Grafik perkembangan">
        <h2 style="margin-top:0">Perkembangan persentase</h2>
        
        <svg viewBox="0 0 <?= $lebar_svg ?> <?= $tinggi_svg ?>" style="width:100%;height:auto;display:block">
          <!-- Garis latar belakang persentase (0%, 50%, 100%) -->
          <?php foreach ([0, 50, 100] as $titik_persen): ?>
            <?php 
              // Hitung posisi Y secara manual agar mudah dipahami
              $koefisien = $titik_persen / 100;
              $pos_y = $tinggi_svg - $padding - ($koefisien * ($tinggi_svg - (2 * $padding)));
            ?>
            <line x1="<?= $padding ?>" x2="<?= $lebar_svg - $padding ?>" y1="<?= $pos_y ?>" y2="<?= $pos_y ?>" stroke="var(--line)"/>
            <text x="4" y="<?= $pos_y + 4 ?>" font-size="12" fill="var(--muted)"><?= $titik_persen ?>%</text>
          <?php endforeach; ?>

          <?php 
            // Daftar warna untuk masing-masing garis gaya belajar
            $gaya_garis = [
              'persen_visual'     => ['Visual', WARNA_GAYA['V'][0]],
              'persen_auditori'   => ['Auditori', WARNA_GAYA['A'][0]],
              'persen_kinestetik' => ['Kinestetik', WARNA_GAYA['K'][0]],
            ];

            foreach ($gaya_garis as $kolom => [$nama_gaya, $warna_garis]):
              $titik_koordinat = [];
              foreach ($rows as $indeks => $data_baris) {
                // Hitung posisi X dan Y untuk setiap titik data
                if ($jumlah_data <= 1) {
                  $pos_x = $padding;
                } else {
                  $pos_x = $padding + $indeks * ($lebar_svg - (2 * $padding)) / ($jumlah_data - 1);
                }
                
                $nilai_persen = (int)$data_baris[$kolom];
                $pos_y = $tinggi_svg - $padding - (($nilai_persen / 100) * ($tinggi_svg - (2 * $padding)));
                
                $titik_koordinat[] = round($pos_x, 1) . ',' . round($pos_y, 1);
              }
          ?>
            <!-- Garis penghubung titik -->
            <polyline points="<?= e(implode(' ', $titik_koordinat)) ?>" fill="none" stroke="<?= $warna_garis ?>" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            
            <!-- Lingkaran titik di setiap hasil kuis -->
            <?php foreach ($rows as $indeks => $data_baris): ?>
              <?php 
                if ($jumlah_data <= 1) {
                  $pos_x = $padding;
                } else {
                  $pos_x = $padding + $indeks * ($lebar_svg - (2 * $padding)) / ($jumlah_data - 1);
                }
                $nilai_persen = (int)$data_baris[$kolom];
                $pos_y = $tinggi_svg - $padding - (($nilai_persen / 100) * ($tinggi_svg - (2 * $padding)));
              ?>
              <circle cx="<?= round($pos_x, 1) ?>" cy="<?= round($pos_y, 1) ?>" r="5" fill="<?= $warna_garis ?>"/>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </svg>

        <!-- Keterangan warna grafik -->
        <div class="legend">
          <span><i style="background:<?= WARNA_GAYA['V'][0] ?>"></i>Visual</span>
          <span><i style="background:<?= WARNA_GAYA['A'][0] ?>"></i>Auditori</span>
          <span><i style="background:<?= WARNA_GAYA['K'][0] ?>"></i>Kinestetik</span>
        </div>
      </section>
    <?php endif; ?>

    <!-- Daftar riwayat dalam bentuk kartu akordion (dibalik supaya yang terbaru di atas) -->
    <?php foreach (array_reverse($rows) as $r): ?>
      <div class="item">
        <button type="button" class="head" aria-expanded="false">
          <span class="pill" style="background:<?= WARNA_GAYA[$r['kode']][0] ?>"><?= e($r['gaya']) ?></span>
          <span class="date"><?= e(tanggal_id($r['dibuat_pada'])) ?></span>
          <span class="chev" aria-hidden="true">&#9662;</span>
        </button>
        
        <div class="body hidden">
          <!-- Baris persentase untuk masing-masing tipe -->
          <div class="row">
            <div class="lab"><span>Visual</span><span><?= (int)$r['persen_visual'] ?>%</span></div>
            <div class="track"><div style="width:<?= (int)$r['persen_visual'] ?>%;background:<?= WARNA_GAYA['V'][0] ?>"></div></div>
          </div>
          
          <div class="row">
            <div class="lab"><span>Auditori</span><span><?= (int)$r['persen_auditori'] ?>%</span></div>
            <div class="track"><div style="width:<?= (int)$r['persen_auditori'] ?>%;background:<?= WARNA_GAYA['A'][0] ?>"></div></div>
          </div>
          
          <div class="row">
            <div class="lab"><span>Kinestetik</span><span><?= (int)$r['persen_kinestetik'] ?>%</span></div>
            <div class="track"><div style="width:<?= (int)$r['persen_kinestetik'] ?>%;background:<?= WARNA_GAYA['K'][0] ?>"></div></div>
          </div>

          <!-- Tombol aksi (Lihat Detail dan Hapus) -->
          <div class="actions" style="justify-content:flex-start;align-items:center">
            <a class="btn ghost" style="padding:8px 18px" href="<?= e(url('/hasil.php?id=' . (int)$r['id'])) ?>">Lihat detail</a>
            
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button type="button" class="btn red del" style="padding:8px 18px">Hapus hasil ini</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
  // Script sederhana untuk membuka/tutup riwayat dan tombol konfirmasi hapus
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
        delBtn.dataset.sure = "1";
        delBtn.textContent = 'Klik lagi untuk konfirmasi hapus';
      }
    };
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>