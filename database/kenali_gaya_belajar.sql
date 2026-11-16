-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 16 Nov 2026 pada 14.15
-- Versi server: 10.11.16-MariaDB
-- Versi PHP: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `kenali_gaya_belajar`
--
CREATE DATABASE IF NOT EXISTS `kenali_gaya_belajar` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kenali_gaya_belajar`;

-- --------------------------------------------------------

--
-- Struktur dari tabel `gaya_belajar`
--

CREATE TABLE `gaya_belajar` (
  `id` int(11) NOT NULL,
  `kode` char(1) NOT NULL,
  `nama` varchar(30) NOT NULL,
  `julukan` varchar(60) NOT NULL,
  `pengertian` text NOT NULL,
  `kalimat_hasil` varchar(200) NOT NULL,
  `ciri` text DEFAULT NULL,
  `tips` text DEFAULT NULL,
  `bidang` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `gaya_belajar`
--

INSERT INTO `gaya_belajar` (`id`, `kode`, `nama`, `julukan`, `pengertian`, `kalimat_hasil`, `ciri`, `tips`, `bidang`) VALUES
(1, 'V', 'Visual', 'Si Pemetaan Visual', 'Orang visual menyerap informasi paling baik lewat penglihatan: gambar, warna, diagram, dan tata letak yang rapi.', 'Kamu paling cepat paham lewat gambar dan struktur yang jelas.', 'Mudah mengingat wajah, bentuk, dan warna\nSuka membuat catatan berwarna dan bergambar\nLebih paham saat melihat peta, grafik, atau slide\nSulit fokus kalau hanya mendengar ceramah panjang', 'Buat mind map dan diagram alur\nPakai stabilo dengan kode warna\nTonton video penjelasan\nUbah angka dan teks menjadi tabel atau grafik', 'Desainer, arsitek, dan pekerjaan yang banyak memakai visual.'),
(2, 'A', 'Auditori', 'Sang Pendengar Ulung', 'Orang auditori menyerap informasi paling baik lewat pendengaran: penjelasan lisan, diskusi, dan irama.', 'Kamu paling cepat paham lewat suara dan percakapan.', 'Mudah mengingat percakapan dan penjelasan\nSuka berdiskusi dan bertanya\nSering membaca sambil bersuara\nMudah terganggu oleh suara bising', 'Jelaskan ulang materi dengan suara\nRekam rangkuman, lalu dengarkan ulang\nBelajar dalam kelompok diskusi\nDengarkan podcast atau materi audio', 'Pembicara, pengajar, dan pekerjaan yang banyak berkomunikasi lisan.'),
(3, 'K', 'Kinestetik', 'Si Pencoba Langsung', 'Orang kinestetik menyerap informasi paling baik lewat gerakan dan pengalaman langsung.', 'Kamu paling cepat paham lewat praktik dan gerakan.', 'Sulit duduk diam dalam waktu lama\nLebih paham setelah mencoba sendiri\nSuka praktik, eksperimen, dan simulasi\nSering bergerak atau memegang benda saat berpikir', 'Perbanyak latihan soal dan praktik\nBelajar sambil berjalan atau berdiri\nPakai benda nyata sebagai alat peraga\nIstirahat singkat untuk bergerak tiap 25 menit', 'Teknisi, atlet, dan pekerjaan yang banyak praktik lapangan.');

-- --------------------------------------------------------

--
-- Struktur dari tabel `hasil_kuis`
--

CREATE TABLE `hasil_kuis` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `persen_visual` tinyint(3) UNSIGNED NOT NULL,
  `persen_auditori` tinyint(3) UNSIGNED NOT NULL,
  `persen_kinestetik` tinyint(3) UNSIGNED NOT NULL,
  `gaya_utama_id` int(11) NOT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jawaban`
--

CREATE TABLE `jawaban` (
  `id` int(11) NOT NULL,
  `pertanyaan_id` int(11) NOT NULL,
  `gaya_id` int(11) NOT NULL,
  `teks` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `jawaban`
--

INSERT INTO `jawaban` (`id`, `pertanyaan_id`, `gaya_id`, `teks`) VALUES
(4, 2, 1, 'Melihat gambar, diagram, atau catatan materi'),
(5, 2, 2, 'Mendengarkan penjelasan dari guru atau teman'),
(6, 2, 3, 'Langsung mencoba atau mempraktikkan materi tersebut'),
(7, 3, 1, 'Memperhatikan gambar resep dan urutan langkahnya'),
(8, 3, 2, 'Mendengarkan penjelasan chef tentang cara mengolah bahan'),
(9, 3, 3, 'Langsung mencoba mengolah bahan sambil mengikuti arahan chef'),
(10, 4, 1, 'Mengingat bentuk tulisan, warna, atau gambar'),
(11, 4, 2, 'Mengingat suara atau penjelasan yang pernah didengar'),
(12, 4, 3, 'Mengingat kegiatan yang dilakukan saat mempelajarinya'),
(13, 5, 1, 'Membuat catatan berwarna dan menandai bagian penting'),
(14, 5, 2, 'Membaca materi dengan suara keras dan mendengarkannya berulang kali'),
(15, 5, 3, 'Berlatih presentasi sambil bergerak dan memperagakan penjelasan'),
(16, 6, 1, 'Guru menampilkan slide, gambar, atau diagram'),
(17, 6, 2, 'Guru menjelaskan materi secara lisan'),
(18, 6, 3, 'Guru memberikan demonstrasi atau praktik'),
(19, 7, 1, 'Melihat peta atau gambar rute perjalanan'),
(20, 7, 2, 'Mendengarkan seseorang menjelaskan arah perjalanan'),
(21, 7, 3, 'Mengikuti rute sambil mengenali jalan secara langsung'),
(22, 8, 1, 'Membuat catatan atau mind map bersama'),
(23, 8, 2, 'Berdiskusi dan bertukar pendapat'),
(24, 8, 3, 'Mengerjakan proyek atau praktik bersama'),
(25, 9, 1, 'Melihat contoh penyelesaian dalam bentuk tulisan atau diagram'),
(26, 9, 2, 'Mendengarkan penjelasan langkah demi langkah'),
(27, 9, 3, 'Mencoba mengerjakan soal serupa sampai memahami caranya'),
(28, 10, 1, 'Mencari gambar atau video ilustrasi'),
(29, 10, 2, 'Meminta seseorang menjelaskannya kembali'),
(30, 10, 3, 'Mencoba mengerjakan atau mempraktikkannya sendiri'),
(31, 11, 1, 'Tutorial dengan screenshot, diagram, dan petunjuk visual'),
(32, 11, 2, 'Tutorial dengan penjelasan suara yang runtut'),
(33, 11, 3, 'Tutorial yang langsung mengajak mencoba setiap fitur'),
(34, 12, 1, 'Melihat tulisan, kosakata, dan gambar'),
(35, 12, 2, 'Mendengarkan pelafalan dan percakapan'),
(36, 12, 3, 'Mempraktikkan percakapan secara langsung'),
(37, 13, 1, 'Pameran edukasi dengan banyak infografis dan visual menarik'),
(38, 13, 2, 'Seminar interaktif dengan sesi tanya jawab'),
(39, 13, 3, 'Workshop yang mengajak peserta membuat sesuatu secara langsung'),
(40, 14, 1, 'Melihat kembali catatan atau rangkuman'),
(41, 14, 2, 'Mendengarkan kembali penjelasan materi'),
(42, 14, 3, 'Mengulang praktik atau latihan yang pernah dilakukan'),
(43, 15, 1, 'Membuat desain dan merancang tampilan produk'),
(44, 15, 2, 'Menyampaikan ide dan mengoordinasikan diskusi'),
(45, 15, 3, 'Merakit, mencoba, dan menguji produk'),
(46, 16, 1, 'Belajar dengan video ilustrasi, infografis, dan mind map'),
(47, 16, 2, 'Belajar melalui diskusi, podcast, dan penjelasan interaktif'),
(48, 16, 3, 'Belajar melalui eksperimen, simulasi, dan praktik langsung');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pertanyaan`
--

CREATE TABLE `pertanyaan` (
  `id` int(11) NOT NULL,
  `teks` varchar(255) NOT NULL,
  `urutan` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pertanyaan`
--

INSERT INTO `pertanyaan` (`id`, `teks`, `urutan`) VALUES
(2, 'Ketika mempelajari materi baru, kamu lebih suka ...', 2),
(3, 'Kamu baru saja bergabung dengan kelas memasak. Chef sedang mengajarkan resep baru. Apa yang pertama kali kamu lakukan?', 3),
(4, 'Ketika mengingat informasi, kamu biasanya ...', 4),
(5, 'Kamu harus mempersiapkan presentasi untuk besok. Bagaimana cara kamu berlatih?', 5),
(6, 'Ketika guru menjelaskan materi, kamu lebih mudah memahami jika ...', 6),
(7, 'Kamu tersesat di tempat yang belum pernah dikunjungi. Bagaimana kamu ingin mendapatkan petunjuk?', 7),
(8, 'Ketika mengikuti pembelajaran kelompok, kamu lebih suka ...', 8),
(9, 'Ketika menghadapi soal matematika yang sulit, apa yang paling membantumu?', 9),
(10, 'Ketika menemukan materi yang sulit dipahami, kamu akan ...', 10),
(11, 'Kamu ingin mempelajari aplikasi yang belum pernah digunakan. Tutorial seperti apa yang paling menarik?', 11),
(12, 'Ketika mempelajari bahasa asing, kamu lebih suka ...', 12),
(13, 'Kamu diberi kesempatan mengikuti satu kegiatan pembelajaran di luar kelas. Mana yang paling ingin kamu ikuti?', 13),
(14, 'Ketika mengingat materi pelajaran, hal yang paling membantu adalah ...', 14),
(15, 'Kamu diminta membuat sebuah produk sederhana bersama kelompok. Peran apa yang paling nyaman kamu ambil?', 15),
(16, 'Jika bebas memilih metode belajar untuk satu hari penuh, pengalaman belajar seperti apa yang kamu pilih?', 16);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(60) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `foto` varchar(255) DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password_hash`, `role`, `foto`, `dibuat_pada`) VALUES
(1, 'Admin', 'admin@email.com', '$2y$10$r4ZtNtEtniptaMQVRtL5JuQxWXzeI.QjNlKfamSAmtuP/5yqRiLuy', 'admin', NULL, '2026-11-11 17:08:14'),
(7, 'Dosen', 'dosen@gmail.com', '$2y$10$m4mPHhv298mBYOwWzafOGOEfMVj6nIUQm.LITEvwY1cOFNFksa1IS', 'user', NULL, '2026-11-16 20:12:51');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `gaya_belajar`
--
ALTER TABLE `gaya_belajar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indeks untuk tabel `hasil_kuis`
--
ALTER TABLE `hasil_kuis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `gaya_utama_id` (`gaya_utama_id`);

--
-- Indeks untuk tabel `jawaban`
--
ALTER TABLE `jawaban`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pertanyaan_id` (`pertanyaan_id`),
  ADD KEY `gaya_id` (`gaya_id`);

--
-- Indeks untuk tabel `pertanyaan`
--
ALTER TABLE `pertanyaan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `gaya_belajar`
--
ALTER TABLE `gaya_belajar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `hasil_kuis`
--
ALTER TABLE `hasil_kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `jawaban`
--
ALTER TABLE `jawaban`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT untuk tabel `pertanyaan`
--
ALTER TABLE `pertanyaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `hasil_kuis`
--
ALTER TABLE `hasil_kuis`
  ADD CONSTRAINT `hasil_kuis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hasil_kuis_ibfk_2` FOREIGN KEY (`gaya_utama_id`) REFERENCES `gaya_belajar` (`id`);

--
-- Ketidakleluasaan untuk tabel `jawaban`
--
ALTER TABLE `jawaban`
  ADD CONSTRAINT `jawaban_ibfk_1` FOREIGN KEY (`pertanyaan_id`) REFERENCES `pertanyaan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jawaban_ibfk_2` FOREIGN KEY (`gaya_id`) REFERENCES `gaya_belajar` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
