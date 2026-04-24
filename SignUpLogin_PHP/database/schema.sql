-- Database Schema for CCS Portal (PHP Version)
-- Run this SQL to create the required tables

CREATE DATABASE IF NOT EXISTS ccs_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ccs_portal;

-- Signups table (main users table)
CREATE TABLE IF NOT EXISTS signups (
    IdNumber VARCHAR(8) PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    MiddleName VARCHAR(100) DEFAULT '',
    CourseLevel VARCHAR(50) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Course VARCHAR(100) NOT NULL,
    Address TEXT NOT NULL,
    Role VARCHAR(20) DEFAULT 'Student',
    RemainingSessions INT DEFAULT 30,
    LastAnnouncementsReadAt DATETIME DEFAULT NULL,
    ProfileImagePath VARCHAR(255) DEFAULT NULL,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (Email),
    INDEX idx_role (Role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sit-in records table
CREATE TABLE IF NOT EXISTS sit_in_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    StudentIdNumber VARCHAR(8) NOT NULL,
    TimeIn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    TimeOut DATETIME DEFAULT NULL,
    PcNumber VARCHAR(20) DEFAULT NULL,
    Purpose TEXT DEFAULT NULL,
    INDEX idx_student (StudentIdNumber),
    FOREIGN KEY (StudentIdNumber) REFERENCES signups(IdNumber) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcements table
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(255) NOT NULL,
    Content TEXT NOT NULL,
    CreatedBy VARCHAR(100) NOT NULL,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    IsActive BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lab Status table
CREATE TABLE IF NOT EXISTS lab_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    LabName VARCHAR(100) NOT NULL,
    Status VARCHAR(50) NOT NULL DEFAULT 'Available',
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feedback table
CREATE TABLE IF NOT EXISTS feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    StudentIdNumber VARCHAR(8) NOT NULL,
    Subject VARCHAR(255) NOT NULL,
    Message TEXT NOT NULL,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    IsRead BOOLEAN DEFAULT FALSE,
    INDEX idx_student (StudentIdNumber),
    FOREIGN KEY (StudentIdNumber) REFERENCES signups(IdNumber) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student Points table
CREATE TABLE IF NOT EXISTS student_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    StudentIdNumber VARCHAR(8) NOT NULL,
    Points INT DEFAULT 0,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (StudentIdNumber),
    FOREIGN KEY (StudentIdNumber) REFERENCES signups(IdNumber) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: Admin@123)
-- Note: You need to generate a proper bcrypt hash for production
-- This is a sample hash - replace with password_hash('Admin@123', PASSWORD_BCRYPT)
INSERT INTO signups (IdNumber, FirstName, LastName, MiddleName, CourseLevel, Password, Email, Course, Address, Role, RemainingSessions)
VALUES ('ADMIN001', 'Admin', 'User', '', 'N/A', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@ccs.edu', 'Administration', 'CCS Office', 'Admin', 999)
ON DUPLICATE KEY UPDATE FirstName = FirstName;

-- Insert sample lab statuses
INSERT INTO lab_statuses (LabName, Status) VALUES 
('Lab 1', 'Available'),
('Lab 2', 'Available'),
('Lab 3', 'Occupied')
ON DUPLICATE KEY UPDATE LabName = LabName;
