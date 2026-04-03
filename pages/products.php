<?php
$brandFilter = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

$resultBrands = $conn->query("SELECT * FROM tb_brands ORDER BY b_name ASC");
$brands = $resultBrands->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT p.*, b.b_name FROM tb_products p LEFT JOIN tb_brands b ON p.b_id = b.b_id WHERE p.p_status = 'active'";
$params = [];
$types = "";

if ($brandFilter > 0) {
    $sql .= " AND p.b_id = ?";
    $params[] = $brandFilter;
    $types .= "i";
}

if (!empty($searchQuery)) {
    $sql .= " AND (p.p_name LIKE ? OR b.b_name LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt->execute();
    $result = $stmt->get_result();
}
$products = $result->fetch_all(MYSQLI_ASSOC);

$brandName = 'สินค้าทั้งหมด';
if (!empty($searchQuery)) {
    $brandName = "ผลการค้นหา: '" . sanitize($searchQuery) . "'";
} elseif ($brandFilter > 0) {
    foreach ($brands as $b) {
        if ($b['b_id'] == $brandFilter) {
            $brandName = $b['b_name'];
            break;
        }
    }
}
?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?php echo sanitize($brandName); ?></h2>
            <p class="section-subtitle">PRODUCTS</p>
        </div>

        <div class="filter-bar">
            <a href="<?php echo BASE_URL; ?>index.php?page=products" class="filter-btn <?php echo $brandFilter === 0 ? 'active' : ''; ?>">ทั้งหมด</a>
            <?php foreach ($brands as $brand): ?>
            <a href="<?php echo BASE_URL; ?>index.php?page=products&brand=<?php echo $brand['b_id']; ?>"
               class="filter-btn <?php echo $brandFilter === (int)$brand['b_id'] ? 'active' : ''; ?>">
                <?php echo sanitize($brand['b_name']); ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($products)): ?>
        <div class="empty-state">
            <span class="empty-icon"><i class="fas fa-search"></i></span>
            <h3>ไม่พบสินค้าที่คุณกำลังมองหา</h3>
            <p>เราไม่พบสินค้าที่ตรงกับ "<?php echo sanitize($searchQuery ?: $brandName); ?>" ในขณะนี้ ลองใช้คำค้นหาที่กว้างขึ้นหรือเลือกดูสินค้าทั้งหมดของเรา</p>
            <a href="<?php echo BASE_URL; ?>index.php?page=products" class="btn btn-secondary">
                ดูสินค้าทั้งหมด
            </a>
        </div>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
            <a href="<?php echo BASE_URL; ?>index.php?page=product_detail&id=<?php echo $product['p_id']; ?>" class="product-card">
                <div class="product-card-img">
                    <?php if ($product['p_img'] && $product['p_img'] !== 'no_image.png'): ?>
                        <img src="<?php echo UPLOAD_URL . 'products/' . $product['p_img']; ?>" alt="<?php echo sanitize($product['p_name']); ?>">
                    <?php else: ?>
                        <span class="no-img">&#128095;</span>
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <div class="product-card-brand"><?php echo sanitize($product['b_name'] ?? 'ไม่ระบุ'); ?></div>
                    <div class="product-card-name"><?php echo sanitize($product['p_name']); ?></div>
                    <div class="product-card-price"><?php echo formatPrice($product['p_price']); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
