<section class="auth-page shell">
    <div class="auth-card">
        <h2>Регистрация</h2>

        <?php if ($errors !== []): ?>
            <div class="form-error"><ul><?php foreach ($errors as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" action="/register" class="stack-form">
            <?= csrf_field() ?>
            <label>
                <span>Имя автора</span>
                <input type="text" name="username" value="<?= e($old['username'] ?? '') ?>" maxlength="40" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
            </label>
            <label>
                <span>Пароль</span>
                <input type="password" name="password" minlength="8" required>
            </label>
            <button class="button" type="submit">Зарегистрироваться</button>
        </form>
        <p>Уже зарегистрированы? <a href="/login">Войти</a></p>
    </div>
</section>
