# Todo Tracker - Личный трекер задач для учеников

## Проблема

Каждый день ученик получает задачи отовсюду: устно от учителя, в чате класса, в электронном дневнике. В итоге тратит силы не на выполнение, а на поиск информации.

## Решение

Todo Tracker - веб-приложение для управления учебными задачами в одном месте.

## Возможности

- Регистрация (ученик, учитель, родитель)
- Создание задач с дедлайном и приоритетом
- Отслеживание статуса выполнения
- Статистика прогресса
- Контроль родителя (в разработке)

## Технологии

- PHP 7.4+
- MySQL
- HTML5/CSS3
- XAMPP

## Установка

### 1. Установите XAMPP

Скачайте с [apachefriends.org](https://www.apachefriends.org/) и установите в `C:\xampp\`

### 2. Склонируйте проект

```bash
cd C:\xampp\htdocs\
git clone https://github.com/ваш-username/todo-tracker.git
```

### 3. Запустите сервер

Откройте XAMPP Control Panel → Запустите Apache (и MySQL)

### 4. Откройте в браузере

```
http://localhost/todo_tracker/web/
```

или если порт 8080:

```
http://localhost:8080/todo_tracker/web/
```

## Структура проекта

```
todo_tracker/
├── web/
│   ├── core/          # Конфигурация
│   ├── css/           # Стили
│   ├── img/           # Изображения
│   ├── js/            # Скрипты
│   ├── include/       # PHP компоненты
│   ├── db/            # SQL схемы
│   ├── index.php
│   ├── register.php
│   ├── login.php
│   ├── dashboard.php
│   ├── tasks.php
│   ├── add_task.php
│   └── logout.php
├── apk/               # Android версия
├── ipa/               # iOS версия
└── README.md
```

## База данных

Создаётся автоматически при первом запуске. Таблицы:

- `users` - пользователи
- `tasks` - задачи
- `reminders` - напоминания
- `task_progress` - прогресс выполнения

## Лицензия

MIT
```

## 📤 Как залить на GitHub

### 1. Создай репозиторий на GitHub

- Зайди на [github.com](https://github.com)
- Нажми **New repository**
- Назови: `todo-tracker`
- Нажми **Create repository**

### 2. Создай `.gitignore` в корне проекта

```bash
cd C:\xampp\htdocs\todo_tracker\
```

Создай файл `.gitignore`:

```
# XAMPP specific
*.exe
*.dll

# PHP error logs
*.log
error_log

# OS files
.DS_Store
Thumbs.db

# IDE
.vscode/
.idea/

# Database
*.sqlite
*.db
