<?php
header('Content-Type: application/json; charset=utf-8');

$p_id = isset($_GET['p_id']) ? (int)$_GET['p_id'] : 0;
$color_id = isset($_GET['color_id']) ? (int)$_GET['color_id'] : 0;

if ($p_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$final_color_id = ($color_id > 0) ? (int)$color_id : null;
$stmtStocks = $conn->prepare("SELECT size_number, qty FROM tb_stock WHERE p_id = ? AND (color_id = ? OR (color_id IS NULL AND ? IS NULL)) ORDER BY CAST(size_number AS UNSIGNED) ASC");
$stmtStocks->bind_param("iiis", $p_id, $final_color_id, $final_color_id);
$stmtStocks->execute();
$resultStocks = $stmtStocks->get_result();
$stocks = $resultStocks->fetch_all(MYSQLI_ASSOC);

if (empty($stocks)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

// Return sizes along with qty so the frontend knows what's out of stock for this specific color
echo json_encode(['success' => true, 'data' => $stocks]);
exit;
