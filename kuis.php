<?php
// kuis.php
require __DIR__ . '/config/koneksi.php';
wajib_login();

$judul = 'Kuis Gaya Belajar';
require __DIR__ . '/includes/header.php';

// Ambil daftar pertanyaan dan pilihan jawabannya dari database
$pertanyaanList = [];
$resP = $db->query("SELECT * FROM pertanyaan ORDER BY urutan ASC, id ASC");
while ($row = $resP->fetch_assoc()) {
    $pId = $row['id'];
    $resJ = $db->query("SELECT * FROM jawaban WHERE pertanyaan_id = $pId");
    $row['pilihan'] = [];
    while ($j = $resJ->fetch_assoc()) {
        $row['pilihan'][] = $j;
    }
    $pertanyaanList[] = $row;
}
?>

<h1>Kuis Gaya Belajar</h1>
<p class="sub">Jawablah pertanyaan di bawah ini sesuai dengan kebiasaan belajarmu.</p>

<!-- TAG FORM DIBAWAH INI YANG DITAMBAHKAN AGAR BISA TERHUBUNG KE PROSES-KUIS.PHP -->
<form action="proses-kuis.php" method="POST">
    <?= csrf_field() ?>

    <?php if (empty($pertanyaanList)): ?>
        <p>Belum ada pertanyaan kuis yang tersedia.</p>
    <?php else: ?>
        <?php foreach ($pertanyaanList as $index => $p): ?>
            <div class="card" style="margin-bottom: 20px;">
                <p><strong><?= ($index + 1) ?>. <?= e($p['teks']) ?></strong></p>
                
                <?php foreach ($p['pilihan'] as $pil): ?>
                    <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                        <input type="radio" name="jawaban[<?= (int)$p['id'] ?>]" value="<?= (int)$pil['gaya_id'] ?>" required>
                        <?= e($pil['teks']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn">Kirim Jawaban & Lihat Hasil</button>
        </div>
    <?php endif; ?>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>