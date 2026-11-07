<section class="directory-page shell">
    <header class="page-heading split-heading">
        <div><h2>Пользователи</h2></div>
        <form class="search-form" method="get" action="/people">
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Поиск по имени">
            <button type="submit">Найти</button>
        </form>
    </header>

    <div class="people-grid">
        <?php if ($people === []): ?>
            <div class="empty-state"><p>Пользователи не найдены.</p></div>
        <?php endif; ?>
        <?php foreach ($people as $person): ?>
            <article class="person-card">
                <a class="avatar avatar-large" href="/users/<?= (int) $person['id'] ?>"><?= e(mb_strtoupper(mb_substr($person['username'], 0, 1))) ?></a>
                <div class="person-main">
                    <h2><a href="/users/<?= (int) $person['id'] ?>"><?= e($person['username']) ?></a></h2>
                    <div class="person-stats"><span><?= (int) $person['public_posts_count'] ?> публикаций</span><span><?= (int) $person['followers_count'] ?> читателей</span></div>
                </div>
                <form method="post" action="/users/<?= (int) $person['id'] ?>/follow">
                    <?= csrf_field() ?><input type="hidden" name="return_to" value="/people<?= $search !== '' ? '?q=' . e(urlencode($search)) : '' ?>">
                    <button class="button <?= $person['is_following'] ? 'button-outline' : 'button-dark' ?> button-small" type="submit">
                        <?= $person['is_following'] ? 'Отписаться' : 'Подписаться' ?>
                    </button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>

