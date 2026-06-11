<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/reservation_helpers.php';

require_login();

$isAdmin = is_admin();
$currentUser = current_user();
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;
$statusOptions = reservation_status_options();

if ($status !== '' && !isset($statusOptions[$status])) {
    $status = '';
}

$conditions = [];
$types = '';
$params = [];

if (!$isAdmin) {
    $conditions[] = 'reservations.user_id = ?';
    $types .= 'i';
    $params[] = (int) $currentUser['id'];
}

if ($search !== '') {
    $conditions[] = '(rooms.room_name LIKE ? OR users.name LIKE ? OR reservations.purpose LIKE ?)';
    $types .= 'sss';
    $keyword = '%' . $search . '%';
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}

if ($status !== '') {
    $conditions[] = 'reservations.status = ?';
    $types .= 's';
    $params[] = $status;
}

$whereSql = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
$totalReservations = fetch_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM reservations
     INNER JOIN rooms ON rooms.id = reservations.room_id
     INNER JOIN users ON users.id = reservations.user_id
     $whereSql",
    $types,
    $params
);
$totalPages = max(1, (int) ceil($totalReservations / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$reservations = fetch_all_rows(
    $conn,
    "SELECT reservations.id, reservations.reservation_date, reservations.start_time, reservations.end_time,
            reservations.purpose, reservations.status, rooms.room_name, rooms.location, users.name AS user_name
     FROM reservations
     INNER JOIN rooms ON rooms.id = reservations.room_id
     INNER JOIN users ON users.id = reservations.user_id
     $whereSql
     ORDER BY reservations.reservation_date DESC, reservations.start_time DESC, reservations.id DESC
     LIMIT ? OFFSET ?",
    $types . 'ii',
    array_merge($params, [$perPage, $offset])
);

$messages = [
    'created' => 'Reservasi berhasil diajukan.',
    'cancelled' => 'Reservasi berhasil dibatalkan.',
    'deleted' => 'Reservasi berhasil dihapus.',
    'status_updated' => 'Status reservasi berhasil diperbarui.',
    'updated' => 'Reservasi berhasil diperbarui.',
];

$errors = [
    'not_found' => 'Reservasi tidak ditemukan.',
    'forbidden' => 'Aksi tidak dapat dilakukan untuk reservasi ini.',
    'status_conflict' => 'Status tidak dapat diperbarui karena jadwal bentrok.',
];

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
$queryBase = [
    'search' => $search,
    'status' => $status,
];

$pageTitle = $isAdmin ? 'Reservasi' : 'Reservasi Saya';
$bodyClass = 'dashboard-body';
$hideFooter = true;
require_once __DIR__ . '/../includes/header.php';
?>

<main class="dashboard-page">
    <div class="dashboard-shell">
        <header class="content-topbar">
            <div>
                <span class="dashboard-crumb">Halaman / <strong>Reservasi</strong></span>
                <h1><?= $isAdmin ? 'Kelola Reservasi' : 'Reservasi Saya'; ?></h1>
                <p><?= $isAdmin ? 'Tinjau pengajuan ruangan dan kelola status reservasi.' : 'Ajukan dan pantau status reservasi ruangan Anda.'; ?></p>
            </div>
            <a href="<?= url('pages/reservation-create.php'); ?>" class="btn btn-primary">Ajukan Reservasi</a>
        </header>

        <?php if (isset($messages[$message]) || isset($errors[$error])): ?>
            <div class="toast-stack" aria-live="polite" aria-atomic="true">
                <?php if (isset($messages[$message])): ?>
                    <div class="app-toast toast-success" data-toast>
                        <span class="toast-icon"></span>
                        <p><?= e($messages[$message]); ?></p>
                        <button type="button" aria-label="Tutup notifikasi" data-toast-close>&times;</button>
                    </div>
                <?php endif; ?>

                <?php if (isset($errors[$error])): ?>
                    <div class="app-toast toast-danger" data-toast>
                        <span class="toast-icon"></span>
                        <p><?= e($errors[$error]); ?></p>
                        <button type="button" aria-label="Tutup notifikasi" data-toast-close>&times;</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <section class="dashboard-panel reservations-panel">
            <div class="panel-header reservation-toolbar">
                <div>
                    <span class="panel-kicker">Daftar</span>
                    <h2><?= e($totalReservations); ?> reservasi ditemukan</h2>
                </div>
                <form method="get" class="room-search-form reservation-search-form">
                    <input
                        type="search"
                        name="search"
                        class="form-control"
                        value="<?= e($search); ?>"
                        placeholder="cari disini"
                    >
                    <select name="status" class="form-select">
                        <option value="">Semua status</option>
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= e($value); ?>" <?= $status === $value ? 'selected' : ''; ?>>
                                <?= e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline-primary">Terapkan</button>
                </form>
            </div>

            <?php if (empty($reservations)): ?>
                <div class="empty-state">
                    <strong>Belum ada reservasi.</strong>
                    <p>Data reservasi akan muncul setelah pengajuan dibuat.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table dashboard-table">
                        <thead>
                            <tr>
                                <?php if ($isAdmin): ?>
                                    <th>Pemohon</th>
                                <?php endif; ?>
                                <th>Ruangan</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Tujuan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <?php if ($isAdmin): ?>
                                        <td><?= e($reservation['user_name']); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <strong><?= e($reservation['room_name']); ?></strong>
                                        <span class="table-muted"><?= e($reservation['location']); ?></span>
                                    </td>
                                    <td><?= e(date('d M Y', strtotime($reservation['reservation_date']))); ?></td>
                                    <td><?= e(substr($reservation['start_time'], 0, 5)); ?> - <?= e(substr($reservation['end_time'], 0, 5)); ?></td>
                                    <td><?= e($reservation['purpose']); ?></td>
                                    <td>
                                        <span class="reservation-status status-<?= e($reservation['status']); ?>">
                                            <?= e(reservation_status_label($reservation['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <?php if ($isAdmin): ?>
                                                <a href="<?= url('pages/reservation-edit.php?id=' . $reservation['id']); ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                                                <a href="<?= url('pages/reservation-status.php?id=' . $reservation['id']); ?>" class="btn btn-outline-primary btn-sm">Status</a>
                                                <form method="post" action="<?= url('pages/reservation-delete.php'); ?>">
                                                    <input type="hidden" name="id" value="<?= e($reservation['id']); ?>">
                                                    <button type="submit" class="btn btn-danger-lite btn-sm" data-confirm="Hapus reservasi ini?">Hapus</button>
                                                </form>
                                            <?php elseif ($reservation['status'] === 'pending'): ?>
                                                <a href="<?= url('pages/reservation-edit.php?id=' . $reservation['id']); ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                                                <form method="post" action="<?= url('pages/reservation-delete.php'); ?>">
                                                    <input type="hidden" name="id" value="<?= e($reservation['id']); ?>">
                                                    <button type="submit" class="btn btn-danger-lite btn-sm" data-confirm="Batalkan reservasi ini?">Batalkan</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="table-muted">Tidak ada aksi</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination-bar" aria-label="Navigasi halaman reservasi">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php $pageQuery = http_build_query(array_merge($queryBase, ['page' => $i])); ?>
                            <a class="<?= $i === $page ? 'active' : ''; ?>" href="<?= url('pages/reservations.php?' . $pageQuery); ?>">
                                <?= e($i); ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
