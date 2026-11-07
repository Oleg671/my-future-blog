<section class="stories-section shell">
    <h1>Блог «Записочки»</h1>
    <?php if (!$currentUser): ?>
        <p>Здесь можно читать публикации пользователей. Чтобы создать свой пост или оставить комментарий, войдите или зарегистрируйтесь.</p>
        <p><a class="button" href="/login">Войти</a> <a class="button button-secondary" href="/register">Регистрация</a></p>
    <?php else: ?>
        <p>Здравствуйте, <?= e($currentUser['username']) ?>!</p>
    <?php endif; ?>

    <div class="section-head">
        <h2><?= $mode === 'following' ? 'Посты моих подписок' : 'Последние посты' ?></h2>
        <div class="feed-tabs">
            <a class="<?= $mode === 'all' ? 'active' : '' ?>" href="/?feed=all">Все</a>
            <?php if ($currentUser): ?>
                <a class="<?= $mode === 'following' ? 'active' : '' ?>" href="/?feed=following">Подписки</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="filters">
        <strong>Теги:</strong>
        <a href="/?feed=<?= e($mode) ?>">все</a>
        <?php foreach ($popularTags as $tag): ?>
            <a class="<?= $selectedTag === $tag['name'] ? 'active' : '' ?>" href="/?feed=<?= e($mode) ?>&tag=<?= urlencode($tag['name']) ?>">#<?= e($tag['name']) ?></a>
        <?php endforeach; ?>
        <span>Сортировка: сначала новые</span>
    </div>

    <div class="story-list">
        <?php if ($posts === []): ?>
            <div class="empty-state">
                <p><?= $mode === 'following' ? 'В ленте пока нет постов. Подпишитесь на пользователей.' : 'Посты не найдены.' ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php require __DIR__ . '/partials/post-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

