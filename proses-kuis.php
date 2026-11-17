<?php
/* 
  File: proses-kuis.php
  Keterangan: File ini bertugas untuk memproses jawaban pilihan kuis yang 
  dikirimkan oleh pengguna, menghitung persentase skor masing-masing gaya belajar, 
  menentukan gaya belajar utama, lalu menyimpan hasilnya ke dalam database.
*/

// Mulai sesi jika belum aktif
session_start();
include "config/koneksi.php"; // Menggunakan include standar mahasiswa

// Cek apakah user sudah login atau belum
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Cek apakah halaman ini diakses via tombol submit (POST)
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: kuis.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$jawabanUser = isset($_POST['jawaban']) ? $_POST['jawaban'] : [];

// Validasi jika user belum menjawab sama sekali
if (empty($jawabanUser)) {
    $_SESSION['galat'] = 'Kamu harus menjawab setidaknya satu pertanyaan!';
    header('Location: kuis.php');
    exit;
}

// Inisialisasi variabel penghitung untuk setiap gaya belajar
// 1 = Visual, 2 = Auditoris, 3 = Kinestetik
$totalV = 0;
$totalA = 0;
$totalK = 0;

// Hitung jumlah jawaban dari pilihan user
foreach ($jawabanUser as $pertanyaanId => $gayaId) {
    if ($gayaId == 1) {
        $totalV = $totalV + 1;
    } elseif ($gayaId == 2) {
        $totalA = $totalA + 1;
    } elseif ($gayaId == 3) {
        $totalK = $totalK + 1;
    }
}

// Hitung total seluruh jawaban yang masuk
$totalJawab = count($jawabanUser);

// Hitung persentase masing-masing gaya belajar
$persenV = 0;
$persenA = 0;
$persenK = 0;

if ($totalJawab >  0) {
    $persenV = round(($totalV / $totalJawab) * 100);
    $persenA = round(($totalA / $totalJawab) * 100);
    $persenK = round(($totalK / $totalJawab) * 100);
}

// Tentukan gaya belajar utama berdasarkan nilai yang paling tinggi
$gayaUtamaId = 1; // Default diset ke Visual

if ($totalA > $totalV && $totalA > $totalK) {
    $gayaUtamaId = 2; // Auditoris lebih tinggi
} elseif ($totalK > $totalV && $totalK > $totalA) {
    $gayaUtamaId = 3; // Kinestetik lebih tinggi
}

// Simpan hasil perhitungan ke dalam database
$query = "INSERT INTO hasil_kuis (user_id, gaya_utama_id, persen_visual, persen_auditori, persen_kinestetik) VALUES (?, ?, ?, ?, ?)";
$stmt = $db->prepare($query);
$stmt->bind_param('iiiii', $userId, $gayaUtamaId, $persenV, $persenA, $persenK);

if ($stmt->execute()) {
    // Ambil ID dari data yang baru saja dimasukkan
    $hasilId = $stmt->insert_id;
    
    // Alihkan ke halaman hasil kuis
    header('Location: hasil.php?id=' . $hasilId);
    exit;
} else {
    // Jika gagal menyimpan ke database
    $_SESSION['galat'] = 'Gagal menyimpan hasil kuis, silakan coba lagi.';
    header('Location: kuis.php');
    exit;
}
?>