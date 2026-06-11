<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/room_helpers.php';

require_admin();

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
$facilityName = '';
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $facilityName = trim($_POST['facility_name'] ?? '');

        if ($facilityName === '') {
            $formError = 'Nama fasilitas wajib diisi.';
        } elseif (fetch_count(
            $conn,
            "SELECT COUNT(*) AS total FROM facilities WHERE LOWER(facility_name) = LOWER(?)",
            's',
            [$facilityName]
        ) > 0) {
            $formError = 'Fasilitas dengan nama tersebut sudah ada.';
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO facilities (facility_name) VALUES (?)");
            mysqli_stmt_bind_param($stmt, 's', $facilityName);

            if (!mysqli_stmt_execute($stmt)) {
                redirect('pages/facilities.php?error=duplicate');
            }

            redirect('pages/facilities.php?message=created');
        }
    }

    if ($action === 'delete') {
        $facilityId = (int) ($_POST['id'] ?? 0);

        if ($facilityId <= 0) {
            redirect('pages/facilities.php?error=not_found');
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM facilities WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $facilityId);

        if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) < 1) {
            redirect('pages/facilities.php?error=not_found');
        }

        redirect('pages/facilities.php?message=deleted');
    }
}

$facilities = fetch_all_rows(
    $conn,
    "SELECT facilities.id, facilities.facility_name, COUNT(room_facilities.room_id) AS room_total
     FROM facilities
     LEFT JOIN room_facilities ON room_facilities.facility_id = facilities.id
     GROUP BY facilities.id, facilities.facility_name
     ORDER BY facilities.facility_name ASC"
);

$messages = [
    'created' => 'Fasilitas berhasil ditambahkan.',
    'deleted' => 'Fasilitas berhasil dihapus.',
];

$errors = [
    'duplicate' => 'Fasilitas gagal ditambahkan. Gunakan nama fasilitas yang berbeda.',
    'not_found' => 'Fasilitas tidak ditemukan.',
];

$pageTitle = 'Fasilitas';
$bodyClass = 'dashboard-body';
$hideFooter = true;
require_once __DIR__ . '/../includes/header.php';
?>

<main class="dashboard-page">
    <div class="dashboard-shell">
        <header class="content-topbar">
            <div>
                <span class="dashboard-crumb">Halaman / <strong>Fasilitas</strong></span>
                <h1>Kelola Fasilitas</h1>
                <p>Tambahkan fasilitas yang dapat dipilih saat mengelola data ruangan.</p>
            </div>
            <a href="<?= url('pages/rooms.php'); ?>" class="btn btn-outline-primary">Lihat Ruangan</a>
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

        <section class="facility-layout">
            <article class="dashboard-panel form-panel">
                <div class="panel-header">
                    <div>
                        <span class="panel-kicker">Fasilitas</span>
                        <h2>Tambah fasilitas</h2>
                    </div>
                </div>
                <form method="post" data-validate class="facility-create-form">
                    <input type="hidden" name="action" value="create">
                    <div class="form-field">
                        <label for="facility_name">Nama Fasilitas</label>
                        <input
                            type="text"
                            id="facility_name"
                            name="facility_name"
                            class="form-control <?= $formError !== '' ? 'is-invalid-lite' : ''; ?>"
                            value="<?= e($facilityName); ?>"
                            data-required
                            data-message="Nama fasilitas wajib diisi."
                        >
                        <?php if ($formError !== ''): ?>
                            <span class="form-error"><?= e($formError); ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Tambah Fasilitas</button>
                </form>
            </article>

            <section class="dashboard-panel">
                <div class="panel-header">
                    <div>
                        <span class="panel-kicker">Daftar</span>
                        <h2>Fasilitas tersedia</h2>
                    </div>
                    <span class="range-pill"><?= e(count($facilities)); ?> fasilitas</span>
                </div>

                <?php if (empty($facilities)): ?>
                    <div class="empty-state">
                        <strong>Belum ada fasilitas.</strong>
                        <p>Tambahkan fasilitas agar dapat dipakai pada form ruangan.</p>
                    </div>
                <?php else: ?>
                    <div class="facility-table-list">
                        <?php foreach ($facilities as $facility): ?>
                            <div class="facility-row">
                                <div>
                                    <strong><?= e($facility['facility_name']); ?></strong>
                                    <span><?= e($facility['room_total']); ?> ruangan memakai fasilitas ini</span>
                                </div>
                                <form method="post">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e($facility['id']); ?>">
                                    <button type="submit" class="btn btn-danger-lite btn-sm" data-confirm="Hapus fasilitas ini?">Hapus</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
