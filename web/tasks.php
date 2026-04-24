<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'include/db_connect.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student';

$managed_student_ids = [];
if($role === 'teacher') {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id
        FROM users u
        JOIN teacher_classes tc ON tc.class_name = u.class_letter
        WHERE tc.teacher_id = ? AND u.role = 'student'
          AND u.school = (SELECT school FROM users WHERE id = ?)
    ");
    $stmt->execute([$user_id, $user_id]);
    $managed_rows = $stmt->fetchAll();
    $managed_student_ids = array();
    foreach($managed_rows as $row) {
        $managed_student_ids[] = (int)$row['id'];
    }
} elseif($role === 'parent') {
    $stmt = $pdo->prepare("SELECT child_user_id as id FROM parent_children WHERE parent_id = ?");
    $stmt->execute([$user_id]);
    $managed_rows = $stmt->fetchAll();
    $managed_student_ids = array();
    foreach($managed_rows as $row) {
        $managed_student_ids[] = (int)$row['id'];
    }
}

$can_manage_sql = $managed_student_ids ? implode(',', array_fill(0, count($managed_student_ids), '?')) : '';

// Обновление статуса
if(isset($_POST['update_status'])) {
    $task_id = (int)$_POST['task_id'];
    $status = $_POST['status'];

    if($can_manage_sql) {
        $params = array_merge([$status, $task_id, $user_id], $managed_student_ids);
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND (user_id = ? OR user_id IN ($can_manage_sql))");
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$status, $task_id, $user_id]);
    }
    header("Location: tasks.php");
    exit();
}

// Удаление задачи
if(isset($_GET['delete'])) {
    $task_id = (int)$_GET['delete'];
    if($can_manage_sql) {
        $params = array_merge([$task_id, $user_id, $user_id], $managed_student_ids);
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND (user_id = ? OR creator_id = ? OR user_id IN ($can_manage_sql))");
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$task_id, $user_id]);
    }
    header("Location: tasks.php?deleted=1");
    exit();
}

// Получаем все задачи
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT t.*, u.first_name, u.last_name, u.class_letter FROM tasks t JOIN users u ON u.id = t.user_id WHERE (t.user_id = ?";
$params = [$user_id];
if($can_manage_sql) {
    $query .= " OR t.user_id IN ($can_manage_sql)";
    $params = array_merge($params, $managed_student_ids);
}
$query .= ")";

if($filter == 'completed') {
    $query .= " AND status = 'completed'";
} elseif($filter == 'pending') {
    $query .= " AND status = 'pending'";
} elseif($filter == 'in_progress') {
    $query .= " AND status = 'in_progress'";
} elseif($filter == 'overdue') {
    $query .= " AND status != 'completed' AND deadline < CURDATE()";
} elseif($filter == 'high') {
    $query .= " AND priority = 'high' AND status != 'completed'";
}

$query .= " ORDER BY 
    CASE 
        WHEN deadline < CURDATE() AND status != 'completed' THEN 1
        WHEN priority = 'high' AND status != 'completed' THEN 2
        WHEN status = 'pending' THEN 3
        WHEN status = 'in_progress' THEN 4
        ELSE 5
    END,
    deadline ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Получаем статистику для фильтров
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status != 'completed' AND deadline < CURDATE() THEN 1 ELSE 0 END) as overdue,
        SUM(CASE WHEN priority = 'high' AND status != 'completed' THEN 1 ELSE 0 END) as highpriority
    FROM tasks 
    WHERE user_id = ? 
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои задачи - Todo Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <style>
        /* Tasks Page Styles */
        .tasks-page {
            background: var(--gray-100);
            min-height: calc(100vh - 70px);
            padding: 2rem 0;
        }
        
        /* Stats Bar */
        .stats-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .stat-badge {
            background: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        .stat-badge:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-badge .stat-icon {
            font-size: 1.5rem;
        }
        
        .stat-badge .stat-info {
            display: flex;
            flex-direction: column;
        }
        
        .stat-badge .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .stat-badge .stat-label {
            font-size: 0.75rem;
            color: var(--gray-600);
        }
        
        /* Filters */
        .filters-section {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 1rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
        }
        
        .filter-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            background: var(--white);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            color: var(--gray-700);
        }
        
        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .filter-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--white);
        }
        
        .add-task-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            background: var(--gradient-primary);
            color: var(--white);
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .add-task-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        /* Tasks Grid */
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }
        
        /* Task Card */
        .task-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .task-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }
        
        .task-card.overdue-card {
            border-left: 4px solid var(--danger);
        }
        
        .task-card.completed-card {
            opacity: 0.75;
            background: var(--gray-100);
        }
        
        .task-card.completed-card:hover {
            opacity: 0.9;
        }
        
        /* Card Header */
        .card-header {
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05) 0%, rgba(114, 9, 183, 0.05) 100%);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .priority-high {
            background: #fee;
            color: #c33;
        }
        
        .priority-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .priority-low {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-in_progress {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .status-completed {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .status-overdue {
            background: #ffebee;
            color: #d32f2f;
        }
        
        /* Card Body */
        .card-body {
            padding: 1.25rem 1.5rem;
        }
        
        .task-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }
        
        .task-description {
            color: var(--gray-600);
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .task-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.813rem;
            color: var(--gray-600);
        }
        
        .deadline.urgent {
            color: var(--danger);
            font-weight: 600;
        }
        
        /* Card Footer */
        .card-footer {
            padding: 1rem 1.5rem;
            background: var(--gray-100);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .status-form {
            flex: 1;
        }
        
        .status-select {
            width: 100%;
            padding: 0.5rem;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius);
            font-size: 0.813rem;
            background: var(--white);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .status-select:hover {
            border-color: var(--primary);
        }
        
        .delete-btn {
            padding: 0.5rem;
            color: var(--gray-500);
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.813rem;
        }
        
        .delete-btn:hover {
            color: var(--danger);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem;
            background: var(--white);
            border-radius: var(--radius-lg);
        }
        
        .empty-state .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .tasks-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-bar {
                justify-content: center;
            }
            
            .filters-section {
                flex-direction: column;
            }
            
            .filter-group {
                justify-content: center;
            }
            
            .card-footer {
                flex-direction: column;
            }
            
            .status-form {
                width: 100%;
            }
            
            .delete-btn {
                justify-content: center;
                width: 100%;
                padding: 0.5rem;
                background: var(--white);
                border-radius: var(--radius);
            }
        }
    </style>
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <div class="tasks-page">
        <div class="container">
            <!-- Статистика -->
            <div class="stats-bar">
                <div class="stat-badge">
                    <div class="stat-icon">📋</div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $stats['total'] ?? 0; ?></span>
                        <span class="stat-label">Всего</span>
                    </div>
                </div>
                <div class="stat-badge">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $stats['completed'] ?? 0; ?></span>
                        <span class="stat-label">Выполнено</span>
                    </div>
                </div>
                <div class="stat-badge">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $stats['pending'] ?? 0; ?></span>
                        <span class="stat-label">Ожидают</span>
                    </div>
                </div>
                <div class="stat-badge">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $stats['in_progress'] ?? 0; ?></span>
                        <span class="stat-label">В процессе</span>
                    </div>
                </div>
                <?php if(($stats['overdue'] ?? 0) > 0): ?>
                    <div class="stat-badge" style="border-left: 3px solid var(--danger);">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-info">
                            <span class="stat-value" style="color: var(--danger);"><?php echo $stats['overdue']; ?></span>
                            <span class="stat-label">Просрочено</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Фильтры -->
            <div class="filters-section">
                <div class="filter-group">
                    <a href="?filter=all" class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">📋 Все</a>
                    <a href="?filter=pending" class="filter-btn <?php echo $filter == 'pending' ? 'active' : ''; ?>">⏳ Ожидают</a>
                    <a href="?filter=in_progress" class="filter-btn <?php echo $filter == 'in_progress' ? 'active' : ''; ?>">🔄 В процессе</a>
                    <a href="?filter=completed" class="filter-btn <?php echo $filter == 'completed' ? 'active' : ''; ?>">✅ Выполнено</a>
                    <a href="?filter=overdue" class="filter-btn <?php echo $filter == 'overdue' ? 'active' : ''; ?>">⚠️ Просрочено</a>
                    <a href="?filter=high" class="filter-btn <?php echo $filter == 'high' ? 'active' : ''; ?>">🔴 Высокий приоритет</a>
                </div>
                <a href="add_task.php" class="add-task-btn">
                    ➕ Создать задачу
                </a>
            </div>
            
            <!-- Список задач -->
            <?php if(empty($tasks)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎉</div>
                    <p>У вас пока нет задач в этой категории</p>
                    <a href="add_task.php" class="btn btn-primary">➕ Создать задачу</a>
                </div>
            <?php else: ?>
                <div class="tasks-grid">
                    <?php foreach($tasks as $task): 
                        $is_overdue = (strtotime($task['deadline']) < time() && $task['status'] != 'completed');
                        $is_completed = ($task['status'] == 'completed');
                        $card_class = '';
                        if($is_overdue) $card_class = 'overdue-card';
                        if($is_completed) $card_class = 'completed-card';
                    ?>
                        <div class="task-card <?php echo $card_class; ?>">
                            <div class="card-header">
                                <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                    <?php
                                    $priority_icons = ['low' => '🟢', 'medium' => '🟡', 'high' => '🔴'];
                                    $priority_names = ['low' => 'Низкий', 'medium' => 'Средний', 'high' => 'Высокий'];
                                    echo $priority_icons[$task['priority']] . ' ' . $priority_names[$task['priority']];
                                    ?>
                                </span>
                                <span class="status-badge status-<?php echo $task['status']; ?>">
                                    <?php
                                    $status_icons = ['pending' => '⏳', 'in_progress' => '🔄', 'completed' => '✅', 'overdue' => '⚠️'];
                                    $status_names = ['pending' => 'Ожидает', 'in_progress' => 'В процессе', 'completed' => 'Выполнено', 'overdue' => 'Просрочено'];
                                    echo $status_icons[$task['status']] . ' ' . $status_names[$task['status']];
                                    ?>
                                </span>
                            </div>
                            
                            <div class="card-body">
                                <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                                <?php if((int)$task['user_id'] !== (int)$user_id): ?>
                                    <p class="task-description">👤 Ученик: <?php echo htmlspecialchars($task['last_name'] . ' ' . $task['first_name'] . ' (' . $task['class_letter'] . ')'); ?></p>
                                <?php endif; ?>
                                <?php if(!empty($task['description'])): ?>
                                    <p class="task-description"><?php echo nl2br(htmlspecialchars(substr($task['description'], 0, 120))); ?><?php echo strlen($task['description']) > 120 ? '...' : ''; ?></p>
                                <?php endif; ?>
                                
                                <div class="task-meta">
                                    <div class="meta-item deadline <?php echo $is_overdue ? 'urgent' : ''; ?>">
                                        📅 <?php echo date('d.m.Y', strtotime($task['deadline'])); ?>
                                        <?php if($is_overdue): ?>
                                            <span style="color: var(--danger);">(Просрочено!)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="meta-item">
                                        🕐 Создано: <?php echo date('d.m.Y', strtotime($task['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $task['status']=='pending' ? 'selected' : ''; ?>>⏳ Ожидает</option>
                                        <option value="in_progress" <?php echo $task['status']=='in_progress' ? 'selected' : ''; ?>>🔄 В процессе</option>
                                        <option value="completed" <?php echo $task['status']=='completed' ? 'selected' : ''; ?>>✅ Выполнено</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                                <a href="?delete=<?php echo $task['id']; ?>" class="delete-btn" onclick="return confirm('❓ Точно удалить задачу «<?php echo htmlspecialchars($task['title']); ?>»?')">
                                    🗑️ Удалить
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'include/footer.php'; ?>
</body>
</html>