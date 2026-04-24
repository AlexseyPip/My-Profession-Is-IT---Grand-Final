<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'include/db_connect.php';

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student';

if(!in_array($role, ['teacher', 'parent'], true)) {
    header("Location: dashboard.php");
    exit();
}

$children = [];
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
    $children = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.class_letter
        FROM parent_children pc
        JOIN users u ON u.id = pc.child_user_id
        WHERE pc.parent_id = ? AND u.role = 'student'
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute([$user_id]);
    $children = $stmt->fetchAll();
}

$child_ids = array();
foreach($children as $row) {
    $child_ids[] = (int)$row['id'];
}
$selected_child = isset($_GET['child']) ? (int)$_GET['child'] : (isset($_POST['child']) ? (int)$_POST['child'] : 0);
$selected_status = $_GET['status'] ?? ($_POST['status_filter'] ?? 'all');
$search_query = trim($_GET['q'] ?? ($_POST['q_filter'] ?? ''));
$allowed_statuses = ['all', 'pending', 'in_progress', 'completed', 'overdue'];
if(!in_array($selected_status, $allowed_statuses, true)) {
    $selected_status = 'all';
}

$editable_statuses = ['pending', 'in_progress', 'completed'];

if($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($child_ids)) {
    $task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $action = $_POST['action'] ?? '';

    if($task_id > 0) {
        $placeholders = implode(',', array_fill(0, count($child_ids), '?'));
        $checkQuery = "SELECT id FROM tasks WHERE id = ? AND user_id IN ($placeholders) LIMIT 1";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute(array_merge([$task_id], $child_ids));
        $allowedTask = $checkStmt->fetch();

        if($allowedTask) {
            if($action === 'delete') {
                $delStmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
                $delStmt->execute([$task_id]);
            } elseif($action === 'status') {
                $new_status = $_POST['status'] ?? '';
                if(in_array($new_status, $editable_statuses, true)) {
                    $upStmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
                    $upStmt->execute([$new_status, $task_id]);
                }
            }
        }
    }

    $queryString = http_build_query([
        'child' => $selected_child,
        'status' => $selected_status,
        'q' => $search_query
    ]);
    header("Location: relationships.php" . ($queryString ? "?$queryString" : ""));
    exit();
}

$tasks = [];
if(!empty($child_ids)) {
    $placeholders = implode(',', array_fill(0, count($child_ids), '?'));
    $query = "
        SELECT t.*, u.first_name, u.last_name, u.class_letter
        FROM tasks t
        JOIN users u ON u.id = t.user_id
        WHERE t.user_id IN ($placeholders)
    ";
    $params = $child_ids;

    if($selected_child > 0 && in_array($selected_child, $child_ids, true)) {
        $query .= " AND t.user_id = ?";
        $params[] = $selected_child;
    }

    if($selected_status !== 'all') {
        if($selected_status === 'overdue') {
            $query .= " AND t.status != 'completed' AND t.deadline < CURDATE()";
        } else {
            $query .= " AND t.status = ?";
            $params[] = $selected_status;
        }
    }

    if($search_query !== '') {
        $query .= " AND (t.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $search_like = '%' . $search_query . '%';
        $params[] = $search_like;
        $params[] = $search_like;
        $params[] = $search_like;
    }

    $query .= " ORDER BY t.deadline ASC, t.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
}

$status_labels = [
    'pending' => 'Ожидает',
    'in_progress' => 'В процессе',
    'completed' => 'Выполнено',
    'overdue' => 'Просрочено'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ученики и задачи - УчиДобро</title>
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/responsive.css">
    <style>
        .rel-page { background: var(--gray-100); min-height: calc(100vh - 70px); padding: 2rem 0; }
        .rel-card { background: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.25rem; margin-bottom: 1rem; }
        .rel-title { font-size: 1.4rem; margin-bottom: 0.5rem; }
        .chips { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; }
        .chip { background: var(--gray-100); padding: 0.45rem 0.75rem; border-radius: var(--radius-full); font-size: 0.85rem; }
        .filters { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 0.75rem; align-items: end; }
        .tasks { display: grid; gap: 0.75rem; }
        .task { background: var(--white); border-radius: var(--radius); padding: 1rem; box-shadow: var(--shadow-sm); }
        .meta { color: var(--gray-600); font-size: 0.85rem; margin-top: 0.4rem; display: flex; flex-wrap: wrap; gap: 0.75rem; }
        .task-actions { display: grid; grid-template-columns: 1fr auto; gap: 0.5rem; margin-top: 0.7rem; align-items: center; }
        .danger-btn { border: 2px solid #e35d6a; color: #e35d6a; background: transparent; border-radius: var(--radius); padding: 0.45rem 0.7rem; cursor: pointer; }
        .danger-btn:hover { background: #e35d6a; color: #fff; }
        @media (max-width: 768px) { .filters { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'include/header.php'; ?>
    <main class="rel-page">
        <div class="container">
            <section class="rel-card">
                <h1 class="rel-title"><?php echo $role === 'teacher' ? 'Мои ученики' : 'Мои дети'; ?></h1>
                <p><?php echo $role === 'teacher' ? 'Список учеников из ваших классов и их задач.' : 'Список привязанных детей и их задач.'; ?></p>
                <div class="chips">
                    <?php if(empty($children)): ?>
                        <span class="chip">Пока никого нет. Добавьте классы/детей в профиле.</span>
                    <?php else: ?>
                        <?php foreach($children as $child): ?>
                            <span class="chip"><?php echo htmlspecialchars($child['last_name'] . ' ' . $child['first_name'] . ' (' . $child['class_letter'] . ')'); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="rel-card">
                <form method="GET" class="filters">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="child">Ученик</label>
                        <select id="child" name="child" class="form-select">
                            <option value="0">Все</option>
                            <?php foreach($children as $child): ?>
                                <option value="<?php echo (int)$child['id']; ?>" <?php echo $selected_child === (int)$child['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($child['last_name'] . ' ' . $child['first_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="status">Статус</label>
                        <select id="status" name="status" class="form-select">
                            <option value="all" <?php echo $selected_status === 'all' ? 'selected' : ''; ?>>Все</option>
                            <option value="pending" <?php echo $selected_status === 'pending' ? 'selected' : ''; ?>>Ожидает</option>
                            <option value="in_progress" <?php echo $selected_status === 'in_progress' ? 'selected' : ''; ?>>В процессе</option>
                            <option value="completed" <?php echo $selected_status === 'completed' ? 'selected' : ''; ?>>Выполнено</option>
                            <option value="overdue" <?php echo $selected_status === 'overdue' ? 'selected' : ''; ?>>Просрочено</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" for="q">Поиск</label>
                        <input type="text" id="q" name="q" class="form-input" placeholder="Название задачи или ФИО" value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Применить</button>
                </form>
            </section>

            <section class="tasks">
                <?php if(empty($tasks)): ?>
                    <div class="rel-card">Задачи по выбранным фильтрам не найдены.</div>
                <?php else: ?>
                    <?php foreach($tasks as $task): ?>
                        <article class="task">
                            <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                            <div class="meta">
                                <span>👤 <?php echo htmlspecialchars($task['last_name'] . ' ' . $task['first_name']); ?></span>
                                <span>🏫 <?php echo htmlspecialchars($task['class_letter']); ?></span>
                                <span>📅 <?php echo date('d.m.Y', strtotime($task['deadline'])); ?></span>
                                <span>📌 <?php echo htmlspecialchars($status_labels[$task['status']] ?? $task['status']); ?></span>
                            </div>
                            <div class="task-actions">
                                <form method="POST" style="display:flex; gap:0.5rem;">
                                    <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="child" value="<?php echo (int)$selected_child; ?>">
                                    <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($selected_status); ?>">
                                    <input type="hidden" name="q_filter" value="<?php echo htmlspecialchars($search_query); ?>">
                                    <select name="status" class="form-select" style="min-width:170px;">
                                        <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Ожидает</option>
                                        <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>В процессе</option>
                                        <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Выполнено</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary">Сохранить</button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Удалить эту задачу?');">
                                    <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="child" value="<?php echo (int)$selected_child; ?>">
                                    <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($selected_status); ?>">
                                    <input type="hidden" name="q_filter" value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button type="submit" class="danger-btn">Удалить</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <?php include 'include/footer.php'; ?>
</body>
</html>
