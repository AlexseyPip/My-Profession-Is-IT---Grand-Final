const CACHE_NAME = 'uchidobro-v1.0.2';
const OFFLINE_URL = '/offline.html';

// Файлы для кэширования
const urlsToCache = [
  '/offline.html',
  
  // CSS
  '/css/variables.css',
  '/css/reset.css',
  '/css/components.css',
  '/css/responsive.css',
  
  // JS
  '/js/main.js',
  '/js/notifications.js',
  
  // В проекте часть медиа может отсутствовать на окружении,
  // поэтому кэшируем в install только гарантированные страницы/стили/скрипты.
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(async cache => {
      await Promise.all(
        urlsToCache.map(url =>
          cache.add(url).catch(() => null)
        )
      );
      await self.skipWaiting();
    })
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(OFFLINE_URL))
    );
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
      .catch(() => {
        if (event.request.mode === 'navigate') {
          return caches.match(OFFLINE_URL);
        }
        return new Response('Нет соединения', { status: 503 });
      })
  );
});

self.addEventListener('message', event => {
  if (!event.data || event.data.type !== 'SCHEDULE_TEST_NOTIFICATION') {
    return;
  }

  const delay = Number(event.data.delay || 0);
  const title = event.data.title || '🔔 Тестовое уведомление';
  const body = event.data.body || 'Проверка уведомлений работает корректно.';
  const url = event.data.url || '/dashboard.php';

  setTimeout(() => {
    self.registration.showNotification(title, {
      body,
      icon: '/img/android/launchericon-192x192.png',
      badge: '/img/android/launchericon-72x72.png',
      data: { url },
      tag: 'install-test-notification',
      renotify: true,
      vibrate: [150, 100, 150]
    });
  }, Math.max(0, delay));
});

self.addEventListener('push', event => {
  let payload = {
    title: 'Новое уведомление',
    body: 'У вас есть обновления в УчиДобро',
    url: '/tasks.php'
  };

  if (event.data) {
    try {
      payload = { ...payload, ...event.data.json() };
    } catch (error) {
      payload.body = event.data.text() || payload.body;
    }
  }

  event.waitUntil(
    self.registration.showNotification(payload.title, {
      body: payload.body,
      icon: '/img/android/launchericon-192x192.png',
      badge: '/img/android/launchericon-72x72.png',
      data: { url: payload.url || '/tasks.php' },
      tag: payload.tag || 'task-notification',
      renotify: true
    })
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const targetUrl = (event.notification.data && event.notification.data.url) || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
      for (const client of windowClients) {
        if ('focus' in client) {
          client.navigate(targetUrl);
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
      return null;
    })
  );
});