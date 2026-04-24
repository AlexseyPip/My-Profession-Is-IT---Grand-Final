<?php
session_start();
include 'include/db_connect.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if(empty($email) || empty($password)) {
        $error = "Заполните все поля";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            // Сохраняем данные в сессию
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['first_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['last_name'] = $user['last_name'];
            
            // Запоминаем пользователя если нужно
            if($remember) {
                setcookie('user_email', $email, time() + (86400 * 30), "/");
                setcookie('user_password', $_POST['password'], time() + (86400 * 30), "/");
            }
            
            // Перенаправляем в зависимости от роли
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Неверный email или пароль";
        }
    }
}

// Проверка на запомненные данные
$remembered_email = isset($_COOKIE['user_email']) ? $_COOKIE['user_email'] : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Todo Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <div class="logo-icon">✓</div>
                    <span>Todo Tracker</span>
                </div>
                <h1>Добро пожаловать!</h1>
                <p>Войдите чтобы продолжить</p>
            </div>
            
            <?php if($error): ?>
                <div class="auth-errors">
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['registered'])): ?>
                <div class="auth-success">
                    <div class="success-message">Регистрация успешна! Войдите в аккаунт</div>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-input" placeholder="example@mail.ru" required value="<?php echo htmlspecialchars($remembered_email); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Пароль</label>
                    <input type="password" name="password" id="password" class="form-input" required>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" <?php echo $remembered_email ? 'checked' : ''; ?>>
                        <span>Запомнить меня</span>
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Забыли пароль?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Войти</button>
                
                <div class="auth-footer">
                    <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>