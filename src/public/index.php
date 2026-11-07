<?php
declare(strict_types=1);

require dirname(__DIR__) . '/functions.php';

$db = db();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$path = '/' . trim($path, '/');
if ($path === '//') {
    $path = '/';
}

try {
    if ($method === 'GET' && $path === '/') {
        $mode = ($_GET['feed'] ?? 'all') === 'following' ? 'following' : 'all';
        $tag = trim((string) ($_GET['tag'] ?? ''));
        render('home', [
            'pageTitle' => $mode === 'following' ? 'Моя лента — Записочки' : 'Посты — Записочки',
            'posts' => post_feed($db, auth_id(), $mode, $tag),
            'popularTags' => all_tags($db),
            'mode' => $mode,
            'selectedTag' => $tag,
        ]);
    }

    if ($path === '/register') {
        if ($method === 'GET') {
            render('auth/register', ['pageTitle' => 'Регистрация — Записочки', 'errors' => [], 'old' => []]);
        }
        verify_csrf();
        $result = user_create(
            $db,
            (string) ($_POST['username'] ?? ''),
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? '')
        );
        if ($result['errors'] !== []) {
            render('auth/register', [
                'pageTitle' => 'Регистрация — Записочки',
                'errors' => $result['errors'],
                'old' => $_POST,
            ], 422);
        }
        auth_login((int) $result['id']);
        flash('success', 'Аккаунт создан. Добро пожаловать в «Записочки»!');
        redirect('/');
    }

    if ($path === '/login') {
        if ($method === 'GET') {
            render('auth/login', ['pageTitle' => 'Вход — Записочки', 'error' => null]);
        }
        verify_csrf();
        if (!auth_attempt($db, (string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            render('auth/login', [
                'pageTitle' => 'Вход — Записочки',
                'error' => 'Email или пароль не подошли.',
                'email' => (string) ($_POST['email'] ?? ''),
            ], 422);
        }
        flash('success', 'Вы снова с нами.');
        redirect('/');
    }

    if ($method === 'POST' && $path === '/logout') {
        verify_csrf();
        auth_logout();
        flash('success', 'Вы вышли из аккаунта.');
        redirect('/');
    }

    if ($path === '/posts/new') {
        $userId = require_user();
        if ($method === 'GET') {
            render('posts/editor', [
                'pageTitle' => 'Новая публикация — Записочки',
                'post' => null,
                'error' => null,
                'form' => [],
            ]);
        }
        verify_csrf();
        try {
            $postId = post_create($db, $userId, $_POST);
            flash('success', 'Публикация создана.');
            redirect('/posts/' . $postId);
        } catch (RuntimeException $exception) {
            render('posts/editor', [
                'pageTitle' => 'Новая публикация — Записочки',
                'post' => null,
                'error' => $exception->getMessage(),
                'form' => $_POST,
            ], 422);
        }
    }

    if ($method === 'GET' && preg_match('#^/posts/(\d+)$#', $path, $matches)) {
        $post = post_find($db, (int) $matches[1]);
        if (!$post) {
            render('error', ['pageTitle' => 'Публикация не найдена — Записочки'], 404);
        }
        $canView = post_can_view($db, $post, auth_id());
        if ($post['visibility'] === 'private' && !$canView) {
            render('error', ['pageTitle' => 'Публикация не найдена — Записочки'], 404);
        }
        render('posts/show', [
            'pageTitle' => $post['title'] . ' — Записочки',
            'post' => $post,
            'canView' => $canView,
            'requestStatus' => access_status($db, (int) $post['id'], auth_id()),
            'comments' => $canView ? comments_for_post($db, (int) $post['id']) : [],
        ]);
    }

    if (preg_match('#^/posts/(\d+)/edit$#', $path, $matches)) {
        $userId = require_user();
        $post = post_find($db, (int) $matches[1]);
        if (!$post || (int) $post['author_id'] !== $userId) {
            render('error', ['pageTitle' => 'Публикация не найдена — Записочки'], 404);
        }
        if ($method === 'GET') {
            render('posts/editor', [
                'pageTitle' => 'Редактирование — Записочки',
                'post' => $post,
                'error' => null,
                'form' => $post,
            ]);
        }
        verify_csrf();
        try {
            post_update($db, (int) $post['id'], $userId, $_POST);
            flash('success', 'Изменения сохранены.');
            redirect('/posts/' . $post['id']);
        } catch (RuntimeException $exception) {
            render('posts/editor', [
                'pageTitle' => 'Редактирование — Записочки',
                'post' => $post,
                'error' => $exception->getMessage(),
                'form' => $_POST,
            ], 422);
        }
    }

    if ($method === 'POST' && preg_match('#^/posts/(\d+)/delete$#', $path, $matches)) {
        $userId = require_user();
        verify_csrf();
        if (post_delete($db, (int) $matches[1], $userId)) {
            flash('success', 'Публикация удалена.');
        } else {
            flash('error', 'Не удалось удалить публикацию.');
        }
        redirect('/');
    }

    if ($method === 'POST' && preg_match('#^/posts/(\d+)/comments$#', $path, $matches)) {
        $userId = require_user();
        verify_csrf();
        $post = post_find($db, (int) $matches[1]);
        if (!$post || !post_can_view($db, $post, $userId)) {
            render('error', ['pageTitle' => 'Публикация не найдена — Записочки'], 404);
        }
        try {
            comment_create($db, (int) $post['id'], $userId, (string) ($_POST['body'] ?? ''));
            flash('success', 'Комментарий добавлен.');
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('/posts/' . $post['id'] . '#comments');
    }

    if ($method === 'POST' && preg_match('#^/comments/(\d+)/delete$#', $path, $matches)) {
        $userId = require_user();
        verify_csrf();
        $postId = comment_delete($db, (int) $matches[1], $userId);
        flash($postId ? 'success' : 'error', $postId ? 'Комментарий удалён.' : 'Комментарий не найден.');
        redirect($postId ? '/posts/' . $postId . '#comments' : '/');
    }

    if ($method === 'POST' && preg_match('#^/posts/(\d+)/request-access$#', $path, $matches)) {
        $userId = require_user();
        verify_csrf();
        $post = post_find($db, (int) $matches[1]);
        if (!$post || $post['visibility'] !== 'request' || (int) $post['author_id'] === $userId) {
            render('error', ['pageTitle' => 'Публикация не найдена — Записочки'], 404);
        }
        request_access($db, (int) $post['id'], $userId);
        flash('success', 'Запрос отправлен автору.');
        redirect('/posts/' . $post['id']);
    }

    if ($method === 'GET' && $path === '/people') {
        $userId = require_user();
        $search = trim((string) ($_GET['q'] ?? ''));
        render('people', [
            'pageTitle' => 'Пользователи — Записочки',
            'people' => users_discover($db, $userId, $search),
            'search' => $search,
        ]);
    }

    if ($method === 'POST' && preg_match('#^/users/(\d+)/follow$#', $path, $matches)) {
        $userId = require_user();
        verify_csrf();
        $targetId = (int) $matches[1];
        $following = toggle_follow($db, $userId, $targetId);
        flash('success', $following ? 'Автор добавлен в вашу ленту.' : 'Вы отписались от автора.');
        $returnTo = (string) ($_POST['return_to'] ?? '/people');
        redirect(str_starts_with($returnTo, '/') ? $returnTo : '/people');
    }

    if ($method === 'GET' && preg_match('#^/users/(\d+)$#', $path, $matches)) {
        $profile = user_find($db, (int) $matches[1]);
        if (!$profile) {
            render('error', ['pageTitle' => 'Пользователь не найден — Записочки'], 404);
        }
        render('profile', [
            'pageTitle' => $profile['username'] . ' — Записочки',
            'profile' => $profile,
            'posts' => posts_by_author($db, (int) $profile['id'], auth_id()),
            'isFollowing' => auth_id() ? is_following($db, (int) auth_id(), (int) $profile['id']) : false,
        ]);
    }

    if ($method === 'GET' && $path === '/access-requests') {
        $userId = require_user();
        render('requests', [
            'pageTitle' => 'Запросы доступа — Записочки',
            'requests' => incoming_requests($db, $userId),
        ]);
    }

    if ($method === 'POST' && preg_match('#^/access-requests/(\d+)$#', $path, $matches)) {
        $userId = require_user();
        verify_csrf();
        $decision = (string) ($_POST['decision'] ?? '');
        $ok = decide_request($db, (int) $matches[1], $userId, $decision);
        flash($ok ? 'success' : 'error', $ok ? 'Решение сохранено.' : 'Не удалось обработать запрос.');
        redirect('/access-requests');
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    render('error', [
        'pageTitle' => 'Ошибка — Записочки',
        'message' => getenv('APP_ENV') === 'development' ? $exception->getMessage() : null,
    ], 500);
}

render('error', ['pageTitle' => 'Страница не найдена — Записочки'], 404);
