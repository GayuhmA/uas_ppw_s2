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
$totalFacilities = count($facilities);
$linkedRoomTotal = 0;

foreach ($facilities as $facility) {
    $linkedRoomTotal += (int) $facility['room_total'];
}

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
        <header class="content-topbar facility-topbar">
            <div>
                <span class="dashboard-crumb">Halaman / <strong>Fasilitas</strong></span>
                <h1>Kelola Fasilitas</h1>
                <p>Atur fasilitas yang akan muncul pada data ruangan dan form reservasi.</p>
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
            <article class="dashboard-panel facility-form-panel">
                <div class="panel-header">
                    <div>
                        <span class="panel-kicker">Input</span>
                        <h2>Tambah fasilitas</h2>
                        <p class="panel-subtitle">Nama yang dibuat di sini langsung tersedia di form tambah dan edit ruangan.</p>
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
                            placeholder="Contoh: Proyektor, AC, Wi-Fi"
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

            <section class="dashboard-panel facility-list-panel">
                <div class="panel-header">
                    <div>
                        <span class="panel-kicker">Daftar</span>
                        <h2>Fasilitas tersedia</h2>
                        <p class="panel-subtitle">Pantau fasilitas mana yang sudah dipakai ruangan dan rapikan data yang belum relevan.</p>
                    </div>
                    <span class="range-pill"><?= e($totalFacilities); ?> fasilitas</span>
                </div>

                <?php if (empty($facilities)): ?>
                    <div class="empty-state facility-empty-state">
                        <strong>Belum ada fasilitas.</strong>
                        <p>Tambahkan fasilitas agar dapat dipakai pada form ruangan.</p>
                    </div>
                <?php else: ?>
                    <div class="facility-card-grid">
                        <?php foreach ($facilities as $facility): ?>
                            <?php
                                $roomTotal = (int) $facility['room_total'];
                                $usagePercent = $linkedRoomTotal > 0 ? min(100, max(8, (int) round(($roomTotal / max(1, $linkedRoomTotal)) * 100))) : 8;
                            ?>
                            <article class="facility-card">
                                <div class="facility-card-main">
                                    <span class="facility-card-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M4 21v-7"></path>
                                            <path d="M4 10V3"></path>
                                            <path d="M12 21v-9"></path>
                                            <path d="M12 8V3"></path>
                                            <path d="M20 21v-5"></path>
                                            <path d="M20 12V3"></path>
                                            <path d="M2 14h4"></path>
                                            <path d="M10 8h4"></path>
                                            <path d="M18 16h4"></path>
                                        </svg>
                                    </span>
                                    <div>
                                        <span class="facility-card-label"><?= $roomTotal > 0 ? 'Terpakai' : 'Belum dipakai'; ?></span>
                                        <h3><?= e($facility['facility_name']); ?></h3>
                                    </div>
                                </div>

                                <div class="facility-usage">
                                    <div>
                                        <span>Pemakaian</span>
                                        <strong><?= e($roomTotal); ?> ruangan</strong>
                                    </div>
                                    <span class="facility-usage-track" aria-hidden="true">
                                        <span style="width: <?= e($usagePercent); ?>%;"></span>
                                    </span>
                                </div>

                                <div class="facility-card-footer">
                                    <span><?= $roomTotal > 0 ? 'Aktif pada data ruangan' : 'Belum terhubung'; ?></span>
                                    <form method="post">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e($facility['id']); ?>">
                                        <button type="submit" class="facility-delete-btn" data-confirm="Hapus fasilitas ini?">Hapus</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
