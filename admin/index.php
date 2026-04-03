<?php
require_once dirname(__DIR__) . '/inc/connect.php';
require_once dirname(__DIR__) . '/inc/function.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . BASE_URL);
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

$validPages = ['dashboard', 'products', 'brands', 'stock', 'orders', 'payments', 'colors', 'users'];

if (!in_array($page, $validPages)) {
    $page = 'dashboard';
}

// Handle AJAX actions before ANY output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    if ($page === 'users') {
        require_once __DIR__ . '/pages/users.php';
        exit;
    }
}

require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/sidebar.php';

echo '<main class="admin-main">';
require_once __DIR__ . '/pages/' . $page . '.php';
echo '</main>';

require_once __DIR__ . '/inc/footer.php';
