<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/room_helpers.php';

require_login();

$isAdmin = is_admin();
$currentUser = current_user();
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

$selectedDate = date('Y-m-d');
$selectedDateRaw = trim($_GET['date'] ?? '');

if (
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDateRaw)
    && strtotime($selectedDateRaw) !== false
    && $selectedDateRaw >= date('Y-m-d')
) {
    $selectedDate = $selectedDateRaw;
}

function room_detail_timeline_grid($startTime, $endTime)
{
    $dayStart = 8 * 60;
    $dayEnd = 22 * 60;
    $startParts = explode(':', $startTime);
    $endParts = explode(':', $endTime);
    $startMinutes = ((int) ($startParts[0] ?? 0) * 60) + (int) ($startParts[1] ?? 0);
    $endMinutes = ((int) ($endParts[0] ?? 0) * 60) + (int) ($endParts[1] ?? 0);

    if ($endMinutes <= $dayStart || $startMinutes >= $dayEnd) {
        return null;
    }

    $startMinutes = max($dayStart, $startMinutes);
    $endMinutes = min($dayEnd, $endMinutes);
    $start = (int) floor(($startMinutes - $dayStart) / 30) + 1;
    $end = (int) ceil(($endMinutes - $dayStart) / 30) + 1;

    return [
        'start' => max(1, min(28, $start)),
        'span' => max(1, min(29, $end) - max(1, min(28, $start))),
    ];
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

$availabilityReservations = fetch_all_rows(
    $conn,
    "SELECT user_id, start_time, end_time, purpose, status
     FROM reservations
     WHERE room_id = ?
       AND reservation_date = ?
       AND status IN ('pending', 'approved')
     ORDER BY start_time ASC",
    'is',
    [$roomId, $selectedDate]
);

$upcomingReservations = fetch_all_rows(
    $conn,
    "SELECT user_id, reservation_date, start_time, end_time, purpose, status
     FROM reservations
     WHERE room_id = ?
       AND reservation_date >= CURDATE()
       AND status IN ('pending', 'approved')
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
                <?php if ($isAdmin): ?>
                    <a href="<?= url('pages/room-edit.php?id=' . $room['id']); ?>" class="btn btn-primary">Edit Ruangan</a>
                <?php elseif ($room['status'] === 'available'): ?>
                    <a href="<?= url('pages/reservation-create.php?room_id=' . $room['id'] . '&reservation_date=' . urlencode($selectedDate)); ?>" class="btn btn-primary">Ajukan Ruangan Ini</a>
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
                    <?php if (!$isAdmin): ?>
                        <div class="room-description-block">
                            <span>Catatan jadwal</span>
                            <p>Status ruangan menunjukkan kesiapan fasilitas. Ketersediaan tanggal dan jam akan diperiksa saat pengajuan dikirim.</p>
                        </div>
                    <?php endif; ?>
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

        <section class="dashboard-panel room-availability-panel">
            <div class="panel-header room-availability-header">
                <div>
                    <span class="panel-kicker">Ketersediaan</span>
                    <h2>Jadwal <?= e(date('d M Y', strtotime($selectedDate))); ?></h2>
                    <p class="panel-subtitle">Area hijau menunjukkan waktu yang masih kosong. Blok berwarna menandai jadwal yang sedang menunggu atau sudah disetujui.</p>
                </div>
                <form method="get" class="room-availability-form">
                    <input type="hidden" name="id" value="<?= e($room['id']); ?>">
                    <label class="visually-hidden" for="availability-date">Pilih tanggal</label>
                    <input
                        type="date"
                        id="availability-date"
                        name="date"
                        class="form-control"
                        value="<?= e($selectedDate); ?>"
                        min="<?= e(date('Y-m-d')); ?>"
                    >
                    <button type="submit" class="btn btn-outline-primary btn-sm">Lihat</button>
                    <?php if (!$isAdmin && $room['status'] === 'available'): ?>
                        <a href="<?= url('pages/reservation-create.php?room_id=' . $room['id'] . '&reservation_date=' . urlencode($selectedDate)); ?>" class="btn btn-primary btn-sm">Ajukan</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="availability-board">
                <div class="availability-labels" aria-hidden="true">
                    <span>08</span>
                    <span>10</span>
                    <span>12</span>
                    <span>14</span>
                    <span>16</span>
                    <span>18</span>
                    <span>20</span>
                    <span>22</span>
                </div>
                <div class="availability-row">
                    <span class="availability-open">Tersedia</span>
                    <?php foreach ($availabilityReservations as $reservation): ?>
                        <?php
                            $grid = room_detail_timeline_grid($reservation['start_time'], $reservation['end_time']);

                            if ($grid === null) {
                                continue;
                            }

                            $canSeePurpose = $isAdmin || (int) $reservation['user_id'] === (int) $currentUser['id'];
                            $label = $canSeePurpose ? $reservation['purpose'] : reservation_status_label($reservation['status']);
                            $timeRange = substr($reservation['start_time'], 0, 5) . ' - ' . substr($reservation['end_time'], 0, 5);
                        ?>
                        <span
                            class="availability-block <?= e('availability-' . $reservation['status']); ?>"
                            style="--start: <?= e($grid['start']); ?>; --span: <?= e($grid['span']); ?>;"
                            title="<?= e($timeRange . ' - ' . $label); ?>"
                        >
                            <?= e($timeRange); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="dashboard-panel reservations-panel">
            <div class="panel-header">
                <div>
                    <span class="panel-kicker">Jadwal</span>
                    <h2>Jadwal aktif mendatang</h2>
                </div>
            </div>

            <?php if (empty($upcomingReservations)): ?>
                <div class="empty-state">
                    <strong>Belum ada jadwal mendatang.</strong>
                    <p>Ruangan ini belum memiliki reservasi menunggu atau disetujui.</p>
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
                                <?php
                                    $canSeePurpose = $isAdmin || (int) $reservation['user_id'] === (int) $currentUser['id'];
                                    $purposeText = $canSeePurpose ? $reservation['purpose'] : reservation_status_label($reservation['status']);
                                ?>
                                <tr>
                                    <td><?= e(date('d M Y', strtotime($reservation['reservation_date']))); ?></td>
                                    <td><?= e(substr($reservation['start_time'], 0, 5)); ?> - <?= e(substr($reservation['end_time'], 0, 5)); ?></td>
                                    <td><?= e($purposeText); ?></td>
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
