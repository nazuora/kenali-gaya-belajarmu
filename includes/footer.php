<?php
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
function tampilJam() {
    const e = document.getElementById('jam');

    if (e) {
        e.textContent = new Date()
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
</script>

</body>
</html>