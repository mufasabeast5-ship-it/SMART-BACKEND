-- ============================================================
-- SMART Attendance Hub — MySQL Database Schema
-- Run this in phpMyAdmin or via MySQL CLI on XAMPP
-- ============================================================

CREATE DATABASE IF NOT EXISTS smart_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_attendance;

-- ============================================================
-- USERS (Admin / Teacher logins)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)        NOT NULL,
    email       VARCHAR(255)        NOT NULL UNIQUE,
    password    VARCHAR(255)        NOT NULL,  -- bcrypt hash
    role        ENUM('admin','teacher','student') NOT NULL DEFAULT 'admin',
    created_at  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin: admin@school.com / admin123
INSERT INTO users (name, email, password, role) VALUES
('Administrator', 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ============================================================
-- TEACHERS
-- ============================================================
CREATE TABLE IF NOT EXISTS teachers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)        NOT NULL,
    email       VARCHAR(255)        NOT NULL UNIQUE,
    subject     VARCHAR(150)        NOT NULL DEFAULT '',
    created_at  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- CLASSES
-- ============================================================
CREATE TABLE IF NOT EXISTS classes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)        NOT NULL,
    section     VARCHAR(50)         NOT NULL,
    teacher_id  INT UNSIGNED        NULL,
    created_at  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- STUDENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)        NOT NULL,
    email       VARCHAR(255)        NOT NULL UNIQUE,
    class_id    INT UNSIGNED        NULL,
    created_at  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- ATTENDANCE
-- ============================================================
CREATE TABLE IF NOT EXISTS attendance (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id  INT UNSIGNED        NOT NULL,
    class_id    INT UNSIGNED        NOT NULL,
    date        DATE                NOT NULL,
    status      ENUM('present','absent','late') NOT NULL,
    created_at  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id)   REFERENCES classes(id)  ON DELETE CASCADE,
    UNIQUE KEY  uq_attendance (student_id, class_id, date)
) ENGINE=InnoDB;
-- ============================================================
-- ENROLLMENT REQUESTS
-- ============================================================
CREATE TABLE IF NOT EXISTS enrollment_requests (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED        NOT NULL,
    class_id    INT UNSIGNED        NOT NULL,
    status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY  uq_enrollment (user_id, class_id)
) ENGINE=InnoDB;

-- ============================================================
-- LESSONS
-- ============================================================
CREATE TABLE IF NOT EXISTS lessons (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id    INT UNSIGNED        NOT NULL,
    title       VARCHAR(255)        NOT NULL,
    description TEXT                NULL,
    lesson_date DATE                NOT NULL,
    created_at  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

