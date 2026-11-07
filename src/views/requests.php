<section class="requests-page shell-narrow">
    <header class="page-heading"><h2>Запросы доступа</h2><p>Здесь можно одобрить или отклонить запрос на просмотр поста.</p></header>
    <div class="request-list">
        <?php if ($requests === []): ?>
            <div class="empty-state"><p>Новых запросов нет.</p><a class="button button-outline" href="/">На главную</a></div>
        <?php endif; ?>
        <?php foreach ($requests as $request): ?>
            <article class="request-card">
                <div class="avatar"><?= e(mb_strtoupper(mb_substr($request['username'], 0, 1))) ?></div>
                <div><p><a href="/users/<?= (int) $request['requester_id'] ?>"><?= e($request['username']) ?></a> просит открыть</p><h2><a href="/posts/<?= (int) $request['post_id'] ?>"><?= e($request['title']) ?></a></h2><time><?= e(format_date($request['created_at'], true)) ?></time></div>
                <form method="post" action="/access-requests/<?= (int) $request['id'] ?>" class="request-actions">
                    <?= csrf_field() ?>
                    <button class="button button-dark button-small" name="decision" value="approved" type="submit">Открыть</button>
                    <button class="text-link" name="decision" value="rejected" type="submit">Отклонить</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>

