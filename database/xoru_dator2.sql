CREATE DATABASE IF NOT EXISTS xoru_dator2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE xoru_dator2;


CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NULL,
    provider ENUM('email','google','facebook') NOT NULL DEFAULT 'email',
    provider_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_provider (provider, provider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS availability (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    available_date DATE NOT NULL,
    available_time TIME NOT NULL,
    status ENUM('Available','Booked','Blocked') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_schedule (available_date, available_time),
    INDEX idx_schedule_date_status (available_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    form_type ENUM('booking','estimate') NOT NULL DEFAULT 'booking',
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    vehicle VARCHAR(150) NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    availability_id INT UNSIGNED NULL,
    date DATE NULL,
    time TIME NULL,
    message TEXT NOT NULL,
    status ENUM('Pending','Approved','Declined','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    admin_note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bookings_customer_id (customer_id),
    INDEX idx_bookings_service_id (service_id),
    INDEX idx_bookings_availability_id (availability_id),
    INDEX idx_bookings_status (status),
    CONSTRAINT fk_bookings_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_availability FOREIGN KEY (availability_id) REFERENCES availability(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    type ENUM('Inconvenience','Review') NOT NULL DEFAULT 'Inconvenience',
    rating TINYINT UNSIGNED NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('New','Read','Resolved') NOT NULL DEFAULT 'New',
    admin_reply TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reports_customer_id (customer_id),
    INDEX idx_reports_status (status),
    CONSTRAINT fk_reports_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO services (service_name, description) VALUES
('Radiator Repair', 'Professional radiator repair service.'),
('Coolant Flush', 'Cooling system coolant flushing and replacement.'),
('Leak Detection', 'Diagnosis and detection of radiator and cooling-system leaks.'),
('Core Replacement', 'Radiator core replacement for damaged units.');

-- Sample working hours. Admin can add more slots from the Admin Dashboard.
INSERT IGNORE INTO availability (available_date, available_time) VALUES
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00'),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00'),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00:00'),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00'),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00'),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:00:00'),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00'),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00'),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), '11:00:00'),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), '13:00:00'),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00'),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), '15:00:00');
