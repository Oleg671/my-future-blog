<section class="profile-page shell">
    <header class="profile-header">
        <div class="avatar avatar-giant"><?= e(mb_strtoupper(mb_substr($profile['username'], 0, 1))) ?></div>
        <div class="profile-info">
            <h2>Профиль: <?= e($profile['username']) ?></h2>
            <?php if ($currentUser && (int) $currentUser['id'] === (int) $profile['id']): ?>
                <p><?= e($profile['email']) ?></p>
            <?php endif; ?>
            <div class="profile-stats">
                <span><strong><?= (int) $profile['posts_count'] ?></strong> публикаций</span>
                <span><strong><?= (int) $profile['followers_count'] ?></strong> читателей</span>
                <span><strong><?= (int) $profile['following_count'] ?></strong> подписок</span>
            </div>
        </div>
        <?php if ($currentUser && (int) $currentUser['id'] !== (int) $profile['id']): ?>
            <form method="post" action="/users/<?= (int) $profile['id'] ?>/follow">
                <?= csrf_field() ?><input type="hidden" name="return_to" value="/users/<?= (int) $profile['id'] ?>">
                <button class="button <?= $isFollowing ? 'button-outline' : 'button-dark' ?>" type="submit"><?= $isFollowing ? 'Отписаться' : 'Подписаться' ?></button>
            </form>
        <?php endif; ?>
    </header>

    <div class="profile-content">
        <div class="section-head"><h2>Посты пользователя</h2></div>
        <div class="story-list">
            <?php if ($posts === []): ?><div class="empty-state"><p>Постов пока нет.</p></div><?php endif; ?>
            <?php foreach ($posts as $post): ?><?php require __DIR__ . '/partials/post-card.php'; ?><?php endforeach; ?>
        </div>
    </div>
</section>

