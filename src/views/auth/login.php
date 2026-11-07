<section class="auth-page shell">
    <div class="auth-card">
        <h2>Вход</h2>

        <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>

        <form method="post" action="/login" class="stack-form">
            <?= csrf_field() ?>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= e($email ?? '') ?>" required>
            </label>
            <label>
                <span>Пароль</span>
                <input type="password" name="password" required>
            </label>
            <button class="button" type="submit">Войти</button>
        </form>
        <p>Нет аккаунта? <a href="/register">Зарегистрироваться</a></p>
        <p><small>Тестовый вход: test@test.test / test</small></p>
    </div>
</section>

