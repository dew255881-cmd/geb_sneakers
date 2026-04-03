<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$action = sanitize($_POST['action'] ?? '');
$p_id = (int)($_POST['p_id'] ?? 0);
$color_id = (int)($_POST['color_id'] ?? 0);
$size = sanitize($_POST['size'] ?? '');

$cartKey = $p_id . '_' . $color_id . '_' . $size;

if (!isset($_SESSION['cart'][$cartKey])) {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
    exit;
}

if ($action === 'update_qty') {
    $newQty = (int)($_POST['qty'] ?? 1);
    
    if ($newQty < 1) {
        unset($_SESSION['cart'][$cartKey]);
    } else {
        $stmtCheck = $conn->prepare("SELECT qty FROM tb_stock WHERE p_id = ? AND (color_id = ? OR (color_id IS NULL AND ? = 0)) AND size_number = ?");
        $stmtCheck->bind_param("iiis", $p_id, $color_id, $color_id, $size);
        $stmtCheck->execute();
        $stockRow = $stmtCheck->get_result()->fetch_assoc();

        if (!$stockRow || $newQty > $stockRow['qty']) {
            echo json_encode(['success' => false, 'message' => 'Out of stock', 'max_qty' => $stockRow['qty'] ?? 0]);
            exit;
        }
        
        $_SESSION['cart'][$cartKey]['qty'] = $newQty;
    }
} elseif ($action === 'delete') {
    unset($_SESSION['cart'][$cartKey]);
}

$cartCount = getCartCount();
$cartTotal = getCartTotal();

echo json_encode([
    'success' => true,
    'cart_summary' => [
        'count' => $cartCount,
        'total_formatted' => formatPrice($cartTotal > 0 ? $cartTotal + SHIPPING_FEE : 0)
    ]
]);
exit;
