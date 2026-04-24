class NotificationManager {
    constructor() {
        this.ready = false;
        this.registration = null;
        this.pollingStarted = false;
        this.testScheduled = localStorage.getItem('install_test_notification_scheduled') === '1';
        this.init();
    }
    
    async init() {
        if (!('Notification' in window) || !('serviceWorker' in navigator)) {
            return;
        }

        let permission = Notification.permission;
        if (permission === 'default') {
            permission = await Notification.requestPermission();
        }
        this.ready = permission === 'granted';

        if (!this.ready) {
            return;
        }

        this.registration = await navigator.serviceWorker.ready;
        this.startBackgroundCheck();
        this.scheduleInstallTestNotification();
    }

    scheduleInstallTestNotification() {
        if (this.testScheduled || !this.registration) {
            return;
        }

        const swTarget = navigator.serviceWorker.controller || this.registration.active || this.registration.waiting;
        if (!swTarget) {
            return;
        }

        swTarget.postMessage({
            type: 'SCHEDULE_TEST_NOTIFICATION',
            delay: 2 * 60 * 1000,
            title: '✅ УчиДобро установлено',
            body: 'Это тест: уведомления работают. Можно пользоваться напоминаниями о дедлайнах.',
            url: '/tasks.php'
        });

        localStorage.setItem('install_test_notification_scheduled', '1');
        this.testScheduled = true;
    }
    
    // Показ уведомления
    async show(title, body, url = '/') {
        if (!this.ready) return;

        const reg = this.registration || await navigator.serviceWorker.ready;
        reg.showNotification(title, {
            body: body,
            icon: '/img/android/launchericon-192x192.png',
            badge: '/img/android/launchericon-72x72.png',
            vibrate: [200, 100, 200],
            data: { url: url }
        });
    }
    
    // Фоновая проверка задач каждые 5 минут
    startBackgroundCheck() {
        if ('serviceWorker' in navigator && 'SyncManager' in window && this.registration) {
            // Через Service Worker
            this.registration.sync.register('check-tasks').catch(() => this.startFallbackPolling());
        } else {
            this.startFallbackPolling();
        }
    }

    startFallbackPolling() {
        if (this.pollingStarted) {
            return;
        }
        this.pollingStarted = true;
        setInterval(() => this.checkTasks(), 5 * 60 * 1000);
    }
    
    // Проверка задач через AJAX
    async checkTasks() {
        try {
            const response = await fetch('/api/check_tasks.php');
            const data = await response.json();
            
            if (data.overdue > 0) {
                this.show(
                    '⚠️ Просроченные задачи!',
                    `У вас ${data.overdue} просроченных задач. Не откладывайте!`,
                    '/tasks.php?filter=overdue'
                );
            }
            
            if (data.today_count > 0) {
                this.show(
                    '📅 Задачи на сегодня',
                    `Сегодня нужно выполнить ${data.today_count} задач`,
                    '/tasks.php'
                );
            }
        } catch (error) {
            console.log('Ошибка проверки задач:', error);
        }
    }
}

// Создаём глобальный объект
window.notifications = new NotificationManager();

// Функция напоминания о задаче
function remindTask(taskId, taskTitle, deadline) {
    const daysLeft = Math.ceil((new Date(deadline) - new Date()) / (1000 * 60 * 60 * 24));
    
    if (daysLeft === 1) {
        window.notifications.show(
            '⏰ Завтра дедлайн!',
            `Задача "${taskTitle}" должна быть выполнена завтра`,
            `/tasks.php?id=${taskId}`
        );
    } else if (daysLeft === 0) {
        window.notifications.show(
            '⚠️ Сегодня последний день!',
            `Задача "${taskTitle}" должна быть выполнена сегодня!`,
            `/tasks.php?id=${taskId}`
        );
    }
}