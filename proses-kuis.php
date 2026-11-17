<?php
// proses-kuis.php
require __DIR__ . '/config/koneksi.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('/kuis.php'));
    exit;
}

csrf_periksa();

$userId = $_SESSION['user']['id'];
$jawabanUser = $_POST['jawaban'] ?? []; // Format: [pertanyaan_id => gaya_id]

if (empty($jawabanUser)) {
    $_SESSION['galat'] = 'Kamu harus menjawab setidaknya satu pertanyaan.';
    header('Location: ' . url('/kuis.php'));
    exit;
}

// Hitung total pilihan per gaya belajar (V=1, A=2, K=3)
$totalV = 0;
$totalA = 0;
$totalK = 0;
$totalJawab = count($jawabanUser);

foreach ($jawabanUser as $pId => $gayaId) {
    $gId = (int)$gayaId;
    if ($gId === 1) $totalV++;
    elseif ($gId === 2) $totalA++;
    elseif ($gId === 3) $totalK++;
}

// Hitung persentase
$persenV = $totalJawab > 0 ? round(($totalV / $totalJawab) * 100) : 0;
$persenA = $totalJawab > 0 ? round(($totalA / $totalJawab) * 100) : 0;
$persenK = $totalJawab > 0 ? round(($totalK / $totalJawab) * 100) : 0;

// Tentukan gaya utama (yang persentasenya paling tinggi)
$gayaUtamaId = 1; // Default Visual
$maxSkor = max($totalV, $totalA, $totalK);
if ($maxSkor === $totalA) {
    $gayaUtamaId = 2; // Auditoris
} elseif ($maxSkor === $totalK) {
    $gayaUtamaId = 3; // Kinestetik
}

// Simpan ke database tabel hasil_kuis (menggunakan NOW() langsung tanpa menyertakan kolom tanggal secara manual)
$stmt = $db->prepare('
    INSERT INTO hasil_kuis (user_id, gaya_utama_id, persen_visual, persen_auditori, persen_kinestetik) 
    VALUES (?, ?, ?, ?, ?)
');
$stmt->bind_param('iiiii', $userId, $gayaUtamaId, $persenV, $persenA, $persenK);

if ($stmt->execute()) {
    $hasilId = $db->insert_id;
    // Arahkan ke halaman hasil kuis dengan membawa ID hasil
    header('Location: ' . url('/hasil.php?id=' . $hasilId));
    exit;
} else {
    $_SESSION['galat'] = 'Gagal menyimpan hasil kuis: ' . $db->error;
    header('Location: ' . url('/kuis.php'));
    exit;
}