
CREATE DATABASE IF NOT EXISTS gym_db;
USE gym_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('manager', 'trainer', 'member') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    duration_days INT NOT NULL,
    price DECIMAL(10,2) NOT NULL
);

CREATE TABLE memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    package_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired') DEFAULT 'active',
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
);

CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    capacity INT NOT NULL,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    member_id INT NOT NULL,
    status ENUM('booked', 'cancelled', 'attended') DEFAULT 'booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

CREATE INDEX idx_user_username ON users(username);
CREATE INDEX idx_memberships_dates ON memberships(start_date, end_date);
CREATE INDEX idx_schedules_times ON schedules(start_time, end_time);

INSERT INTO users (username, password_hash, role) VALUES 
('admin', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'manager'),
('hlv01', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'trainer'),
('hlv02', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'trainer'),
('hlv03', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'trainer'),
('hlv04', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'trainer'),
('hlv05', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'trainer'),
('hoivien01', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien02', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien03', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien04', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien05', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien06', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien07', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien08', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien09', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien10', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien11', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien12', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien13', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien14', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member'),
('hoivien15', '$2y$10$c1.gQJMvJfSZcwpj1UaEVuTxTlocoDJ48hxgsgjWc6FGkUdvCMplO', 'member');

INSERT INTO trainers (user_id, name, specialty) VALUES
(2, 'Trainer One', 'Yoga'),
(3, 'Trainer Two', 'Weightlifting'),
(4, 'Trainer Three', 'Cardio'),
(5, 'Trainer Four', 'Crossfit'),
(6, 'Trainer Five', 'Pilates');

INSERT INTO members (user_id, name, phone) VALUES
(7, 'Member One', '12345'),
(8, 'Member Two', '12345'),
(9, 'Member Three', '12345'),
(10, 'Member 4', '12345'),
(11, 'Member 5', '12345'),
(12, 'Member 6', '12345'),
(13, 'Member 7', '12345'),
(14, 'Member 8', '12345'),
(15, 'Member 9', '12345'),
(16, 'Member 10', '12345'),
(17, 'Member 11', '12345'),
(18, 'Member 12', '12345'),
(19, 'Member 13', '12345'),
(20, 'Member 14', '12345'),
(21, 'Member 15', '12345');

INSERT INTO packages (name, duration_days, price) VALUES
('1 Month', 30, 50.00),
('3 Months', 90, 130.00),
('6 Months', 180, 240.00),
('1 Year', 365, 400.00),
('1 Week Trial', 7, 15.00);

INSERT INTO memberships (member_id, package_id, start_date, end_date, status) VALUES
(1, 1, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'active'),
(2, 2, DATE_SUB(CURDATE(), INTERVAL 100 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'expired'),
(3, 1, DATE_SUB(CURDATE(), INTERVAL 28 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'active');

INSERT INTO schedules (trainer_id, class_name, start_time, end_time, capacity) VALUES
(1, 'Morning Yoga', DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL '1 1' DAY_HOUR), 3),
(2, 'Heavy Lifts', DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL '1 2' DAY_HOUR), 5),
(1, 'Evening Yoga', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL '2 1' DAY_HOUR), 3),
(3, 'Cardio Blast', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL '2 1' DAY_HOUR), 10),
(4, 'Crossfit 101', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL '3 1' DAY_HOUR), 3),
(5, 'Pilates Core', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL '3 1' DAY_HOUR), 8),
(2, 'Powerlifting', DATE_ADD(NOW(), INTERVAL 4 DAY), DATE_ADD(NOW(), INTERVAL '4 1' DAY_HOUR), 4),
(1, 'Relax Yoga', DATE_ADD(NOW(), INTERVAL 4 DAY), DATE_ADD(NOW(), INTERVAL '4 1' DAY_HOUR), 5);

INSERT INTO bookings (schedule_id, member_id, status) VALUES
(1, 1, 'booked'),
(1, 2, 'booked'),
(2, 3, 'booked');
