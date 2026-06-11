<?php

function bind_params_if_needed($stmt, $types, $params)
{
    if ($types === '' || empty($params)) {
        return;
    }

    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$refs);
}

function fetch_one($conn, $sql, $types = '', $params = [])
{
    $stmt = mysqli_prepare($conn, $sql);
    bind_params_if_needed($stmt, $types, $params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($result) ?: null;
}

function fetch_all_rows($conn, $sql, $types = '', $params = [])
{
    $stmt = mysqli_prepare($conn, $sql);
    bind_params_if_needed($stmt, $types, $params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function fetch_count($conn, $sql, $types = '', $params = [])
{
    $row = fetch_one($conn, $sql, $types, $params);

    return (int) ($row['total'] ?? 0);
}

function room_status_label($status)
{
    $labels = [
        'available' => 'Tersedia',
        'maintenance' => 'Perawatan',
        'unavailable' => 'Tidak Tersedia',
    ];

    return $labels[$status] ?? ucfirst((string) $status);
}

function room_status_options()
{
    return [
        'available' => 'Tersedia',
        'maintenance' => 'Perawatan',
        'unavailable' => 'Tidak Tersedia',
    ];
}

function reservation_status_label($status)
{
    $labels = [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
        'completed' => 'Selesai',
    ];

    return $labels[$status] ?? ucfirst((string) $status);
}

function fetch_facilities($conn)
{
    return fetch_all_rows(
        $conn,
        "SELECT id, facility_name
         FROM facilities
         ORDER BY facility_name ASC"
    );
}

function clean_facility_ids($facilityIds)
{
    if (!is_array($facilityIds)) {
        return [];
    }

    $cleanIds = [];
    foreach ($facilityIds as $facilityId) {
        $id = (int) $facilityId;

        if ($id > 0) {
            $cleanIds[$id] = $id;
        }
    }

    return array_values($cleanIds);
}

function validate_facility_ids($conn, $facilityIds)
{
    if (empty($facilityIds)) {
        return true;
    }

    $placeholders = implode(',', array_fill(0, count($facilityIds), '?'));
    $types = str_repeat('i', count($facilityIds));
    $count = fetch_count(
        $conn,
        "SELECT COUNT(*) AS total FROM facilities WHERE id IN ($placeholders)",
        $types,
        $facilityIds
    );

    return $count === count($facilityIds);
}

function sync_room_facilities($conn, $roomId, $facilityIds)
{
    $deleteStmt = mysqli_prepare($conn, "DELETE FROM room_facilities WHERE room_id = ?");
    mysqli_stmt_bind_param($deleteStmt, 'i', $roomId);
    mysqli_stmt_execute($deleteStmt);

    if (empty($facilityIds)) {
        return;
    }

    $insertStmt = mysqli_prepare(
        $conn,
        "INSERT INTO room_facilities (room_id, facility_id) VALUES (?, ?)"
    );

    foreach ($facilityIds as $facilityId) {
        mysqli_stmt_bind_param($insertStmt, 'ii', $roomId, $facilityId);
        mysqli_stmt_execute($insertStmt);
    }
}

function fetch_room_facility_map($conn, $roomIds)
{
    $roomIds = clean_facility_ids($roomIds);

    if (empty($roomIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
    $types = str_repeat('i', count($roomIds));
    $rows = fetch_all_rows(
        $conn,
        "SELECT room_facilities.room_id, facilities.facility_name
         FROM room_facilities
         INNER JOIN facilities ON facilities.id = room_facilities.facility_id
         WHERE room_facilities.room_id IN ($placeholders)
         ORDER BY facilities.facility_name ASC",
        $types,
        $roomIds
    );
    $facilityMap = [];

    foreach ($rows as $row) {
        $facilityMap[(int) $row['room_id']][] = $row['facility_name'];
    }

    return $facilityMap;
}

function clean_room_input($data)
{
    return [
        'room_name' => trim($data['room_name'] ?? ''),
        'location' => trim($data['location'] ?? ''),
        'capacity' => trim($data['capacity'] ?? ''),
        'status' => trim($data['status'] ?? 'available'),
        'description' => trim($data['description'] ?? ''),
    ];
}

function validate_room_input($data)
{
    $errors = [];
    $statusOptions = room_status_options();

    if ($data['room_name'] === '') {
        $errors['room_name'] = 'Nama ruangan wajib diisi.';
    }

    if ($data['location'] === '') {
        $errors['location'] = 'Lokasi ruangan wajib diisi.';
    }

    if ($data['capacity'] === '' || filter_var($data['capacity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $errors['capacity'] = 'Kapasitas harus berupa angka lebih dari 0.';
    }

    if (!isset($statusOptions[$data['status']])) {
        $errors['status'] = 'Status ruangan tidak valid.';
    }

    return $errors;
}
