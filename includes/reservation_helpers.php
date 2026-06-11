<?php
require_once __DIR__ . '/room_helpers.php';

function reservation_status_options()
{
    return [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
        'completed' => 'Selesai',
    ];
}

function clean_reservation_input($data)
{
    return [
        'room_id' => (int) ($data['room_id'] ?? 0),
        'reservation_date' => trim($data['reservation_date'] ?? ''),
        'start_time' => trim($data['start_time'] ?? ''),
        'end_time' => trim($data['end_time'] ?? ''),
        'purpose' => trim($data['purpose'] ?? ''),
    ];
}

function reservation_time_is_valid($startTime, $endTime)
{
    return $startTime !== '' && $endTime !== '' && strtotime($startTime) < strtotime($endTime);
}

function reservation_has_conflict($conn, $roomId, $reservationDate, $startTime, $endTime, $excludeReservationId = 0)
{
    $sql = "SELECT COUNT(*) AS total
            FROM reservations
            WHERE room_id = ?
              AND reservation_date = ?
              AND status IN ('pending', 'approved')
              AND start_time < ?
              AND end_time > ?";
    $types = 'isss';
    $params = [$roomId, $reservationDate, $endTime, $startTime];

    if ($excludeReservationId > 0) {
        $sql .= " AND id <> ?";
        $types .= 'i';
        $params[] = $excludeReservationId;
    }

    return fetch_count($conn, $sql, $types, $params) > 0;
}

function validate_reservation_input($conn, $data, $excludeReservationId = 0)
{
    $errors = [];

    if ($data['room_id'] <= 0) {
        $errors['room_id'] = 'Ruangan wajib dipilih.';
    } else {
        $room = fetch_one(
            $conn,
            "SELECT id, status FROM rooms WHERE id = ?",
            'i',
            [$data['room_id']]
        );

        if (!$room) {
            $errors['room_id'] = 'Ruangan tidak ditemukan.';
        } elseif ($room['status'] !== 'available') {
            $errors['room_id'] = 'Ruangan belum tersedia untuk reservasi.';
        }
    }

    if ($data['reservation_date'] === '') {
        $errors['reservation_date'] = 'Tanggal reservasi wajib diisi.';
    }

    if ($data['start_time'] === '') {
        $errors['start_time'] = 'Jam mulai wajib diisi.';
    }

    if ($data['end_time'] === '') {
        $errors['end_time'] = 'Jam selesai wajib diisi.';
    }

    if ($data['start_time'] !== '' && $data['end_time'] !== '' && !reservation_time_is_valid($data['start_time'], $data['end_time'])) {
        $errors['end_time'] = 'Jam selesai harus setelah jam mulai.';
    }

    if ($data['purpose'] === '') {
        $errors['purpose'] = 'Tujuan penggunaan wajib diisi.';
    } elseif (strlen($data['purpose']) < 5) {
        $errors['purpose'] = 'Tujuan penggunaan minimal 5 karakter.';
    }

    if (
        empty($errors)
        && reservation_has_conflict(
            $conn,
            $data['room_id'],
            $data['reservation_date'],
            $data['start_time'],
            $data['end_time'],
            $excludeReservationId
        )
    ) {
        $errors['reservation_date'] = 'Jadwal ruangan sudah dipakai pada waktu tersebut.';
    }

    return $errors;
}

function fetch_reservation_detail($conn, $reservationId)
{
    return fetch_one(
        $conn,
        "SELECT reservations.id, reservations.user_id, reservations.room_id, reservations.reservation_date,
                reservations.start_time, reservations.end_time, reservations.purpose, reservations.status,
                rooms.room_name, rooms.location, users.name AS user_name, users.email AS user_email
         FROM reservations
         INNER JOIN rooms ON rooms.id = reservations.room_id
         INNER JOIN users ON users.id = reservations.user_id
         WHERE reservations.id = ?",
        'i',
        [$reservationId]
    );
}

function insert_reservation_log($conn, $reservationId, $oldStatus, $newStatus, $changedBy, $note = '')
{
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO reservation_logs (reservation_id, old_status, new_status, changed_by, note)
         VALUES (?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'issis', $reservationId, $oldStatus, $newStatus, $changedBy, $note);
    mysqli_stmt_execute($stmt);
}
