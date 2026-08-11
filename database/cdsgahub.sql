CREATE DATABASE IF NOT EXISTS cdsgahub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cdsgahub;

-- Core accounts table for all 3 roles
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin', 'professor', 'student') NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'active', 'disabled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Fixed course list (seeded from your Section dropdown)
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

INSERT INTO courses (code, name) VALUES
('BSIT', 'Information Technology'),
('BSCS', 'Computer Science'),
('BSIS', 'Information Systems'),
('BSBA', 'Business Administration'),
('BSA', 'Accountancy'),
('BSN', 'Nursing'),
('BSE', 'Engineering'),
('BEED', 'Education'),
('BSP', 'Psychology'),
('BSC', 'Criminology'),
('BSTHM', 'Tourism / Hospitality'),
('BAC', 'Communication'),
('BSAR', 'Architecture');

-- Student-specific info, linked to users
CREATE TABLE students (
    user_id INT PRIMARY KEY,
    student_id_number VARCHAR(30) NOT NULL UNIQUE,
    course_id INT NOT NULL,
    section_label VARCHAR(10) NOT NULL,
    year_level TINYINT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)
) ENGINE=InnoDB;

-- Professor-specific info, linked to users
CREATE TABLE professors (
    user_id INT PRIMARY KEY,
    department VARCHAR(100) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Email verification codes (registration flow)
CREATE TABLE email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;