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
    csrf_periksa(); // Cek token CSRF dari koneksi.php
    $id_hasil = (int)($_POST['id'] ?? 0);

    // Hapus hanya jika id hasil cocok dan milik user yang sedang login
    $stmt = mysqli_prepare($db, 'DELETE FROM hasil_kuis WHERE id = ? AND user_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $id_hasil, $id_user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Redirect ke riwayat.php agar tidak kirim ulang form saat di-refresh
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
  .wadah-riwayat {
    max-width: 720px;
    margin: 24px auto;
    padding: 0 16px;
  }
  .judul-halaman {
    margin-bottom: 4px;
  }
  .subjudul {
    color: #6B6080;
    margin-top: 0;
    margin-bottom: 20px;
  }
  .kartu-kosong, .kartu-riwayat {
    background: #FFFFFF;
    border: 1.5px solid #EDE3F3;
    border-radius: 16px;
    margin-bottom: 14px;
    overflow: hidden;
  }
  .kartu-kosong {
    padding: 28px 20px;
    text-align: center;
  }
  .tombol-buka {
    width: 100%;
    background: none;
    border: none;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    cursor: pointer;
    text-align: left;
    color: #3A2F52;
    font: inherit;
  }
  .label-gaya {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.9rem;
    color: #2D3142;
    margin-bottom: 4px;
  }
  .tanggal-riwayat {
    font-size: 0.85rem;
    color: #6B6080;
  }
  .isi-detail {
    padding: 16px 20px 20px;
    border-top: 1px solid #EDE3F3;
  }
  .tersembunyi {
    display: none;
  }
  .baris-nilai {
    margin-bottom: 12px;
  }
  .teks-nilai {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    margin-bottom: 4px;
  }
  .jalur-bar {
    height: 10px;
    background: #EDE3F3;
    border-radius: 999px;
    overflow: hidden;
  }
  .isi-bar {
    height: 100%;
  }
  .kotak-tips {
    margin-top: 14px;
    padding: 12px 14px;
    border-radius: 12px;
    background: #FFF9F3;
    border: 1px solid #EDE3F3;
  }
  .aksi-tombol {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 16px;
  }

  /* Mode gelap otomatis sesuai pengaturan perangkat */
  @media (prefers-color-scheme: dark) {
    .subjudul, .tanggal-riwayat {
      color: #BDB3D1;
    }
    .kartu-kosong, .kartu-riwayat {
      background: #2A2540;
      border-color: #3F3858;
    }
    .tombol-buka {
      color: #F3EEFA;
    }
    .label-gaya {
      color: #F3EEFA;
    }
    .isi-detail {
      border-top-color: #3F3858;
    }
    .jalur-bar {
      background: #3F3858;
    }
    .kotak-tips {
      background: #1E1A2B;
      border-color: #3F3858;
    }
  }
</style>

<div class="wadah-riwayat">
  <h1 class="judul-halaman">Riwayat Kuismu</h1>
  <p class="subjudul">Lihat hasil kuis yang pernah kamu kerjakan sebelumnya.</p>

  <?php if (!$sudah_login): ?>
    <div class="kartu-kosong">
      <strong>Kamu belum masuk ke akun.</strong>
      <p>Silakan masuk terlebih dahulu agar riwayat kuis pribadimu bisa ditampilkan.</p>
      <a class="btn" href="<?= e(url('/masuk.php')) ?>">Masuk</a>
    </div>

  <?php elseif (count($daftar_riwayat) === 0): ?>
    <div class="kartu-kosong">
      <strong>Belum ada riwayat kuis.</strong>
      <p>Akun ini belum pernah mengerjakan kuis. Yuk coba kerjakan kuis pertamamu!</p>
      <a class="btn" href="<?= e(url('/kuis.php')) ?>">Mulai Kuis</a>
    </div>

  <?php else: ?>
    <?php foreach ($daftar_riwayat as $item): ?>
      <?php
        // Tentukan warna sesuai kode gaya belajar (V = Visual, A = Auditori, K = Kinestetik)
        $kode = $item['kode'];
        if ($kode === 'A') {
            $warna_utama = '#FF9F7A';
            $warna_lembut = '#FFE9DE';
        } elseif ($kode === 'K') {
            $warna_utama = '#5CCB9C';
            $warna_lembut = '#DDF6EA';
        } else {
            $warna_utama = '#8B7CF0';
            $warna_lembut = '#ECE8FF';
        }
      ?>
      <div class="kartu-riwayat">
        <!-- Klik tombol ini untuk membuka/menutup detail hasil -->
        <button type="button" class="tombol-buka" aria-expanded="false">
          <div>
            <span class="label-gaya" style="background: <?= e($warna_lembut) ?>; border: 1.5px solid <?= e($warna_utama) ?>;">
              <?= e($item['gaya']) ?> - <?= e($item['julukan']) ?>
            </span>
            <div class="tanggal-riwayat"><?= e(tanggal_id($item['dibuat_pada'])) ?></div>
          </div>
          <span aria-hidden="true">&#9662;</span>
        </button>

        <!-- Isi detail hasil kuis -->
        <div class="isi-detail tersembunyi">
          <p><strong>Gaya Utama:</strong> <?= e($item['gaya']) ?> (<?= e($item['julukan']) ?>)</p>
          <p><?= e($item['pengertian']) ?></p>

          <div class="baris-nilai">
            <div class="teks-nilai">
              <span>Visual</span>
              <span><?= (int)$item['persen_visual'] ?>%</span>
            </div>
            <div class="jalur-bar">
              <div class="isi-bar" style="width: <?= (int)$item['persen_visual'] ?>%; background: #8B7CF0;"></div>
            </div>
          </div>

          <div class="baris-nilai">
            <div class="teks-nilai">
              <span>Auditori</span>
              <span><?= (int)$item['persen_auditori'] ?>%</span>
            </div>
            <div class="jalur-bar">
              <div class="isi-bar" style="width: <?= (int)$item['persen_auditori'] ?>%; background: #FF9F7A;"></div>
            </div>
          </div>

          <div class="baris-nilai">
            <div class="teks-nilai">
              <span>Kinestetik</span>
              <span><?= (int)$item['persen_kinestetik'] ?>%</span>
            </div>
            <div class="jalur-bar">
              <div class="isi-bar" style="width: <?= (int)$item['persen_kinestetik'] ?>%; background: #5CCB9C;"></div>
            </div>
          </div>

          <div class="kotak-tips">
            <strong>Tips Belajar:</strong>
            <p style="margin: 6px 0 0;"><?= nl2br(e($item['tips'])) ?></p>
          </div>

          <div class="aksi-tombol">
            <a class="btn ghost" href="<?= e(url('/hasil.php?id=' . (int)$item['id'])) ?>">Lihat Halaman Hasil</a>

            <form method="post" style="margin: 0;">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
              <button type="button" class="btn red tombol-hapus">Hapus Hasil</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
  // Buka atau tutup detail hasil saat tombol panah/header diklik
  document.querySelectorAll('.tombol-buka').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
      const kartu = tombol.closest('.kartu-riwayat');
      const detail = kartu.querySelector('.isi-detail');
      const sedangBuka = !detail.classList.contains('tersembunyi');

      detail.classList.toggle('tersembunyi', sedangBuka);
      tombol.setAttribute('aria-expanded', String(!sedangBuka));
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
