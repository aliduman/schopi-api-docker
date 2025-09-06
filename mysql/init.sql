-- Development veritabanı için örnek tablolar
CREATE DATABASE IF NOT EXISTS development;
USE development;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (name, email) VALUES 
('Test User', 'test@example.com'),
('Admin User', 'admin@example.com');
