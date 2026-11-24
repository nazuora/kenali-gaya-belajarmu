<?php
/*
  File: jenis.php
  Keterangan: Halaman informasi jenis gaya belajar (Visual, Auditori,
  dan Kinestetik) beserta ciri-ciri, tips belajar, dan bidang pekerjaan.
*/

require __DIR__ . '/config/koneksi.php';

$daftar_gaya = [
    [
        'kode' => 'V',
        'nama' => 'Visual',

        'awal' => 'Gaya belajar visual merupakan kecenderungan seseorang untuk lebih mudah menerima, memahami, dan mengingat informasi melalui penglihatan.',

        'inti' => 'Gambar, warna, tulisan, diagram, grafik, dan peta konsep',

        'akhir' => 'dapat membantu seseorang memahami materi dengan lebih jelas. Seseorang dengan kecenderungan visual biasanya lebih nyaman ketika materi pembelajaran disajikan secara terstruktur dan memiliki tampilan yang menarik.',

        'ciri' => [
            'Lebih mudah memahami informasi melalui gambar, ilustrasi, diagram, dan grafik.',
            'Cenderung mengingat informasi berdasarkan bentuk, warna, simbol, dan tampilan yang pernah dilihat.',
            'Menyukai catatan yang rapi, terstruktur, serta menggunakan warna untuk membedakan informasi penting.',
            'Lebih mudah memahami materi apabila disertai contoh visual seperti gambar, tabel, atau presentasi.',
            'Cenderung memperhatikan detail tampilan dan tata letak ketika mempelajari suatu informasi.'
        ],

        'tips' => [
            'Gunakan mind map atau peta konsep untuk menghubungkan berbagai informasi dalam satu materi.',
            'Manfaatkan stabilo atau pulpen berwarna untuk menandai bagian penting pada catatan.',
            'Ubah informasi yang panjang menjadi diagram, tabel, bagan, atau gambar sederhana.',
            'Gunakan video pembelajaran dan ilustrasi untuk membantu memahami materi yang sulit.',
            'Buat rangkuman dengan susunan yang rapi agar informasi lebih mudah dipelajari kembali.'
        ],

        'pekerjaan' => [
            'Desainer Grafis',
            'Ilustrator',
            'Arsitek',
            'Fotografer',
            'Desainer Interior'
        ]
    ],

    [
        'kode' => 'A',
        'nama' => 'Auditori',

        'awal' => 'Gaya belajar auditori merupakan kecenderungan seseorang untuk lebih mudah menerima, memahami, dan mengingat informasi melalui pendengaran.',

        'inti' => 'Penjelasan secara lisan, percakapan, diskusi, dan rekaman suara',

        'akhir' => 'dapat membantu proses memahami materi. Seseorang dengan kecenderungan auditori biasanya merasa lebih terbantu ketika dapat mendengarkan penjelasan dan membicarakan kembali informasi yang telah dipelajari.',

        'ciri' => [
            'Lebih mudah memahami materi ketika mendengarkan penjelasan secara langsung.',
            'Cenderung mengingat percakapan, instruksi lisan, dan informasi yang pernah didengar.',
            'Menyukai kegiatan diskusi, tanya jawab, dan belajar bersama orang lain.',
            'Sering membaca materi dengan suara atau mengulang informasi secara lisan.',
            'Lebih mudah berkonsentrasi ketika suasana belajar tidak terlalu bising.'
        ],

        'tips' => [
            'Dengarkan penjelasan guru atau dosen dengan saksama dan catat poin-poin pentingnya.',
            'Coba jelaskan kembali materi menggunakan kata-kata sendiri secara lisan.',
            'Gunakan rekaman suara berisi rangkuman materi untuk didengarkan kembali.',
            'Ikut dalam kelompok diskusi agar dapat bertukar pendapat dan memahami materi.',
            'Manfaatkan podcast atau video pembelajaran yang memiliki penjelasan suara.'
        ],

        'pekerjaan' => [
            'Penyiar Radio',
            'Presenter',
            'Guru atau Pengajar',
            'Penerjemah Bahasa',
            'Podcaster'
        ]
    ],

    [
        'kode' => 'K',
        'nama' => 'Kinestetik',

        'awal' => 'Gaya belajar kinestetik merupakan kecenderungan seseorang untuk lebih mudah menerima, memahami, dan mengingat informasi melalui aktivitas fisik, gerakan, dan pengalaman secara langsung.',

        'inti' => 'Praktik, percobaan, simulasi, dan kegiatan yang melibatkan pengalaman nyata',

        'akhir' => 'dapat membantu proses memahami materi. Seseorang dengan kecenderungan kinestetik biasanya lebih menyukai pembelajaran yang memberikan kesempatan untuk mencoba dan melakukan sesuatu secara langsung.',

        'ciri' => [
            'Lebih mudah memahami materi setelah mencoba atau mempraktikkannya secara langsung.',
            'Menyukai kegiatan pembelajaran yang melibatkan gerakan, aktivitas, dan pengalaman nyata.',
            'Cenderung lebih tertarik pada praktik dan eksperimen dibandingkan hanya membaca teori.',
            'Senang menggunakan alat peraga atau benda nyata untuk memahami suatu konsep.',
            'Lebih nyaman belajar dengan aktivitas yang bervariasi dan tidak hanya duduk diam dalam waktu lama.'
        ],

        'tips' => [
            'Perbanyak latihan soal, praktik, dan kegiatan langsung yang berhubungan dengan materi.',
            'Gunakan simulasi, eksperimen, atau alat peraga untuk memahami konsep pembelajaran.',
            'Cobalah membuat proyek sederhana agar materi dapat dipelajari melalui pengalaman.',
            'Selingi kegiatan belajar dengan jeda singkat untuk bergerak dan menyegarkan pikiran.',
            'Hubungkan materi yang dipelajari dengan contoh kegiatan dalam kehidupan sehari-hari.'
        ],

        'pekerjaan' => [
            'Koki atau Chef',
            'Teknisi',
            'Teknisi Laboratorium',
            'Pelatih Olahraga',
            'Pengrajin'
        ]
    ]
];

$judul = 'Jenis Gaya Belajar';
require __DIR__ . '/includes/header.php';
?>

<style>
    p.sub {
        display: block !important;
        width: 100% !important;
        max-width: none !important;
    }
</style>

<h1 class="s">Jenis gaya belajar</h1>

<p class="sub">
    Setiap orang memiliki kecenderungan yang berbeda dalam menerima dan memahami informasi.
    Kenali tiga jenis gaya belajar berikut untuk mengetahui cara belajar yang dapat membantumu memahami materi dengan baik.
</p>

<div class="tabs" role="tablist">
    <?php foreach ($daftar_gaya as $i => $g) { ?>
        <?php
            $kodeKecil = strtolower($g['kode']);
            if ($i === 0) {
                $statusAktif = 'true';
            } else {
                $statusAktif = 'false';
            }
        ?>
        <button
            type="button"
            role="tab"
            class="<?= e($kodeKecil) ?>"
            data-kode="<?= e($g['kode']) ?>"
            data-k="<?= e($kodeKecil) ?>"
            aria-selected="<?= e($statusAktif) ?>"
        >
            <?= e($g['nama']) ?>
        </button>
    <?php } ?>
</div>

<?php foreach ($daftar_gaya as $i => $g) { ?>
    <?php
        $kodeKecil = strtolower($g['kode']);
        if ($i === 0) {
            $atributHidden = '';
        } else {
            $atributHidden = ' hidden';
        }
    ?>
    <article
        class="panel <?= e($kodeKecil) ?>"
        id="p-<?= e($g['kode']) ?>"
        role="tabpanel"<?= $atributHidden ?>
    >
        <div class="bubble">
            <h2>Gaya Belajar <?= e($g['nama']) ?></h2>
            <p>
                <?= e($g['awal']) ?>
                <strong><?= e($g['inti']) ?></strong>
                <?= e($g['akhir']) ?>
            </p>
        </div>

        <h3 class="sec">
            Ciri-Ciri Gaya Belajar <?= e($g['nama']) ?>
        </h3>

        <ul class="tips">
            <?php foreach ($g['ciri'] as $ciri_item) { ?>
                <li><?= e($ciri_item) ?></li>
            <?php } ?>
        </ul>

        <h3 class="sec">Tips Belajar yang Dapat Dilakukan</h3>

        <ul class="tips">
            <?php foreach ($g['tips'] as $tips_item) { ?>
                <li><?= e($tips_item) ?></li>
            <?php } ?>
        </ul>

        <h3 class="sec">Contoh Bidang Pekerjaan yang Sesuai</h3>

        <ul class="tips">
            <?php foreach ($g['pekerjaan'] as $pekerjaan_item) { ?>
                <li><?= e($pekerjaan_item) ?></li>
            <?php } ?>
        </ul>
    </article>
<?php } ?>

<div class="bubble" style="margin-top: 1em;">
    <p>
        <strong>Catatan:</strong>
        Gaya belajar bukan kategori yang mutlak.
        Seseorang dapat memiliki lebih dari satu kecenderungan
        gaya belajar. Oleh karena itu, gunakan berbagai metode
        belajar sesuai kebutuhan dan jenis materi yang sedang dipelajari.
    </p>
</div>

<p style="margin-top:18px">
    <a class="btn" href="<?= e(url('/petunjuk.php')) ?>">
        Cari tahu gaya belajarmu
    </a>
</p>

<script>
    // Mengatur perpindahan tab antara Visual, Auditori, dan Kinestetik
    const semuaTombolTab = document.querySelectorAll('.tabs button');
    const semuaPanelKonten = document.querySelectorAll('.panel');

    semuaTombolTab.forEach(function(tombol) {
        tombol.onclick = function() {
            semuaTombolTab.forEach(function(t) {
                t.setAttribute('aria-selected', 'false');
            });

            tombol.setAttribute('aria-selected', 'true');

            const kodePilihan = tombol.getAttribute('data-kode');

            semuaPanelKonten.forEach(function(panel) {
                if (panel.id === 'p-' + kodePilihan) {
                    panel.removeAttribute('hidden');
                } else {
                    panel.setAttribute('hidden', 'true');
                }
            });
        };
    });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>