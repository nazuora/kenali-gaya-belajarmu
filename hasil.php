<?php
/*
  File: hasil.php
  Keterangan: Menampilkan hasil kuis gaya belajar (Visual, Auditori,
  Kinestetik), persentase tiap gaya, ciri-ciri, tips belajar, serta
  menyimpan hasil ke tabel hasil_kuis.
*/

require __DIR__ . '/config/koneksi.php';

if (empty($_SESSION['user'])) {
    header('Location: ' . url('/masuk.php'));
    exit;
}

if (isset($_SESSION['user']['id'])) {
    $userId = (int)$_SESSION['user']['id'];
} else {
    $userId = 0;
}

$daftarGaya = [
    1 => [
        'nama' => 'Visual',
        'judul' => 'Sang Pengamat Visual (Visual)',
        'deskripsi' => 'Orang visual menyerap informasi dengan baik melalui gambar, warna, dan penglihatan.',
        'ciri' => [
            'Mudah memahami gambar dan diagram.',
            'Menyukai catatan yang rapi dan berwarna.',
            'Senang membaca materi.',
            'Mudah mengingat informasi visual.',
            'Lebih nyaman belajar melalui demonstrasi.'
        ],
        'tips' => [
            'Gunakan gambar dan diagram saat belajar.',
            'Buat catatan dengan warna berbeda.',
            'Gunakan mind map untuk merangkum materi.',
            'Pelajari materi melalui video pembelajaran.'
        ]
    ],
    2 => [
        'nama' => 'Auditori',
        'judul' => 'Sang Pendengar Aktif (Auditori)',
        'deskripsi' => 'Orang auditori menyerap informasi dengan baik melalui suara, penjelasan, dan percakapan.',
        'ciri' => [
            'Mudah memahami penjelasan lisan.',
            'Senang berdiskusi dengan orang lain.',
            'Mudah mengingat informasi yang didengar.',
            'Menyukai rekaman pembelajaran.',
            'Senang membaca materi dengan suara.'
        ],
        'tips' => [
            'Dengarkan rekaman materi pembelajaran.',
            'Diskusikan pelajaran bersama teman.',
            'Bacalah materi dengan suara.',
            'Jelaskan kembali materi yang sudah dipelajari.'
        ]
    ],
    3 => [
        'nama' => 'Kinestetik',
        'judul' => 'Sang Pembelajar Aktif (Kinestetik)',
        'deskripsi' => 'Orang kinestetik menyerap informasi paling baik lewat gerakan dan pengalaman langsung.',
        'ciri' => [
            'Senang belajar melalui praktik.',
            'Menyukai kegiatan langsung.',
            'Mudah memahami contoh nyata.',
            'Senang mencoba sesuatu secara mandiri.',
            'Lebih nyaman belajar sambil melakukan aktivitas.'
        ],
        'tips' => [
            'Perbanyak latihan soal dan praktik.',
            'Belajar sambil berjalan atau berdiri.',
            'Pakai benda nyata sebagai alat peraga.',
            'Selingi belajar dengan aktivitas ringan.'
        ]
    ]
];

$hasil = null;
$hasilPending = false;
$pesanTersimpan = false;
$pesanError = '';

// Proses simpan hasil sementara ke tabel hasil_kuis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();

    if (isset($_POST['aksi'])) {
        $aksi = $_POST['aksi'];
    } else {
        $aksi = '';
    }

    if ($aksi === 'simpan') {
        if (isset($_SESSION['hasil_kuis_pending'])) {
            $pending = $_SESSION['hasil_kuis_pending'];
        } else {
            $pending = null;
        }

        if (isset($pending['user_id'])) {
            $pendingUserId = (int)$pending['user_id'];
        } else {
            $pendingUserId = 0;
        }

        if (!is_array($pending) || $pendingUserId !== $userId) {
            header('Location: ' . url('/kuis.php'));
            exit;
        }

        $persenV = (int)$pending['persen_visual'];
        $persenA = (int)$pending['persen_auditori'];
        $persenK = (int)$pending['persen_kinestetik'];
        $gayaId = (int)$pending['gaya_utama_id'];

        if (
            $persenV < 0 || $persenV > 100 ||
            $persenA < 0 || $persenA > 100 ||
            $persenK < 0 || $persenK > 100 ||
            !isset($daftarGaya[$gayaId])
        ) {
            header('Location: ' . url('/kuis.php'));
            exit;
        }

        $stmt = $db->prepare(
            'INSERT INTO hasil_kuis (user_id, gaya_utama_id, persen_visual, persen_auditori, persen_kinestetik) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iiiii', $userId, $gayaId, $persenV, $persenA, $persenK);

        if ($stmt->execute()) {
            $idBaru = (int)$db->insert_id;
            unset($_SESSION['hasil_kuis_pending']);
            $stmt->close();

            header('Location: ' . url('/hasil.php?id=' . $idBaru . '&tersimpan=1'));
            exit;
        } else {
            $pesanError = 'Hasil kuis gagal disimpan. Silakan coba kembali.';
            $stmt->close();
        }
    }
}

// Mengambil hasil yang sudah tersimpan di database berdasarkan ID
if (isset($_GET['id'])) {
    $idHasil = (int)$_GET['id'];

    $stmt = $db->prepare('SELECT * FROM hasil_kuis WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $idHasil, $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $hasil = $result->fetch_assoc();
    $stmt->close();

    if (!$hasil) {
        header('Location: ' . url('/riwayat.php'));
        exit;
    }

    if (isset($_GET['tersimpan']) && $_GET['tersimpan'] == '1') {
        $pesanTersimpan = true;
    }
} else {
    if (isset($_SESSION['hasil_kuis_pending'])) {
        $pending = $_SESSION['hasil_kuis_pending'];
    } else {
        $pending = null;
    }

    if (isset($pending['user_id'])) {
        $pendingUserId = (int)$pending['user_id'];
    } else {
        $pendingUserId = 0;
    }

    if (!is_array($pending) || $pendingUserId !== $userId) {
        header('Location: ' . url('/kuis.php'));
        exit;
    }

    $hasil = $pending;
    $hasilPending = true;
}

$gayaId = (int)$hasil['gaya_utama_id'];
if (!isset($daftarGaya[$gayaId])) {
    $gayaId = 1;
}
$gaya = $daftarGaya[$gayaId];

$persenV = (int)$hasil['persen_visual'];
$persenA = (int)$hasil['persen_auditori'];
$persenK = (int)$hasil['persen_kinestetik'];

$judul = 'Hasil Kuis Gaya Belajar';
require __DIR__ . '/includes/header.php';
?>

<style>
    .hasil-kuis {
        max-width: 850px;
        margin: 30px auto;
        padding: 10px 20px;
    }

    .hasil-kuis .label-hasil {
        font-size: 18px;
        margin-bottom: 4px;
    }

    .hasil-kuis h1 {
        font-size: 36px;
        margin-top: 0;
        margin-bottom: 10px;
    }

    .hasil-kuis .deskripsi {
        line-height: 1.7;
        margin-bottom: 30px;
    }

    .persentase-item {
        margin-bottom: 22px;
    }

    .persentase-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        font-size: 18px;
        font-weight: 700;
    }

    .progress-track {
        width: 100%;
        height: 12px;
        background: var(--line);
        border-radius: 20px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: var(--text);
        border-radius: 20px;
    }

    .hasil-kuis h2 {
        margin-top: 32px;
        margin-bottom: 18px;
    }

    .hasil-kuis ul {
        padding-left: 28px;
        line-height: 1.8;
    }

    .status-hasil {
        margin-top: 20px;
        font-weight: bold;
    }

    .tombol-hasil {
        display: inline-block;
        margin-top: 12px;
    }

    .aksi-hasil {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
</style>

<div class="hasil-kuis">

    <p class="label-hasil">Gaya belajar utamamu</p>

    <h1><?= e($gaya['judul']) ?></h1>

    <p class="deskripsi">
        <?= e($gaya['deskripsi']) ?>
    </p>

    <div class="persentase-item">
        <div class="persentase-label">
            <span>Visual</span>
            <span><?= e($persenV) ?>%</span>
        </div>
        <div class="progress-track">
            <div class="progress-fill" style="width:<?= e($persenV) ?>%"></div>
        </div>
    </div>

    <div class="persentase-item">
        <div class="persentase-label">
            <span>Auditori</span>
            <span><?= e($persenA) ?>%</span>
        </div>
        <div class="progress-track">
            <div class="progress-fill" style="width:<?= e($persenA) ?>%"></div>
        </div>
    </div>

    <div class="persentase-item">
        <div class="persentase-label">
            <span>Kinestetik</span>
            <span><?= e($persenK) ?>%</span>
        </div>
        <div class="progress-track">
            <div class="progress-fill" style="width:<?= e($persenK) ?>%"></div>
        </div>
    </div>

    <h2>Ciri-ciri gaya belajar</h2>

    <ul>
        <?php foreach ($gaya['ciri'] as $ciri) { ?>
            <li><?= e($ciri) ?></li>
        <?php } ?>
    </ul>

    <h2>Tips belajar untukmu</h2>

    <ul>
        <?php foreach ($gaya['tips'] as $tip) { ?>
            <li><?= e($tip) ?></li>
        <?php } ?>
    </ul>

    <?php if ($pesanTersimpan) { ?>
        <p class="status-hasil" style="color:var(--ok)">
            Hasil tersimpan di Riwayat.
        </p>
    <?php } ?>

    <?php if ($pesanError !== '') { ?>
        <p class="status-hasil" style="color:var(--err)">
            <?= e($pesanError) ?>
        </p>
    <?php } ?>

    <div class="aksi-hasil">

        <?php if ($hasilPending) { ?>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="simpan">
                <button type="submit" class="btn tombol-hasil">
                    Simpan Hasil Quiz
                </button>
            </form>
        <?php } ?>

        <a href="<?= e(url('/kuis.php')) ?>" class="btn tombol-hasil">
            Ulangi kuis
        </a>

    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>