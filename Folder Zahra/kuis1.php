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

//MENGOLAH JAWABAN PENGGUNA
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
    
        $totalJawab = $totalV + $totalA + $totalK; //penjumlahan akhir

if ($totalJawab !== 15) {
            $galat = 'Pilihan jawaban tidak valid. Silakan coba lagi.'; //memeriksa apakah semua jawaban memang dari opsi yang tersedia
        } else {
            // Hitung persentase tiap gaya belajar agar totalnya pas 100% untuk ditampilkan di hasil
            $persenV = (int)round(($totalV / $totalJawab) * 100);
            $persenA = (int)round(($totalA / $totalJawab) * 100);
            $persenK = 100 - $persenV - $persenA;
         

         $gayaUtamaId = 1; //menentukan gaya belajar utama 
            if ($totalA > $totalV && $totalA >= $totalK) {
                $gayaUtamaId = 2;
            } elseif ($totalK > $totalV && $totalK > $totalA) {
                $gayaUtamaId = 3;
            }

//memasukkan data ke database tabel hasil_kuis    
    $sql = 'INSERT INTO hasil_kuis (user_id, gaya_utama_id, persen_visual, persen_auditori, persen_kinestetik) VALUES (?, ?, ?, ?, ?)';
            $stmt = $db->prepare($sql); //menyiapkan sql dan membantu mencegah sql injection
            $stmt->bind_param('iiiii', $userId, $gayaUtamaId, $persenV, $persenA, $persenK); //ngisi datanya

            if ($stmt->execute()) { //kalau perintah stmt terjalankan 
                $hasilId = (int)$stmt->insert_id; // mengambil id dari hasil yang baru saja dijalankan
                header('Location: ' . url('/hasil.php?id=' . $hasilId)); //terus diarahkan ke laman hasil kuis esuai id si hasilnya
                exit;
            } else {
                $galat = 'Gagal menyimpan hasil kuis. Silakan coba lagi.';
            }
        }
    }
}

//PENGAMBILAN DATA
$query_sql = 'SELECT p.id AS id_pertanyaan, p.teks AS teks_pertanyaan, j.gaya_id, j.teks AS teks_jawaban
              FROM pertanyaan p
              JOIN jawaban j ON j.pertanyaan_id = p.id
              ORDER BY p.urutan, p.id, j.gaya_id';
              /*Select untuk mengambil kolom mana saja yang ingin diambil dari database
                From mengambil data dari pertanyaan p dan join menggabungkan data dari tabel
                jawaban dan On berfungsi supaya sistem tau ini list jawaban punya soal yang mana
                Order By untuk mengurutkan*/ 
                
//PENGELOMPOKKAN SOAL DAN JAWABANnya
$daftar_soal = []; //buat array kosong tempat seluruh data pertanyyan dan jawaban
$hasil_query = $db->query($query_sql); //ngambil data dari database

foreach ($hasil_query as $baris) { //perulangan untuk menyimpan hasil query sebagai baris
    $id_p = (int)$baris['id_pertanyaan']; //mengambil id dari baris pertanyaan yg sdg diproses

    if (!isset($daftar_soal[$id_p])) { //memeriksa apakah soal dgn id ini udah dikelompokkan atau belom
        $daftar_soal[$id_p] = [];
        $daftar_soal[$id_p]['id'] = $id_p;
        $daftar_soal[$id_p]['teks'] = $baris['teks_pertanyaan'];
        $daftar_soal[$id_p]['jawaban'] = [];
    }

    $daftar_soal[$id_p]['jawaban'][] = [ //memasukkan daftar jawaban
        'gaya' => (int)$baris['gaya_id'], //ngambil id gaya
        'teks' => $baris['teks_jawaban'],//ngambil tulisannya
    ];
}

$daftar_soal = array_values($daftar_soal); 
/*Ngubah indeks array yang sebelumnya pake id pertanyaan jadi mulai dari 0
Supaya lebih mudah ketika data dikirim ke JavaScript dan diakses menggunakan indeks array*/