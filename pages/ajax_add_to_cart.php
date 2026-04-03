<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'คำขอไม่ถูกต้อง (CSRF)']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนเพิ่มสินค้าลงตะกร้า', 'redirect' => BASE_URL . 'index.php?page=login']);
    exit;
}

$productId = (int)($_POST['p_id'] ?? 0);
$selectedSize = sanitize($_POST['size'] ?? '');
$selectedColor = (int)($_POST['color_id'] ?? 0);
$qty = 1;

if ($productId <= 0 || empty($selectedSize)) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเลือกตัวเลือกสินค้าให้ครบถ้วน']);
    exit;
}

// Fetch Product Info
$stmt = $conn->prepare("SELECT p.*, b.b_name FROM tb_products p LEFT JOIN tb_brands b ON p.b_id = b.b_id WHERE p.p_id = ? AND p.p_status = 'active'");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลสินค้า']);
    exit;
}

$finalColorId = ($selectedColor > 0) ? $selectedColor : null;

// Check Stock
$stmtCheck = $conn->prepare("SELECT qty FROM tb_stock WHERE p_id = ? AND (color_id = ? OR (color_id IS NULL AND ? IS NULL)) AND size_number = ?");
$stmtCheck->bind_param("iiis", $productId, $finalColorId, $finalColorId, $selectedSize);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();
$stockRow = $resultCheck->fetch_assoc();

if (!$stockRow || $stockRow['qty'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'สินค้าไซส์และสีนี้หมดแล้ว']);
    exit;
}

// Get color info for cart display
$colorInfo = null;
if ($finalColorId) {
    $stmtColorInfo = $conn->prepare("SELECT color_name, color_img FROM tb_product_colors WHERE color_id = ?");
    $stmtColorInfo->bind_param("i", $finalColorId);
    $stmtColorInfo->execute();
    $colorInfo = $stmtColorInfo->get_result()->fetch_assoc();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartKey = $productId . '_' . $selectedColor . '_' . $selectedSize;

if (isset($_SESSION['cart'][$cartKey])) {
    $newQty = $_SESSION['cart'][$cartKey]['qty'] + $qty;
    if ($newQty > $stockRow['qty']) {
        echo json_encode(['success' => false, 'message' => 'จำนวนสินค้าในตะกร้าเกินสต็อกที่มี']);
        exit;
    }
    $_SESSION['cart'][$cartKey]['qty'] = $newQty;
} else {
    $imgToUse = ($colorInfo && $colorInfo['color_img'] && $colorInfo['color_img'] !== 'no_image.png') ? $colorInfo['color_img'] : $product['p_img'];
    
    $_SESSION['cart'][$cartKey] = [
        'p_id' => $productId,
        'color_id' => $finalColorId,
        'p_name' => $product['p_name'],
        'color_name' => $colorInfo['color_name'] ?? '',
        'size' => $selectedSize,
        'qty' => $qty,
        'price' => $product['p_price'],
        'p_img' => $imgToUse
    ];
}

$itemInfo = $_SESSION['cart'][$cartKey];
$cartCount = getCartCount();
$cartTotal = getCartTotal();

echo json_encode([
    'success' => true,
    'message' => 'เพิ่มสินค้าในตะกร้าแล้ว',
    'added_item' => [
        'name' => $itemInfo['p_name'],
        'color' => $itemInfo['color_name'],
        'size' => $itemInfo['size'],
        'qty' => $itemInfo['qty'],
        'price_formatted' => formatPrice($itemInfo['price']),
        'img_url' => ($itemInfo['color_id'] ? UPLOAD_URL . 'colors/' : UPLOAD_URL . 'products/') . $itemInfo['p_img']
    ],
    'cart_summary' => [
        'count' => $cartCount,
        'total_formatted' => formatPrice($cartTotal > 0 ? $cartTotal + SHIPPING_FEE : 0)
    ]
]);
exit;
