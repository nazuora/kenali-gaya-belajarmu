<?php
/*
  File: includes/footer.php
  Keterangan: Bagian penutup konten utama, kaki halaman (footer),
  serta skrip jam berjalan yang diperbarui setiap detik.
*/
?>

    <!-- Penutup Konten Utama Halaman -->
    </main>

    <!-- Bagian Kaki (Footer) Website -->
    <footer>
      <div class="wrap">
        <p>Proyek Tugas Besar Pemrograman Web — Kelompok 5</p>
      </div>
    </footer>

<script>
// Menampilkan dan memperbarui jam berjalan pada elemen #jam
function tampilJam() {
    const elemenJam = document.getElementById('jam');

    if (elemenJam) {
        elemenJam.textContent = new Date()
            .toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            })
            .replace(/\./g, ':');
    }
}

tampilJam();
setInterval(tampilJam, 1000);

// Tombol berbentuk tautan menjadi abu-abu sebentar sebelum pindah halaman
document.querySelectorAll('a.btn').forEach(function(tombol) {
    tombol.addEventListener('click', function(e) {
        if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) {
            return;
        }
        e.preventDefault();
        tombol.classList.add('ditekan');
        setTimeout(function() {
            window.location.href = tombol.href;
        }, 250);
    });
});
</script>

</body>
</html>