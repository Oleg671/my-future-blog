<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <header class="site-header">
        <div class="shell">
            <h1><a href="/">Записочки</a></h1>
            <nav class="main-nav">
                <a href="/">Главная</a>
                <?php if ($currentUser): ?>
                    <a href="/posts/new">Новый пост</a>
                    <a href="/?feed=following">Моя лента</a>
                    <a href="/people">Пользователи</a>
                    <a href="/access-requests">Запросы</a>
                    <form method="post" action="/logout" class="inline-form">
                        <?= csrf_field() ?>
                        <button class="nav-button" type="submit">Выйти (<?= e($currentUser['username']) ?>)</button>
                    </form>
                <?php else: ?>
                    <a href="/login">Вход</a>
                    <a href="/register">Регистрация</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <?php if (!empty($flashes)): ?>
        <div class="flash-stack shell">
            <?php foreach ($flashes as $flash): ?>
                <div class="flash flash-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <main>
        <?= $content ?>
    </main>

    <script>
        document.querySelectorAll('[data-confirm]').forEach(form =>
            form.addEventListener('submit', event => {
                if (!confirm(form.dataset.confirm)) event.preventDefault();
            })
        );
    </script>
</body>
</html>

