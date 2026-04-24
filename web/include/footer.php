<!-- Footer -->
<footer class="main-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- О проекте -->
            <div class="footer-col">
                <div class="footer-logo">
                    <img src="img/logo.png" style="width: 45px; height: 32px;">
                    <span>УчиДобро</span>
                </div>
                <p class="footer-description">
                    Умный трекер задач, который помогает ученикам не забывать о важных делах, 
                    повышать успеваемость и оставаться мотивированными.
                </p>
                <div class="social-links">
                    <a href="#" class="social-link" title="Telegram">📱</a>
                    <a href="#" class="social-link" title="VK">📘</a>
                    <a href="#" class="social-link" title="YouTube">▶️</a>
                    <a href="#" class="social-link" title="GitHub">💻</a>
                </div>
            </div>
            
            <!-- Навигация -->
            <div class="footer-col">
                <h4 class="footer-title">Навигация</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Главная</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php">Панель управления</a></li>
                        <li><a href="tasks.php">Мои задачи</a></li>
                        <li><a href="add_task.php">Создать задачу</a></li>
                        <?php if(isset($_SESSION['role']) && in_array($_SESSION['role'], ['teacher', 'parent'], true)): ?>
                            <li><a href="relationships.php">Ученики и задачи</a></li>
                        <?php endif; ?>
                        <li><a href="profile.php">Профиль</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Вход</a></li>
                        <li><a href="register.php">Регистрация</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Информация -->
            <div class="footer-col">
                <h4 class="footer-title">Информация</h4>
                <ul class="footer-links">
                    <li><a href="#how-it-works">Как это работает</a></li>
                    <li><a href="#solutions">Возможности</a></li>
                    <li><a href="#roles">Для кого</a></li>
                    <li><a href="#">Помощь</a></li>
                    <li><a href="#">Поддержка</a></li>
                </ul>
            </div>
            
            <!-- Контакты -->
            <div class="footer-col">
                <h4 class="footer-title">Контакты</h4>
                <ul class="footer-links">
                    <li><a href="mailto:support@uchidobro.ru">support@uchidobro.ru</a></li>
                    <li><a href="#">Telegram-бот</a></li>
                    <li><a href="#">Мобильное приложение</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Подвал -->
        <div class="footer-bottom">
            <div class="footer-copyright">
                <p>&copy; <?php echo date('Y'); ?> УчиДобро. Все права защищены.</p>
            </div>
            <div class="footer-legal">
                <a href="#">Политика конфиденциальности</a>
                <a href="#">Пользовательское соглашение</a>
            </div>
        </div>
    </div>

    <!-- Блок установки приложения -->
<div class="install-app" id="installApp">
    <div class="install-content">
        <div class="install-icon">📱</div>
        <div class="install-text">
            <h4>Установи приложение УчиДобро</h4>
            <p>Быстрый доступ, офлайн-режим и уведомления о дедлайнах</p>
        </div>
        <div class="install-buttons">
            <button id="installPWA" class="btn-install">📲 Установить</button>
            <button id="downloadAPK" class="btn-apk">📥 APK для Android</button>
            <button id="closeInstall" class="btn-close">✕</button>
        </div>
    </div>
</div>

<style>
.install-app {
    position: fixed;
    bottom: 20px;
    left: 20px;
    right: 20px;
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-xl);
    z-index: 1000;
    transform: translateY(150%);
    transition: transform 0.3s ease;
    max-width: 500px;
    margin: 0 auto;
}

.install-app.show {
    transform: translateY(0);
}

.install-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    flex-wrap: wrap;
}

.install-icon {
    font-size: 2.5rem;
}

.install-text {
    flex: 1;
}

.install-text h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: var(--gray-900);
}

.install-text p {
    font-size: 0.75rem;
    color: var(--gray-600);
}

.install-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-install, .btn-apk {
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    font-size: 0.813rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    border: none;
}

.btn-install {
    background: var(--gradient-primary);
    color: var(--white);
}

.btn-install:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-apk {
    background: var(--gray-200);
    color: var(--gray-700);
}

.btn-apk:hover {
    background: var(--gray-300);
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    color: var(--gray-400);
    padding: 0 0.5rem;
}

.btn-close:hover {
    color: var(--gray-600);
}

@media (max-width: 640px) {
    .install-content {
        flex-direction: column;
        text-align: center;
    }
    
    .install-buttons {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script src="/js/notifications.js"></script>
<script>
// PWA Installation
let deferredPrompt;
const installBtn = document.getElementById('installPWA');
const installContainer = document.getElementById('installApp');

// Проверяем, показывали ли уже предложение
if (!localStorage.getItem('installDismissed')) {
    setTimeout(() => {
        installContainer.classList.add('show');
    }, 5000);
}

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Показываем кнопку установки
    installContainer.classList.add('show');
    
    installBtn.addEventListener('click', () => {
        installContainer.classList.remove('show');
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('User accepted install');
                localStorage.setItem('installDismissed', 'true');
            }
            deferredPrompt = null;
        });
    });
});

// Закрытие блока
document.getElementById('closeInstall').addEventListener('click', () => {
    installContainer.classList.remove('show');
    localStorage.setItem('installDismissed', 'true');
});

// Скрываем если уже установлено
window.addEventListener('appinstalled', () => {
    installContainer.classList.remove('show');
    localStorage.setItem('installDismissed', 'true');
    console.log('App installed');
    if (window.notifications) {
        window.notifications.init();
    }
});

// Скачивание APK
document.getElementById('downloadAPK').addEventListener('click', () => {
    window.open('/apk/uchidobro.apk', '_blank');
});
</script>
</footer>

<style>
/* Footer Styles */
.main-footer {
    background: var(--gray-900);
    color: var(--gray-400);
    padding: 3rem 0 1rem;
    margin-top: auto;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--white);
}

.footer-description {
    font-size: 0.875rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    color: var(--gray-400);
}

.social-links {
    display: flex;
    gap: 1rem;
}

.social-link {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    text-decoration: none;
    font-size: 1.125rem;
}

.social-link:hover {
    background: var(--primary);
    transform: translateY(-3px);
}

.footer-title {
    color: var(--white);
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.footer-links {
    list-style: none;
    padding: 0;
}

.footer-links li {
    margin-bottom: 0.75rem;
}

.footer-links a {
    color: var(--gray-400);
    text-decoration: none;
    transition: var(--transition);
    font-size: 0.875rem;
}

.footer-links a:hover {
    color: var(--white);
    padding-left: 5px;
}

/* Footer Bottom */
.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    padding-top: 1rem;
    font-size: 0.813rem;
}

.footer-copyright p {
    color: var(--gray-500);
}

.footer-legal {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.footer-legal a {
    color: var(--gray-500);
    text-decoration: none;
    transition: var(--transition);
}

.footer-legal a:hover {
    color: var(--white);
}

/* Responsive */
@media (max-width: 768px) {
    .footer-grid {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .footer-logo {
        justify-content: center;
    }
    
    .social-links {
        justify-content: center;
    }
    
    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }
    
    .footer-legal {
        justify-content: center;
    }
}
</style>