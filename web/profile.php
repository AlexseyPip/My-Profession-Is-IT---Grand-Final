<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'include/db_connect.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$role = $user['role'] ?? 'student';

$teacher_classes = [];
$parent_children = [];
if($role === 'teacher') {
    $stmt = $pdo->prepare("SELECT class_name FROM teacher_classes WHERE teacher_id = ? ORDER BY class_name");
    $stmt->execute([$user_id]);
    $teacher_rows = $stmt->fetchAll();
    $teacher_classes = array();
    foreach($teacher_rows as $row) {
        $teacher_classes[] = $row['class_name'];
    }
} elseif($role === 'parent') {
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email
        FROM parent_children pc
        JOIN users u ON u.id = pc.child_user_id
        WHERE pc.parent_id = ?
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute([$user_id]);
    $parent_children = $stmt->fetchAll();
}

// Получаем статистику
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN status != 'completed' AND deadline < CURDATE() THEN 1 ELSE 0 END) as overdue_tasks,
        SUM(CASE WHEN priority = 'high' AND status != 'completed' THEN 1 ELSE 0 END) as high_priority_tasks
    FROM tasks 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Обновление профиля
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $school = trim($_POST['school']);
    $class_letter = trim($_POST['class_letter']);
    $classes_input = trim($_POST['teacher_classes'] ?? '');
    $children_input = trim($_POST['children_identifiers'] ?? '');
    
    if(empty($first_name) || empty($last_name)) {
        $error_message = "Имя и фамилия обязательны для заполнения";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, school = ?, class_letter = ? WHERE id = ?");
        if($stmt->execute([$first_name, $last_name, $school, $class_letter, $user_id])) {
            $_SESSION['name'] = $first_name;
            $success_message = "Профиль успешно обновлён!";
            // Обновляем данные в переменной
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['school'] = $school;
            $user['class_letter'] = $class_letter;

            if($role === 'teacher') {
                $classes = preg_split('/[\s,;]+/', mb_strtoupper($classes_input), -1, PREG_SPLIT_NO_EMPTY);
                $classes = array_values(array_unique(array_map('trim', $classes)));
                $pdo->prepare("DELETE FROM teacher_classes WHERE teacher_id = ?")->execute([$user_id]);
                if(!empty($classes)) {
                    $insert = $pdo->prepare("INSERT INTO teacher_classes (teacher_id, class_name) VALUES (?, ?)");
                    foreach($classes as $class_name) {
                        $insert->execute([$user_id, $class_name]);
                    }
                }
                $teacher_classes = $classes;
            }

            if($role === 'parent') {
                $identifiers = preg_split('/[\r\n,;]+/', $children_input, -1, PREG_SPLIT_NO_EMPTY);
                $identifiers = array_values(array_unique(array_map('trim', $identifiers)));
                $pdo->prepare("DELETE FROM parent_children WHERE parent_id = ?")->execute([$user_id]);
                $insert = $pdo->prepare("INSERT INTO parent_children (parent_id, child_user_id) VALUES (?, ?)");
                foreach($identifiers as $identifier) {
                    if($identifier === '') continue;
                    $is_id = ctype_digit($identifier);
                    $query = $is_id
                        ? "SELECT id FROM users WHERE role = 'student' AND id = ?"
                        : "SELECT id FROM users WHERE role = 'student' AND email = ?";
                    $child_stmt = $pdo->prepare($query);
                    $child_stmt->execute([$identifier]);
                    $child = $child_stmt->fetch();
                    if($child) {
                        $insert->execute([$user_id, $child['id']]);
                    }
                }
                $stmt = $pdo->prepare("
                    SELECT u.id, u.first_name, u.last_name, u.email
                    FROM parent_children pc
                    JOIN users u ON u.id = pc.child_user_id
                    WHERE pc.parent_id = ?
                    ORDER BY u.last_name, u.first_name
                ");
                $stmt->execute([$user_id]);
                $parent_children = $stmt->fetchAll();
            }
        } else {
            $error_message = "Ошибка при обновлении профиля";
        }
    }
}

// Смена пароля
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Заполните все поля";
    } elseif($new_password !== $confirm_password) {
        $error_message = "Новый пароль и подтверждение не совпадают";
    } elseif(strlen($new_password) < 6) {
        $error_message = "Новый пароль должен быть не менее 6 символов";
    } elseif(!password_verify($current_password, $user['password'])) {
        $error_message = "Текущий пароль неверен";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if($stmt->execute([$hashed_password, $user_id])) {
            $success_message = "Пароль успешно изменён!";
        } else {
            $error_message = "Ошибка при смене пароля";
        }
    }
}

$completion_rate = ($stats['total_tasks'] > 0) ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - УчиДобро</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <style>
        .profile-page {
            background: var(--gray-100);
            min-height: calc(100vh - 70px);
            padding: 2rem 0;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Profile Header */
        .profile-header {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
            box-shadow: var(--shadow-sm);
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--white);
        }
        
        .profile-info h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--gray-900);
        }
        
        .profile-info .profile-role {
            display: inline-block;
            padding: 0.25rem 1rem;
            background: var(--primary-light);
            color: var(--white);
            border-radius: var(--radius-full);
            font-size: 0.813rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        
        .profile-stats {
            margin-left: auto;
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .profile-stat {
            text-align: center;
        }
        
        .profile-stat .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .profile-stat .stat-label {
            font-size: 0.75rem;
            color: var(--gray-600);
        }
        
        /* Cards */
        .profile-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--gray-200);
            color: var(--gray-900);
        }
        
        /* Form */
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
            font-size: 0.875rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .form-group input:disabled {
            background: var(--gray-100);
            cursor: not-allowed;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .btn-save {
            background: var(--gradient-primary);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        /* Success/Error Messages */
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            border-left: 3px solid #28a745;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            border-left: 3px solid #dc3545;
        }
        
        /* Stats Grid */
        .stats-grid-profile {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card-profile {
            background: var(--white);
            border-radius: var(--radius);
            padding: 1rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        .stat-card-profile:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-emoji {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-number-profile {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label-profile {
            font-size: 0.75rem;
            color: var(--gray-600);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-stats {
                margin-left: 0;
                justify-content: center;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <div class="profile-page">
        <div class="container">
            <div class="profile-container">
                <!-- Успех/Ошибка -->
                <?php if($success_message): ?>
                    <div class="success-message">✓ <?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <?php if($error_message): ?>
                    <div class="error-message">⚠ <?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <!-- Шапка профиля -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php
                        $avatar_emoji = '';
                        if($role == 'student') $avatar_emoji = '🎓';
                        elseif($role == 'teacher') $avatar_emoji = '👨‍🏫';
                        else $avatar_emoji = '👪';
                        echo $avatar_emoji;
                        ?>
                    </div>
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="profile-role">
                            <?php
                            if($role == 'student') echo "🎓 Ученик";
                            elseif($role == 'teacher') echo "👨‍🏫 Учитель";
                            else echo "👪 Родитель";
                            ?>
                        </span>
                    </div>
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <div class="stat-value"><?php echo $stats['total_tasks'] ?? 0; ?></div>
                            <div class="stat-label">всего задач</div>
                        </div>
                        <div class="profile-stat">
                            <div class="stat-value"><?php echo $stats['completed_tasks'] ?? 0; ?></div>
                            <div class="stat-label">выполнено</div>
                        </div>
                        <div class="profile-stat">
                            <div class="stat-value"><?php echo $completion_rate; ?>%</div>
                            <div class="stat-label">прогресс</div>
                        </div>
                    </div>
                </div>
                
                <div class="stats-grid-profile">
                    <div class="stat-card-profile">
                        <div class="stat-emoji">⏳</div>
                        <div class="stat-number-profile"><?php echo $stats['pending_tasks'] ?? 0; ?></div>
                        <div class="stat-label-profile">В ожидании</div>
                    </div>
                    <div class="stat-card-profile">
                        <div class="stat-emoji">🔄</div>
                        <div class="stat-number-profile"><?php echo $stats['in_progress_tasks'] ?? 0; ?></div>
                        <div class="stat-label-profile">В процессе</div>
                    </div>
                    <div class="stat-card-profile">
                        <div class="stat-emoji">⚠️</div>
                        <div class="stat-number-profile"><?php echo $stats['overdue_tasks'] ?? 0; ?></div>
                        <div class="stat-label-profile">Просрочено</div>
                    </div>
                    <div class="stat-card-profile">
                        <div class="stat-emoji">🔴</div>
                        <div class="stat-number-profile"><?php echo $stats['high_priority_tasks'] ?? 0; ?></div>
                        <div class="stat-label-profile">Высокий приоритет</div>
                    </div>
                </div>
                
                <!-- Редактирование профиля -->
                <div class="profile-card">
                    <h2 class="card-title">✏️ Редактировать профиль</h2>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">Имя</label>
                                <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Фамилия</label>
                                <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="school">Школа</label>
                                <input type="text" name="school" id="school" value="<?php echo htmlspecialchars($user['school']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="class_letter">Класс</label>
                                <input type="text" name="class_letter" id="class_letter" value="<?php echo htmlspecialchars($user['class_letter']); ?>">
                            </div>
                        </div>

                        <?php if($role === 'teacher'): ?>
                        <div class="form-group">
                            <label for="teacher_classes">Классы, которые вы ведёте (можно несколько)</label>
                            <input type="text" name="teacher_classes" id="teacher_classes" placeholder="Например: 5А, 5Б, 6А" value="<?php echo htmlspecialchars(implode(', ', $teacher_classes)); ?>">
                        </div>
                        <?php endif; ?>

                        <?php if($role === 'parent'): ?>
                        <div class="form-group">
                            <label for="children_identifiers">Дети (вводом email или ID, через запятую/новую строку)</label>
                            <textarea name="children_identifiers" id="children_identifiers" rows="4" style="width:100%;padding:0.75rem 1rem;border:2px solid var(--gray-200);border-radius:var(--radius);"><?php
                                $parent_emails = array();
                                foreach($parent_children as $child) {
                                    $parent_emails[] = $child['email'];
                                }
                                echo htmlspecialchars(implode("\n", $parent_emails));
                            ?></textarea>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="email">Email (нельзя изменить)</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn-save">💾 Сохранить изменения</button>
                    </form>
                </div>

                <?php if($role === 'parent' && !empty($parent_children)): ?>
                <div class="profile-card">
                    <h2 class="card-title">👨‍👩‍👧 Привязанные дети</h2>
                    <?php foreach($parent_children as $child): ?>
                        <p><?php echo htmlspecialchars($child['last_name'] . ' ' . $child['first_name']); ?> — <?php echo htmlspecialchars($child['email']); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Смена пароля -->
                <div class="profile-card">
                    <h2 class="card-title">🔒 Сменить пароль</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Текущий пароль</label>
                            <input type="password" name="current_password" id="current_password" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">Новый пароль</label>
                                <input type="password" name="new_password" id="new_password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Подтверждение пароля</label>
                                <input type="password" name="confirm_password" id="confirm_password" required>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn-save">🔄 Сменить пароль</button>
                    </form>
                </div>
                
                <!-- Информация об аккаунте -->
                <div class="profile-card">
                    <h2 class="card-title">ℹ️ Информация об аккаунте</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Дата регистрации</label>
                            <input type="text" value="<?php echo date('d.m.Y', strtotime($user['created_at'])); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Дата рождения</label>
                            <input type="text" value="<?php echo date('d.m.Y', strtotime($user['birth_date'])); ?>" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'include/footer.php'; ?>
</body>
</html>