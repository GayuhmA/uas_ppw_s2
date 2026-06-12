<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/reservation_helpers.php';

require_admin();

$reservationId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($reservationId <= 0) {
    redirect('pages/reservations.php?error=not_found');
}

$reservation = fetch_reservation_detail($conn, $reservationId);

if (!$reservation) {
    redirect('pages/reservations.php?error=not_found');
}

$statusOptions = reservation_status_transition_options($reservation['status']);
$formStatus = $reservation['status'];
$note = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $formStatus = trim($_POST['status'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if (!isset($statusOptions[$formStatus])) {
        $errors['status'] = 'Perubahan status tidak valid untuk kondisi reservasi saat ini.';
    }

    if (
        empty($errors)
        && $formStatus === 'approved'
        && reservation_has_conflict(
            $conn,
            (int) $reservation['room_id'],
            $reservation['reservation_date'],
            $reservation['start_time'],
            $reservation['end_time'],
            $reservationId,
            ['approved']
        )
    ) {
        $errors['status'] = 'Reservasi tidak dapat disetujui karena jadwal bentrok.';
    }

    if (empty($errors)) {
        mysqli_begin_transaction($conn);

        if ($formStatus !== $reservation['status']) {
            $stmt = mysqli_prepare($conn, "UPDATE reservations SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $formStatus, $reservationId);
            mysqli_stmt_execute($stmt);
        }

        if ($formStatus !== $reservation['status'] || $note !== '') {
            insert_reservation_log($conn, $reservationId, $reservation['status'], $formStatus, (int) $_SESSION['user_id'], $note);
        }

        mysqli_commit($conn);
        redirect('pages/reservations.php?message=status_updated');
    }
}

$pageTitle = 'Ubah Status Reservasi';
$bodyClass = 'dashboard-body';
$hideFooter = true;
require_once __DIR__ . '/../includes/header.php';
?>

<main class="dashboard-page">
    <div class="dashboard-shell">
        <header class="content-topbar">
            <div>
                <span class="dashboard-crumb">Reservasi / <strong>Status</strong></span>
                <h1>Ubah Status Reservasi</h1>
                <p>Perbarui keputusan pengajuan ruangan dengan data yang sudah ditinjau.</p>
            </div>
            <a href="<?= url('pages/reservations.php'); ?>" class="btn btn-outline-primary">Kembali</a>
        </header>

        <section class="reservation-form-layout">
            <article class="dashboard-panel form-panel">
                <form method="post" data-validate>
                    <?= csrf_field(); ?>
                    <input type="hidden" name="id" value="<?= e($reservation['id']); ?>">
                    <div class="reservation-summary-box">
                        <span class="panel-kicker">Detail pengajuan</span>
                        <h2><?= e($reservation['room_name']); ?></h2>
                        <dl>
                            <div>
                                <dt>Pemohon</dt>
                                <dd><?= e($reservation['user_name']); ?></dd>
                            </div>
                            <div>
                                <dt>Tanggal</dt>
                                <dd><?= e(date('d M Y', strtotime($reservation['reservation_date']))); ?></dd>
                            </div>
                            <div>
                                <dt>Waktu</dt>
                                <dd><?= e(substr($reservation['start_time'], 0, 5)); ?> - <?= e(substr($reservation['end_time'], 0, 5)); ?></dd>
                            </div>
                            <div>
                                <dt>Status saat ini</dt>
                                <dd>
                                    <span class="reservation-status status-<?= e($reservation['status']); ?>">
                                        <?= e(reservation_status_label($reservation['status'])); ?>
                                    </span>
                                </dd>
                            </div>
                        </dl>
                        <p><?= e($reservation['purpose']); ?></p>
                    </div>

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="status">Status Baru</label>
                            <select
                                id="status"
                                name="status"
                                class="form-select <?= isset($errors['status']) ? 'is-invalid-lite' : ''; ?>"
                                data-required
                                data-message="Status wajib dipilih."
                            >
                                <?php foreach ($statusOptions as $value => $label): ?>
                                    <option value="<?= e($value); ?>" <?= $formStatus === $value ? 'selected' : ''; ?>>
                                        <?= e($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                                <span class="form-error"><?= e($errors['status']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-field form-field-wide">
                            <label for="note">Catatan</label>
                            <textarea id="note" name="note" class="form-control" rows="3"><?= e($note); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="<?= url('pages/reservations.php'); ?>" class="btn btn-outline-primary">Batal</a>
                        <button type="submit" class="btn btn-primary" data-confirm="Simpan perubahan status reservasi?">Simpan Status</button>
                    </div>
                </form>
            </article>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
