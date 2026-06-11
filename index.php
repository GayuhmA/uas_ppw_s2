<?php
$pageTitle = 'Beranda';
require_once __DIR__ . '/includes/header.php';
?>

<main class="page-main">
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-xl-6 col-lg-6">
                    <span class="eyebrow">Reservasi Ruangan</span>
                    <h1 class="hero-title">Peminjaman ruangan kampus menjadi lebih tertata.</h1>
                    <p class="hero-copy">
                        Pengguna dapat melihat ketersediaan ruangan, memeriksa fasilitas, dan mengajukan
                        pemakaian melalui satu sistem. Layanan ini dapat digunakan untuk kegiatan kelas,
                        rapat, praktikum, maupun seminar.
                    </p>

                    <div class="hero-actions">
                        <a href="login.php" class="btn btn-primary btn-lg">Masuk ke Sistem</a>
                    </div>

                    <div class="hero-metrics" aria-label="Ringkasan sistem">
                        <div>
                            <strong>5</strong>
                            <span>ruangan aktif</span>
                        </div>
                        <div>
                            <strong>08.00</strong>
                            <span>jadwal awal</span>
                        </div>
                        <div>
                            <strong>3</strong>
                            <span>menunggu persetujuan</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-lg-6">
                    <div class="schedule-visual" aria-label="Pratinjau jadwal reservasi">
                        <div class="visual-topline">
                            <div>
                                <span class="panel-kicker">Hari Ini</span>
                                <h2>Jadwal Pemakaian Ruang</h2>
                            </div>
                            <span class="live-badge">Aktif</span>
                        </div>

                        <div class="schedule-grid">
                            <aside class="room-stack">
                                <div class="room-pill active">
                                    <span>Laboratorium Komputer 1</span>
                                    <small>40 kursi</small>
                                </div>
                                <div class="room-pill">
                                    <span>Ruang Seminar</span>
                                    <small>80 kursi</small>
                                </div>
                                <div class="room-pill">
                                    <span>Aula Mini</span>
                                    <small>120 kursi</small>
                                </div>
                            </aside>

                            <div class="time-board">
                                <div class="time-labels">
                                    <span>08</span>
                                    <span>10</span>
                                    <span>13</span>
                                    <span>15</span>
                                </div>

                                <div class="timeline-row">
                                    <span class="timeline-block approved" style="--start: 1; --span: 3;">
                                        Praktikum Web
                                    </span>
                                </div>
                                <div class="timeline-row">
                                    <span class="timeline-block pending" style="--start: 2; --span: 2;">
                                        Rapat Divisi
                                    </span>
                                </div>
                                <div class="timeline-row">
                                    <span class="timeline-block open" style="--start: 4; --span: 2;">
                                        Tersedia
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="review-strip">
                            <div>
                                <span class="mini-label">Pengajuan Terbaru</span>
                                <strong>Ruang Rapat Departemen</strong>
                            </div>
                            <span class="status-chip">Menunggu</span>
                        </div>
                    </div>

                    <div class="floating-note">
                        <span class="note-line"></span>
                        <div>
                            <strong>Jadwal diperiksa sebelum disimpan</strong>
                            <p>Sistem membantu mencegah satu ruangan digunakan pada waktu yang sama.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="campus-strip" aria-label="Contoh ruangan">
                <div class="strip-item">
                    <span class="strip-code">A301</span>
                    <div>
                        <strong>Ruang Kelas</strong>
                        <p>Proyektor, pendingin ruangan, papan tulis</p>
                    </div>
                </div>
                <div class="strip-item">
                    <span class="strip-code">LAB-1</span>
                    <div>
                        <strong>Laboratorium Komputer</strong>
                        <p>40 komputer, jaringan nirkabel, proyektor</p>
                    </div>
                </div>
                <div class="strip-item">
                    <span class="strip-code">SEM</span>
                    <div>
                        <strong>Ruang Seminar</strong>
                        <p>Tata suara, 80 kursi</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="workflow-band">
                <div class="section-heading">
                    <span class="eyebrow">Alur Layanan</span>
                    <h2>Proses reservasi dibuat jelas sejak memilih ruangan hingga menerima keputusan.</h2>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <article class="feature-card">
                            <div class="feature-icon">01</div>
                            <h3>Pilih ruangan</h3>
                            <p>Lihat daftar ruangan beserta lokasi, kapasitas, status, dan fasilitasnya.</p>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="feature-card">
                            <div class="feature-icon">02</div>
                            <h3>Ajukan pemakaian</h3>
                            <p>Tentukan tanggal, jam mulai, jam selesai, serta tujuan penggunaan ruangan.</p>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="feature-card">
                            <div class="feature-icon">03</div>
                            <h3>Pantau keputusan</h3>
                            <p>Setelah diajukan, status reservasi dapat dilihat secara berkala di dalam sistem.</p>
                        </article>
                    </div>
                </div>

                <div class="system-note">
                    <span>Ruang kelas</span>
                    <span>Laboratorium</span>
                    <span>Ruang seminar</span>
                    <span>Ruang rapat</span>
                </div>
            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
