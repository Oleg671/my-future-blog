<?php $notFound = http_response_code() === 404; ?>
<section class="error-page shell-narrow">
    <span class="error-code"><?= $notFound ? '404' : '500' ?></span>
    <h1><?= $notFound ? 'Страница не найдена.' : 'Ошибка приложения.' ?></h1>
    <p><?= e($message ?? ($notFound
        ? 'Возможно, публикацию удалили или она доступна только автору.'
        : 'Не удалось выполнить запрос. Попробуйте обновить страницу.')) ?></p>
    <a class="button button-accent" href="/">Вернуться на главную</a>
</section>
