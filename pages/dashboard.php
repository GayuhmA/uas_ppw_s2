<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = current_user();
$isAdmin = is_admin();

function dashboard_bind_params($stmt, $types, $params)
{
    if ($types === '' || empty($params)) {
        return;
    }

    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$refs);
}

function dashboard_count($conn, $sql, $types = '', $params = [])
{
    $stmt = mysqli_prepare($conn, $sql);
    dashboard_bind_params($stmt, $types, $params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return (int) ($row['total'] ?? 0);
}

function dashboard_rows($conn, $sql, $types = '', $params = [])
{
    $stmt = mysqli_prepare($conn, $sql);
    dashboard_bind_params($stmt, $types, $params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function dashboard_status_label($status)
{
    $labels = [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
        'completed' => 'Selesai',
    ];

    return $labels[$status] ?? ucfirst((string) $status);
}

function dashboard_date($date)
{
    return date('d M Y', strtotime($date));
}

function dashboard_short_date($date)
{
    return date('d M', strtotime($date));
}

function dashboard_initials($name)
{
    $words = preg_split('/\s+/', trim((string) $name));
    $initials = '';

    foreach ($words as $word) {
        if ($word === '') {
            continue;
        }

        $initials .= strtoupper(substr($word, 0, 1));

        if (strlen($initials) >= 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : 'R';
}

function dashboard_stat_icon($name)
{
    $icons = [
        'rooms' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5c0-.6.4-1 1-1h10c.6 0 1 .4 1 1v16"></path><path d="M16 9h3c.6 0 1 .4 1 1v11"></path><path d="M3 21h18"></path><path d="M8 8h3"></path><path d="M8 12h3"></path><path d="M8 16h3"></path></svg>',
        'pending' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>',
        'today' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3"></path><path d="M17 3v3"></path><path d="M4 8h16"></path><path d="M5 5h14c.6 0 1 .4 1 1v14c0 .6-.4 1-1 1H5c-.6 0-1-.4-1-1V6c0-.6.4-1 1-1z"></path><path d="m9 15 2 2 4-5"></path></svg>',
        'total' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4h8"></path><path d="M9 2h6c.6 0 1 .4 1 1v2H8V3c0-.6.4-1 1-1z"></path><path d="M6 5h12c.6 0 1 .4 1 1v15H5V6c0-.6.4-1 1-1z"></path><path d="M8 11h8"></path><path d="M8 15h8"></path></svg>',
    ];

    return $icons[$name] ?? '';
}

$totalRooms = dashboard_count($conn, "SELECT COUNT(*) AS total FROM v_room_facility_summary");
$availableRooms = dashboard_count($conn, "SELECT COUNT(*) AS total FROM v_room_facility_summary WHERE status = 'available'");
$maintenanceRooms = dashboard_count($conn, "SELECT COUNT(*) AS total FROM v_room_facility_summary WHERE status <> 'available'");
$facilityCount = dashboard_count($conn, "SELECT COUNT(*) AS total FROM facilities");
$availabilityRate = $totalRooms > 0 ? round(($availableRooms / $totalRooms) * 100) : 0;

if ($isAdmin) {
    $pendingReservations = dashboard_count($conn, "SELECT COUNT(*) AS total FROM reservations WHERE status = 'pending'");
    $todayReservations = dashboard_count($conn, "SELECT COUNT(*) AS total FROM reservations WHERE reservation_date = CURDATE()");
    $totalReservations = dashboard_count($conn, "SELECT COUNT(*) AS total FROM reservations");
    $approvedReservations = dashboard_count($conn, "SELECT COUNT(*) AS total FROM reservations WHERE status = 'approved'");
    $completedReservations = dashboard_count($conn, "SELECT COUNT(*) AS total FROM reservations WHERE status = 'completed'");
    $rejectedReservations = dashboard_count($conn, "SELECT COUNT(*) AS total FROM reservations WHERE status IN ('rejected', 'cancelled')");
    $chartRows = dashboard_rows(
        $conn,
        "SELECT reservation_date, COUNT(*) AS total
         FROM reservations
         WHERE reservation_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
         GROUP BY reservation_date"
    );
    $recentReservations = dashboard_rows(
        $conn,
        "SELECT reservations.id, reservations.reservation_date, reservations.start_time, reservations.end_time,
                reservations.purpose, reservations.status, reservations.room_name, reservations.user_name
         FROM v_reservation_details AS reservations
         ORDER BY reservations.created_at DESC, reservations.id DESC
         LIMIT 6"
    );
    $dashboardSubtitle = 'Pantau pengajuan ruangan, jadwal harian, dan kesiapan ruang dari satu tempat.';
    $reservationLabel = 'Pengajuan terbaru';
    $heroActionLabel = 'Tinjau Reservasi';
} else {
    $pendingReservations = dashboard_count(
        $conn,
        "SELECT COUNT(*) AS total FROM reservations WHERE user_id = ? AND status = 'pending'",
        'i',
        [$user['id']]
    );
    $todayReservations = dashboard_count(
        $conn,
        "SELECT COUNT(*) AS total FROM reservations WHERE user_id = ? AND status = 'approved' AND reservation_date >= CURDATE()",
        'i',
        [$user['id']]
    );
    $totalReservations = dashboard_count(
        $conn,
        "SELECT COUNT(*) AS total FROM reservations WHERE user_id = ?",
        'i',
        [$user['id']]
    );
    $approvedReservations = dashboard_count(
        $conn,
        "SELECT COUNT(*) AS total FROM reservations WHERE user_id = ? AND status = 'approved'",
        'i',
        [$user['id']]
    );
    $completedReservations = dashboard_count(
        $conn,
        "SELECT COUNT(*) AS total FROM reservations WHERE user_id = ? AND status = 'completed'",
        'i',
        [$user['id']]
    );
    $rejectedReservations = dashboard_count(
        $conn,
        "SELECT COUNT(*) AS total FROM reservations WHERE user_id = ? AND status IN ('rejected', 'cancelled')",
        'i',
        [$user['id']]
    );
    $chartRows = dashboard_rows(
        $conn,
        "SELECT reservation_date, COUNT(*) AS total
         FROM reservations
         WHERE user_id = ? AND reservation_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
         GROUP BY reservation_date",
        'i',
        [$user['id']]
    );
    $recentReservations = dashboard_rows(
        $conn,
        "SELECT reservations.id, reservations.reservation_date, reservations.start_time, reservations.end_time,
                reservations.purpose, reservations.status, reservations.room_name, reservations.user_name
         FROM v_reservation_details AS reservations
         WHERE reservations.user_id = ?
         ORDER BY reservations.created_at DESC, reservations.id DESC
         LIMIT 6",
        'i',
        [$user['id']]
    );
    $dashboardSubtitle = 'Lihat status pengajuan, jadwal mendatang, dan ruang yang masih tersedia.';
    $reservationLabel = 'Reservasi saya';
    $heroActionLabel = 'Ajukan Reservasi';
}

$chartCounts = [];
foreach ($chartRows as $row) {
    $chartCounts[$row['reservation_date']] = (int) $row['total'];
}

$recentStatusCounts = [
    'all' => count($recentReservations),
    'pending' => 0,
    'approved' => 0,
    'completed' => 0,
    'rejected' => 0,
];

foreach ($recentReservations as $reservation) {
    if ($reservation['status'] === 'cancelled') {
        $recentStatusCounts['rejected'] += 1;
        continue;
    }

    if (isset($recentStatusCounts[$reservation['status']])) {
        $recentStatusCounts[$reservation['status']] += 1;
    }
}

function calculate_timeline_grid($startTime, $endTime) {
    $startH = (int) date('H', strtotime($startTime));
    $endH = (int) date('H', strtotime($endTime));

    $startCol = max(1, min(5, floor(($startH - 8) / 2) + 1));
    $endCol = max(1, min(6, ceil(($endH - 8) / 2) + 1));

    $span = max(1, $endCol - $startCol);
    return ['start' => $startCol, 'span' => $span];
}

$scheduleRooms = [];
$scheduleReservations = [];

if ($isAdmin) {
    $scheduleRooms = dashboard_rows($conn, "SELECT id, room_name, capacity FROM rooms WHERE status = 'available' ORDER BY room_name ASC LIMIT 3");
} else {
    $scheduleRooms = dashboard_rows(
        $conn,
        "SELECT rooms.id, rooms.room_name, rooms.capacity
         FROM reservations
         INNER JOIN rooms ON rooms.id = reservations.room_id
         WHERE reservations.user_id = ?
           AND reservations.reservation_date = CURDATE()
           AND reservations.status IN ('approved', 'pending')
         GROUP BY rooms.id, rooms.room_name, rooms.capacity
         ORDER BY MIN(reservations.start_time) ASC, rooms.room_name ASC",
        'i',
        [$user['id']]
    );
}

$roomIds = array_column($scheduleRooms, 'id');

if (!empty($roomIds)) {
    $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
    $scheduleSql = "SELECT room_id, start_time, end_time, purpose, status
         FROM reservations
         WHERE reservation_date = CURDATE()
         AND room_id IN ($placeholders)
         AND status IN ('approved', 'pending')";
    $scheduleTypes = str_repeat('i', count($roomIds));
    $scheduleParams = $roomIds;

    if (!$isAdmin) {
        $scheduleSql .= " AND user_id = ?";
        $scheduleTypes .= 'i';
        $scheduleParams[] = (int) $user['id'];
    }

    $scheduleSql .= " ORDER BY start_time ASC";
    $scheduleReservations = dashboard_rows($conn, $scheduleSql, $scheduleTypes, $scheduleParams);
}

$schedulePanelTitle = $isAdmin ? 'Jadwal Pemakaian Ruang' : 'Jadwal Saya Hari Ini';
$scheduleEmptyTitle = $isAdmin ? 'Belum ada ruangan yang aktif.' : 'Belum ada jadwal kamu hari ini.';
$scheduleEmptyText = $isAdmin ? '' : 'Reservasi menunggu atau disetujui untuk hari ini akan muncul di sini.';

$roomReservations = [];
foreach ($scheduleReservations as $res) {
    $roomReservations[$res['room_id']][] = $res;
}

$statusTabs = [
    [
        'label' => 'Semua',
        'count' => $recentStatusCounts['all'],
        'statuses' => '',
    ],
    [
        'label' => 'Menunggu',
        'count' => $recentStatusCounts['pending'],
        'statuses' => 'pending',
    ],
    [
        'label' => 'Disetujui',
        'count' => $recentStatusCounts['approved'],
        'statuses' => 'approved',
    ],
    [
        'label' => 'Selesai',
        'count' => $recentStatusCounts['completed'],
        'statuses' => 'completed',
    ],
    [
        'label' => 'Ditolak',
        'count' => $recentStatusCounts['rejected'],
        'statuses' => 'rejected,cancelled',
    ],
];

$statCards = [
    [
        'label' => 'Ruangan tersedia',
        'value' => $availableRooms,
        'hint' => $availabilityRate . '% dari ' . $totalRooms . ' ruangan siap dipakai',
        'tone' => 'primary',
        'icon' => 'rooms',
    ],
    [
        'label' => 'Menunggu konfirmasi',
        'value' => $pendingReservations,
        'hint' => $isAdmin ? 'perlu ditinjau admin' : 'sedang diproses',
        'tone' => 'warning',
        'icon' => 'pending',
    ],
    [
        'label' => $isAdmin ? 'Reservasi hari ini' : 'Jadwal disetujui',
        'value' => $todayReservations,
        'hint' => $isAdmin ? 'aktif di tanggal ini' : 'jadwal mendatang',
        'tone' => 'success',
        'icon' => 'today',
    ],
    [
        'label' => 'Total reservasi',
        'value' => $totalReservations,
        'hint' => $isAdmin ? 'seluruh pengajuan tercatat' : 'riwayat pengajuan kamu',
        'tone' => 'neutral',
        'icon' => 'total',
    ],
];

$pageTitle = 'Dashboard';
$bodyClass = 'dashboard-body';
$hideFooter = true;
require_once __DIR__ . '/../includes/header.php';
?>

<main class="dashboard-page">
    <div class="dashboard-shell">
        <header class="dashboard-topbar">
            <div>
                <span class="dashboard-crumb">Halaman / <strong>Dashboard</strong></span>
                <h1>Dashboard Reservasi</h1>
            </div>
            <div class="dashboard-topbar-actions">
                <span class="topbar-chip"><?= date('d M Y'); ?></span>
            </div>
        </header>

        <section class="dashboard-hero-card">
            <div>
                <span><?= $isAdmin ? 'Dashboard Admin' : 'Dashboard Pengguna'; ?></span>
                <h2>Selamat datang, <?= e($user['name']) ?>.</h2>
                <p><?= e($dashboardSubtitle) ?></p>
            </div>
            <a href="<?= url('pages/reservations.php') ?>" class="btn btn-light dashboard-hero-action"><?= e($heroActionLabel); ?></a>
        </section>

        <div class="dashboard-stats">
            <?php foreach ($statCards as $card): ?>
                <article class="dashboard-stat <?= e('stat-' . $card['tone']) ?>">
                    <div class="stat-icon"><?= dashboard_stat_icon($card['icon']); ?></div>
                    <span><?= e($card['label']) ?></span>
                    <strong><?= e($card['value']) ?></strong>
                    <p><?= e($card['hint']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-analytics">
            <section class="dashboard-panel schedule-panel">
                <div class="panel-header">
                    <div>
                        <span class="panel-kicker">Hari Ini</span>
                        <h2><?= e($schedulePanelTitle); ?></h2>
                    </div>
                    <span class="live-badge">Aktif</span>
                </div>

                <?php if (empty($scheduleRooms)): ?>
                    <div class="empty-state" style="margin: 2rem;">
                        <strong><?= e($scheduleEmptyTitle); ?></strong>
                        <?php if ($scheduleEmptyText !== ''): ?>
                            <p><?= e($scheduleEmptyText); ?></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="schedule-grid" style="padding: 0 1rem 1.25rem;">
                        <aside class="room-stack">
                            <?php foreach ($scheduleRooms as $index => $room): ?>
                                <button type="button" class="room-pill <?= $index === 0 ? 'active' : ''; ?>" data-schedule-target="room-<?= e($room['id']); ?>" style="text-align: left; width: 100%; border-width: 1px; border-style: solid; cursor: pointer;">
                                    <span><?= e($room['room_name']); ?></span>
                                    <small><?= e($room['capacity']); ?> kursi</small>
                                </button>
                            <?php endforeach; ?>
                        </aside>

                        <div class="time-board">
                            <div class="time-labels">
                                <span>08</span>
                                <span>10</span>
                                <span>13</span>
                                <span>15</span>
                            </div>

                            <?php foreach ($scheduleRooms as $index => $room): ?>
                                <div class="timeline-row" id="room-<?= e($room['id']); ?>" style="display: <?= $index === 0 ? 'grid' : 'none'; ?>;">
                                    <?php
                                        $roomRes = $roomReservations[$room['id']] ?? [];
                                        if (empty($roomRes)):
                                    ?>
                                        <span class="timeline-block open" style="--start: 1; --span: 5;">
                                            Tersedia
                                        </span>
                                    <?php else: ?>
                                        <?php foreach ($roomRes as $res): ?>
                                            <?php
                                                $grid = calculate_timeline_grid($res['start_time'], $res['end_time']);
                                                $statusClass = $res['status'] === 'approved' ? 'approved' : 'pending';
                                            ?>
                                            <span class="timeline-block <?= $statusClass; ?>" style="--start: <?= $grid['start']; ?>; --span: <?= $grid['span']; ?>;" title="<?= e($res['purpose']); ?>">
                                                <?= e($res['purpose']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="dashboard-panel insight-panel">
                <div class="panel-header">
                    <div>
                        <span class="panel-kicker">Kesiapan</span>
                        <h2>Ruang siap dipakai</h2>
                    </div>
                </div>
                <div class="availability-meter" style="--meter: <?= e($availabilityRate); ?>%;">
                    <strong><?= e($availabilityRate); ?>%</strong>
                    <span><?= e($availableRooms); ?> dari <?= e($totalRooms); ?> ruangan</span>
                </div>
                <div class="insight-list">
                    <div>
                        <span>Perlu perhatian</span>
                        <strong><?= e($maintenanceRooms); ?> ruang</strong>
                    </div>
                    <div>
                        <span>Fasilitas tercatat</span>
                        <strong><?= e($facilityCount); ?> item</strong>
                    </div>
                    <div>
                        <span>Status disetujui</span>
                        <strong><?= e($approvedReservations); ?> reservasi</strong>
                    </div>
                </div>
            </section>
        </div>

        <section class="dashboard-panel reservations-panel">
            <div class="panel-header panel-header-spread">
                <div>
                    <span class="panel-kicker">Reservasi</span>
                    <h2><?= e($reservationLabel) ?></h2>
                </div>
                <a href="<?= url('pages/reservations.php') ?>" class="btn btn-outline-primary btn-sm">Lihat semua</a>
            </div>

            <div class="reservation-tabs" data-reservation-filters>
                <?php foreach ($statusTabs as $index => $tab): ?>
                    <button
                        type="button"
                        class="<?= $index === 0 ? 'active' : ''; ?>"
                        data-status-filter="<?= e($tab['statuses']); ?>"
                        aria-pressed="<?= $index === 0 ? 'true' : 'false'; ?>"
                    >
                        <?= e($tab['label']); ?> <strong><?= e($tab['count']); ?></strong>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php if (empty($recentReservations)): ?>
                <div class="empty-state">
                    <strong>Belum ada reservasi.</strong>
                    <p>Data reservasi terbaru akan muncul di sini setelah pengajuan dibuat.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table dashboard-table align-middle">
                        <thead>
                            <tr>
                                <th>Ruangan</th>
                                <?php if ($isAdmin): ?>
                                    <th>Pemohon</th>
                                <?php endif; ?>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Tujuan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentReservations as $reservation): ?>
                                <tr data-reservation-row data-status="<?= e($reservation['status']); ?>">
                                    <td>
                                        <strong><?= e($reservation['room_name']) ?></strong>
                                    </td>
                                    <?php if ($isAdmin): ?>
                                        <td><?= e($reservation['user_name']) ?></td>
                                    <?php endif; ?>
                                    <td><?= e(dashboard_date($reservation['reservation_date'])) ?></td>
                                    <td>
                                        <?= e(substr($reservation['start_time'], 0, 5)) ?>
                                        -
                                        <?= e(substr($reservation['end_time'], 0, 5)) ?>
                                    </td>
                                    <td><?= e($reservation['purpose']) ?></td>
                                    <td>
                                        <span class="reservation-status status-<?= e($reservation['status']) ?>">
                                            <?= e(dashboard_status_label($reservation['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="empty-state filter-empty-state" data-filter-empty hidden>
                    <strong>Data tidak ditemukan.</strong>
                    <p>Tidak ada reservasi terbaru dengan status yang dipilih.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
