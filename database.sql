-- ============================================================
-- ጨርቃጨርቅ ፋብሪካ | Textile Factory Management System
-- Database Schema
-- Run this in phpMyAdmin before using the system
-- ============================================================

CREATE DATABASE IF NOT EXISTS textile_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE textile_db;

-- Users: Owner, Manager, Attendance Writer
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('owner','manager','writer') NOT NULL,
    phone       VARCHAR(20)  DEFAULT NULL,
    created_by  INT          DEFAULT NULL,
    is_active   TINYINT(1)   DEFAULT 1,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Salary levels: A, B, C, D
CREATE TABLE IF NOT EXISTS salary_levels (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    level       CHAR(1)      NOT NULL UNIQUE,
    label       VARCHAR(100) NOT NULL,
    daily_rate  DECIMAL(10,2) NOT NULL,
    updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employees
CREATE TABLE IF NOT EXISTS employees (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    phone       VARCHAR(20)  DEFAULT NULL,
    position    VARCHAR(100) DEFAULT NULL,
    level       CHAR(1)      NOT NULL DEFAULT 'D',
    is_active   TINYINT(1)   DEFAULT 1,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attendance records (one row per employee per day)
CREATE TABLE IF NOT EXISTS attendance (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT          NOT NULL,
    work_date   DATE         NOT NULL,
    status      ENUM('present','absent') DEFAULT 'absent',
    daily_rate  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    recorded_by INT          DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY  uq_emp_date  (employee_id, work_date),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Salary payments made to employees
CREATE TABLE IF NOT EXISTS payments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT          NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    paid_by     INT          NOT NULL,
    note        VARCHAR(255) DEFAULT NULL,
    paid_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Production orders
CREATE TABLE IF NOT EXISTS orders (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    order_number        VARCHAR(50)  NOT NULL UNIQUE,
    client_name         VARCHAR(100) NOT NULL,
    product_name        VARCHAR(100) NOT NULL,
    target_quantity     INT          NOT NULL,
    deadline            DATE         DEFAULT NULL,
    status              ENUM('new','accepted','working','half_finished','finished') DEFAULT 'new',
    completed_quantity  INT          DEFAULT 0,
    incomplete_reason   TEXT         DEFAULT NULL,
    created_by          INT          DEFAULT NULL,
    finished_at         TIMESTAMP    NULL DEFAULT NULL,
    created_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order status change history
CREATE TABLE IF NOT EXISTS order_updates (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT          NOT NULL,
    old_status  VARCHAR(50)  DEFAULT NULL,
    new_status  VARCHAR(50)  DEFAULT NULL,
    note        TEXT         DEFAULT NULL,
    updated_by  INT          DEFAULT NULL,
    updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Default Data
-- ============================================================

-- Salary levels (manager can change the rates later)
INSERT INTO salary_levels (level, label, daily_rate) VALUES
('A', 'Excellent / በጣም ጥሩ',  350.00),
('B', 'Good / ጥሩ',            280.00),
('C', 'Average / መካከለኛ',     220.00),
('D', 'Beginner / ጀማሪ',      180.00)
ON DUPLICATE KEY UPDATE level = level;

-- NOTE: Run setup.php to create the default owner account.
-- Default login: username = owner | password = owner123
