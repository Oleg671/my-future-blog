<?php $canReadPost = post_can_view(db(), $post, auth_id()); ?>
<article class="story-card">
    <div class="story-meta">
        <a class="author-line" href="/users/<?= (int) $post['author_id'] ?>">
            <span class="avatar avatar-small"><?= e(mb_strtoupper(mb_substr($post['username'], 0, 1))) ?></span>
            <span><?= e($post['username']) ?></span>
        </a>
        <span class="dot">·</span>
        <time datetime="<?= e($post['created_at']) ?>"><?= e(format_date($post['created_at'])) ?></time>
        <?php if ($post['visibility'] !== 'public'): ?>
            <span class="visibility-pill visibility-<?= e($post['visibility']) ?>"><?= e(visibility_label($post['visibility'])) ?></span>
        <?php endif; ?>
    </div>
    <h2><a href="/posts/<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a></h2>
    <p><?= $canReadPost ? e(excerpt($post['body'])) : 'Текст доступен только по запросу.' ?></p>
    <footer class="story-footer">
        <div class="tag-list">
            <?php foreach ($post['tags'] as $postTag): ?>
                <a href="/?tag=<?= urlencode($postTag) ?>">#<?= e($postTag) ?></a>
            <?php endforeach; ?>
        </div>
        <a class="comment-count" href="/posts/<?= (int) $post['id'] ?>#comments" aria-label="Комментарии">
            <span>◌</span> <?= (int) $post['comments_count'] ?>
        </a>
    </footer>
</article>

