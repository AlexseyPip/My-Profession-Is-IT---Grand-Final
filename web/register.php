<?php
session_start();
include 'include/db_connect.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'] ?? 'student';
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $birth_date = $_POST['birth_date'];
    $school = trim($_POST['school']);
    $class_letter = trim($_POST['class_letter']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Валидация
    $errors = [];
    $allowed_roles = ['student', 'teacher', 'parent'];
    if(!in_array($role, $allowed_roles, true)) {
        $role = 'student';
    }
    
    if($password !== $confirm_password) {
        $errors[] = "Пароли не совпадают";
    }
    
    if(strlen($password) < 6) {
        $errors[] = "Пароль должен быть не менее 6 символов";
    }
    
    // Проверка на существующий email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if($stmt->fetch()) {
        $errors[] = "Пользователь с таким email уже существует";
    }
    
    if(empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (role, first_name, last_name, birth_date, school, class_letter, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if($stmt->execute([$role, $first_name, $last_name, $birth_date, $school, $class_letter, $email, $hashed_password])) {
            // Получаем ID нового пользователя
            $user_id = $pdo->lastInsertId();
            $stmtRole = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
            $stmtRole->execute([$user_id]);
            $createdUser = $stmtRole->fetch();
            $stored_role = $createdUser['role'] ?? 'student';
            
            // Сразу авторизуем
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $stored_role;
            $_SESSION['name'] = $first_name;
            $_SESSION['email'] = $email;
            
            // Перенаправляем в зависимости от роли
            header("Location: dashboard.php");
            exit();
        } else {
            $errors[] = "Ошибка регистрации. Попробуйте позже.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Todo Tracker</title>
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
                <h1>Создать аккаунт</h1>
                <p>Присоединяйся к сообществу организованных учеников</p>
            </div>
            
            <?php if(!empty($errors)): ?>
                <div class="auth-errors">
                    <?php foreach($errors as $error): ?>
                        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="role" class="form-label">Кто вы?</label>
                    <select name="role" id="role" class="form-select" required>
                        <option value="student">🎓 Ученик</option>
                        <option value="teacher">👨‍🏫 Учитель</option>
                        <option value="parent">👪 Родитель</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="form-label">Имя</label>
                        <input type="text" name="first_name" id="first_name" class="form-input" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="form-label">Фамилия</label>
                        <input type="text" name="last_name" id="last_name" class="form-input" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="birth_date" class="form-label">Дата рождения</label>
                    <input type="date" name="birth_date" id="birth_date" class="form-input" required value="<?php echo isset($_POST['birth_date']) ? $_POST['birth_date'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="school" class="form-label">Школа</label>
                    <input type="text" name="school" id="school" class="form-input" placeholder="Например: ГБОУ СОШ №1" required value="<?php echo isset($_POST['school']) ? htmlspecialchars($_POST['school']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="class_letter" class="form-label">Класс</label>
                    <input type="text" name="class_letter" id="class_letter" class="form-input" placeholder="Например: 11А" required value="<?php echo isset($_POST['class_letter']) ? htmlspecialchars($_POST['class_letter']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-input" placeholder="example@mail.ru" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" name="password" id="password" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Подтверждение</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-input" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Зарегистрироваться</button>
                
                <div class="auth-footer">
                    <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>