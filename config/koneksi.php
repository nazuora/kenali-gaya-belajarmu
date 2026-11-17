<?php
// ==========================================
// 1. PENGATURAN KONEKSI DATABASE
// ==========================================
$host     = "localhost";
$username = "root";
$password = "";
$database = "kenali_gaya_belajar";

// Membuat koneksi ke database MySQL menggunakan MySQLi biasa
$db = mysqli_connect($host, $username, $password, $database);

// Cek apakah koneksi berhasil atau gagal
if (!$db) {
    // Jika gagal, hentikan program dan tampilkan pesan sederhana
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Atur format karakter ke UTF-8 agar huruf/simbol tidak berantakan
mysqli_set_charset($db, "utf8mb4");


// ==========================================
// 2. PENGATURAN URL DASAR (BASE URL)
// ==========================================
/* 
  Catatan: Sesuaikan nama folder project kamu di dalam htdocs XAMPP.
  Jika project ditaruh di dalam folder "kenali-gaya-belajarmu", maka tulis seperti di bawah.
*/
define('BASE_URL', 'http://localhost/kenali-gaya-belajarmu');

// Fungsi bantu untuk mempermudah pembuatan link halaman
function url($jalur) {
    return BASE_URL . $jalur;
}


// ==========================================
// 3. PENGATURAN SESI (SESSION)
// ==========================================
// Menyalakan sesi untuk mengingat data login pengguna
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ==========================================
// 4. FUNGSI PENGAMANAN (KEAMANAN DATA & XSS)
// ==========================================

// Fungsi untuk membersihkan teks agar aman dari serangan kode berbahaya (XSS)
function e($teks) {
    if ($teks === null) {
        return '';
    }
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}

// Fungsi untuk mengecek apakah pengguna sudah login atau belum
function wajib_login() {
    if (!isset($_SESSION['user'])) {
        // Jika belum login, tendang ke halaman masuk
        header('Location: ' . url('/masuk.php'));
        exit;
    }
}

// Fungsi untuk mengecek apakah yang login adalah admin
function wajib_admin() {
    wajib_login(); // Pastikan login dulu
    
    // Cek rolenya apakah admin
    if ($_SESSION['user']['role'] != 'admin') {
        echo "Akses ditolak! Halaman ini khusus untuk Admin.";
        exit;
    }
}

// Token Keamanan Form (CSRF Token) untuk mencegah pengiriman form ilegal
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


// ==========================================
// 5. FUNGSI PENDUKUNG LAINNYA
// ==========================================

// Mengambil nama depan saja dari nama lengkap pengguna
function nama_depan($nama_lengkap) {
    $pecah = explode(' ', trim($nama_lengkap));
    return $pecah[0];
}

// Mengubah format tanggal database menjadi format tanggal Indonesia (Contoh: 17 November 2026)
function tanggal_id($waktu) {
    $daftar_bulan = [
        '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $waktu_unix = strtotime($waktu);
    $hari  = date('j', $waktu_unix);
    $bulan = $daftar_bulan[(int)date('n', $waktu_unix)];
    $tahun = date('Y', $waktu_unix);
    
    return $hari . ' ' . $bulan . ' ' . $tahun;
}

// Fungsi sementara untuk mencegah error jika belum dibuat
if (!function_exists('simpan_hasil_tertunda')) {
    function simpan_hasil_tertunda($db, $userId) {
        // Kembalikan nilai null jika belum ada logika penyimpanan hasil kuis sementara
        return null;
    }
}
?>