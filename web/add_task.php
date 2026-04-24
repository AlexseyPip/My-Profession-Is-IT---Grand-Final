<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'include/db_connect.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student';
$available_students = [];

if($role === 'teacher') {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.first_name, u.last_name, u.class_letter
        FROM users u
        JOIN teacher_classes tc ON tc.class_name = u.class_letter
        WHERE tc.teacher_id = ? AND u.role = 'student'
          AND u.school = (SELECT school FROM users WHERE id = ?)
        ORDER BY u.class_letter, u.last_name, u.first_name
    ");
    $stmt->execute([$user_id, $user_id]);
    $available_students = $stmt->fetchAll();
} elseif($role === 'parent') {
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.class_letter
        FROM parent_children pc
        JOIN users u ON u.id = pc.child_user_id
        WHERE pc.parent_id = ? AND u.role = 'student'
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute([$user_id]);
    $available_students = $stmt->fetchAll();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $deadline = $_POST['deadline'];
    $priority = $_POST['priority'];
    $assign_mode = $_POST['assign_mode'] ?? 'self';
    $target_user_id = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
    
    $errors = [];
    
    if(empty($title)) {
        $errors[] = "Название задачи обязательно";
    }
    
    if(empty($deadline)) {
        $errors[] = "Укажите дату выполнения";
    }
    
    if(empty($errors)) {
        $target_ids = [$user_id];

        if(in_array($role, ['teacher', 'parent'], true) && !empty($available_students)) {
            if($assign_mode === 'all') {
                $target_ids = array();
                foreach($available_students as $item) {
                    $target_ids[] = (int)$item['id'];
                }
            } elseif($assign_mode === 'one') {
                $allowed_ids = array();
                foreach($available_students as $item) {
                    $allowed_ids[] = (int)$item['id'];
                }
                if(!in_array($target_user_id, $allowed_ids, true)) {
                    $errors[] = "Выберите ученика из доступного списка";
                } else {
                    $target_ids = [$target_user_id];
                }
            }
        }
    }

    if(empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO tasks (user_id, teacher_id, title, description, deadline, priority, created_by, creator_role, creator_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $created_by = ($role === 'teacher' || $role === 'parent') ? 'teacher' : 'student';

        foreach($target_ids as $target_id) {
            $stmt->execute([
                $target_id,
                ($role === 'teacher' || $role === 'parent') ? $user_id : null,
                $title,
                $description,
                $deadline,
                $priority,
                $created_by,
                $role,
                $user_id
            ]);
        }

        if($stmt) {
            header("Location: dashboard.php?added=1");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать задачу - Todo Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/add-task.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <main class="add-task-main">
        <div class="container">
            <div class="add-task-container">
                <div class="add-task-card">
                    <div class="add-task-header">
                        <h1>➕ Создать новую задачу</h1>
                        <p>Добавь задание, чтобы ничего не забыть</p>
                    </div>
                    
                    <?php if(!empty($errors)): ?>
                        <div class="auth-errors">
                            <?php foreach($errors as $error): ?>
                                <div class="error-message">❌ <?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="task-form">
                        <?php if(in_array($role, ['teacher', 'parent'], true) && !empty($available_students)): ?>
                        <div class="form-group">
                            <label for="assign_mode">👥 Кому назначить</label>
                            <select name="assign_mode" id="assign_mode">
                                <option value="self">Только себе</option>
                                <option value="all">Всем доступным ученикам</option>
                                <option value="one">Конкретному ученику</option>
                            </select>
                        </div>
                        <div class="form-group" id="targetUserBlock" style="display:none;">
                            <label for="target_user_id">🎯 Ученик</label>
                            <select name="target_user_id" id="target_user_id">
                                <option value="">Выберите ученика</option>
                                <?php foreach($available_students as $student): ?>
                                    <option value="<?php echo (int)$student['id']; ?>">
                                        <?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name'] . ' (' . $student['class_letter'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="title">📌 Название задачи</label>
                            <input type="text" name="title" id="title" placeholder="Например: Сдать проект по математике" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">📝 Описание (необязательно)</label>
                            <textarea name="description" id="description" placeholder="Подробности задания..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="deadline">📅 Дедлайн</label>
                                <input type="date" name="deadline" id="deadline" required value="<?php echo isset($_POST['deadline']) ? $_POST['deadline'] : date('Y-m-d', strtotime('+3 days')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="priority">⚡ Приоритет</label>
                                <select name="priority" id="priority">
                                    <option value="low">🟢 Низкий</option>
                                    <option value="medium" selected>🟡 Средний</option>
                                    <option value="high">🔴 Высокий</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="dashboard.php" class="btn btn-secondary">Отмена</a>
                            <button type="submit" class="btn btn-primary">➕ Создать задачу</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'include/footer.php'; ?>
    <script>
    const modeSelect = document.getElementById('assign_mode');
    const targetBlock = document.getElementById('targetUserBlock');
    if (modeSelect && targetBlock) {
        const syncVisibility = () => {
            targetBlock.style.display = modeSelect.value === 'one' ? 'block' : 'none';
        };
        modeSelect.addEventListener('change', syncVisibility);
        syncVisibility();
    }
    </script>
</body>
</html>