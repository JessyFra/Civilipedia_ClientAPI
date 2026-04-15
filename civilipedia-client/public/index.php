<?php
ob_start();
session_start();

$env = parse_ini_file(__DIR__ . '/../.env');
foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
}

require_once __DIR__ . '/../src/ApiClient.php';
require_once __DIR__ . '/../src/Auth.php';

$page = $_GET['page'] ?? 'home';

$pages = [
    'home'           => __DIR__ . '/../src/pages/home.php',
    'logout'         => null,
    'login'          => __DIR__ . '/../src/pages/login.php',
    'register'       => __DIR__ . '/../src/pages/register.php',
    'article'        => __DIR__ . '/../src/pages/article.php',
    'article_create' => __DIR__ . '/../src/pages/article_create.php',
    'article_delete' => __DIR__ . '/../src/pages/article_delete.php',
    'profile'        => __DIR__ . '/../src/pages/profile.php',
    'profile_avatar' => __DIR__ . '/../src/pages/profile_avatar.php',
    'admin'          => __DIR__ . '/../src/pages/admin.php',
    'admin_ban'      => __DIR__ . '/../src/pages/admin_ban.php',
    'admin_unban'    => __DIR__ . '/../src/pages/admin_unban.php',
    'contact'        => __DIR__ . '/../src/pages/contact.php',
];

$template = $pages[$page] ?? $pages['home'];

// Déconnexion
if ($page === 'logout') {
    Auth::logout();
    header('Location: /?page=home');
    exit;
}

// Suppression article classique (redirect)
if ($page === 'article_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id && Auth::isLoggedIn()) {
        $api = new ApiClient();
        $api->delete('/articles/' . $id, Auth::getToken());
    }
    header('Location: /?page=home');
    exit;
}

// Suppression article AJAX (retourne 200 sans redirect)
if ($page === 'article_delete_ajax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id && Auth::isLoggedIn()) {
        $api      = new ApiClient();
        $response = $api->delete('/articles/' . $id, Auth::getToken());
        http_response_code($response['status'] === 204 || $response['status'] === 200 ? 200 : 400);
    } else {
        http_response_code(403);
    }
    exit;
}

include __DIR__ . '/../src/layout/header.php';
include $template;
include __DIR__ . '/../src/layout/footer.php';

ob_end_flush();
