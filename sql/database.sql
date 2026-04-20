-- File: sql/database.sql
CREATE DATABASE IF NOT EXISTS auditorium_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE auditorium_db;

CREATE TABLE IF NOT EXISTS organizers (
  organizer_id INT AUTO_INCREMENT PRIMARY KEY,
  organizer_name VARCHAR(255) NOT NULL,
  department VARCHAR(255) DEFAULT NULL,
  contact_number VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS auditoriums (
  auditorium_id INT AUTO_INCREMENT PRIMARY KEY,
  auditorium_name VARCHAR(255) NOT NULL,
  location VARCHAR(255) DEFAULT NULL,
  capacity INT DEFAULT 0,
  status VARCHAR(50) DEFAULT 'Available'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
  event_id INT AUTO_INCREMENT PRIMARY KEY,
  event_name VARCHAR(255) NOT NULL,
  event_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  organizer_id INT NOT NULL,
  FOREIGN KEY (organizer_id) REFERENCES organizers(organizer_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
  booking_id INT AUTO_INCREMENT PRIMARY KEY,
  booking_date DATE NOT NULL,
  event_id INT NOT NULL,
  auditorium_id INT NOT NULL,
  FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
  FOREIGN KEY (auditorium_id) REFERENCES auditoriums(auditorium_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS equipment (
  equipment_id INT AUTO_INCREMENT PRIMARY KEY,
  equipment_name VARCHAR(255) NOT NULL,
  quantity INT DEFAULT 0,
  status VARCHAR(50) DEFAULT 'Available'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_equipment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  equipment_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
  FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS staff (
  staff_id INT AUTO_INCREMENT PRIMARY KEY,
  staff_name VARCHAR(255) NOT NULL,
  role VARCHAR(255) DEFAULT NULL,
  contact_number VARCHAR(50) DEFAULT NULL,
  auditorium_id INT DEFAULT NULL,
  FOREIGN KEY (auditorium_id) REFERENCES auditoriums(auditorium_id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO organizers (organizer_name, department, contact_number, email) VALUES
('Alice Johnson', 'Events', '555-0100', 'alice@example.com'),
('Bob Smith', 'Student Affairs', '555-0110', 'bob@example.com');

INSERT INTO auditoriums (auditorium_name, location, capacity, status) VALUES
('Main Hall', 'Building A', 300, 'Available'),
('Lecture Room 1', 'Building B', 80, 'Available');

INSERT INTO events (event_name, event_date, start_time, end_time, organizer_id) VALUES
('Orientation', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00', '11:00:00', 1),
('Guest Lecture', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '14:00:00', '16:00:00', 2);

INSERT INTO bookings (booking_date, event_id, auditorium_id) VALUES
(CURDATE(), 1, 1);

INSERT INTO equipment (equipment_name, quantity, status) VALUES
('Projector', 2, 'Available'),
('Microphone', 5, 'Available');

INSERT INTO staff (staff_name, role, contact_number, auditorium_id) VALUES
('Eve Martin', 'Technician', '555-0120', 1),
('Sam Turner', 'Cleaner', '555-0130', NULL);
