<?php
/*
  File: kuis.php
  Keterangan: Halaman pengerjaan kuis gaya belajar (Visual, Auditori,
  Kinestetik) sekaligus menghitung skor, menyimpan hasil ke database,
  dan mengarahkan pengguna ke halaman hasil.
*/

require __DIR__ . '/config/koneksi.php';

// Wajib login dan sudah membaca halaman petunjuk sebelum mengerjakan kuis
wajib_login();

$sudah_baca = false;
if (isset($_SESSION['sudah_baca_petunjuk']) && $_SESSION['sudah_baca_petunjuk'] === true) {
    $sudah_baca = true;
}

if (!$sudah_baca) {
    header('Location: ' . url('/petunjuk.php'));
    exit;
}

$galat = '';

if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    $userId = (int)$_SESSION['user']['id'];
} else {
    $userId = 0;
}

// Proses pengiriman jawaban kuis (POST) dan simpan ke tabel hasil_kuis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();

    if (isset($_POST['jawaban'])) {
    $jawabanUser = $_POST['jawaban'];
} else {
    $jawabanUser = [];
}

    if (!is_array($jawabanUser) || count($jawabanUser) === 0) {
        $galat = 'Kamu harus menjawab pertanyaan kuis terlebih dahulu.';
    } else {
        // 1 = Visual, 2 = Auditori, 3 = Kinestetik
        $totalV = 0;
        $totalA = 0;
        $totalK = 0;

        foreach ($jawabanUser as $pertanyaanId => $gayaId) {
            $gId = (int)$gayaId;
            if ($gId === 1) {
                $totalV++;
            } elseif ($gId === 2) {
                $totalA++;
            } elseif ($gId === 3) {
                $totalK++;
            }
        }

        $totalJawab = $totalV + $totalA + $totalK;

        if ($totalJawab === 0) {
            $galat = 'Pilihan jawaban tidak valid. Silakan coba lagi.';
        } else {
            // Hitung persentase tiap gaya belajar agar totalnya pas 100%
            $persenV = (int)round(($totalV / $totalJawab) * 100);
            $persenA = (int)round(($totalA / $totalJawab) * 100);
            $persenK = 100 - $persenV - $persenA;
            if ($persenK < 0) {
                $persenK = 0;
            }

            // Tentukan gaya belajar utama berdasarkan jumlah jawaban tertinggi
            $gayaUtamaId = 1;
            if ($totalA > $totalV && $totalA >= $totalK) {
                $gayaUtamaId = 2;
            } elseif ($totalK > $totalV && $totalK > $totalA) {
                $gayaUtamaId = 3;
            }

            $sql = 'INSERT INTO hasil_kuis (user_id, gaya_utama_id, persen_visual, persen_auditori, persen_kinestetik) VALUES (?, ?, ?, ?, ?)';
            $stmt = $db->prepare($sql);
            $stmt->bind_param('iiiii', $userId, $gayaUtamaId, $persenV, $persenA, $persenK);

            if ($stmt->execute()) {
                $hasilId = (int)$stmt->insert_id;
                header('Location: ' . url('/hasil.php?id=' . $hasilId));
                exit;
            } else {
                $galat = 'Gagal menyimpan hasil kuis. Silakan coba lagi.';
            }
        }
    }
}

// Ambil daftar pertanyaan beserta pilihan jawabannya dari database
$query_sql = 'SELECT p.id AS id_pertanyaan, p.teks AS teks_pertanyaan, j.gaya_id, j.teks AS teks_jawaban
              FROM pertanyaan p
              JOIN jawaban j ON j.pertanyaan_id = p.id
              ORDER BY p.urutan, p.id, j.gaya_id';

$daftar_soal = [];
$hasil_query = $db->query($query_sql);

foreach ($hasil_query as $baris) {
    $id_p = (int)$baris['id_pertanyaan'];

    if (!isset($daftar_soal[$id_p])) {
        $daftar_soal[$id_p] = [];
        $daftar_soal[$id_p]['id'] = $id_p;
        $daftar_soal[$id_p]['teks'] = $baris['teks_pertanyaan'];
        $daftar_soal[$id_p]['jawaban'] = [];
    }

    $daftar_soal[$id_p]['jawaban'][] = [
        'gaya' => (int)$baris['gaya_id'],
        'teks' => $baris['teks_jawaban'],
    ];
}

$daftar_soal = array_values($daftar_soal);

$judul = 'Kuis Gaya Belajar';
require __DIR__ . '/includes/header.php';
?>

<div class="narrow">
<?php if ($galat !== ''): ?>
  <div class="err" style="margin-bottom: 16px;"><?= e($galat) ?></div>
<?php endif; ?>

<?php if (empty($daftar_soal)): ?>
  <div class="empty">
    <strong>Belum ada pertanyaan.</strong>
    <p>Admin perlu menambahkan pertanyaan terlebih dahulu.</p>
  </div>
<?php else: ?>
  <div class="qtop">
    <span>Kuis</span>
    <span id="nomor-soal"></span>
  </div>

  <div class="bar" role="progressbar" aria-label="Kemajuan kuis" aria-valuemin="0" aria-valuemax="100" id="wadah-progress">
    <div id="bar-isi"></div>
  </div>

  <section class="card" style="margin-top: 18px;">
    <h1 class="s" id="teks-pertanyaan"></h1>

    <div id="pilihan-jawaban"></div>

    <div class="actions">
      <button type="button" class="btn ghost" id="tombol-kembali">Kembali</button>
      <button type="button" class="btn" id="tombol-lanjut" disabled>Lanjut</button>
    </div>
  </section>

  <!-- Form tersembunyi untuk mengirim seluruh jawaban ke kuis.php -->
  <form id="form-kirim-jawaban" method="post" action="<?= e(url('/kuis.php')) ?>" hidden>
    <?= csrf_field() ?>
  </form>

  <script>
    const dataSoal = <?= json_encode($daftar_soal, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    let indeksAktif = 0;
    const pilihanUser = Array(dataSoal.length).fill(null);

    function ambilElemen(id) {
      return document.getElementById(id);
    }

    // Render pertanyaan aktif, tombol pilihan, dan status navigasi
    function tampilkanSoal() {
      ambilElemen('teks-pertanyaan').textContent = dataSoal[indeksAktif].teks;
      ambilElemen('nomor-soal').textContent = (indeksAktif + 1) + ' dari ' + dataSoal.length;

      const persentaseProgress = (indeksAktif / dataSoal.length) * 100;
      ambilElemen('bar-isi').style.width = persentaseProgress + '%';
      ambilElemen('wadah-progress').setAttribute('aria-valuenow', Math.round(persentaseProgress));

      const wadahPilihan = ambilElemen('pilihan-jawaban');
      wadahPilihan.innerHTML = '';

      dataSoal[indeksAktif].jawaban.forEach(function(itemJawaban) {
        const tombolPilihan = document.createElement('button');
        tombolPilihan.type = 'button';
        tombolPilihan.className = 'opt';
        tombolPilihan.textContent = itemJawaban.teks;

        const apakahDipilih = (pilihanUser[indeksAktif] === itemJawaban.gaya);
        tombolPilihan.setAttribute('aria-pressed', apakahDipilih);

        tombolPilihan.onclick = function() {
          pilihanUser[indeksAktif] = itemJawaban.gaya;
          tampilkanSoal();
        };

        wadahPilihan.appendChild(tombolPilihan);
      });

      if (indeksAktif > 0) {
        ambilElemen('tombol-kembali').style.visibility = 'visible';
      } else {
        ambilElemen('tombol-kembali').style.visibility = 'hidden';
      }

      ambilElemen('tombol-lanjut').disabled = (pilihanUser[indeksAktif] === null);

      if (indeksAktif === dataSoal.length - 1) {
        ambilElemen('tombol-lanjut').textContent = 'Lihat hasil';
      } else {
        ambilElemen('tombol-lanjut').textContent = 'Lanjut';
      }
    }

    ambilElemen('tombol-kembali').onclick = function() {
      if (indeksAktif > 0) {
        indeksAktif--;
        tampilkanSoal();
      }
    };

    // Pindah soal berikutnya atau kirim semua jawaban saat di soal terakhir
    ambilElemen('tombol-lanjut').onclick = function() {
      if (indeksAktif < dataSoal.length - 1) {
        indeksAktif++;
        tampilkanSoal();
        return;
      }

      const formKirim = ambilElemen('form-kirim-jawaban');
      formKirim.innerHTML = '<?= csrf_field() ?>';

      dataSoal.forEach(function(soal, nomorUrut) {
        const inputTersembunyi = document.createElement('input');
        inputTersembunyi.type = 'hidden';
        inputTersembunyi.name = 'jawaban[' + soal.id + ']';
        inputTersembunyi.value = pilihanUser[nomorUrut];
        formKirim.appendChild(inputTersembunyi);
      });

      formKirim.submit();
    };

    tampilkanSoal();
  </script>
<?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>