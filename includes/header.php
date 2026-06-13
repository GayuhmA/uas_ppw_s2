<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

if (!isset($pageTitle)) {
    $pageTitle = 'Sistem Reservasi Ruangan';
}

$isAppShell = empty($hideNavbar) && is_logged_in();
$bodyClasses = trim(($bodyClass ?? '') . ($isAppShell ? ' has-sidebar' : ''));

if (!function_exists('sidebar_icon')) {
    function sidebar_icon($name)
    {
        $icons = [
            'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4z"></path><path d="M13 4h7v4h-7z"></path><path d="M13 10h7v10h-7z"></path><path d="M4 13h7v7H4z"></path></svg>',
            'rooms' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V5.8c0-.9.6-1.6 1.5-1.8l8-1.7c1.2-.3 2.5.6 2.5 1.9V21"></path><path d="M17 7h2.5c.8 0 1.5.7 1.5 1.5V21"></path><path d="M3 21h18"></path><path d="M13 12h.01"></path></svg>',
            'reservations' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3"></path><path d="M17 3v3"></path><path d="M4 8h16"></path><path d="M5 5h14c.6 0 1 .4 1 1v14c0 .6-.4 1-1 1H5c-.6 0-1-.4-1-1V6c0-.6.4-1 1-1z"></path><path d="m9 15 2 2 4-5"></path></svg>',
            'facilities' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21v-7"></path><path d="M4 10V3"></path><path d="M12 21v-9"></path><path d="M12 8V3"></path><path d="M20 21v-5"></path><path d="M20 12V3"></path><path d="M2 14h4"></path><path d="M10 8h4"></path><path d="M18 16h4"></path></svg>',
            'my-reservations' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3"></path><path d="M17 3v3"></path><path d="M4 8h16"></path><path d="M5 5h14c.6 0 1 .4 1 1v14c0 .6-.4 1-1 1H5c-.6 0-1-.4-1-1V6c0-.6.4-1 1-1z"></path><path d="M8 13h5"></path><path d="M8 17h8"></path></svg>',
        ];

        return $icons[$name] ?? '';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle); ?> - Sistem Reservasi Ruangan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/style.css'); ?>">
</head>
<body class="<?= e($bodyClasses); ?>">
    <?php if (empty($hideNavbar) && !$isAppShell): ?>
    <nav class="navbar navbar-expand-lg app-navbar">
        <div class="container">
            <a class="navbar-brand" href="<?= url('index.php'); ?>">
                <span class="brand-mark">R</span>
                <span>Reservasi Ruang</span>
            </a>

            <div class="public-nav-actions">
                <a class="btn btn-primary btn-sm" href="<?= url('login.php'); ?>">Login</a>
            </div>
        </div>
    </nav>
    <?php elseif ($isAppShell): ?>
    <div class="app-shell">
        <aside class="app-sidebar" aria-label="Navigasi aplikasi">
            <a class="sidebar-brand" href="<?= url('pages/dashboard.php'); ?>">
                <span class="brand-mark">R</span>
                <span>Reservasi Ruang</span>
            </a>

            <button
                class="app-nav-toggle"
                type="button"
                data-app-nav-toggle
                aria-controls="appNavigation"
                aria-expanded="false"
                aria-label="Buka navigasi"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="sidebar-menu" id="appNavigation" data-app-nav-menu>
                <div class="sidebar-context">
                    <span>Kampus</span>
                    <strong>Gedung Perkuliahan</strong>
                </div>

                <span class="sidebar-label">Umum</span>
                <nav class="sidebar-nav">
                    <a class="sidebar-link <?= active_nav('pages/dashboard.php'); ?>" href="<?= url('pages/dashboard.php'); ?>">
                        <span class="sidebar-icon"><?= sidebar_icon('dashboard'); ?></span>
                        <span>Dashboard</span>
                    </a>
                    <a class="sidebar-link <?= active_nav_group(['pages/rooms.php', 'pages/room-detail.php', 'pages/room-create.php', 'pages/room-edit.php']); ?>" href="<?= url('pages/rooms.php'); ?>">
                        <span class="sidebar-icon"><?= sidebar_icon('rooms'); ?></span>
                        <span>Ruangan</span>
                    </a>
                </nav>

                <span class="sidebar-label">Manajemen</span>
                <nav class="sidebar-nav">
                    <?php if (is_admin()): ?>
                        <a class="sidebar-link <?= active_nav_group(['pages/reservations.php', 'pages/reservation-create.php', 'pages/reservation-edit.php', 'pages/reservation-delete.php', 'pages/reservation-status.php']); ?>" href="<?= url('pages/reservations.php'); ?>">
                            <span class="sidebar-icon"><?= sidebar_icon('reservations'); ?></span>
                            <span>Reservasi</span>
                        </a>
                        <a class="sidebar-link <?= active_nav('pages/facilities.php'); ?>" href="<?= url('pages/facilities.php'); ?>">
                            <span class="sidebar-icon"><?= sidebar_icon('facilities'); ?></span>
                            <span>Fasilitas</span>
                        </a>
                    <?php else: ?>
                        <a class="sidebar-link <?= active_nav_group(['pages/reservations.php', 'pages/reservation-create.php', 'pages/reservation-edit.php', 'pages/reservation-delete.php', 'pages/reservation-status.php']); ?>" href="<?= url('pages/reservations.php'); ?>">
                            <span class="sidebar-icon"><?= sidebar_icon('my-reservations'); ?></span>
                            <span>Reservasi Saya</span>
                        </a>
                    <?php endif; ?>
                </nav>

                <div class="sidebar-account">
                    <span class="sidebar-avatar"><?= e(strtoupper(substr($_SESSION['user_name'] ?? 'P', 0, 1))); ?></span>
                    <div>
                        <span><?= e($_SESSION['user_name'] ?? 'Pengguna'); ?></span>
                        <small><?= e($_SESSION['role'] ?? 'user'); ?></small>
                    </div>
                    <a href="<?= url('logout.php'); ?>">Logout</a>
                </div>
            </div>
        </aside>

        <div class="app-shell-main">
    <?php endif; ?>
