<?php
header('Content-Type: application/json; charset=utf-8');

$color_id = isset($_GET['color_id']) ? (int)$_GET['color_id'] : 0;

if ($color_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// Get main color image
$stmtMain = $conn->prepare("SELECT color_img FROM tb_product_colors WHERE color_id = ?");
$stmtMain->bind_param("i", $color_id);
$stmtMain->execute();
$mainResult = $stmtMain->get_result()->fetch_assoc();

if (!$mainResult) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลสีนี้']);
    exit;
}

$images = [];

// Push main image first
if ($mainResult['color_img'] !== 'no_image.png') {
    $images[] = $mainResult['color_img'];
}

// Fetch gallery images
$stmtGal = $conn->prepare("SELECT g_img FROM tb_product_gallery WHERE color_id = ? ORDER BY g_id ASC");
$stmtGal->bind_param("i", $color_id);
$stmtGal->execute();
$resGal = $stmtGal->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($resGal as $g) {
    $images[] = $g['g_img'];
}

echo json_encode(['success' => true, 'data' => $images]);
exit;
