<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'include/db_connect.php';

$user_id = $_SESSION['user_id'];

// Обновление статуса
if(isset($_POST['update_status'])) {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$status, $task_id, $user_id]);
    header("Location: tasks.php");
    exit();
}

// Получаем все задачи
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY deadline ASC");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои задачи - Todo Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .task-item.completed { opacity: 0.7; text-decoration: line-through; }
        .priority-high { border-left: 5px solid #ff6b6b; }
        .priority-medium { border-left: 5px solid #ffd93d; }
        .priority-low { border-left: 5px solid #6bcf7f; }
        .task-item { padding: 15px; margin: 10px 0; background: #f9f9f9; border-radius: 5px; }
    </style>
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <div class="dashboard">
        <h1>Мои задачи</h1>
        <a href="add_task.php" class="btn primary">+ Добавить задачу</a>
        
        <?php foreach($tasks as $task): ?>
        <div class="task-item priority-<?php echo $task['priority']; ?> <?php echo $task['status'] == 'completed' ? 'completed' : ''; ?>">
            <h3><?php echo htmlspecialchars($task['title']); ?></h3>
            <p><?php echo htmlspecialchars($task['description']); ?></p>
            <p>📅 Дедлайн: <?php echo $task['deadline']; ?></p>
            <p>⭐ Статус: <?php echo $task['status']; ?></p>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                <select name="status" onchange="this.form.submit()">
                    <option value="pending" <?php echo $task['status']=='pending' ? 'selected' : ''; ?>>Не начато</option>
                    <option value="in_progress" <?php echo $task['status']=='in_progress' ? 'selected' : ''; ?>>В процессе</option>
                    <option value="completed" <?php echo $task['status']=='completed' ? 'selected' : ''; ?>>Выполнено</option>
                </select>
                <input type="hidden" name="update_status" value="1">
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php include 'include/footer.php'; ?>
</body>
</html>