<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/reservation_helpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/reservations.php');
}

require_valid_csrf();

$reservationId = (int) ($_POST['id'] ?? 0);

if ($reservationId <= 0) {
    redirect('pages/reservations.php?error=not_found');
}

$reservation = fetch_reservation_detail($conn, $reservationId);

if (!$reservation) {
    redirect('pages/reservations.php?error=not_found');
}

if (is_admin()) {
    if (!reservation_can_cancel_by_admin($reservation['status'])) {
        redirect('pages/reservations.php?error=forbidden');
    }

    mysqli_begin_transaction($conn);
    set_reservation_audit_context($conn, (int) $_SESSION['user_id'], 'Dibatalkan oleh admin.');

    $stmt = mysqli_prepare($conn, "UPDATE reservations SET status = 'cancelled' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $reservationId);
    mysqli_stmt_execute($stmt);

    clear_reservation_audit_context($conn);
    mysqli_commit($conn);

    redirect('pages/reservations.php?message=cancelled');
}

if ((int) $reservation['user_id'] !== (int) $_SESSION['user_id'] || !reservation_can_cancel_by_owner($reservation['status'])) {
    redirect('pages/reservations.php?error=forbidden');
}

mysqli_begin_transaction($conn);
set_reservation_audit_context($conn, (int) $_SESSION['user_id'], 'Dibatalkan oleh pemohon.');

$stmt = mysqli_prepare($conn, "UPDATE reservations SET status = 'cancelled' WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $reservationId);
mysqli_stmt_execute($stmt);

clear_reservation_audit_context($conn);
mysqli_commit($conn);

redirect('pages/reservations.php?message=cancelled');
