<?php
$host = 'localhost';
$dbname = 'todo_tracker';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Создаём БД если нет
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    // Создаём таблицы
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role ENUM('student', 'teacher', 'parent') DEFAULT 'student',
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            birth_date DATE,
            school VARCHAR(100),
            class_letter VARCHAR(5),
            email VARCHAR(100) UNIQUE,
            password VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            teacher_id INT,
            title VARCHAR(255),
            description TEXT,
            deadline DATE,
            status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            points INT DEFAULT 0,
            created_by ENUM('student', 'teacher') DEFAULT 'student',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (teacher_id) REFERENCES users(id)
        );
        
        CREATE TABLE IF NOT EXISTS reminders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT,
            reminder_time DATETIME,
            sent BOOLEAN DEFAULT FALSE,
            reminder_count INT DEFAULT 0,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
        );
        
        CREATE TABLE IF NOT EXISTS task_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT,
            user_id INT,
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            teacher_comment TEXT,
            grade INT,
            FOREIGN KEY (task_id) REFERENCES tasks(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
    ");
    
} catch(PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}
?>