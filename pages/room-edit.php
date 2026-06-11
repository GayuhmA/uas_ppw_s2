<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/room_helpers.php';

require_admin();

$roomId = (int) ($_GET['id'] ?? 0);

if ($roomId <= 0) {
    redirect('pages/rooms.php?error=not_found');
}

$room = fetch_one(
    $conn,
    "SELECT id, room_name, location, capacity, description, status
     FROM rooms
     WHERE id = ?",
    'i',
    [$roomId]
);

if (!$room) {
    redirect('pages/rooms.php?error=not_found');
}

$form = [
    'room_name' => $room['room_name'],
    'location' => $room['location'],
    'capacity' => $room['capacity'],
    'status' => $room['status'],
    'description' => $room['description'] ?? '',
];
$facilities = fetch_facilities($conn);
$selectedFacilities = array_map(
    'intval',
    array_column(
        fetch_all_rows($conn, "SELECT facility_id FROM room_facilities WHERE room_id = ?", 'i', [$roomId]),
        'facility_id'
    )
);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = clean_room_input($_POST);
    $selectedFacilities = clean_facility_ids($_POST['facility_ids'] ?? []);
    $errors = validate_room_input($form);

    if (!validate_facility_ids($conn, $selectedFacilities)) {
        $errors['facility_ids'] = 'Pilihan fasilitas tidak valid.';
    }

    if (empty($errors)) {
        mysqli_begin_transaction($conn);

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE rooms
             SET room_name = ?, location = ?, capacity = ?, description = ?, status = ?
             WHERE id = ?"
        );
        $capacity = (int) $form['capacity'];
        mysqli_stmt_bind_param(
            $stmt,
            'ssissi',
            $form['room_name'],
            $form['location'],
            $capacity,
            $form['description'],
            $form['status'],
            $roomId
        );
        mysqli_stmt_execute($stmt);
        sync_room_facilities($conn, $roomId, $selectedFacilities);
        mysqli_commit($conn);

        redirect('pages/rooms.php?message=updated');
    }
}

$pageTitle = 'Edit Ruangan';
$bodyClass = 'dashboard-body';
$hideFooter = true;
require_once __DIR__ . '/../includes/header.php';
?>

<main class="dashboard-page">
    <div class="dashboard-shell">
        <header class="content-topbar">
            <div>
                <span class="dashboard-crumb">Ruangan / <strong>Edit</strong></span>
                <h1>Edit Ruangan</h1>
                <p>Perbarui informasi ruangan dengan data yang paling sesuai.</p>
            </div>
            <a href="<?= url('pages/rooms.php'); ?>" class="btn btn-outline-primary">Kembali</a>
        </header>

        <section class="dashboard-panel form-panel">
            <form method="post" data-validate>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="room_name">Nama Ruangan</label>
                        <input
                            type="text"
                            id="room_name"
                            name="room_name"
                            class="form-control <?= isset($errors['room_name']) ? 'is-invalid-lite' : ''; ?>"
                            value="<?= e($form['room_name']); ?>"
                            data-required
                            data-message="Nama ruangan wajib diisi."
                        >
                        <?php if (isset($errors['room_name'])): ?>
                            <span class="form-error"><?= e($errors['room_name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="location">Lokasi</label>
                        <input
                            type="text"
                            id="location"
                            name="location"
                            class="form-control <?= isset($errors['location']) ? 'is-invalid-lite' : ''; ?>"
                            value="<?= e($form['location']); ?>"
                            data-required
                            data-message="Lokasi ruangan wajib diisi."
                        >
                        <?php if (isset($errors['location'])): ?>
                            <span class="form-error"><?= e($errors['location']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="capacity">Kapasitas</label>
                        <input
                            type="number"
                            id="capacity"
                            name="capacity"
                            min="1"
                            class="form-control <?= isset($errors['capacity']) ? 'is-invalid-lite' : ''; ?>"
                            value="<?= e($form['capacity']); ?>"
                            data-required
                            data-message="Kapasitas wajib diisi."
                        >
                        <?php if (isset($errors['capacity'])): ?>
                            <span class="form-error"><?= e($errors['capacity']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <?php foreach (room_status_options() as $value => $label): ?>
                                <option value="<?= e($value); ?>" <?= $form['status'] === $value ? 'selected' : ''; ?>>
                                    <?= e($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <span class="form-error"><?= e($errors['status']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-field form-field-wide">
                        <label for="description">Deskripsi</label>
                        <textarea id="description" name="description" class="form-control" rows="4"><?= e($form['description']); ?></textarea>
                    </div>

                    <div class="form-field form-field-wide">
                        <span class="form-label-text">Fasilitas</span>
                        <div class="facility-options">
                            <?php foreach ($facilities as $facility): ?>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="facility_ids[]"
                                        value="<?= e($facility['id']); ?>"
                                        <?= in_array((int) $facility['id'], $selectedFacilities, true) ? 'checked' : ''; ?>
                                    >
                                    <span><?= e($facility['facility_name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (isset($errors['facility_ids'])): ?>
                            <span class="form-error"><?= e($errors['facility_ids']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="<?= url('pages/rooms.php'); ?>" class="btn btn-outline-primary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
