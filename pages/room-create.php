<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/room_helpers.php';

require_admin();

$form = [
    'room_name' => '',
    'location' => '',
    'capacity' => '',
    'status' => 'available',
    'description' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = clean_room_input($_POST);
    $errors = validate_room_input($form);

    if (empty($errors)) {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO rooms (room_name, location, capacity, description, status)
             VALUES (?, ?, ?, ?, ?)"
        );
        $capacity = (int) $form['capacity'];
        mysqli_stmt_bind_param(
            $stmt,
            'ssiss',
            $form['room_name'],
            $form['location'],
            $capacity,
            $form['description'],
            $form['status']
        );
        mysqli_stmt_execute($stmt);

        redirect('pages/rooms.php?message=created');
    }
}

$pageTitle = 'Tambah Ruangan';
$bodyClass = 'dashboard-body';
$hideFooter = true;
require_once __DIR__ . '/../includes/header.php';
?>

<main class="dashboard-page">
    <div class="dashboard-shell">
        <header class="content-topbar">
            <div>
                <span class="dashboard-crumb">Ruangan / <strong>Tambah</strong></span>
                <h1>Tambah Ruangan</h1>
                <p>Lengkapi data dasar ruangan agar dapat digunakan pada proses reservasi.</p>
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
                </div>

                <div class="form-actions">
                    <a href="<?= url('pages/rooms.php'); ?>" class="btn btn-outline-primary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Ruangan</button>
                </div>
            </form>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
