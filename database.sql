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
