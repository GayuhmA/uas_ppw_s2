CREATE DATABASE IF NOT EXISTS db_reservasi_ruangan;
USE db_reservasi_ruangan;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(100) NOT NULL,
    location VARCHAR(150) NOT NULL,
    capacity INT NOT NULL,
    description TEXT NULL,
    status ENUM('available','maintenance','unavailable') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facility_name VARCHAR(100) NOT NULL
);

CREATE TABLE room_facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    facility_id INT NOT NULL,
    UNIQUE (room_id, facility_id),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

CREATE TABLE reservation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    old_status VARCHAR(30) NOT NULL,
    new_status VARCHAR(30) NOT NULL,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    note VARCHAR(255) NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

-- Dummy Data for Users (admin123 for admin, user123 for users)
INSERT INTO users (name, email, password, role) VALUES
('Administrator', 'admin@kampus.ac.id', '$2y$10$BCcbJBZud.7juWTHJBKSqug7ok1JP7fhUDHnm9YBPwuXPFBzFyx0S', 'admin'),
('Mahasiswa Satu', 'mahasiswa1@kampus.ac.id', '$2y$10$YxyuODVb3hoIBffN0k12YOU3y.RCF9uu2VrPiiBBKr53EfryYKGmS', 'user'),
('Mahasiswa Dua', 'mahasiswa2@kampus.ac.id', '$2y$10$YxyuODVb3hoIBffN0k12YOU3y.RCF9uu2VrPiiBBKr53EfryYKGmS', 'user');

-- Dummy Data for Rooms
INSERT INTO rooms (room_name, location, capacity, description, status) VALUES
('Ruang Kelas A301', 'Gedung A Lantai 3', 40, 'Ruang kelas standar untuk perkuliahan.', 'available'),
('Laboratorium Komputer 1', 'Gedung B Lantai 1', 30, 'Lab dengan 30 PC dan koneksi internet cepat.', 'available'),
('Ruang Seminar', 'Gedung C Lantai 2', 100, 'Ruang luas untuk seminar atau workshop.', 'available'),
('Ruang Rapat Departemen', 'Gedung D Lantai 1', 15, 'Ruang rapat khusus dosen dan staf.', 'available'),
('Aula Mini', 'Gedung Pusat', 200, 'Aula serbaguna untuk acara besar mahasiswa.', 'maintenance');

-- Dummy Data for Facilities
INSERT INTO facilities (facility_name) VALUES
('Proyektor'), ('AC'), ('WiFi'), ('Whiteboard'), ('Sound System');

-- Dummy Data for Room Facilities
INSERT INTO room_facilities (room_id, facility_id) VALUES
(1, 1), (1, 2), (1, 4),
(2, 1), (2, 2), (2, 3), (2, 4),
(3, 1), (3, 2), (3, 5),
(4, 2), (4, 3), (4, 4),
(5, 2), (5, 5);

-- Dummy Data for Reservations
INSERT INTO reservations (user_id, room_id, reservation_date, start_time, end_time, purpose, status) VALUES
(2, 1, CURDATE() + INTERVAL 1 DAY, '08:00:00', '10:00:00', 'Kuliah Pengganti Pemrograman Web', 'approved'),
(3, 2, CURDATE() + INTERVAL 2 DAY, '13:00:00', '16:00:00', 'Praktikum Jaringan Komputer', 'pending'),
(2, 3, CURDATE() + INTERVAL 5 DAY, '09:00:00', '15:00:00', 'Seminar Teknologi Informasi', 'approved'),
(3, 4, CURDATE() + INTERVAL 1 DAY, '10:00:00', '12:00:00', 'Rapat Himpunan', 'rejected'),
(2, 1, CURDATE() + INTERVAL 3 DAY, '10:00:00', '12:00:00', 'Diskusi Kelompok Belajar', 'pending');

DROP VIEW IF EXISTS v_reservation_details;
DROP VIEW IF EXISTS v_room_facility_summary;
DROP FUNCTION IF EXISTS fn_reservation_duration_minutes;
DROP FUNCTION IF EXISTS fn_room_is_available;
DROP TRIGGER IF EXISTS trg_reservations_before_insert;
DROP TRIGGER IF EXISTS trg_reservations_before_update;
DROP TRIGGER IF EXISTS trg_reservations_after_update_status;

DELIMITER $$

CREATE FUNCTION fn_reservation_duration_minutes(p_start_time TIME, p_end_time TIME)
RETURNS INT
DETERMINISTIC
BEGIN
    RETURN TIMESTAMPDIFF(
        MINUTE,
        TIMESTAMP(CURDATE(), p_start_time),
        TIMESTAMP(CURDATE(), p_end_time)
    );
END$$

CREATE FUNCTION fn_room_is_available(
    p_room_id INT,
    p_reservation_date DATE,
    p_start_time TIME,
    p_end_time TIME,
    p_exclude_reservation_id INT
)
RETURNS TINYINT
READS SQL DATA
BEGIN
    DECLARE v_ready_room INT DEFAULT 0;
    DECLARE v_conflict_total INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_ready_room
    FROM rooms
    WHERE id = p_room_id
      AND status = 'available';

    IF v_ready_room = 0 THEN
        RETURN 0;
    END IF;

    SELECT COUNT(*)
    INTO v_conflict_total
    FROM reservations
    WHERE room_id = p_room_id
      AND reservation_date = p_reservation_date
      AND status IN ('pending', 'approved')
      AND start_time < p_end_time
      AND end_time > p_start_time
      AND (p_exclude_reservation_id IS NULL OR id <> p_exclude_reservation_id);

    RETURN IF(v_conflict_total = 0, 1, 0);
END$$

CREATE TRIGGER trg_reservations_before_insert
BEFORE INSERT ON reservations
FOR EACH ROW
BEGIN
    IF fn_reservation_duration_minutes(NEW.start_time, NEW.end_time) <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Jam selesai reservasi harus setelah jam mulai.';
    END IF;

    IF NEW.status IN ('pending', 'approved')
       AND fn_room_is_available(NEW.room_id, NEW.reservation_date, NEW.start_time, NEW.end_time, NULL) = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ruangan tidak tersedia atau jadwal reservasi bentrok.';
    END IF;
END$$

CREATE TRIGGER trg_reservations_before_update
BEFORE UPDATE ON reservations
FOR EACH ROW
BEGIN
    IF fn_reservation_duration_minutes(NEW.start_time, NEW.end_time) <= 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Jam selesai reservasi harus setelah jam mulai.';
    END IF;

    IF NEW.status IN ('pending', 'approved')
       AND fn_room_is_available(NEW.room_id, NEW.reservation_date, NEW.start_time, NEW.end_time, NEW.id) = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ruangan tidak tersedia atau jadwal reservasi bentrok.';
    END IF;
END$$

CREATE TRIGGER trg_reservations_after_update_status
AFTER UPDATE ON reservations
FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status THEN
        INSERT INTO reservation_logs (reservation_id, old_status, new_status, changed_by, note)
        VALUES (
            NEW.id,
            OLD.status,
            NEW.status,
            COALESCE(@app_user_id, NEW.user_id),
            LEFT(COALESCE(NULLIF(@app_note, ''), 'Perubahan status dicatat otomatis oleh trigger.'), 255)
        );
    END IF;
END$$

DELIMITER ;

CREATE VIEW v_reservation_details AS
SELECT
    reservations.id,
    reservations.user_id,
    reservations.room_id,
    reservations.reservation_date,
    reservations.start_time,
    reservations.end_time,
    reservations.purpose,
    reservations.status,
    reservations.created_at,
    reservations.updated_at,
    fn_reservation_duration_minutes(reservations.start_time, reservations.end_time) AS duration_minutes,
    users.name AS user_name,
    users.email AS user_email,
    rooms.room_name,
    rooms.location,
    rooms.capacity,
    rooms.status AS room_status
FROM reservations
INNER JOIN users ON users.id = reservations.user_id
INNER JOIN rooms ON rooms.id = reservations.room_id;

CREATE VIEW v_room_facility_summary AS
SELECT
    rooms.id AS room_id,
    rooms.room_name,
    rooms.location,
    rooms.capacity,
    rooms.status,
    COUNT(DISTINCT room_facilities.facility_id) AS facility_total,
    GROUP_CONCAT(facilities.facility_name ORDER BY facilities.facility_name SEPARATOR ', ') AS facility_list,
    COUNT(DISTINCT CASE
        WHEN reservations.status IN ('pending', 'approved')
             AND reservations.reservation_date >= CURDATE()
        THEN reservations.id
    END) AS active_reservation_total
FROM rooms
LEFT JOIN room_facilities ON room_facilities.room_id = rooms.id
LEFT JOIN facilities ON facilities.id = room_facilities.facility_id
LEFT JOIN reservations ON reservations.room_id = rooms.id
GROUP BY rooms.id, rooms.room_name, rooms.location, rooms.capacity, rooms.status;
