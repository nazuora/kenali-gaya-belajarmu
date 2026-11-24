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

$db = mysqli_connect($host, $username, $password, $database);
if (!$db) {
    die('Koneksi ke database gagal: ' . mysqli_connect_error());
}
mysqli_set_charset($db, 'utf8mb4');

// Base URL dan helper pembuat tautan halaman
$akarProyek = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
$dr = $_SERVER['DOCUMENT_ROOT'] ?? '';
$akarServer = $dr !== '' ? str_replace('\\', '/', rtrim((string)realpath($dr), '/\\')) : '';
define('BASE_URL', ($akarServer !== '' && stripos($akarProyek, $akarServer) === 0) ? (string)substr($akarProyek, strlen($akarServer)) : '');;

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

function wajib_admin(): void {
    wajib_login();
    if ($_SESSION['user']['role'] !== 'admin') {
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
    $kirim = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $kirim)) {
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

if (!function_exists('simpan_hasil_tertunda')) {
    function simpan_hasil_tertunda($db, $userId) {
        return null;
    }
}