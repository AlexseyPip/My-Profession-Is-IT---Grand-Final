<?php
// Автоматическое определение окружения
$is_local = ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1' || strpos($_SERVER['SERVER_NAME'], 'localhost') !== false);

if($is_local) {
    // Локальный XAMPP (стандартный порт 3306)
    $host = 'localhost';
    $port = '3306';
    $dbname = 'todo_tracker';
    $username = 'root';
    $password = '';
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
} else {
    // Хостинг spaceweb.ru (порт 3308!)
    $host = '127.0.0.1';  // localhost тоже работает, но 127.0.0.1 надёжнее
    $port = '3308';        // ВАЖНО: порт 3308, а не стандартный 3306!
    $dbname = 'alexseywe2';
    $username = 'alexseywe2';
    $password = 'KZ66MK3rX9FK7XQ$';
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
}

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Создаём таблицы только на локалке
    if($is_local) {
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
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
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
                FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS teacher_classes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                teacher_id INT NOT NULL,
                class_name VARCHAR(20) NOT NULL,
                UNIQUE KEY uniq_teacher_class (teacher_id, class_name),
                FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS parent_children (
                id INT AUTO_INCREMENT PRIMARY KEY,
                parent_id INT NOT NULL,
                child_user_id INT NOT NULL,
                note VARCHAR(100) NULL,
                UNIQUE KEY uniq_parent_child (parent_id, child_user_id),
                FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (child_user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ");
    }

    // Схема для ролевого назначения задач (безопасно при повторном вызове)
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('student','teacher','parent') DEFAULT 'student'");
    } catch (PDOException $e) {
        // На некоторых хостингах может быть ограничение прав ALTER
    }
    try {
        $pdo->exec("ALTER TABLE tasks ADD COLUMN creator_role ENUM('student','teacher','parent') DEFAULT 'student' AFTER created_by");
    } catch (PDOException $e) {
        // Колонка уже существует
    }
    try {
        $pdo->exec("ALTER TABLE tasks ADD COLUMN creator_id INT NULL AFTER creator_role");
    } catch (PDOException $e) {
        // Колонка уже существует
    }
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS teacher_classes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            class_name VARCHAR(20) NOT NULL,
            UNIQUE KEY uniq_teacher_class (teacher_id, class_name),
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    } catch (PDOException $e) {
        // Таблица уже существует или ограничения хостинга
    }
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS parent_children (
            id INT AUTO_INCREMENT PRIMARY KEY,
            parent_id INT NOT NULL,
            child_user_id INT NOT NULL,
            note VARCHAR(100) NULL,
            UNIQUE KEY uniq_parent_child (parent_id, child_user_id),
            FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (child_user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    } catch (PDOException $e) {
        // Таблица уже существует или ограничения хостинга
    }
    
    // Раскомментируй для проверки подключения
    // echo "✅ База данных подключена успешно! (порт: $port)";
    
} catch(PDOException $e) {
    die("Ошибка БД: " . $e->getMessage() . " [Порт: $port]");
}
?>