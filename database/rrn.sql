-- =====================================================================
-- Rent and Ride Nepal (RRN) - Database Schema
-- Vehicle Rental Platform for Nepal (Bikes & Cars)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS rrn_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE rrn_db;

-- ---------------------------------------------------------------------
-- Table: users
-- ---------------------------------------------------------------------
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    city VARCHAR(80) DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: admins
-- ---------------------------------------------------------------------
CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin','manager') NOT NULL DEFAULT 'manager',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: vehicle_categories
-- ---------------------------------------------------------------------
CREATE TABLE vehicle_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    vehicle_type ENUM('Bike','Car') NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: vehicles
-- ---------------------------------------------------------------------
CREATE TABLE vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_type ENUM('Bike','Car') NOT NULL,
    category_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(60) NOT NULL,
    model_year YEAR DEFAULT NULL,
    fuel_type ENUM('Petrol','Diesel','Electric','Hybrid') NOT NULL DEFAULT 'Petrol',
    transmission ENUM('Manual','Automatic') NOT NULL DEFAULT 'Manual',
    seating_capacity INT NOT NULL DEFAULT 2,
    daily_price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    description TEXT,
    pickup_location VARCHAR(120) DEFAULT 'Kathmandu',
    availability ENUM('Available','Booked','Maintenance') NOT NULL DEFAULT 'Available',
    source ENUM('Internal','External') NOT NULL DEFAULT 'Internal',
    external_ref_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES vehicle_categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: driving_licenses
-- ---------------------------------------------------------------------
CREATE TABLE driving_licenses (
    license_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    holder_name VARCHAR(100) NOT NULL,
    license_number VARCHAR(60) NOT NULL,
    license_type ENUM('Nepalese','Foreign') NOT NULL,
    country_of_issue VARCHAR(80) NOT NULL DEFAULT 'Nepal',
    expiry_date DATE NOT NULL,
    license_image VARCHAR(255) NOT NULL,
    verification_status ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
    admin_remarks VARCHAR(255) DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: reservations
-- ---------------------------------------------------------------------
CREATE TABLE reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    license_id INT DEFAULT NULL,
    pickup_date DATE NOT NULL,
    return_date DATE NOT NULL,
    pickup_location VARCHAR(120) NOT NULL,
    total_days INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending','Approved','Rejected','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE,
    FOREIGN KEY (license_id) REFERENCES driving_licenses(license_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: payments
-- ---------------------------------------------------------------------
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    gateway ENUM('eSewa','Khalti') NOT NULL,
    transaction_code VARCHAR(100) DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('Pending','Success','Failed') NOT NULL DEFAULT 'Pending',
    paid_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- Sample data
-- =====================================================================

INSERT INTO vehicle_categories (category_name, vehicle_type) VALUES
('Scooter','Bike'),
('Sports Bike','Bike'),
('Cruiser','Bike'),
('Hatchback','Car'),
('SUV','Car'),
('Sedan','Car');

-- Default admin login -> email: admin@rentandridenepal.com | password: Admin@123
-- (hash below is a genuine bcrypt hash of "Admin@123" and will verify correctly with PHP's password_verify)
INSERT INTO admins (full_name, email, password_hash, role) VALUES
('System Admin', 'admin@rentandridenepal.com', '$2b$10$u0Gj/Yh8NV7ihdoLHZfVouCReNUSLWEsrP2XKjEXdE2BuZnIgK3QG', 'super_admin');

-- Sample vehicles
INSERT INTO vehicles (vehicle_type, category_id, name, brand, model_year, fuel_type, transmission, seating_capacity, daily_price, image, description, pickup_location, availability) VALUES
('Bike', 1, 'Activa 6G', 'Honda', 2023, 'Petrol', 'Automatic', 2, 1200.00, 'activa.jpg', 'Reliable and fuel-efficient scooter, perfect for city rides in Kathmandu.', 'Kathmandu', 'Available'),
('Bike', 2, 'Pulsar NS200', 'Bajaj', 2022, 'Petrol', 'Manual', 2, 1800.00, 'pulsar.jpg', 'Sporty performance bike great for highway trips.', 'Pokhara', 'Available'),
('Bike', 3, 'Royal Enfield Classic 350', 'Royal Enfield', 2023, 'Petrol', 'Manual', 2, 2500.00, 're-classic.jpg', 'Iconic cruiser bike with a timeless design.', 'Kathmandu', 'Available'),
('Car', 4, 'Alto K10', 'Suzuki', 2022, 'Petrol', 'Manual', 4, 3500.00, 'alto.jpg', 'Compact and economical hatchback for city driving.', 'Kathmandu', 'Available'),
('Car', 5, 'Scorpio N', 'Mahindra', 2023, 'Diesel', 'Manual', 7, 7500.00, 'scorpio.jpg', 'Powerful SUV suitable for hill and highway travel.', 'Chitwan', 'Available'),
('Car', 6, 'City', 'Honda', 2023, 'Petrol', 'Automatic', 5, 6000.00, 'city.jpg', 'Comfortable sedan with automatic transmission.', 'Lalitpur', 'Available');
