<?php
/*
  File: hasil.php
  Keterangan:
  Menampilkan hasil kuis gaya belajar dan menyediakan
  fitur penyimpanan hasil ke riwayat.
*/

require __DIR__ . '/../config/koneksi.php';

// Memastikan pengguna sudah login
if (empty($_SESSION['user'])) {
    header('Location: ' . url('/masuk.php'));
    exit;
}

$userId = (int) $_SESSION['user']['id'];

// Data gaya belajar
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

// Variabel hasil
$hasil = null;
$hasilPending = false;
$pesanTersimpan = false;
$pesanError = '';

// PROSES SIMPAN HASIL
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_periksa();

    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {

        $pending = $_SESSION['hasil_kuis_pending'] ?? null;

        if (
            !is_array($pending) ||
            (int)($pending['user_id'] ?? 0) !== $userId
        ) {
            header('Location: ' . url('/kuis.php'));
            exit;
        }

        $persenV = (int)$pending['persen_visual'];
        $persenA = (int)$pending['persen_auditori'];
        $persenK = (int)$pending['persen_kinestetik'];
        $gayaId = (int)$pending['gaya_utama_id'];

        // Validasi hasil
        if (
            $persenV < 0 || $persenV > 100 ||
            $persenA < 0 || $persenA > 100 ||
            $persenK < 0 || $persenK > 100 ||
            !isset($daftarGaya[$gayaId])
        ) {
            header('Location: ' . url('/kuis.php'));
            exit;
        }

        // Simpan ke database
        $stmt = $db->prepare(
            "INSERT INTO hasil_kuis
            (user_id, gaya_utama_id, persen_visual,
             persen_auditori, persen_kinestetik)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'iiiii',
            $userId,
            $gayaId,
            $persenV,
            $persenA,
            $persenK
        );

        if ($stmt->execute()) {

            $idBaru = $db->insert_id;

            // Hapus hasil sementara
            unset($_SESSION['hasil_kuis_pending']);

            header(
                'Location: ' .
                url('/new/hasil1.php?id=' . $idBaru . '&tersimpan=1')
            );
            exit;

        } else {
            $pesanError = 'Hasil kuis gagal disimpan. Silakan coba kembali.';
        }

        $stmt->close();
    }
}

// MENGAMBIL HASIL YANG SUDAH TERSIMPAN
if (isset($_GET['id'])) {

    $idHasil = (int)$_GET['id'];

    $stmt = $db->prepare(
        "SELECT *
         FROM hasil_kuis
         WHERE id = ? AND user_id = ?
         LIMIT 1"
    );

    $stmt->bind_param('ii', $idHasil, $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $hasil = $result->fetch_assoc();

    $stmt->close();

    if (!$hasil) {
        header('Location: ' . url('/riwayat.php'));
        exit;
    }

    $pesanTersimpan = isset($_GET['tersimpan']);

// MENGAMBIL HASIL SEMENTARA
} else {

    $pending = $_SESSION['hasil_kuis_pending'] ?? null;

    if (
        !is_array($pending) ||
        (int)($pending['user_id'] ?? 0) !== $userId
    ) {
        header('Location: ' . url('/kuis.php'));
        exit;
    }

    $hasil = $pending;
    $hasilPending = true;
}

// Menentukan gaya belajar utama
$gayaId = (int)$hasil['gaya_utama_id'];
$gaya = $daftarGaya[$gayaId];

$persenV = (int)$hasil['persen_visual'];
$persenA = (int)$hasil['persen_auditori'];
$persenK = (int)$hasil['persen_kinestetik'];

require __DIR__ . '/../includes/header.php';
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
        font-weight: 500;
    }

    .progress-track {
        width: 100%;
        height: 12px;
        background: #414141;
        border-radius: 20px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: #f5f5f5;
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
            <span><?= $persenV ?>%</span>
        </div>
        <div class="progress-track">
            <div class="progress-fill"
                 style="width:<?= $persenV ?>%">
            </div>
        </div>
    </div>

    <div class="persentase-item">
        <div class="persentase-label">
            <span>Auditori</span>
            <span><?= $persenA ?>%</span>
        </div>
        <div class="progress-track">
            <div class="progress-fill"
                 style="width:<?= $persenA ?>%">
            </div>
        </div>
    </div>

    <div class="persentase-item">
        <div class="persentase-label">
            <span>Kinestetik</span>
            <span><?= $persenK ?>%</span>
        </div>
        <div class="progress-track">
            <div class="progress-fill"
                 style="width:<?= $persenK ?>%">
            </div>
        </div>
    </div>

    <h2>Ciri-ciri gaya belajar</h2>

    <ul>
        <?php foreach ($gaya['ciri'] as $ciri): ?>
            <li><?= e($ciri) ?></li>
        <?php endforeach; ?>
    </ul>

    <h2>Tips belajar untukmu</h2>

    <ul>
        <?php foreach ($gaya['tips'] as $tip): ?>
            <li><?= e($tip) ?></li>
        <?php endforeach; ?>
    </ul>

    <?php if ($pesanTersimpan): ?>

        <p class="status-hasil" style="color:#78d6aa">
            Hasil tersimpan di Riwayat.
        </p>

    <?php endif; ?>

    <?php if ($pesanError !== ''): ?>

        <p class="status-hasil">
            <?= e($pesanError) ?>
        </p>

    <?php endif; ?>

    <div class="aksi-hasil">

    <?php if ($hasilPending): ?>

        <form method="POST">
            <?= csrf_field() ?>

            <input type="hidden" name="aksi" value="simpan">

            <button type="submit" class="btn tombol-hasil">
                Simpan Hasil Quiz
            </button>
        </form>

    <?php endif; ?>

    <a href="<?= e(url('/kuis.php')) ?>" class="btn tombol-hasil">
        Ulangi kuis
    </a>

</div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>