<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'include/db_connect.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Получаем статистику
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed FROM tasks WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Последние задачи
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY deadline ASC LIMIT 5");
$stmt->execute([$user_id]);
$recent_tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления - Todo Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <div class="dashboard">
        <h1>Привет, <?php echo $_SESSION['name']; ?>!</h1>
        <p>Роль: <?php echo $role == 'student' ? 'Ученик' : ($role == 'teacher' ? 'Учитель' : 'Родитель'); ?></p>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Всего задач</h3>
                <p class="number"><?php echo $stats['total'] ?? 0; ?></p>
            </div>
            <div class="stat-card">
                <h3>Выполнено</h3>
                <p class="number"><?php echo $stats['completed'] ?? 0; ?></p>
            </div>
            <div class="stat-card">
                <h3>Прогресс</h3>
                <p class="number"><?php echo $stats['total'] > 0 ? round(($stats['completed']/$stats['total'])*100) : 0; ?>%</p>
            </div>
        </div>
        
        <div class="recent-tasks">
            <h2>Последние задачи</h2>
            <a href="add_task.php" class="btn primary">+ Новая задача</a>
            
            <table>
                <thead>
                    <tr><th>Задача</th><th>Дедлайн</th><th>Статус</th><th>Действия</th></tr>
                </thead>
                <tbody>
                    <?php foreach($recent_tasks as $task): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($task['title']); ?></td>
                        <td><?php echo $task['deadline']; ?></td>
                        <td><?php echo $task['status']; ?></td>
                        <td><a href="tasks.php?id=<?php echo $task['id']; ?>">Подробнее</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include 'include/footer.php'; ?>
</body>
</html>