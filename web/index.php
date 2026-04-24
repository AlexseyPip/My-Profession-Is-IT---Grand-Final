<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo Tracker - Умный трекер задач для учеников</title>
    
    <!-- Мета теги -->
    <meta name="description" content="Перестань играть в археолога в поисках потерянных дел. Todo Tracker - личный помощник для управления учебными задачами.">
    <meta name="keywords" content="трекер задач, учеба, школьник, задания, контроль">
    
    <!-- CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/landing.css">
    <link rel="stylesheet" href="css/responsive.css">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <!-- PWA настройки для полного экрана -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="УчиДобро">
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#4361ee">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

<!-- Скрываем адресную строку на мобильных -->
<script>
// Форсируем полноэкранный режим
if ('standalone' in navigator && navigator.standalone) {
    // Уже в режиме приложения
    document.documentElement.style.setProperty('--safe-area-top', 'env(safe-area-inset-top)');
} else if (window.matchMedia('(display-mode: standalone)').matches) {
    // Тоже в режиме приложения
    document.documentElement.style.setProperty('--safe-area-top', 'env(safe-area-inset-top)');
}

// Для Android - скрываем навигацию после загрузки
window.addEventListener('load', function() {
    setTimeout(function() {
        window.scrollTo(0, 1);
    }, 100);
});
</script>

<style>
/* Убираем белые полосы и делаем полноэкранным */
:root {
    --safe-area-top: env(safe-area-inset-top);
    --safe-area-bottom: env(safe-area-inset-bottom);
}

body {
    padding-top: 0 !important;
    margin-top: 0 !important;
}

/* Убираем браузерную шапку в WebView */
body:fullscreen {
    overflow: auto !important;
}

/* Стили для PWA-режима */
@media all and (display-mode: standalone) {
    body {
        padding-top: var(--safe-area-top);
    }
    
    /* Скрываем элементы браузера если нужно */
    .browser-only {
        display: none !important;
    }
}
</style>

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>✓</text></svg>">
</head>
<body>
    <!-- Header -->
    <header class="landing-header" id="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="img/logo.png" style="width: 65px; height: 45px;">
                    <span>УчиДобро</span>
                </div>
                <button class="landing-nav-toggle" id="landingNavToggle" aria-label="Открыть меню">☰</button>
                <nav class="nav-links">
                    <a href="#problems">Проблемы</a>
                    <a href="#solutions">Решения</a>
                    <a href="#roles">Роли</a>
                    <a href="#how-it-works">Как работает</a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn btn-primary">Панель</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline">Войти</a>
                        <a href="register.php" class="btn btn-primary">Начать</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <div>
                    <h1 class="hero-title">
                        Перестань играть в археолога<br>
                        в поисках потерянных дел
                    </h1>
                    <p class="hero-subtitle">
                        Все задания в одном месте. Контроль, прогресс и мотивация.<br>
                        Забудь о потерянных домашках и просроченных дедлайнах.
                    </p>
                    <div class="hero-buttons">
                        <a href="register.php" class="btn btn-primary btn-large">Начать бесплатно →</a>
                        <a href="#how-it-works" class="btn btn-outline btn-large">Как это работает</a>
                    </div>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <span class="stat-number">10,000+</span>
                            <span class="stat-label">активных учеников</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">50,000+</span>
                            <span class="stat-label">выполненных задач</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">95%</span>
                            <span class="stat-label">повышение успеваемости</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Проблемы -->
    <section id="problems" class="problems-section">
        <div class="container">
            <h2 class="section-title">Знакомая ситуация?</h2>
            <p class="section-subtitle">Каждый день ты сталкиваешься с этими проблемами</p>
            <div class="problems-grid">
                <div class="problem-card">
                    <div class="problem-icon">📝</div>
                    <h3 class="problem-title">Забываешь задания</h3>
                    <p>Устные задания от учителя, задачи из чата класса, домашки из дневника — всё теряется в потоке информации</p>
                </div>
                <div class="problem-card">
                    <div class="problem-icon">🔍</div>
                    <h3 class="problem-title">Тратишь время на поиск</h3>
                    <p>Вместо выполнения тратишь силы на детективное расследование: "А что именно нужно сделать?"</p>
                </div>
                <div class="problem-card">
                    <div class="problem-icon">😓</div>
                    <h3 class="problem-title">Теряешь мотивацию</h3>
                    <p>Просроченные дедлайны, забытые задачи, хаос в учебе — всё это убивает желание учиться</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Решения -->
    <section id="solutions" class="solutions-section">
        <div class="container">
            <h2 class="section-title">Как мы это решаем?</h2>
            <p class="section-subtitle">Todo Tracker делает процесс обучения понятным и контролируемым</p>
            <div class="solutions-grid">
                <div class="solution-card card">
                    <div class="solution-icon">📋</div>
                    <h3>Все задачи в одном месте</h3>
                    <p>Создавай задачи откуда угодно: с телефона, компьютера, голосом. Ничего не потеряется</p>
                </div>
                <div class="solution-card card">
                    <div class="solution-icon">⏰</div>
                    <h3>Умные напоминания</h3>
                    <p>Система напомнит о дедлайне, а если игнорируешь — отправит сигнал родителям</p>
                </div>
                <div class="solution-card card">
                    <div class="solution-icon">📊</div>
                    <h3>Прогресс и мотивация</h3>
                    <p>Баллы, достижения, статистика — видишь свой рост и получаешь награды</p>
                </div>
                <div class="solution-card card">
                    <div class="solution-icon">👨‍👩‍👧</div>
                    <h3>Вовлечение родителей</h3>
                    <p>Родители видят прогресс и получают оповещения только в критических случаях</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Роли -->
    <section id="roles" class="roles-section">
        <div class="container">
            <h2 class="section-title">Для всех участников образования</h2>
            <p class="section-subtitle">Мы объединяем ученика, учителя и родителя в одном сервисе</p>
            <div class="roles-grid">
                <div class="role-card">
                    <div class="role-icon">🎓</div>
                    <h3 class="role-name">Ученик</h3>
                    <p class="role-description">Создавай и отслеживай задачи, получай напоминания, копи баллы и достигай новых вершин</p>
                </div>
                <div class="role-card">
                    <div class="role-icon">👨‍🏫</div>
                    <h3 class="role-name">Учитель</h3>
                    <p class="role-description">Создавай задания для класса, проверяй работы, оставляй комментарии и оценки</p>
                </div>
                <div class="role-card">
                    <div class="role-icon">👪</div>
                    <h3 class="role-name">Родитель</h3>
                    <p class="role-description">Следи за прогрессом ребенка, получай оповещения о проблемах, ставьте совместные цели</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Как работает -->
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <h2 class="section-title">Простота в каждом шаге</h2>
            <p class="section-subtitle">Всего 4 шага к организованной учебе</p>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Создай задачу</h3>
                    <p>Добавь описание, дедлайн, приоритет. Можно голосом или текстом</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Получай напоминания</h3>
                    <p>Система напомнит о дедлайне в удобное время</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Выполняй и отмечай</h3>
                    <p>Отметь задачу выполненной, получи баллы и комментарии учителя</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Следи за прогрессом</h3>
                    <p>Смотри статистику, получай достижения и улучшай результаты</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Готов начать учиться эффективнее?</h2>
            <p>Присоединяйся к тысячам учеников, которые уже используют Todo Tracker</p>
            <div class="cta-buttons">
                <a href="register.php" class="btn btn-primary btn-large">Зарегистрироваться бесплатно</a>
                <a href="#solutions" class="btn btn-outline btn-large">Узнать больше</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <div class="footer-logo">УчиДобро</div>
                    <p>Помогаем не забывать важное!</p>
                </div>
                <div class="footer-links">
                    <h4>Продукт</h4>
                    <a href="#how-it-works">Как работает</a>
                    <a href="#solutions">Возможности</a>
                    <a href="#roles">Для кого</a>
                </div>
                <div class="footer-links">
                    <h4>Поддержка</h4>
                    <a href="#">Помощь</a>
                    <a href="#">Контакты</a>
                    <a href="#">FAQ</a>
                </div>
                <div class="footer-links">
                    <h4>Правовая информация</h4>
                    <a href="#">Политика конфиденциальности</a>
                    <a href="#">Пользовательское соглашение</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Todo Tracker. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Эффект скролла для шапки
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Плавный скролл для якорных ссылок
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Анимация появления элементов при скролле
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.problem-card, .solution-card, .role-card, .step').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });

        const landingToggle = document.getElementById('landingNavToggle');
        const landingNav = document.querySelector('.landing-header .nav-links');
        if (landingToggle && landingNav) {
            landingToggle.addEventListener('click', () => {
                landingNav.classList.toggle('open');
            });
        }
    </script>

    <script src="/js/notifications.js"></script>
    <!-- Регистрация PWA -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => {
                console.log('✅ Service Worker зарегистрирован:', reg.scope);
                
                // Проверяем обновления
                reg.addEventListener('updatefound', () => {
                    const newWorker = reg.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // Показываем уведомление о новой версии
                            if (confirm('Доступна новая версия! Обновить?')) {
                                window.location.reload();
                            }
                        }
                    });
                });
            })
            .catch(err => console.log('❌ Ошибка Service Worker:', err));
    });
}

// Проверка соединения
window.addEventListener('online', () => {
    document.body.classList.remove('offline-mode');
    if (window.notifications) {
        window.notifications.show('✅ Соединение восстановлено', 'Приложение снова в сети');
    }
});

window.addEventListener('offline', () => {
    document.body.classList.add('offline-mode');
    if (window.notifications) {
        window.notifications.show('📡 Вы офлайн', 'Некоторые функции будут недоступны');
    }
});

// Запрос разрешения на уведомления через 3 секунды
setTimeout(() => {
    if (window.notifications && !localStorage.getItem('notifications_asked')) {
        localStorage.setItem('notifications_asked', 'true');
        if (confirm('🔔 Получать уведомления о дедлайнах?')) {
            window.notifications.init();
        }
    }
}, 3000);
</script>
</body>
</html>