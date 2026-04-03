<?php
$resultNew = $conn->query("SELECT p.*, b.b_name FROM tb_products p LEFT JOIN tb_brands b ON p.b_id = b.b_id WHERE p.p_status = 'active' ORDER BY p.created_at DESC LIMIT 8");
$newProducts = $resultNew->fetch_all(MYSQLI_ASSOC);

$resultBrands = $conn->query("SELECT * FROM tb_brands ORDER BY b_name ASC");
$brands = $resultBrands->fetch_all(MYSQLI_ASSOC);
?>

<?php
$heroImages = [
    'sneaker-friend.jpg',
    'sneaker-gray.jpg',
    'sneaker-green.jpg',
    'sneaker-many.jpg'
];
?>

<section class="hero-slider">
    <div class="slider-container">
        <?php foreach($heroImages as $index => $img): ?>
        <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: url('<?php echo BASE_URL; ?>assets/images/hero/<?php echo $img; ?>')">
            <div class="slide-overlay"></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="hero-content">
        <p class="hero-tag">Premium Sneaker Store</p>
        <h1 class="hero-title">GEB SNEAKERS</h1>
        <p class="hero-desc">คัดสรรรองเท้าผ้าใบแบรนด์เนมมือ 1 เพื่อสไตล์ที่เป็นคุณ</p>
        <a href="<?php echo BASE_URL; ?>index.php?page=products" class="btn btn-primary">เลือกซื้อเลย</a>
    </div>
    
    <div class="slider-dots">
        <?php foreach($heroImages as $index => $img): ?>
        <button class="slider-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>" aria-label="Slide <?php echo $index + 1; ?>"></button>
        <?php endforeach; ?>
    </div>
</section>

<?php if (!empty($brands)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">แบรนด์ของเรา</h2>
            <p class="section-subtitle">BRANDS WE CARRY</p>
        </div>
        <div class="brand-grid">
            <?php foreach ($brands as $brand): ?>
            <a href="<?php echo BASE_URL; ?>index.php?page=products&brand=<?php echo $brand['b_id']; ?>" class="brand-card">
                <div class="brand-card-name"><?php echo sanitize($brand['b_name']); ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($newProducts)): ?>
<section class="section" style="background: var(--gray-50);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">สินค้ามาใหม่</h2>
            <p class="section-subtitle">NEW ARRIVALS</p>
        </div>
        <div class="product-grid">
            <?php foreach ($newProducts as $product): ?>
            <a href="<?php echo BASE_URL; ?>index.php?page=product_detail&id=<?php echo $product['p_id']; ?>" class="product-card">
                <div class="product-card-img">
                    <?php if ($product['p_img'] && $product['p_img'] !== 'no_image.png'): ?>
                        <img src="<?php echo UPLOAD_URL . 'products/' . $product['p_img']; ?>" alt="<?php echo sanitize($product['p_name']); ?>">
                    <?php else: ?>
                        <span class="no-img">&#128095;</span>
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <div class="product-card-brand"><?php echo sanitize($product['b_name'] ?? 'ไม่ระบุแบรนด์'); ?></div>
                    <div class="product-card-name"><?php echo sanitize($product['p_name']); ?></div>
                    <div class="product-card-price"><?php echo formatPrice($product['p_price']); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo BASE_URL; ?>index.php?page=products" class="btn btn-secondary">ดูสินค้าทั้งหมด</a>
        </div>
    </div>
</section>
<?php endif; ?>
