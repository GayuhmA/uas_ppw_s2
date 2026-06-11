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
