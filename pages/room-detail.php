<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/room_helpers.php';

require_login();

$roomId = (int) ($_GET['id'] ?? 0);

if ($roomId <= 0) {
    redirect('pages/rooms.php?error=not_found');
}

$room = fetch_one(
    $conn,
    "SELECT id, room_name, location, capacity, description, status, created_at
     FROM rooms
     WHERE id = ?",
    'i',
    [$roomId]
);

if (!$room) {
    redirect('pages/rooms.php?error=not_found');
}

$facilities = fetch_all_rows(
    $conn,
    "SELECT facilities.facility_name
     FROM room_facilities
     INNER JOIN facilities ON facilities.id = room_facilities.facility_id
     WHERE room_facilities.room_id = ?
     ORDER BY facilities.facility_name ASC",
    'i',
    [$roomId]
);

$upcomingReservations = fetch_all_rows(
    $conn,
    "SELECT reservation_date, start_time, end_time, purpose, status
     FROM reservations
     WHERE room_id = ? AND reservation_date >= CURDATE()
     ORDER BY reservation_date ASC, start_time ASC
     LIMIT 5",
    'i',
    [$roomId]
);

$pageTitle = 'Detail Ruangan';
$bodyClass = 'dashboard-body';
$hideFooter = true;
require_once __DIR__ . '/../includes/header.php';
?>

<main class="dashboard-page">
    <div class="dashboard-shell">
        <header class="content-topbar">
            <div>
                <span class="dashboard-crumb">Ruangan / <strong>Detail</strong></span>
                <h1><?= e($room['room_name']); ?></h1>
                <p><?= e($room['location']); ?></p>
            </div>
            <div class="content-actions">
                <a href="<?= url('pages/rooms.php'); ?>" class="btn btn-outline-primary">Kembali</a>
                <?php if (is_admin()): ?>
                    <a href="<?= url('pages/room-edit.php?id=' . $room['id']); ?>" class="btn btn-primary">Edit Ruangan</a>
                <?php endif; ?>
            </div>
        </header>

        <section class="room-detail-layout">
            <article class="dashboard-panel room-detail-card">
                <div class="panel-header">
                    <div>
                        <span class="panel-kicker">Informasi</span>
                        <h2>Profil ruangan</h2>
                    </div>
                    <span class="room-status status-room-<?= e($room['status']); ?>">
                        <?= e(room_status_label($room['status'])); ?>
                    </span>
                </div>
                <div class="room-detail-body">
                    <dl class="room-detail-list">
                        <div>
                            <dt>Kapasitas</dt>
                            <dd><?= e($room['capacity']); ?> orang</dd>
                        </div>
                        <div>
                            <dt>Lokasi</dt>
                            <dd><?= e($room['location']); ?></dd>
                        </div>
                        <div>
                            <dt>Dibuat</dt>
                            <dd><?= e(date('d M Y', strtotime($room['created_at']))); ?></dd>
                        </div>
                    </dl>
                    <div class="room-description-block">
                        <span>Deskripsi</span>
                        <p><?= e($room['description'] ?: 'Deskripsi ruangan belum tersedia.'); ?></p>
                    </div>
                </div>
            </article>

            <aside class="dashboard-panel room-side-panel">
                <div class="panel-header">
                    <div>
                        <span class="panel-kicker">Fasilitas</span>
                        <h2>Kelengkapan</h2>
                    </div>
                </div>
                <div class="facility-badges">
                    <?php if (empty($facilities)): ?>
                        <span>Belum ada fasilitas tercatat</span>
                    <?php else: ?>
                        <?php foreach ($facilities as $facility): ?>
                            <span><?= e($facility['facility_name']); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </aside>
        </section>

        <section class="dashboard-panel reservations-panel">
            <div class="panel-header">
                <div>
                    <span class="panel-kicker">Jadwal</span>
                    <h2>Reservasi mendatang</h2>
                </div>
            </div>

            <?php if (empty($upcomingReservations)): ?>
                <div class="empty-state">
                    <strong>Belum ada jadwal mendatang.</strong>
                    <p>Ruangan ini belum memiliki reservasi yang akan datang.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table dashboard-table align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Tujuan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingReservations as $reservation): ?>
                                <tr>
                                    <td><?= e(date('d M Y', strtotime($reservation['reservation_date']))); ?></td>
                                    <td><?= e(substr($reservation['start_time'], 0, 5)); ?> - <?= e(substr($reservation['end_time'], 0, 5)); ?></td>
                                    <td><?= e($reservation['purpose']); ?></td>
                                    <td>
                                        <span class="reservation-status status-<?= e($reservation['status']); ?>">
                                            <?= e(reservation_status_label($reservation['status'] ?? 'pending')); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
