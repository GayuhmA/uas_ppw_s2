<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
$user = current_user();
?>

<main class="page-wrapper py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Selamat datang, <?= e($user['name']) ?>!</h1>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <p class="text-muted mb-0">Ini adalah halaman dashboard awal untuk melihat aktivitas dan reservasi Anda.</p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
