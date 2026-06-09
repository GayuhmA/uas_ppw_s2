<?php
require_once __DIR__ . '/helpers.php';

if (!isset($pageTitle)) {
    $pageTitle = 'Sistem Reservasi Ruangan';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle); ?> - Sistem Reservasi Ruangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/style.css'); ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg app-navbar">
        <div class="container">
            <a class="navbar-brand" href="<?= url('index.php'); ?>">
                <span class="brand-mark">R</span>
                <span>Reservasi Ruang</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= active_nav('index.php'); ?>" href="<?= url('index.php'); ?>">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= active_nav('pages/rooms.php'); ?>" href="<?= url('pages/rooms.php'); ?>">Ruangan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= active_nav('login.php'); ?>" href="<?= url('login.php'); ?>">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
