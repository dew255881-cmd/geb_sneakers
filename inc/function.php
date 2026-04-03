<?php
define('SHIPPING_FEE', 50);

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['u_id']);
}

function isAdmin() {
    return isset($_SESSION['u_level']) && $_SESSION['u_level'] === 'admin';
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

function formatPrice($price) {
    return '฿' . number_format($price, 2);
}

function uploadImage($file, $targetDir) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return false;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $newName = uniqid() . '.' . $ext;
    $targetPath = UPLOAD_PATH . $targetDir . DIRECTORY_SEPARATOR . $newName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $newName;
    }

    return false;
}

function getCartCount() {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['qty'];
        }
        return $count;
    }
    return 0;
}

function getCartTotal() {
    $total = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['qty'];
        }
    }
    return $total;
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getStatusBadge($status) {
    $map = [
        'pending'   => ['รอดำเนินการ', 'badge-pending'],
        'confirmed' => ['เตรียมจัดส่ง', 'badge-confirmed'],
        'shipped'   => ['จัดส่งแล้ว', 'badge-shipped'],
        'done'      => ['สำเร็จ', 'badge-done'],
        'cancelled' => ['ยกเลิกแล้ว', 'badge-cancelled'],
        'approved'  => ['อนุมัติ', 'badge-done'],
        'rejected'  => ['ปฏิเสธ', 'badge-cancelled'],
    ];
    $info = $map[$status] ?? [$status, 'badge-pending'];
    return '<span class="badge ' . $info[1] . '">' . $info[0] . '</span>';
}

function getPayStatusBadge($status) {
    return getStatusBadge($status);
}
