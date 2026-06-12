<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/reservation_helpers.php';

require_login();

$reservationId = (int) ($_GET['id'] ?? 0);

if ($reservationId <= 0) {
    redirect('pages/reservations.php?error=not_found');
}

$reservation = fetch_reservation_detail($conn, $reservationId);

if (!$reservation) {
    redirect('pages/reservations.php?error=not_found');
}

$isOwner = (int) $reservation['user_id'] === (int) $_SESSION['user_id'];

if (!reservation_can_edit_details($reservation['status']) || (!is_admin() && !$isOwner)) {
    redirect('pages/reservations.php?error=forbidden');
}

$rooms = fetch_all_rows(
    $conn,
    "SELECT id, room_name, location, capacity
     FROM rooms
     WHERE status = 'available' OR id = ?
     ORDER BY room_name ASC",
    'i',
    [(int) $reservation['room_id']]
);
$form = [
    'room_id' => (int) $reservation['room_id'],
    'reservation_date' => $reservation['reservation_date'],
    'start_time' => substr($reservation['start_time'], 0, 5),
    'end_time' => substr($reservation['end_time'], 0, 5),
    'purpose' => $reservation['purpose'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $form = clean_reservation_input($_POST);
    $errors = validate_reservation_input($conn, $form, $reservationId);

    if (empty($errors)) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE reservations
             SET room_id = ?, reservation_date = ?, start_time = ?, end_time = ?, purpose = ?
             WHERE id = ?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            'issssi',
            $form['room_id'],
            $form['reservation_date'],
            $form['start_time'],
            $form['end_time'],
            $form['purpose'],
            $reservationId
        );
        mysqli_stmt_execute($stmt);

        redirect('pages/reservations.php?message=updated');
    }
}

$pageTitle = 'Edit Reservasi';
$bodyClass = 'dashboard-body';
$hideFooter = true;
require_once __DIR__ . '/../includes/header.php';
?>

<main class="dashboard-page">
    <div class="dashboard-shell">
        <header class="content-topbar">
            <div>
                <span class="dashboard-crumb">Reservasi / <strong>Edit</strong></span>
                <h1>Edit Reservasi</h1>
                <p>Perbarui ruangan, jadwal, atau tujuan penggunaan reservasi.</p>
            </div>
            <a href="<?= url('pages/reservations.php'); ?>" class="btn btn-outline-primary">Kembali</a>
        </header>

        <section class="dashboard-panel form-panel">
            <form method="post" data-validate data-reservation-form>
                <?= csrf_field(); ?>
                <div class="form-grid">
                    <div class="form-field form-field-wide">
                        <label for="room_id">Ruangan</label>
                        <select
                            id="room_id"
                            name="room_id"
                            class="form-select <?= isset($errors['room_id']) ? 'is-invalid-lite' : ''; ?>"
                            data-required
                            data-message="Ruangan wajib dipilih."
                        >
                            <option value="">Pilih ruangan</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= e($room['id']); ?>" <?= (int) $form['room_id'] === (int) $room['id'] ? 'selected' : ''; ?>>
                                    <?= e($room['room_name']); ?> - <?= e($room['location']); ?> (<?= e($room['capacity']); ?> orang)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['room_id'])): ?>
                            <span class="form-error"><?= e($errors['room_id']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="reservation_date">Tanggal</label>
                        <input
                            type="date"
                            id="reservation_date"
                            name="reservation_date"
                            class="form-control <?= isset($errors['reservation_date']) ? 'is-invalid-lite' : ''; ?>"
                            value="<?= e($form['reservation_date']); ?>"
                            min="<?= e(date('Y-m-d')); ?>"
                            data-required
                            data-message="Tanggal reservasi wajib diisi."
                        >
                        <?php if (isset($errors['reservation_date'])): ?>
                            <span class="form-error"><?= e($errors['reservation_date']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="start_time">Jam Mulai</label>
                        <input
                            type="time"
                            id="start_time"
                            name="start_time"
                            class="form-control <?= isset($errors['start_time']) ? 'is-invalid-lite' : ''; ?>"
                            value="<?= e($form['start_time']); ?>"
                            data-required
                            data-message="Jam mulai wajib diisi."
                        >
                        <?php if (isset($errors['start_time'])): ?>
                            <span class="form-error"><?= e($errors['start_time']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="end_time">Jam Selesai</label>
                        <input
                            type="time"
                            id="end_time"
                            name="end_time"
                            class="form-control <?= isset($errors['end_time']) ? 'is-invalid-lite' : ''; ?>"
                            value="<?= e($form['end_time']); ?>"
                            data-required
                            data-after-field="start_time"
                            data-after-message="Jam selesai harus setelah jam mulai."
                            data-message="Jam selesai wajib diisi."
                        >
                        <?php if (isset($errors['end_time'])): ?>
                            <span class="form-error"><?= e($errors['end_time']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field form-field-wide">
                        <label for="purpose">Tujuan Penggunaan</label>
                        <textarea
                            id="purpose"
                            name="purpose"
                            class="form-control <?= isset($errors['purpose']) ? 'is-invalid-lite' : ''; ?>"
                            rows="4"
                            data-required
                            data-min-length="5"
                            data-min-message="Tujuan penggunaan minimal 5 karakter."
                            data-message="Tujuan penggunaan wajib diisi."
                        ><?= e($form['purpose']); ?></textarea>
                        <?php if (isset($errors['purpose'])): ?>
                            <span class="form-error"><?= e($errors['purpose']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="<?= url('pages/reservations.php'); ?>" class="btn btn-outline-primary">Batal</a>
                    <button type="submit" class="btn btn-primary" data-confirm="Simpan perubahan reservasi?">Simpan Perubahan</button>
                </div>
            </form>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
