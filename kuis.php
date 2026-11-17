<?php
/* 
  File: kuis.php
  Keterangan: Halaman utama pengerjaan kuis gaya belajar. File ini memuat 
  pertanyaan-pertanyaan interaktif untuk mengukur kecenderungan tipe 
  Visual, Auditori, dan Kinestetik pengguna, serta memproses hasilnya 
  ke dalam database.
*/

// Memulai sesi untuk memeriksa status login dan petunjuk
session_start();

// Menampilkan laporan error jika terjadi kendala saat pengembangan (opsional)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Memastikan pengguna sudah membaca halaman petunjuk sebelum masuk ke kuis
if (!isset($_SESSION['sudah_baca_petunjuk']) || $_SESSION['sudah_baca_petunjuk'] !== true) {
    header("Location: petunjuk.php");
    exit;
}

// 2. Memuat file koneksi database dan fungsi pemeriksaan login
require __DIR__ . '/config/koneksi.php';
wajib_login();

// Mengambil data pertanyaan beserta pilihan jawabannya dari database
$query_sql = 'SELECT p.id AS id_pertanyaan, p.teks AS teks_pertanyaan, j.gaya_id, j.teks AS teks_jawaban
              FROM pertanyaan p
              JOIN jawaban j ON j.pertanyaan_id = p.id
              ORDER BY p.urutan, p.id, j.gaya_id';

$daftar_soal = [];
$hasil_query = $db->query($query_sql);

// Menyusun ulang data dari database agar mudah dibaca oleh JavaScript
foreach ($hasil_query as $baris) {
    $id_p = (int)$baris['id_pertanyaan'];
    
    // Jika pertanyaan belum ada di dalam array, buat wadahnya
    if (!isset($daftar_soal[$id_p])) {
        $daftar_soal[$id_p]['id']   = $id_p;
        $daftar_soal[$id_p]['teks'] = $baris['teks_pertanyaan'];
        $daftar_soal[$id_p]['jawaban'] = [];
    }
    
    // Masukkan pilihan jawaban ke dalam pertanyaan yang sesuai
    $daftar_soal[$id_p]['jawaban'][] = [
        'gaya' => (int)$baris['gaya_id'], 
        'teks' => $baris['teks_jawaban']
    ];
}

// Mengubah indeks array agar berurutan mulai dari 0
$daftar_soal = array_values($daftar_soal);

$judul = 'Kuis Gaya Belajar';
require __DIR__ . '/includes/header.php';
?>

<div class="narrow">
<?php if (empty($daftar_soal)): ?>
  <!-- Tampilan jika soal belum dimasukkan oleh admin -->
  <div class="empty">
    <strong>Belum ada pertanyaan.</strong>
    <p>Admin perlu menambahkan pertanyaan terlebih dahulu.</p>
  </div>
<?php else: ?>
  <!-- Tampilan bagian atas kuis (nomor soal dan garis kemajuan/progress bar) -->
  <div class="qtop">
    <span>Kuis</span>
    <span id="nomor-soal"></span>
  </div>
  
  <div class="bar" role="progressbar" aria-label="Kemajuan kuis" aria-valuemin="0" aria-valuemax="100" id="wadah-progress">
    <div id="bar-isi"></div>
  </div>

  <!-- Kotak Pertanyaan Kuis -->
  <section class="card" style="margin-top:18px">
    <h1 class="s" id="teks-pertanyaan"></h1>
    
    <!-- Tempat pilihan jawaban akan dimunculkan oleh JavaScript -->
    <div id="pilihan-jawaban"></div>
    
    <!-- Tombol navigasi (Kembali dan Lanjut) -->
    <div class="actions">
      <button type="button" class="btn ghost" id="tombol-kembali">Kembali</button>
      <button type="button" class="btn" id="tombol-lanjut" disabled>Lanjut</button>
    </div>
  </section>

  <!-- Form tersembunyi untuk mengirimkan jawaban akhir ke halaman proses -->
  <form id="form-kirim-jawaban" method="post" action="<?= e(url('/proses-kuis.php')) ?>" hidden>
    <?= csrf_field() ?>
  </form>

  <script>
    // Memasukkan data soal dari PHP ke JavaScript dalam bentuk format JSON
    const dataSoal = <?= json_encode($daftar_soal, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    
    // Variabel untuk melacak posisi soal saat ini (mulai dari 0)
    let indeksAktif = 0;
    
    // Menyimpan pilihan jawaban user (diisi null jika belum dijawab)
    const pilihanUser = Array(dataSoal.length).fill(null);

    // Fungsi singkat untuk memanggil elemen HTML berdasarkan ID-nya
    function ambilElemen(id) {
      return document.getElementById(id);
    }

    // Fungsi utama untuk menampilkan soal dan jawaban ke layar
    function tampilkanSoal() {
      // Tampilkan teks pertanyaan dan nomor urut soal
      ambilElemen('teks-pertanyaan').textContent = dataSoal[indeksAktif].teks;
      ambilElemen('nomor-soal').textContent = (indeksAktif + 1) + ' dari ' + dataSoal.length;
      
      // Hitung persentase kemajuan untuk progress bar
      const persentaseProgress = (indeksAktif / dataSoal.length) * 100;
      ambilElemen('bar-isi').style.width = persentaseProgress + '%';
      ambilElemen('wadah-progress').setAttribute('aria-valuenow', Math.round(persentaseProgress)); 

      // Kosongkan pilihan jawaban sebelumnya
      const wadahPilihan = ambilElemen('pilihan-jawaban');
      wadahPilihan.innerHTML = '';

      // Buat tombol pilihan jawaban secara dinamis untuk setiap soal
      dataSoal[indeksAktif].jawaban.forEach(function(itemJawaban) {
        const tombolPilihan = document.createElement('button');
        tombolPilihan.type = 'button';
        tombolPilihan.className = 'opt';
        tombolPilihan.textContent = itemJawaban.teks;
        
        // Tandai jika jawaban ini sudah pernah dipilih sebelumnya
        const apakahDipilih = (pilihanUser[indeksAktif] === itemJawaban.gaya);
        tombolPilihan.setAttribute('aria-pressed', apakahDipilih);
        
        // Jika tombol diklik, simpan pilihan dan perbarui tampilan
        tombolPilihan.onclick = function() {
          pilihanUser[indeksAktif] = itemJawaban.gaya;
          tampilkanSoal();
        };
        
        wadahPilihan.appendChild(tombolPilihan);
      });

      // Atur tombol 'Kembali' (sembunyikan di soal pertama)
      ambilElemen('tombol-kembali').style.visibility = (indeksAktif > 0) ? 'visible' : 'hidden';
      
      // Tombol 'Lanjut' hanya aktif jika user sudah memilih salah satu jawaban
      ambilElemen('tombol-lanjut').disabled = (pilihanUser[indeksAktif] === null);
      
      // Ubah teks tombol menjadi 'Lihat hasil' jika sudah berada di soal terakhir
      if (indeksAktif === dataSoal.length - 1) {
        ambilElemen('tombol-lanjut').textContent = 'Lihat hasil';
      } else {
        ambilElemen('tombol-lanjut').textContent = 'Lanjut';
      }
    }

    // Aksi ketika tombol 'Kembali' diklik
    ambilElemen('tombol-kembali').onclick = function() {
      if (indeksAktif > 0) {
        indeksAktif--;
        tampilkanSoal();
      }
    };

    // Aksi ketika tombol 'Lanjut' / 'Lihat hasil' diklik
    ambilElemen('tombol-lanjut').onclick = function() {
      // Jika belum soal terakhir, pindah ke soal berikutnya
      if (indeksAktif < dataSoal.length - 1) {
        indeksAktif++;
        tampilkanSoal();
        return;
      }

      // Jika sudah di soal terakhir, kumpulkan semua jawaban ke dalam form tersembunyi lalu kirim
      const formKirim = ambilElemen('form-kirim-jawaban');
      
      // Masukkan token keamanan CSRF ulang
      formKirim.innerHTML = '<?= csrf_field() ?>'; 

      // Masukkan setiap jawaban user ke dalam elemen input tersembunyi (hidden input)
      dataSoal.forEach(function(soal, nomorUrut) {
        const inputTersembunyi = document.createElement('input');
        inputTersembunyi.type = 'hidden'; 
        inputTersembunyi.name = 'jawaban[' + soal.id + ']'; 
        inputTersembunyi.value = pilihanUser[nomorUrut];
        formKirim.appendChild(inputTersembunyi);
      });

      // Kirim data form ke halaman pemrosesan hasil kuis
      formKirim.submit();
    };

    // Panggil fungsi pertama kali saat halaman dibuka
    tampilkanSoal();
  </script>
<?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>