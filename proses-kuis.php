<?php
/*
  File: proses-kuis.php
  Keterangan:
  Memproses jawaban kuis, menghitung persentase setiap gaya belajar,
  menentukan gaya belajar utama, dan menyimpan hasil sementara
  ke dalam session sebelum pengguna memilih untuk menyimpannya.
*/

// Memulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memanggil koneksi database
require __DIR__ . '/config/koneksi.php';

// Memeriksa apakah pengguna sudah login
if (empty($_SESSION['user'])) {
    header('Location: ' . url('/masuk.php'));
    exit;
}

// Memastikan halaman diakses melalui POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('/kuis.php'));
    exit;
}

// Memeriksa keamanan formulir
csrf_periksa();

// Mengambil ID pengguna
$userId = (int)$_SESSION['user']['id'];

// Mengambil jawaban pengguna
$jawabanUser = $_POST['jawaban'] ?? [];

// Memastikan jawaban berbentuk array
if (!is_array($jawabanUser) || empty($jawabanUser)) {
    $_SESSION['galat'] = 'Kamu harus menjawab semua pertanyaan!';
    header('Location: ' . url('/kuis.php'));
    exit;
}

// Mengambil seluruh ID pertanyaan dari database
$query = $db->query('SELECT id FROM pertanyaan ORDER BY urutan, id');

$daftarId = [];

while ($row = $query->fetch_assoc()) {
    $daftarId[] = (int)$row['id'];
}

// Memeriksa apakah pertanyaan tersedia
$totalSoal = count($daftarId);

if ($totalSoal === 0) {
    $_SESSION['galat'] = 'Belum ada pertanyaan dalam kuis.';
    header('Location: ' . url('/kuis.php'));
    exit;
}

// Inisialisasi penghitung gaya belajar
// 1 = Visual, 2 = Auditori, 3 = Kinestetik
$hitung = [
    1 => 0,
    2 => 0,
    3 => 0
];

// Memeriksa dan menghitung seluruh jawaban
foreach ($daftarId as $idPertanyaan) {

    if (!isset($jawabanUser[$idPertanyaan])) {
        $_SESSION['galat'] = 'Kamu harus menjawab semua pertanyaan!';
        header('Location: ' . url('/kuis.php'));
        exit;
    }

    $gayaId = filter_var(
        $jawabanUser[$idPertanyaan],
        FILTER_VALIDATE_INT
    );

    if ($gayaId === false || !isset($hitung[$gayaId])) {
        $_SESSION['galat'] = 'Pilihan jawaban tidak valid.';
        header('Location: ' . url('/kuis.php'));
        exit;
    }

    $hitung[$gayaId]++;
}

// Menghitung persentase masing-masing gaya belajar
$persenV = (int)round(($hitung[1] / $totalSoal) * 100);
$persenA = (int)round(($hitung[2] / $totalSoal) * 100);
$persenK = (int)round(($hitung[3] / $totalSoal) * 100);

// Menentukan gaya belajar utama
$nilaiMaksimal = max($hitung);

$pilihanUtama = array_keys($hitung, $nilaiMaksimal);

// Jika terjadi nilai seri, gunakan ID terkecil
$gayaUtamaId = (int)$pilihanUtama[0];

// Menyimpan hasil sementara ke dalam session
// Hasil belum dimasukkan ke database
$_SESSION['hasil_kuis_pending'] = [
    'user_id' => $userId,
    'persen_visual' => $persenV,
    'persen_auditori' => $persenA,
    'persen_kinestetik' => $persenK,
    'gaya_utama_id' => $gayaUtamaId,
    'dibuat_pada' => time()
];

// Mengarahkan pengguna ke halaman hasil baru
header('Location: ' . url('/new/hasil1.php'));
exit;