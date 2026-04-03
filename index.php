<?php
ob_start();
require_once __DIR__ . '/inc/connect.php';
require_once __DIR__ . '/inc/function.php';
require_once __DIR__ . '/inc/mapsite.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

if ($page === 'logout') {
    session_destroy();
    header("Location: " . BASE_URL);
    exit;
}

if (!array_key_exists($page, $pages)) {
    $page = 'home';
}

$protectedPages = ['cart', 'checkout', 'payment', 'my_orders', 'profile'];
if (in_array($page, $protectedPages) && !isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $page;
    header("Location: " . BASE_URL . "index.php?page=login");
    exit;
}

if (strpos($page, 'ajax_') === 0 || $page === 'get_sizes' || $page === 'get_gallery') {
    if (isset($pages[$page])) {
        require_once __DIR__ . '/' . $pages[$page];
        exit;
    }
}

require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/menu.php';
require_once __DIR__ . '/' . $pages[$page];
require_once __DIR__ . '/inc/footer.php';

ob_end_flush();
