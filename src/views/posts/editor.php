<?php
$editing = $post !== null;
$values = $form ?: ($post ?? []);
$tagValue = $values['tags'] ?? '';
if (is_array($tagValue)) {
    $tagValue = implode(', ', $tagValue);
}
?>
<section class="editor-page shell-narrow">
    <h2><?= $editing ? 'Редактирование поста' : 'Создание поста' ?></h2>

    <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" class="editor-form">
        <?= csrf_field() ?>
        <label>
            <span>Заголовок</span>
            <input type="text" name="title" maxlength="180" required value="<?= e($values['title'] ?? '') ?>">
        </label>
        <label>
            <span>Текст</span>
            <textarea name="body" rows="12" required minlength="20"><?= e($values['body'] ?? '') ?></textarea>
        </label>
        <div class="form-row">
            <label>
                <span>Теги</span>
                <input type="text" name="tags" value="<?= e((string) $tagValue) ?>" placeholder="php, mysql, учеба">
                <small>Введите теги через запятую</small>
            </label>
            <label>
                <span>Кто увидит</span>
                <select name="visibility">
                    <?php $visibility = $values['visibility'] ?? 'public'; ?>
                    <option value="public" <?= $visibility === 'public' ? 'selected' : '' ?>>Все читатели</option>
                    <option value="request" <?= $visibility === 'request' ? 'selected' : '' ?>>Только после запроса</option>
                    <option value="private" <?= $visibility === 'private' ? 'selected' : '' ?>>Только я</option>
                </select>
            </label>
        </div>
        <div class="editor-actions">
            <button class="button" type="submit"><?= $editing ? 'Сохранить' : 'Создать' ?></button>
            <a href="<?= $editing ? '/posts/' . (int) $post['id'] : '/' ?>">Отмена</a>
        </div>
    </form>
</section>

