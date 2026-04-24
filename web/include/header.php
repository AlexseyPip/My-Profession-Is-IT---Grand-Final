<?php
// Получаем имя пользователя из сессии или БД
$user_name = 'Пользователь';
if(isset($_SESSION['user_id'])) {
    // Если в сессии нет name, пробуем получить из БД
    if(!isset($_SESSION['name'])) {
        if(!isset($pdo)) {
            include_once __DIR__ . '/db_connect.php';
        }
        if(isset($pdo)) {
            $stmt = $pdo->prepare("SELECT first_name FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            $_SESSION['name'] = $user['first_name'] ?? 'Пользователь';
        } else {
            $_SESSION['name'] = 'Пользователь';
        }
    }
    $user_name = $_SESSION['name'];
}
?>

<header class="dashboard-header">
    <div class="container">
        <div class="header-content">
            <a href="dashboard.php" class="logo">
                <!-- <div class="logo-icon">✓</div> -->
                <img src="img/logo.png" style="width: 65px; height: 45px;">
                <span>УчиДобро</span>
            </a>
            <button class="nav-toggle" id="navToggle" aria-label="Открыть меню">☰</button>
            <nav class="nav-links">
                <a href="dashboard.php">📊 Панель</a>
                <a href="tasks.php">📝 Задачи</a>
                <a href="add_task.php">➕ Новая</a>
                <?php if(isset($_SESSION['role']) && in_array($_SESSION['role'], ['teacher', 'parent'], true)): ?>
                    <a href="relationships.php">👥 Ученики</a>
                <?php endif; ?>
                <div class="user-menu">
                    <span class="user-name">👤 <?php echo htmlspecialchars($user_name); ?></span>
                    <div class="user-dropdown">
                        <a href="profile.php">Профиль</a>
                        <a href="logout.php">Выйти</a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</header>

<style>
.dashboard-header {
    background: var(--white);
    box-shadow: var(--shadow-sm);
    position: sticky;
    top: 0;
    z-index: 100;
}

.dashboard-header .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 70px;
    max-width: var(--container-max);
    margin: 0 auto;
    padding: 0 var(--container-padding);
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    font-size: 1.25rem;
    font-weight: 700;
}

.logo-icon {
    width: 32px;
    height: 32px;
    background: var(--gradient-primary);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.nav-links {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}

.nav-toggle {
    display: none;
    border: none;
    background: transparent;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--gray-700);
}

.nav-links a {
    color: var(--gray-700);
    text-decoration: none;
    transition: var(--transition);
}

.nav-links a:hover {
    color: var(--primary);
}

.user-menu {
    position: relative;
    cursor: pointer;
}

.user-name {
    padding: 0.5rem 1rem;
    background: var(--gray-100);
    border-radius: var(--radius);
    transition: var(--transition);
}

.user-name:hover {
    background: var(--gray-200);
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    min-width: 150px;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
}

.user-menu:hover .user-dropdown {
    opacity: 1;
    visibility: visible;
}

.user-dropdown a {
    display: block;
    padding: 0.75rem 1rem;
    color: var(--gray-700);
}

.user-dropdown a:hover {
    background: var(--gray-100);
}

@media (max-width: 900px) {
    .dashboard-header .header-content {
        height: auto;
        min-height: 70px;
        flex-wrap: wrap;
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
        gap: 0.75rem;
    }

    .nav-toggle {
        display: block;
        margin-left: auto;
    }

    .nav-links {
        display: none;
        width: 100%;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem;
        background: var(--gray-100);
        border-radius: var(--radius);
    }

    .nav-links.open {
        display: flex;
    }

    .user-menu {
        width: 100%;
    }

    .user-name {
        display: inline-block;
        width: 100%;
    }

    .user-dropdown {
        position: static;
        opacity: 1;
        visibility: visible;
        margin-top: 0.5rem;
        box-shadow: none;
        background: transparent;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('navToggle');
    const nav = document.querySelector('.nav-links');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function() {
        nav.classList.toggle('open');
    });
});
</script>