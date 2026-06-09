<?php
$pageTitle = 'Beranda';
require_once __DIR__ . '/includes/header.php';
?>

<main class="page-main">
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="eyebrow">Reservasi Ruangan Kampus</span>
                    <h1 class="hero-title">Kelola pemesanan ruangan dengan lebih rapi.</h1>
                    <p class="hero-copy">
                        Sistem sederhana untuk melihat ruangan, mengecek fasilitas, membuat reservasi,
                        dan memantau status peminjaman dalam satu tempat.
                    </p>
                    <div class="hero-actions">
                        <a href="pages/rooms.php" class="btn btn-primary btn-lg">Lihat Ruangan</a>
                        <a href="login.php" class="btn btn-outline-primary btn-lg">Masuk</a>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="hero-panel">
                        <div class="panel-header">
                            <div>
                                <p class="panel-kicker">Status Hari Ini</p>
                                <h2>Ringkasan Reservasi</h2>
                            </div>
                            <span class="status-dot"></span>
                        </div>

                        <div class="summary-list">
                            <div class="summary-item">
                                <span>Ruang tersedia</span>
                                <strong>5</strong>
                            </div>
                            <div class="summary-item">
                                <span>Menunggu persetujuan</span>
                                <strong>3</strong>
                            </div>
                            <div class="summary-item">
                                <span>Reservasi disetujui</span>
                                <strong>8</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="section-heading">
                <span class="eyebrow">Fitur Utama</span>
                <h2>Alur reservasi dibuat singkat dan jelas.</h2>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <article class="feature-card">
                        <div class="feature-icon">01</div>
                        <h3>Cek Ruangan</h3>
                        <p>Lihat daftar ruangan, lokasi, kapasitas, status, dan fasilitas yang tersedia.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="feature-card">
                        <div class="feature-icon">02</div>
                        <h3>Buat Reservasi</h3>
                        <p>Pilih tanggal dan jam penggunaan ruangan dengan validasi jadwal yang rapi.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="feature-card">
                        <div class="feature-icon">03</div>
                        <h3>Pantau Status</h3>
                        <p>Admin dapat menyetujui, menolak, atau menyelesaikan reservasi melalui dashboard.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
