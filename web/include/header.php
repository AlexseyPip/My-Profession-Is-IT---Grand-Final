<header>
    <div class="logo">
        <img src="img/logo.png" alt="Todo Tracker" width="40">
        <span>Todo Tracker</span>
    </div>
    <nav>
        <a href="index.php">Главная</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php">Панель</a>
            <a href="tasks.php">Задачи</a>
            <a href="profile.php">Профиль</a>
            <a href="logout.php">Выйти</a>
        <?php endif; ?>
    </nav>
</header>