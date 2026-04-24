<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'include/db_connect.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$user_name = $_SESSION['name'];

// Получаем статистику по задачам
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'overdue' AND deadline < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue
    FROM tasks 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Получаем последние 5 задач
$stmt = $pdo->prepare("
    SELECT * FROM tasks 
    WHERE user_id = ? 
    ORDER BY 
        CASE 
            WHEN status = 'overdue' AND deadline < CURDATE() THEN 1
            WHEN status = 'pending' THEN 2
            WHEN status = 'in_progress' THEN 3
            ELSE 4
        END,
        deadline ASC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_tasks = $stmt->fetchAll();

// Получаем задачи с высоким приоритетом
$stmt = $pdo->prepare("
    SELECT * FROM tasks 
    WHERE user_id = ? AND priority = 'high' AND status != 'completed'
    ORDER BY deadline ASC 
    LIMIT 3
");
$stmt->execute([$user_id]);
$high_priority_tasks = $stmt->fetchAll();

// Получаем статистику по дням (для графика)
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
    FROM tasks 
    WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$user_id]);
$weekly_stats = $stmt->fetchAll();

// Процент выполнения
$completion_rate = ($stats['total'] > 0) ? round(($stats['completed'] / $stats['total']) * 100) : 0;

// Если учитель - получаем задания от учителя
$teacher_tasks = [];
if($role == 'student') {
    $stmt = $pdo->prepare("
        SELECT t.*, u.first_name as teacher_name 
        FROM tasks t
        JOIN users u ON t.teacher_id = u.id
        WHERE t.user_id = ? AND t.created_by = 'teacher'
        ORDER BY t.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $teacher_tasks = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - Todo Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <main class="dashboard-main">
        <div class="container">
            <!-- Приветствие -->
            <div class="welcome-section">
                <div>
                    <h1 class="welcome-title">
                        Привет, <?php echo htmlspecialchars($user_name); ?>! 👋
                    </h1>
                    <p class="welcome-subtitle">
                        <?php
                        $hour = date('H');
                        if($hour < 12) echo "Доброе утро";
                        elseif($hour < 18) echo "Добрый день";
                        else echo "Добрый вечер";
                        ?>
                        , вот что у нас сегодня.
                    </p>
                </div>
                <div class="welcome-actions">
                    <a href="add_task.php" class="btn btn-primary">
                        ➕ Создать задачу
                    </a>
                </div>
            </div>
            
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $stats['total'] ?? 0; ?></span>
                        <span class="stat-label">Всего задач</span>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $stats['completed'] ?? 0; ?></span>
                        <span class="stat-label">Выполнено</span>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $stats['pending'] ?? 0; ?></span>
                        <span class="stat-label">В ожидании</span>
                    </div>
                </div>
                
                <div class="stat-card danger">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $stats['overdue'] ?? 0; ?></span>
                        <span class="stat-label">Просрочено</span>
                    </div>
                </div>
            </div>
            
            <!-- Прогресс-бар -->
            <div class="progress-section">
                <div class="progress-header">
                    <h3>Общий прогресс</h3>
                    <span class="progress-percent"><?php echo $completion_rate; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $completion_rate; ?>%"></div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <!-- Последние задачи -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>📝 Последние задачи</h2>
                        <a href="tasks.php" class="card-link">Все задачи →</a>
                    </div>
                    
                    <?php if(empty($recent_tasks)): ?>
                        <div class="empty-state">
                            <p>У вас пока нет задач</p>
                            <a href="add_task.php" class="btn btn-primary">Создать первую задачу</a>
                        </div>
                    <?php else: ?>
                        <div class="tasks-list">
                            <?php foreach($recent_tasks as $task): ?>
                                <div class="task-item <?php echo $task['status']; ?> priority-<?php echo $task['priority']; ?>">
                                    <div class="task-info">
                                        <div class="task-title">
                                            <?php echo htmlspecialchars($task['title']); ?>
                                            <?php if($task['priority'] == 'high'): ?>
                                                <span class="badge badge-danger">Высокий</span>
                                            <?php elseif($task['priority'] == 'medium'): ?>
                                                <span class="badge badge-warning">Средний</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Низкий</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="task-meta">
                                            <span>📅 Дедлайн: <?php echo date('d.m.Y', strtotime($task['deadline'])); ?></span>
                                            <span class="task-status status-<?php echo $task['status']; ?>">
                                                <?php
                                                $statuses = [
                                                    'pending' => '⏳ Ожидает',
                                                    'in_progress' => '🔄 В процессе',
                                                    'completed' => '✅ Выполнено',
                                                    'overdue' => '⚠️ Просрочено'
                                                ];
                                                echo $statuses[$task['status']] ?? $task['status'];
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <a href="tasks.php?id=<?php echo $task['id']; ?>" class="task-link">Подробнее →</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Высокий приоритет -->
                <?php if(!empty($high_priority_tasks)): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>🔴 Срочные задачи</h2>
                    </div>
                    <div class="tasks-list">
                        <?php foreach($high_priority_tasks as $task): ?>
                            <div class="task-item urgent">
                                <div class="task-info">
                                    <div class="task-title">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                    </div>
                                    <div class="task-meta">
                                        <span>📅 до <?php echo date('d.m.Y', strtotime($task['deadline'])); ?></span>
                                    </div>
                                </div>
                                <a href="tasks.php?id=<?php echo $task['id']; ?>" class="task-link">Выполнить →</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Задания от учителя -->
                <?php if($role == 'student' && !empty($teacher_tasks)): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>👨‍🏫 Задания от учителя</h2>
                    </div>
                    <div class="tasks-list">
                        <?php foreach($teacher_tasks as $task): ?>
                            <div class="task-item teacher-task">
                                <div class="task-info">
                                    <div class="task-title">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                    </div>
                                    <div class="task-meta">
                                        <span>👤 Учитель: <?php echo htmlspecialchars($task['teacher_name']); ?></span>
                                        <span>📅 до <?php echo date('d.m.Y', strtotime($task['deadline'])); ?></span>
                                    </div>
                                </div>
                                <a href="tasks.php?id=<?php echo $task['id']; ?>" class="task-link">Подробнее →</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
                        <!-- Мотивация с достижениями -->
                        <div class="motivation-card">
                <div class="motivation-content">
                    <div class="motivation-icon">
                        <?php
                        if($completion_rate >= 80) {
                            echo "🏆";
                        } elseif($completion_rate >= 50) {
                            echo "🚀";
                        } elseif($completion_rate >= 20) {
                            echo "📈";
                        } elseif($completion_rate > 0) {
                            echo "💪";
                        } else {
                            echo "🌟";
                        }
                        ?>
                    </div>
                    <div class="motivation-text">
                        <h3>
                            <?php 
                            // Достижения на основе количества выполненных задач
                            $completed_count = $stats['completed'] ?? 0;
                            $achievement = "";
                            
                            if($completed_count >= 50) {
                                $achievement = "🏅 Легенда продуктивности";
                            } elseif($completed_count >= 25) {
                                $achievement = "🎖️ Мастер организации";
                            } elseif($completed_count >= 10) {
                                $achievement = "⭐ Восходящая звезда";
                            } elseif($completed_count >= 5) {
                                $achievement = "🌱 Первые победы";
                            }
                            
                            if($completion_rate >= 80) {
                                if($completion_rate >= 95) {
                                    echo "Абсолютный чемпион! 👑 " . ($achievement ? "({$achievement})" : "");
                                } elseif($completion_rate >= 90) {
                                    echo "Легендарный результат! ⭐ " . ($achievement ? "({$achievement})" : "");
                                } else {
                                    echo "Фантастическая продуктивность! 🎯 " . ($achievement ? "({$achievement})" : "");
                                }
                            } elseif($completion_rate >= 50) {
                                if($completion_rate >= 70) {
                                    echo "Отличный темп! 🔥 " . ($achievement ? "({$achievement})" : "");
                                } elseif($completion_rate >= 60) {
                                    echo "Хороший прогресс! 📊 " . ($achievement ? "({$achievement})" : "");
                                } else {
                                    echo "Половина пути позади! 🎉 " . ($achievement ? "({$achievement})" : "");
                                }
                            } elseif($completion_rate >= 20) {
                                if($completion_rate >= 35) {
                                    echo "Виден результат! 🌱 " . ($achievement ? "({$achievement})" : "");
                                } else {
                                    echo "Первый шаг сделан! 🌅 " . ($achievement ? "({$achievement})" : "");
                                }
                            } elseif($completion_rate > 0) {
                                echo "Ты уже начал! ✨ " . ($achievement ? "({$achievement})" : "");
                            } else {
                                $pending_count = $stats['pending'] ?? 0;
                                if($pending_count > 0) {
                                    echo "Время действовать! ⚡";
                                } else {
                                    echo "Давай создадим цель! 🎯";
                                }
                            }
                            ?>
                        </h3>
                        <p>
                            <?php 
                            if($completion_rate >= 80) {
                                if($completion_rate >= 95) {
                                    echo "🔥 ТЫ НЕВЕРОЯТЕН! $completed_count задач выполнено! Осталась последняя — ты справишься!";
                                } elseif($completion_rate >= 90) {
                                    echo "⭐ Почти идеально! $completed_count дел закрыто. Горжусь твоей дисциплиной!";
                                } else {
                                    echo "🚀 Мощный рывок! $completed_count задач выполнено. Ты на связи с успехом!";
                                }
                            } elseif($completion_rate >= 50) {
                                if($completion_rate >= 70) {
                                    echo "🎯 Уже $completed_count задач! Ещё немного — и прорыв. Ты на правильном пути!";
                                } elseif($completion_rate >= 60) {
                                    echo "📊 Отличная статистика! $completed_count выполненных задач. Держи ритм!";
                                } else {
                                    echo "🎉 $completed_count задач за плечами! Половина пути пройдена. Так держать!";
                                }
                            } elseif($completion_rate >= 20) {
                                if($completion_rate >= 35) {
                                    echo "🌱 Уже $completed_count задач! Прогресс очевиден. Ты становишься продуктивнее!";
                                } else {
                                    echo "✨ Первые $completed_count задач! Это только цветочки, ягодки впереди!";
                                }
                            } elseif($completion_rate > 0) {
                                echo "💪 Ура! Первая задача выполнена! Это $completed_count шаг к твоей продуктивности. Продолжай!";
                            } else {
                                $pending_count = $stats['pending'] ?? 0;
                                if($pending_count > 0) {
                                    echo "📝 У тебя $pending_count задач в списке. Самое время превратить их в галочки 'Выполнено'!";
                                } else {
                                    echo "✨ Начни с малого — создай свою первую задачу. Каждое великое достижение начинается с первого шага!";
                                }
                            }
                            ?>
                        </p>
                    </div>
                    <div class="motivation-stats">
                        <div class="motivation-stat">
                            <span class="stat-number"><?php echo ($stats['completed'] ?? 0) * 10; ?></span>
                            <span class="stat-label">очков</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'include/footer.php'; ?>
</body>
</html>