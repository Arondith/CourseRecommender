CREATE DATABASE IF NOT EXISTS coursematch_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE coursematch_db;

CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(80) NOT NULL,
  middle_name VARCHAR(80) NULL,
  last_name VARCHAR(80) NOT NULL,
  phone VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(245) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  strand ENUM('STEM', 'ABM', 'HUMSS') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_students_created_at (created_at),
  INDEX idx_students_strand (strand)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('superadmin', 'moderator', 'viewer') NOT NULL DEFAULT 'viewer',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  personality ENUM('R', 'I', 'A', 'S', 'E', 'C') NOT NULL,
  score_r TINYINT UNSIGNED NOT NULL,
  score_i TINYINT UNSIGNED NOT NULL,
  score_a TINYINT UNSIGNED NOT NULL,
  score_s TINYINT UNSIGNED NOT NULL,
  score_e TINYINT UNSIGNED NOT NULL,
  score_c TINYINT UNSIGNED NOT NULL,
  taken_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_attempt_student
    FOREIGN KEY (student_id) REFERENCES students(id)
    ON DELETE CASCADE,
  INDEX idx_attempt_student_date (student_id, taken_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  token CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reset_email (email),
  INDEX idx_reset_expiry (expires_at)
) ENGINE=InnoDB;
