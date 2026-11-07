# Записочки

Учебное приложение для блога на PHP 8.3 и MySQL 8.4.

## Запуск

```bash
docker compose up --build -d
```

Сайт: http://localhost:8081

Учётная запись:

- пользователь: `test@test.test` / `test`.

## Работа с блогом

1. Запустите проект командой `docker compose up --build -d`.
2. Откройте http://localhost:8081.
3. Войдите под тестовой учётной записью или зарегистрируйте нового пользователя.
4. После входа можно создавать посты, добавлять теги, подписываться на пользователей и писать комментарии.

Основные страницы:

- общая лента: http://localhost:8081/;
- лента подписок: http://localhost:8081/?feed=following;
- пользователи: http://localhost:8081/people;
- создание поста: http://localhost:8081/posts/new;
- запросы доступа: http://localhost:8081/access-requests.

## Структура

- `src/public/index.php` — страницы и обработка действий;
- `src/public/style.css` — оформление;
- `src/functions.php` — подключение к БД и общие функции;
- `src/views` — шаблоны страниц;
- `init.sql` — таблицы и начальные данные;
- `Dockerfile`, `docker-compose.yml` — локальный запуск;
- `REPORT.md` — анализ выполненной задачи.
