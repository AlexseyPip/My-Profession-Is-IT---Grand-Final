<?php
session_start();
include 'include/db_connect.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $birth_date = $_POST['birth_date'];
    $school = $_POST['school'];
    $class_letter = $_POST['class_letter'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (role, first_name, last_name, birth_date, school, class_letter, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if($stmt->execute([$role, $first_name, $last_name, $birth_date, $school, $class_letter, $email, $password])) {
        header("Location: login.php?registered=1");
        exit();
    } else {
        $error = "Ошибка регистрации";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация - Todo Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <div class="form-container">
        <h2>Регистрация</h2>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        
        <form method="POST">
            <select name="role" required>
                <option value="student">Ученик</option>
                <option value="teacher">Учитель</option>
                <option value="parent">Родитель</option>
            </select>
            
            <input type="text" name="first_name" placeholder="Имя" required>
            <input type="text" name="last_name" placeholder="Фамилия" required>
            <input type="date" name="birth_date" required>
            <input type="text" name="school" placeholder="Школа" required>
            <input type="text" name="class_letter" placeholder="Класс (например 11А)" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Пароль" required>
            
            <button type="submit">Зарегистрироваться</button>
        </form>
        
        <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
    </div>
    
    <?php include 'include/footer.php'; ?>
</body>
</html>