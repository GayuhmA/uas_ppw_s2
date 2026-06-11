<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/reservation_helpers.php';

require_login();

$rooms = fetch_all_rows(
    $conn,
    "SELECT id, room_name, location, capacity
     FROM rooms
     WHERE status = 'available'
     ORDER BY room_name ASC"
);
$form = [
    'room_id' => (int) ($_GET['room_id'] ?? 0),
    'reservation_date' => '',
    'start_time' => '',
    'end_time' => '',
    'purpose' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = clean_reservation_input($_POST);
    $errors = validate_reservation_input($conn, $form);

    if (empty($errors)) {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO reservations (user_id, room_id, reservation_date, start_time, end_time, purpose, status)
             VALUES (?, ?, ?, ?, ?, ?, 'pending')"
        );
        $userId = (int) $_SESSION['user_id'];
        mysqli_stmt_bind_param(
            $stmt,
            'iissss',
            $userId,
            $form['room_id'],
            $form['reservation_date'],
            $form['start_time'],
            $form['end_time'],
            $form['purpose']
        );
        mysqli_stmt_execute($stmt);

        redirect('pages/reservations.php?message=created');
    }
}

$pageTitle = 'Ajukan Reservasi';
$bodyClass = 'dashboard-body';
$hideFooter = true;
require_once __DIR__ . '/../includes/header.php';
?>

<main class="dashboard-page">
    <div class="dashboard-shell">
        <header class="content-topbar">
            <div>
                <span class="dashboard-crumb">Reservasi / <strong>Ajukan</strong></span>
                <h1>Ajukan Reservasi</h1>
                <p>Pilih ruangan dan jadwal penggunaan. Pengajuan akan masuk dengan status menunggu.</p>
            </div>
            <a href="<?= url('pages/reservations.php'); ?>" class="btn btn-outline-primary">Kembali</a>
        </header>

        <section class="reservation-form-layout">
            <article class="dashboard-panel form-panel">
                <form method="post" data-validate data-reservation-form>
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
                        <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
                    </div>
                </form>
            </article>

            <aside class="dashboard-panel reservation-help-panel">
                <div class="panel-header">
                    <div>
                        <span class="panel-kicker">Catatan</span>
                        <h2>Alur reservasi</h2>
                    </div>
                </div>
                <div class="reservation-help-list">
                    <div>
                        <strong>1</strong>
                        <span>Pilih ruangan yang tersedia.</span>
                    </div>
                    <div>
                        <strong>2</strong>
                        <span>Isi tanggal, jam, dan tujuan penggunaan.</span>
                    </div>
                    <div>
                        <strong>3</strong>
                        <span>Admin meninjau pengajuan sebelum digunakan.</span>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
