CREATE DATABASE IF NOT EXISTS project_evaluation;
USE project_evaluation;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','evaluator','admin') NOT NULL
);

-- Projects table
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    project_title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    submitted_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending','Under Review','Completed') DEFAULT 'Pending'
);

-- Evaluations table
CREATE TABLE evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    evaluator_id VARCHAR(20) NOT NULL,
    score INT NOT NULL,
    feedback_text TEXT,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

-- Default demo login users
INSERT INTO users (username, password, role) VALUES
('A123', MD5('123456'), 'admin'),
('E123', MD5('123456'), 'evaluator'),
('B123', MD5('123456'), 'student');
