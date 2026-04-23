<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'include/db_connect.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $deadline = $_POST['deadline'];
    $priority = $_POST['priority'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, deadline, priority) VALUES (?, ?, ?, ?, ?)");
    if($stmt->execute([$user_id, $title, $description, $deadline, $priority])) {
        header("Location: dashboard.php?added=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить задачу</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <div class="form-container">
        <h2>Новая задача</h2>
        <form method="POST">
            <input type="text" name="title" placeholder="Название задачи" required>
            <textarea name="description" placeholder="Описание" rows="5"></textarea>
            <input type="date" name="deadline" required>
            <select name="priority">
                <option value="low">Низкий приоритет</option>
                <option value="medium" selected>Средний</option>
                <option value="high">Высокий</option>
            </select>
            <button type="submit">Создать задачу</button>
        </form>
    </div>
    
    <?php include 'include/footer.php'; ?>
</body>
</html>