<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/room_helpers.php';

require_login();

$isAdmin = is_admin();
$query = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

$whereSql = '';
$types = '';
$params = [];

if ($query !== '') {
    $whereSql = 'WHERE room_name LIKE ? OR location LIKE ?';
    $keyword = '%' . $query . '%';
    $types = 'ss';
    $params = [$keyword, $keyword];
}

$totalRooms = fetch_count($conn, "SELECT COUNT(*) AS total FROM rooms $whereSql", $types, $params);
$totalPages = max(1, (int) ceil($totalRooms / $perPage));

if ($page > $totalPages) {
    redirect('pages/rooms.php' . ($query !== '' ? '?q=' . urlencode($query) : ''));
}

$rooms = fetch_all_rows(
    $conn,
    "SELECT id, room_name, location, capacity, description, status
     FROM rooms
     $whereSql
     ORDER BY created_at DESC, id DESC
     LIMIT ? OFFSET ?",
    $types . 'ii',
    array_merge($params, [$perPage, $offset])
);
$roomIds = array_column($rooms, 'id');
$roomFacilityMap = fetch_room_facility_map($conn, $roomIds);

$availableRooms = fetch_count($conn, "SELECT COUNT(*) AS total FROM rooms WHERE status = 'available'");
$maintenanceRooms = fetch_count($conn, "SELECT COUNT(*) AS total FROM rooms WHERE status <> 'available'");

$messages = [
    'created' => 'Ruangan berhasil ditambahkan.',
    'updated' => 'Data ruangan berhasil diperbarui.',
    'deleted' => 'Ruangan berhasil dihapus.',
];

$errors = [
    'not_found' => 'Ruangan tidak ditemukan.',
    'has_reservations' => 'Ruangan tidak dapat dihapus karena sudah memiliki data reservasi.',
    'delete_failed' => 'Ruangan gagal dihapus.',
];

$pageTitle = 'Ruangan';
$bodyClass = 'dashboard-body';
$hideFooter = true;
require_once __DIR__ . '/../includes/header.php';
?>

<main class="dashboard-page">
    <div class="dashboard-shell">
        <header class="content-topbar">
            <div>
                <span class="dashboard-crumb">Halaman / <strong>Ruangan</strong></span>
                <h1>Daftar Ruangan</h1>
                <p>Kelola data ruangan, kapasitas, lokasi, dan status ketersediaannya.</p>
            </div>
            <?php if ($isAdmin): ?>
                <a href="<?= url('pages/room-create.php'); ?>" class="btn btn-primary">Tambah Ruangan</a>
            <?php endif; ?>
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

        <section class="room-summary-grid" aria-label="Ringkasan ruangan">
            <article>
                <span>Total ruangan</span>
                <strong><?= e($totalRooms); ?></strong>
            </article>
            <article>
                <span>Siap dipakai</span>
                <strong><?= e($availableRooms); ?></strong>
            </article>
            <article>
                <span>Perlu perhatian</span>
                <strong><?= e($maintenanceRooms); ?></strong>
            </article>
        </section>

        <section class="dashboard-panel rooms-panel">
            <div class="panel-header room-toolbar">
                <div>
                    <span class="panel-kicker">Ruangan</span>
                    <h2>Data ruangan kampus</h2>
                </div>
                <form class="room-search-form" method="get" action="<?= url('pages/rooms.php'); ?>">
                    <label class="visually-hidden" for="room-search">Cari ruangan</label>
                    <input
                        id="room-search"
                        type="search"
                        name="q"
                        class="form-control"
                        value="<?= e($query); ?>"
                        placeholder="Cari nama atau lokasi"
                    >
                    <button type="submit" class="btn btn-outline-primary">Cari</button>
                </form>
            </div>

            <?php if (empty($rooms)): ?>
                <div class="empty-state">
                    <strong>Ruangan tidak ditemukan.</strong>
                    <p>Coba gunakan kata kunci lain atau tambahkan data ruangan baru.</p>
                </div>
            <?php else: ?>
                <div class="rooms-grid">
                    <?php foreach ($rooms as $room): ?>
                        <article class="room-card">
                            <div class="room-card-topline">
                                <span class="room-status status-room-<?= e($room['status']); ?>">
                                    <?= e(room_status_label($room['status'])); ?>
                                </span>
                                <span class="room-capacity"><?= e($room['capacity']); ?> orang</span>
                            </div>
                            <h3><?= e($room['room_name']); ?></h3>
                            <p class="room-location"><?= e($room['location']); ?></p>
                            <p class="room-description">
                                <?= e($room['description'] ?: 'Deskripsi ruangan belum tersedia.'); ?>
                            </p>
                            <div class="room-facility-list">
                                <?php if (empty($roomFacilityMap[$room['id']])): ?>
                                    <span>Fasilitas belum dicatat</span>
                                <?php else: ?>
                                    <?php foreach (array_slice($roomFacilityMap[$room['id']], 0, 4) as $facilityName): ?>
                                        <span><?= e($facilityName); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="room-card-actions">
                                <a href="<?= url('pages/room-detail.php?id=' . $room['id']); ?>" class="btn btn-outline-primary btn-sm">Detail</a>
                                <?php if ($isAdmin): ?>
                                    <a href="<?= url('pages/room-edit.php?id=' . $room['id']); ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                                    <form method="post" action="<?= url('pages/room-delete.php'); ?>">
                                        <input type="hidden" name="id" value="<?= e($room['id']); ?>">
                                        <button type="submit" class="btn btn-danger-lite btn-sm" data-confirm="Hapus ruangan ini?">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination-bar" aria-label="Navigasi halaman ruangan">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php
                        $queryString = http_build_query(array_filter([
                            'q' => $query !== '' ? $query : null,
                            'page' => $i,
                        ]));
                    ?>
                    <a class="<?= $i === $page ? 'active' : ''; ?>" href="<?= url('pages/rooms.php?' . $queryString); ?>">
                        <?= e($i); ?>
                    </a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
