<?php
$pageTitle = 'Beranda';
require_once __DIR__ . '/includes/header.php';
?>

<main class="page-main">
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-xl-6 col-lg-6">
                    <span class="eyebrow">Reservasi Ruangan Kampus</span>
                    <h1 class="hero-title">Jadwal ruang yang tenang, jelas, dan tidak saling tabrakan.</h1>
                    <p class="hero-copy">
                        Lihat ketersediaan ruangan, pilih fasilitas yang sesuai, lalu ajukan reservasi
                        tanpa alur yang berbelit. Admin cukup meninjau status dari satu dashboard.
                    </p>

                    <div class="hero-actions">
                        <a href="pages/rooms.php" class="btn btn-primary btn-lg">Lihat Ruangan</a>
                        <a href="login.php" class="btn btn-outline-primary btn-lg">Masuk</a>
                    </div>

                    <div class="hero-metrics" aria-label="Ringkasan sistem">
                        <div>
                            <strong>5</strong>
                            <span>ruang aktif</span>
                        </div>
                        <div>
                            <strong>08.00</strong>
                            <span>slot awal</span>
                        </div>
                        <div>
                            <strong>3</strong>
                            <span>pending</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-lg-6">
                    <div class="schedule-visual" aria-label="Pratinjau dashboard reservasi">
                        <div class="visual-topline">
                            <div>
                                <span class="panel-kicker">Selasa, 09 Juni</span>
                                <h2>Agenda Ruangan</h2>
                            </div>
                            <span class="live-badge">Live</span>
                        </div>

                        <div class="schedule-grid">
                            <aside class="room-stack">
                                <div class="room-pill active">
                                    <span>Lab Komputer 1</span>
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
                                        Workshop UI
                                    </span>
                                </div>
                                <div class="timeline-row">
                                    <span class="timeline-block pending" style="--start: 2; --span: 2;">
                                        Sidang Tim
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
                                <span class="mini-label">Pengajuan terbaru</span>
                                <strong>Ruang Rapat Departemen</strong>
                            </div>
                            <span class="status-chip">Pending</span>
                        </div>
                    </div>

                    <div class="floating-note">
                        <span class="note-line"></span>
                        <div>
                            <strong>Tanpa bentrok jadwal</strong>
                            <p>Jam mulai dan selesai dicek sebelum reservasi tersimpan.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="campus-strip" aria-label="Contoh ruangan">
                <div class="strip-item">
                    <span class="strip-code">A301</span>
                    <div>
                        <strong>Ruang Kelas</strong>
                        <p>Proyektor, AC, Whiteboard</p>
                    </div>
                </div>
                <div class="strip-item">
                    <span class="strip-code">LAB-1</span>
                    <div>
                        <strong>Lab Komputer</strong>
                        <p>40 PC, WiFi, Proyektor</p>
                    </div>
                </div>
                <div class="strip-item">
                    <span class="strip-code">SEM</span>
                    <div>
                        <strong>Ruang Seminar</strong>
                        <p>Sound system, 80 kursi</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="workflow-band">
                <div class="section-heading">
                    <span class="eyebrow">Alur Penggunaan</span>
                    <h2>Dibuat seperti ritme administrasi kampus: singkat, bisa dicek, lalu disetujui.</h2>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <article class="feature-card">
                            <div class="feature-icon">01</div>
                            <h3>Cari ruang</h3>
                            <p>Filter ruangan berdasarkan kapasitas, lokasi, status, dan fasilitas yang dibutuhkan.</p>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="feature-card">
                            <div class="feature-icon">02</div>
                            <h3>Ajukan jadwal</h3>
                            <p>Isi tanggal, jam mulai, jam selesai, dan tujuan pemakaian dengan validasi langsung.</p>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="feature-card">
                            <div class="feature-icon">03</div>
                            <h3>Pantau status</h3>
                            <p>Admin meninjau reservasi, sementara user melihat status pending, approved, atau rejected.</p>
                        </article>
                    </div>
                </div>

                <div class="system-note">
                    <span>Bootstrap responsive</span>
                    <span>PHP session</span>
                    <span>MySQL CRUD</span>
                    <span>Validasi JavaScript</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section-block section-tight">
        <div class="container">
            <div class="row g-4 align-items-end">
                <div class="col-lg-5">
                    <span class="eyebrow">Untuk Admin</span>
                    <h2 class="section-title">Data yang perlu dinilai dosen tetap terlihat jelas.</h2>
                </div>
                <div class="col-lg-7">
                    <div class="admin-grid">
                        <div class="admin-item">
                            <span>CRUD</span>
                            <strong>Ruangan & reservasi</strong>
                        </div>
                        <div class="admin-item">
                            <span>Keamanan</span>
                            <strong>Session, hash, escape</strong>
                        </div>
                        <div class="admin-item">
                            <span>Responsif</span>
                            <strong>375px sampai desktop</strong>
                        </div>
                        <div class="admin-item">
                            <span>Dokumentasi</span>
                            <strong>README, SQL, screenshot</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
