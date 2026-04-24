<?php
// Специально для хостинга spaceweb.ru с портом 3308
$host = '127.0.0.1';
$port = '3308';
$dbname = 'alexseywe2';
$username = 'alexseywe2';
$password = 'KZ66MK3rX9FK7XQ$';

try {
    // Используем порт в DSN
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // echo "✅ Подключено! Порт: $port";
    
} catch(PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}
?>