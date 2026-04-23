<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo Tracker - Управляй задачами</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <div class="hero">
        <h1>Перестань играть в археолога</h1>
        <p>Все задачи в одном месте. Контроль, прогресс и мотивация</p>
        <div class="buttons">
            <a href="register.php" class="btn primary">Начать сейчас</a>
            <a href="login.php" class="btn secondary">Войти</a>
        </div>
    </div>

    <div class="features">
        <div class="feature">
            <h3>📝 Создавай задачи</h3>
            <p>Любые задания: устные, из чата, из дневника</p>
        </div>
        <div class="feature">
            <h3>⏰ Напоминания</h3>
            <p>Не пропусти дедлайны. Система напомнит сама</p>
        </div>
        <div class="feature">
            <h3>📊 Прогресс</h3>
            <p>Видишь, что сделано, а что нет. Баллы и достижения</p>
        </div>
    </div>

    <?php include 'include/footer.php'; ?>
</body>
</html>