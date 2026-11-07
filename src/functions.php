<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('future_blog_session');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'path' => '/',
    ]);
    session_start();
}

date_default_timezone_set('Europe/Moscow');

function db(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('DB_HOST') ?: 'db';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'future_blog';
    $user = getenv('DB_USER') ?: 'future_user';
    $password = getenv('DB_PASSWORD') ?: 'future_password';

    try {
        $connection = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $connection;
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        throw new RuntimeException('Не удалось подключиться к базе данных. Обновите страницу через несколько секунд.');
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function render(string $template, array $data = [], int $status = 200): never
{
    $templatePath = __DIR__ . '/views/' . $template . '.php';
    if (!is_file($templatePath)) {
        throw new RuntimeException('Шаблон не найден');
    }

    http_response_code($status);
    $data = array_merge([
        'currentUser' => auth_user(db()),
        'flashes' => consume_flashes(),
        'pageTitle' => 'Записочки',
    ], $data);
    extract($data, EXTR_SKIP);
    ob_start();
    require $templatePath;
    $content = (string) ob_get_clean();
    require __DIR__ . '/views/layout.php';
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Сессия формы устарела. Обновите страницу.');
    }
}

function excerpt(string $body, int $limit = 220): string
{
    $plain = trim(preg_replace('/\s+/u', ' ', $body) ?? '');
    return mb_strlen($plain) <= $limit ? $plain : mb_substr($plain, 0, $limit - 1) . '…';
}

function format_date(string $value, bool $withTime = false): string
{
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }
    $months = [1 => 'янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
    $text = date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    return $withTime ? $text . ', ' . date('H:i', $timestamp) : $text;
}

function visibility_label(string $visibility): string
{
    return match ($visibility) {
        'private' => 'Личный черновик',
        'request' => 'По запросу',
        default => 'Открытая публикация',
    };
}

function auth_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function auth_user(PDO $db): ?array
{
    if (!auth_id()) {
        return null;
    }
    $statement = $db->prepare('SELECT id, username, email, created_at FROM users WHERE id = ?');
    $statement->execute([auth_id()]);
    return $statement->fetch() ?: null;
}

function require_user(): int
{
    if (!auth_id()) {
        flash('info', 'Сначала войдите в аккаунт.');
        redirect('/login');
    }
    return (int) auth_id();
}

function auth_attempt(PDO $db, string $email, string $password): bool
{
    $statement = $db->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $statement->execute([mb_strtolower(trim($email))]);
    $user = $statement->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    auth_login((int) $user['id']);
    return true;
}

function auth_login(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
}

function auth_logout(): void
{
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

function user_create(PDO $db, string $username, string $email, string $password): array
{
    $errors = [];
    $username = trim($username);
    $email = mb_strtolower(trim($email));
    if (!preg_match('/^[\p{L}\p{N}_-]{3,40}$/u', $username)) {
        $errors[] = 'Имя должно содержать 3–40 букв, цифр, дефисов или подчёркиваний.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Укажите корректный email.';
    }
    if (mb_strlen($password) < 8) {
        $errors[] = 'Пароль должен содержать не менее 8 символов.';
    }
    if ($errors) {
        return ['id' => null, 'errors' => $errors];
    }

    try {
        $statement = $db->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $statement->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
        return ['id' => (int) $db->lastInsertId(), 'errors' => []];
    } catch (PDOException $exception) {
        if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
            return ['id' => null, 'errors' => ['Такое имя или email уже зарегистрированы.']];
        }
        throw $exception;
    }
}

function user_find(PDO $db, int $id): ?array
{
    $statement = $db->prepare(
        'SELECT u.id, u.username, u.email, u.created_at,
                (SELECT COUNT(*) FROM follows f WHERE f.followed_id = u.id) AS followers_count,
                (SELECT COUNT(*) FROM follows f WHERE f.follower_id = u.id) AS following_count,
                (SELECT COUNT(*) FROM posts p WHERE p.author_id = u.id) AS posts_count
         FROM users u WHERE u.id = ?'
    );
    $statement->execute([$id]);
    return $statement->fetch() ?: null;
}

function users_discover(PDO $db, int $currentUserId, string $search): array
{
    $sql = 'SELECT u.id, u.username, u.created_at,
                   EXISTS(SELECT 1 FROM follows f WHERE f.follower_id = ? AND f.followed_id = u.id) AS is_following,
                   (SELECT COUNT(*) FROM follows f WHERE f.followed_id = u.id) AS followers_count,
                   (SELECT COUNT(*) FROM posts p WHERE p.author_id = u.id AND p.visibility = \'public\') AS public_posts_count
            FROM users u WHERE u.id <> ?';
    $params = [$currentUserId, $currentUserId];
    $search = trim($search);
    if ($search !== '') {
        $sql .= ' AND u.username LIKE ?';
        $params[] = '%' . $search . '%';
    }
    $sql .= ' ORDER BY followers_count DESC, u.username ASC';
    $statement = $db->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function toggle_follow(PDO $db, int $followerId, int $followedId): bool
{
    if ($followerId === $followedId) {
        return false;
    }
    $check = $db->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?');
    $check->execute([$followerId, $followedId]);
    if ($check->fetchColumn()) {
        $db->prepare('DELETE FROM follows WHERE follower_id = ? AND followed_id = ?')->execute([$followerId, $followedId]);
        return false;
    }
    $db->prepare('INSERT IGNORE INTO follows (follower_id, followed_id) VALUES (?, ?)')->execute([$followerId, $followedId]);
    return true;
}

function is_following(PDO $db, int $followerId, int $followedId): bool
{
    $statement = $db->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?');
    $statement->execute([$followerId, $followedId]);
    return (bool) $statement->fetchColumn();
}

function post_columns(): string
{
    return "p.id, p.author_id, p.title, p.body, p.visibility, p.created_at, p.updated_at,
            u.username,
            (SELECT GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ',')
             FROM post_tags pt JOIN tags t ON t.id = pt.tag_id WHERE pt.post_id = p.id) AS tags,
            (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count";
}

function normalize_post(array $post): array
{
    $post['id'] = (int) $post['id'];
    $post['author_id'] = (int) $post['author_id'];
    $post['comments_count'] = (int) $post['comments_count'];
    $post['tags'] = ($post['tags'] ?? '') === '' ? [] : explode(',', $post['tags']);
    return $post;
}

function post_feed(PDO $db, ?int $viewerId, string $mode, string $tag): array
{
    $sql = 'SELECT ' . post_columns() . " FROM posts p JOIN users u ON u.id = p.author_id WHERE p.visibility = 'public'";
    $params = [];
    if ($mode === 'following' && $viewerId) {
        $sql .= ' AND EXISTS (SELECT 1 FROM follows f WHERE f.follower_id = ? AND f.followed_id = p.author_id)';
        $params[] = $viewerId;
    }
    if ($tag !== '') {
        $sql .= ' AND EXISTS (SELECT 1 FROM post_tags pf JOIN tags tf ON tf.id = pf.tag_id WHERE pf.post_id = p.id AND tf.name = ?)';
        $params[] = $tag;
    }
    $sql .= ' ORDER BY p.created_at DESC LIMIT 50';
    $statement = $db->prepare($sql);
    $statement->execute($params);
    return array_map('normalize_post', $statement->fetchAll());
}

function all_tags(PDO $db): array
{
    return $db->query(
        "SELECT t.name, COUNT(pt.post_id) AS usage_count FROM tags t
         JOIN post_tags pt ON pt.tag_id = t.id JOIN posts p ON p.id = pt.post_id AND p.visibility = 'public'
         GROUP BY t.id, t.name ORDER BY usage_count DESC, t.name ASC LIMIT 18"
    )->fetchAll();
}

function post_find(PDO $db, int $id): ?array
{
    $statement = $db->prepare('SELECT ' . post_columns() . ' FROM posts p JOIN users u ON u.id = p.author_id WHERE p.id = ?');
    $statement->execute([$id]);
    $post = $statement->fetch();
    return $post ? normalize_post($post) : null;
}

function posts_by_author(PDO $db, int $authorId, ?int $viewerId): array
{
    $sql = 'SELECT ' . post_columns() . ' FROM posts p JOIN users u ON u.id = p.author_id WHERE p.author_id = ? AND ';
    $sql .= $viewerId === $authorId ? '1 = 1' : "p.visibility IN ('public', 'request')";
    $sql .= ' ORDER BY p.created_at DESC';
    $statement = $db->prepare($sql);
    $statement->execute([$authorId]);
    return array_map('normalize_post', $statement->fetchAll());
}

function post_can_view(PDO $db, array $post, ?int $viewerId): bool
{
    if ($post['visibility'] === 'public' || $viewerId === (int) $post['author_id']) {
        return true;
    }
    if ($post['visibility'] === 'private' || !$viewerId) {
        return false;
    }
    $statement = $db->prepare("SELECT 1 FROM access_requests WHERE post_id = ? AND requester_id = ? AND status = 'approved'");
    $statement->execute([(int) $post['id'], $viewerId]);
    return (bool) $statement->fetchColumn();
}

function validate_post(array $input): array
{
    $title = trim((string) ($input['title'] ?? ''));
    $body = trim((string) ($input['body'] ?? ''));
    $visibility = (string) ($input['visibility'] ?? 'public');
    if (mb_strlen($title) < 3 || mb_strlen($title) > 180) {
        throw new RuntimeException('Заголовок должен содержать от 3 до 180 символов.');
    }
    if (mb_strlen($body) < 20) {
        throw new RuntimeException('Текст публикации должен содержать не менее 20 символов.');
    }
    if (!in_array($visibility, ['public', 'private', 'request'], true)) {
        throw new RuntimeException('Выбран неизвестный режим доступа.');
    }
    $tags = [];
    foreach (preg_split('/[,#]+/u', (string) ($input['tags'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $tag) {
        $tag = mb_strtolower(trim($tag));
        if ($tag !== '' && mb_strlen($tag) <= 40) {
            $tags[$tag] = true;
        }
    }
    return compact('title', 'body', 'visibility') + ['tags' => array_slice(array_keys($tags), 0, 8)];
}

function sync_tags(PDO $db, int $postId, array $tags): void
{
    $db->prepare('DELETE FROM post_tags WHERE post_id = ?')->execute([$postId]);
    $insertTag = $db->prepare('INSERT INTO tags (name) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
    $attach = $db->prepare('INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)');
    foreach ($tags as $tag) {
        $insertTag->execute([$tag]);
        $attach->execute([$postId, (int) $db->lastInsertId()]);
    }
}

function post_create(PDO $db, int $authorId, array $input): int
{
    $data = validate_post($input);
    $db->beginTransaction();
    try {
        $statement = $db->prepare('INSERT INTO posts (author_id, title, body, visibility) VALUES (?, ?, ?, ?)');
        $statement->execute([$authorId, $data['title'], $data['body'], $data['visibility']]);
        $postId = (int) $db->lastInsertId();
        sync_tags($db, $postId, $data['tags']);
        $db->commit();
        return $postId;
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
}

function post_update(PDO $db, int $postId, int $authorId, array $input): void
{
    $data = validate_post($input);
    $db->beginTransaction();
    try {
        $statement = $db->prepare('UPDATE posts SET title = ?, body = ?, visibility = ? WHERE id = ? AND author_id = ?');
        $statement->execute([$data['title'], $data['body'], $data['visibility'], $postId, $authorId]);
        $check = $db->prepare('SELECT 1 FROM posts WHERE id = ? AND author_id = ?');
        $check->execute([$postId, $authorId]);
        if (!$check->fetchColumn()) {
            throw new RuntimeException('Публикация не найдена.');
        }
        sync_tags($db, $postId, $data['tags']);
        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
}

function post_delete(PDO $db, int $postId, int $authorId): bool
{
    $statement = $db->prepare('DELETE FROM posts WHERE id = ? AND author_id = ?');
    $statement->execute([$postId, $authorId]);
    return $statement->rowCount() === 1;
}

function comments_for_post(PDO $db, int $postId): array
{
    $statement = $db->prepare('SELECT c.id, c.post_id, c.author_id, c.body, c.created_at, u.username FROM comments c JOIN users u ON u.id = c.author_id WHERE c.post_id = ? ORDER BY c.created_at');
    $statement->execute([$postId]);
    return $statement->fetchAll();
}

function comment_create(PDO $db, int $postId, int $authorId, string $body): void
{
    $body = trim($body);
    if ($body === '' || mb_strlen($body) > 1200) {
        throw new RuntimeException('Комментарий должен содержать от 1 до 1200 символов.');
    }
    $db->prepare('INSERT INTO comments (post_id, author_id, body) VALUES (?, ?, ?)')->execute([$postId, $authorId, $body]);
}

function comment_delete(PDO $db, int $commentId, int $userId): ?int
{
    $statement = $db->prepare('SELECT post_id FROM comments WHERE id = ? AND author_id = ?');
    $statement->execute([$commentId, $userId]);
    $postId = $statement->fetchColumn();
    if (!$postId) {
        return null;
    }
    $db->prepare('DELETE FROM comments WHERE id = ? AND author_id = ?')->execute([$commentId, $userId]);
    return (int) $postId;
}

function request_access(PDO $db, int $postId, int $userId): void
{
    $statement = $db->prepare("INSERT INTO access_requests (post_id, requester_id, status) VALUES (?, ?, 'pending') ON DUPLICATE KEY UPDATE status = IF(status = 'rejected', 'pending', status), decided_at = NULL");
    $statement->execute([$postId, $userId]);
}

function access_status(PDO $db, int $postId, ?int $userId): ?string
{
    if (!$userId) {
        return null;
    }
    $statement = $db->prepare('SELECT status FROM access_requests WHERE post_id = ? AND requester_id = ?');
    $statement->execute([$postId, $userId]);
    $status = $statement->fetchColumn();
    return $status === false ? null : (string) $status;
}

function incoming_requests(PDO $db, int $ownerId): array
{
    $statement = $db->prepare("SELECT ar.id, ar.status, ar.created_at, ar.requester_id, p.id AS post_id, p.title, u.username
        FROM access_requests ar JOIN posts p ON p.id = ar.post_id JOIN users u ON u.id = ar.requester_id
        WHERE p.author_id = ? AND ar.status = 'pending' ORDER BY ar.created_at");
    $statement->execute([$ownerId]);
    return $statement->fetchAll();
}

function decide_request(PDO $db, int $requestId, int $ownerId, string $decision): bool
{
    if (!in_array($decision, ['approved', 'rejected'], true)) {
        return false;
    }
    $statement = $db->prepare('UPDATE access_requests ar JOIN posts p ON p.id = ar.post_id SET ar.status = ?, ar.decided_at = NOW() WHERE ar.id = ? AND p.author_id = ?');
    $statement->execute([$decision, $requestId, $ownerId]);
    return $statement->rowCount() === 1;
}
