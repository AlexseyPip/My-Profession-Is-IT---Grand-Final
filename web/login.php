<?php
session_start();
include 'include/db_connect.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['first_name'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Неверный email или пароль";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход - Todo Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <div class="form-container">
        <h2>Вход в систему</h2>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <?php if(isset($_GET['registered'])) echo "<p class='success'>Регистрация успешна! Войдите</p>"; ?>
        
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
        
        <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
    </div>
    
    <?php include 'include/footer.php'; ?>
</body>
</html>