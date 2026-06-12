<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/room_helpers.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/rooms.php');
}

require_valid_csrf();

$roomId = (int) ($_POST['id'] ?? 0);

if ($roomId <= 0) {
    redirect('pages/rooms.php?error=not_found');
}

$room = fetch_one($conn, "SELECT id FROM rooms WHERE id = ?", 'i', [$roomId]);

if (!$room) {
    redirect('pages/rooms.php?error=not_found');
}

$reservationCount = fetch_count(
    $conn,
    "SELECT COUNT(*) AS total FROM reservations WHERE room_id = ?",
    'i',
    [$roomId]
);

if ($reservationCount > 0) {
    redirect('pages/rooms.php?error=has_reservations');
}

$stmt = mysqli_prepare($conn, "DELETE FROM rooms WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $roomId);

if (!mysqli_stmt_execute($stmt)) {
    redirect('pages/rooms.php?error=delete_failed');
}

redirect('pages/rooms.php?message=deleted');
