<?php

require __DIR__ . '/config/koneksi.php'; //memanggil dan menjalankan file koneksi.php

wajib_login(); // menjalankan fungsi wajib_login

$sudah_baca = false;
       if (isset($_SESSION['sudah_baca_petunjuk']) && $_SESSION['sudah_baca_petunjuk'] === true) {
    $sudah_baca = true;
}

       if (!$sudah_baca) {
    header('Location: ' . url('/petunjuk.php'));
    exit;
} // memeriksa apakah pengguna udah ke halaman petunjuk dulu baru ke halaman quis


$galat = '';
    if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    $userId = (int)$_SESSION['user']['id'];
}   else {
    $userId = 0;
} // memeriksa pengguna udah login atau belom sebelum mulai kuis 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa(); 
    //Ketika pengguna menekan tombol submit maka akan dijalankan proses csrf yang memeriksa apakah form berasal dari web kita atau enggak
    if (isset($_POST['jawaban'])) {
    $jawabanUser = $_POST['jawaban'];
} else {
    $jawabanUser = [];
} //memeriksa apakah ada data jawaban yang dikirimkan, kalau ada dimasukkan ke jawabanUser 
}

if (!is_array($jawabanUser) || count($jawabanUser) === 0) {
        $galat = 'Kamu harus menjawab pertanyaan kuis terlebih dahulu.'; //kalau misal ada soal yang belom dijawab
    } else {
       
        $totalV = 0; //variabel untuk menghitung jumlah jawaban
        $totalA = 0;
        $totalK = 0;

        foreach ($jawabanUser as $pertanyaanId => $gayaId) { //foreach untuk mengambil jawaban dari database
            $gId = (int)$gayaId; //ngubah jadi bil.bulat
            if ($gId === 1) {
                $totalV++;
            } elseif ($gId === 2) {
                $totalA++;
            } elseif ($gId === 3) {
                $totalK++;
            }
        } // proses mengitung jawaban kalau nilai gaya id = 1 maka totalv bertambah dst
    }
        $totalJawab = $totalV + $totalA + $totalK; //penjumlahan akhir

$query_sql = 'SELECT p.id AS id_pertanyaan, p.teks AS teks_pertanyaan, j.gaya_id, j.teks AS teks_jawaban
              FROM pertanyaan p
              JOIN jawaban j ON j.pertanyaan_id = p.id
              ORDER BY p.urutan, p.id, j.gaya_id';
              /*Select untuk mengambil kolom mana saja yang ingin diambil dari database
                From mengambil data dari pertanyaan p dan join menggabungkan data dari tabel
                jawaban dan On berfungsi suapaya sistem tau ini list jawaban punya soal yang mana
                Order By untuk mengurutkan*/ 
                