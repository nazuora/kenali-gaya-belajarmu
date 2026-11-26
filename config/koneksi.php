<?php
/*
  File: config/koneksi.php
  Keterangan: Menghubungkan aplikasi ke database MySQL/MariaDB, mengatur
  base URL dan sesi, serta menyediakan fungsi bantu keamanan dan format data.
*/

// Koneksi ke database MariaDB/MySQL proyek Kenali Gaya Belajarmu
$host     = 'localhost';
$username = 'root';
$password = '';
$database = 'kenali_gaya_belajar';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = mysqli_connect($host, $username, $password, $database);

    if ($db) {
        mysqli_set_charset($db, 'utf8mb4');
    }
} catch (mysqli_sql_exception $ex) {
    http_response_code(500);
    exit('Koneksi ke database gagal: ' . $ex->getMessage());
}

// Base URL dan helper pembuat tautan halaman
$akarProyek = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));

if (isset($_SERVER['DOCUMENT_ROOT'])) {
    $dr = $_SERVER['DOCUMENT_ROOT'];
} else {
    $dr = '';
}

if ($dr !== '') {
    $akarServer = str_replace('\\', '/', rtrim((string)realpath($dr), '/\\'));
} else {
    $akarServer = '';
}

if ($akarServer !== '' && stripos($akarProyek, $akarServer) === 0) {
    $nilaiBaseUrl = (string)substr($akarProyek, strlen($akarServer));
} else {
    $nilaiBaseUrl = '';
}

define('BASE_URL', $nilaiBaseUrl);

function url($jalur) {
    return BASE_URL . $jalur;
}

// Mulai sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Escape output HTML untuk mencegah XSS
function e($teks) {
    if ($teks === null) {
        return '';
    }
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}

// Cek status login dan hak akses admin
function wajib_login() {
    if (!isset($_SESSION['user'])) {
        header('Location: ' . url('/masuk.php'));
        exit;
    }
}

function wajib_admin() {
    wajib_login();

    if (isset($_SESSION['user']['role'])) {
        $roleUser = $_SESSION['user']['role'];
    } else {
        $roleUser = '';
    }

    if ($roleUser !== 'admin') {
        http_response_code(403);
        $judul = 'Khusus Admin';
        require __DIR__ . '/../includes/header.php';
        ?>
<div class="narrow" style="max-width:520px">
  <div class="empty">
    <h1 class="s">Halaman khusus admin</h1>
    <p>Akun kamu belum punya akses ke panel admin. Masuk dengan akun admin untuk mengelola pertanyaan kuis dan isi gaya belajar.</p>
    <a class="btn" href="<?= e(url('/')) ?>">Kembali ke Home</a>
    <a class="btn ghost" href="<?= e(url('/keluar.php')) ?>">Keluar dan masuk sebagai admin</a>
  </div>
</div>
<?php
        require __DIR__ . '/../includes/footer.php';
        exit;
    }
}

// Perlindungan CSRF untuk pengiriman form POST
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_periksa() {
    if (isset($_POST['csrf'])) {
        $kirim = (string)$_POST['csrf'];
    } else {
        $kirim = '';
    }

    if (isset($_SESSION['csrf'])) {
        $tokenTersimpan = (string)$_SESSION['csrf'];
    } else {
        $tokenTersimpan = '';
    }

    if ($tokenTersimpan === '' || !hash_equals($tokenTersimpan, $kirim)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

// Helper format nama dan tanggal Indonesia
function nama_depan($nama_lengkap) {
    $pecah = explode(' ', trim($nama_lengkap));
    return $pecah[0];
}

function tanggal_id($waktu) {
    $daftar_bulan = [
        '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    $waktu_unix = strtotime($waktu);
    $hari  = date('j', $waktu_unix);
    $bulan = $daftar_bulan[(int)date('n', $waktu_unix)];
    $tahun = date('Y', $waktu_unix);

    return $hari . ' ' . $bulan . ' ' . $tahun;
}