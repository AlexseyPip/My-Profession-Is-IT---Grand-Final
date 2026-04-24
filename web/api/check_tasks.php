<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../include/db_connect.php';

$user_id = $_SESSION['user_id'];

// Получаем статистику задач
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN status != 'completed' AND deadline < CURDATE() THEN 1 ELSE 0 END) as overdue,
        SUM(CASE WHEN status != 'completed' AND deadline = CURDATE() THEN 1 ELSE 0 END) as today
    FROM tasks 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$result = $stmt->fetch();

echo json_encode([
    'success' => true,
    'overdue' => (int)($result['overdue'] ?? 0),
    'today_count' => (int)($result['today'] ?? 0),
    'timestamp' => time()
]);
?>