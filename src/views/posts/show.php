<article class="post-page shell-narrow">
    <a class="back-link" href="/">← К публикациям</a>
    <header class="post-header">
        <div class="story-meta">
            <a class="author-line" href="/users/<?= (int) $post['author_id'] ?>">
                <span class="avatar"><?= e(mb_strtoupper(mb_substr($post['username'], 0, 1))) ?></span>
                <span><strong><?= e($post['username']) ?></strong><small><?= e(format_date($post['created_at'], true)) ?></small></span>
            </a>
            <span class="visibility-pill visibility-<?= e($post['visibility']) ?>"><?= e(visibility_label($post['visibility'])) ?></span>
        </div>
        <h1><?= e($post['title']) ?></h1>
        <div class="tag-list tag-list-large">
            <?php foreach ($post['tags'] as $postTag): ?><a href="/?tag=<?= urlencode($postTag) ?>">#<?= e($postTag) ?></a><?php endforeach; ?>
        </div>
    </header>

    <?php if ($canView): ?>
        <div class="post-body"><?= nl2br(e($post['body'])) ?></div>

        <?php if ($currentUser && (int) $currentUser['id'] === (int) $post['author_id']): ?>
            <div class="owner-actions">
                <a class="button button-outline button-small" href="/posts/<?= (int) $post['id'] ?>/edit">Редактировать</a>
                <form method="post" action="/posts/<?= (int) $post['id'] ?>/delete" data-confirm="Удалить публикацию без возможности восстановления?">
                    <?= csrf_field() ?>
                    <button class="danger-link" type="submit">Удалить</button>
                </form>
            </div>
        <?php endif; ?>

        <section class="comments-section" id="comments">
            <div class="comments-head">
                <h2>Обсуждение</h2>
                <span><?= count($comments) ?></span>
            </div>
            <?php if ($currentUser): ?>
                <form class="comment-form" method="post" action="/posts/<?= (int) $post['id'] ?>/comments">
                    <?= csrf_field() ?>
                    <span class="avatar"><?= e(mb_strtoupper(mb_substr($currentUser['username'], 0, 1))) ?></span>
                    <div>
                        <textarea name="body" rows="3" maxlength="1200" required placeholder="Введите комментарий"></textarea>
                        <button class="button button-dark button-small" type="submit">Отправить</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="login-prompt"><a href="/login">Войдите</a>, чтобы оставить комментарий.</div>
            <?php endif; ?>

            <div class="comment-list">
                <?php if ($comments === []): ?>
                    <p class="muted">Комментариев пока нет.</p>
                <?php endif; ?>
                <?php foreach ($comments as $comment): ?>
                    <article class="comment">
                        <span class="avatar avatar-small"><?= e(mb_strtoupper(mb_substr($comment['username'], 0, 1))) ?></span>
                        <div>
                            <header><a href="/users/<?= (int) $comment['author_id'] ?>"><?= e($comment['username']) ?></a><time><?= e(format_date($comment['created_at'], true)) ?></time></header>
                            <p><?= nl2br(e($comment['body'])) ?></p>
                            <?php if ($currentUser && (int) $currentUser['id'] === (int) $comment['author_id']): ?>
                                <form method="post" action="/comments/<?= (int) $comment['id'] ?>/delete">
                                    <?= csrf_field() ?><button class="subtle-link" type="submit">Удалить</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php else: ?>
        <div class="locked-card">
            <h2>Пост доступен по запросу</h2>
            <p>Отправьте запрос автору, чтобы посмотреть полный текст.</p>
            <?php if (!$currentUser): ?>
                <a class="button button-accent" href="/login">Войти и запросить доступ</a>
            <?php elseif ($requestStatus === 'pending'): ?>
                <span class="status-note">Запрос ожидает решения автора</span>
            <?php elseif ($requestStatus === 'rejected'): ?>
                <form method="post" action="/posts/<?= (int) $post['id'] ?>/request-access">
                    <?= csrf_field() ?><button class="button button-accent" type="submit">Отправить запрос снова</button>
                </form>
            <?php else: ?>
                <form method="post" action="/posts/<?= (int) $post['id'] ?>/request-access">
                    <?= csrf_field() ?><button class="button button-accent" type="submit">Запросить доступ</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</article>

