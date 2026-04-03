<?php
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "index.php?page=login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'คำขอไม่ถูกต้อง');
        header("Location: " . BASE_URL . "index.php?page=cart");
        exit;
    }

    $cartKey = $_POST['cart_key'] ?? '';

    if ($_POST['action'] === 'remove' && $cartKey) {
        if (isset($_SESSION['cart'][$cartKey])) {
            unset($_SESSION['cart'][$cartKey]);
            setFlash('success', 'ลบสินค้าออกจากตะกร้าแล้ว');
        }
    } elseif ($_POST['action'] === 'update' && $cartKey) {
        $qty = (int)($_POST['qty'] ?? 1);
        
        if (isset($_SESSION['cart'][$cartKey])) {
            $item = $_SESSION['cart'][$cartKey];
            
            $color_id = ($item['color_id'] > 0) ? (int)$item['color_id'] : null;
            $stmt = $conn->prepare("SELECT qty FROM tb_stock WHERE p_id = ? AND (color_id = ? OR (color_id IS NULL AND ? IS NULL)) AND size_number = ?");
            $stmt->bind_param("iiis", $item['p_id'], $color_id, $color_id, $item['size']);
            $stmt->execute();
            $result = $stmt->get_result();
            $stock = $result->fetch_assoc();
            
            if ($stock) {
                if ($qty <= 0) {
                    unset($_SESSION['cart'][$cartKey]);
                } elseif ($qty > $stock['qty']) {
                    setFlash('error', 'สินค้าไซส์นี้มีเหลือเพียง ' . $stock['qty'] . ' ชิ้น');
                    $_SESSION['cart'][$cartKey]['qty'] = $stock['qty'];
                } else {
                    $_SESSION['cart'][$cartKey]['qty'] = $qty;
                }
            } else {
                setFlash('error', 'ไม่พบสต็อกของสินค้านี้');
                unset($_SESSION['cart'][$cartKey]);
            }
        }
    }
    
    header("Location: " . BASE_URL . "index.php?page=cart");
    exit;
}

$cartItems = $_SESSION['cart'] ?? [];
$cartTotal = getCartTotal();
$cartCount = getCartCount();
?>

<div class="cart-page">
    <div class="container">
        <div class="cart-breadcrumb">
            <a href="<?php echo BASE_URL; ?>">หน้าแรก</a>
            <i class="fas fa-chevron-right"></i>
            <span>ตะกร้าสินค้า</span>
        </div>

        <h1 class="cart-heading">ตะกร้าสินค้า <span class="cart-count-label">(<?php echo $cartCount; ?> ชิ้น)</span></h1>
        
        <?php if (empty($cartItems)): ?>
            <div class="cart-empty">
                <div class="cart-empty-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3>ตะกร้าสินค้าว่างเปล่า</h3>
                <p>เลือกซื้อรองเท้าคู่โปรดของคุณกันเถอะ</p>
                <a href="<?php echo BASE_URL; ?>index.php?page=products" class="btn btn-secondary">ไปช้อปปิ้งต่อ</a>
            </div>
        <?php else: ?>
            <div class="cart-grid">
                <div class="cart-items-section">
                    <?php foreach ($cartItems as $key => $item): ?>
                        <div class="cart-product-card">
                            <a href="<?php echo BASE_URL; ?>index.php?page=product_detail&id=<?php echo $item['p_id']; ?>" class="cart-product-img">
                                <?php if ($item['p_img'] && $item['p_img'] !== 'no_image.png'): ?>
                                    <?php 
                                        $imgSrc = UPLOAD_URL . 'products/' . $item['p_img'];
                                        if (isset($item['color_id']) && $item['color_id'] > 0) {
                                            if (file_exists(UPLOAD_PATH . 'colors/' . $item['p_img'])) {
                                                $imgSrc = UPLOAD_URL . 'colors/' . $item['p_img'];
                                            }
                                        }
                                    ?>
                                    <img src="<?php echo $imgSrc; ?>" alt="<?php echo sanitize($item['p_name']); ?>">
                                <?php else: ?>
                                    <div class="cart-no-img"><i class="fas fa-shoe-prints"></i></div>
                                <?php endif; ?>
                            </a>
                            
                            <div class="cart-product-details">
                                <a href="<?php echo BASE_URL; ?>index.php?page=product_detail&id=<?php echo $item['p_id']; ?>" class="cart-product-name">
                                    <?php echo sanitize($item['p_name']); ?>
                                </a>
                                <div class="cart-product-meta">
                                    <?php if (!empty($item['color_name'])): ?>
                                        <span>สี: <?php echo sanitize($item['color_name']); ?></span>
                                        <span class="meta-dot"></span>
                                    <?php endif; ?>
                                    <span>ไซส์: <?php echo sanitize($item['size']); ?></span>
                                </div>
                                <div class="cart-product-price"><?php echo formatPrice($item['price']); ?></div>
                            </div>
                            
                            <div class="cart-product-controls">
                                <form method="POST" class="cart-qty-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="cart_key" value="<?php echo sanitize($key); ?>">
                                    <?php echo csrfField(); ?>
                                    <div class="cart-qty-control">
                                        <button type="button" class="qty-btn" data-action="decrease">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <div class="qty-value"><?php echo (int)$item['qty']; ?></div>
                                        <input type="hidden" name="qty" value="<?php echo (int)$item['qty']; ?>">
                                        <button type="button" class="qty-btn" data-action="increase">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="cart-product-subtotal"><?php echo formatPrice($item['price'] * $item['qty']); ?></div>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_key" value="<?php echo sanitize($key); ?>">
                                    <?php echo csrfField(); ?>
                                    <button type="submit" class="cart-remove-btn" onclick="return confirm('ยืนยันลบสินค้านี้?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary-section">
                    <div class="cart-summary-card">
                        <h3 class="cart-summary-title">สรุปคำสั่งซื้อ</h3>
                        
                        <div class="cart-summary-line">
                            <span>จำนวนสินค้า</span>
                            <span><?php echo $cartCount; ?> ชิ้น</span>
                        </div>
                        
                        <div class="cart-summary-line">
                            <span>ยอดรวมสินค้า</span>
                            <span><?php echo formatPrice($cartTotal); ?></span>
                        </div>
                        
                        <div class="cart-summary-line">
                            <span>ค่าจัดส่ง</span>
                            <span><?php echo formatPrice(SHIPPING_FEE); ?></span>
                        </div>
                        
                        <div class="cart-summary-total-line">
                            <span>ยอดสุทธิ</span>
                            <span><?php echo formatPrice($cartTotal + SHIPPING_FEE); ?></span>
                        </div>
                        
                        <a href="<?php echo BASE_URL; ?>index.php?page=checkout" class="cart-checkout-btn">
                            ดำเนินการชำระเงิน <i class="fas fa-arrow-right"></i>
                        </a>
                        
                        <a href="<?php echo BASE_URL; ?>index.php?page=products" class="cart-continue-btn">
                            <i class="fas fa-arrow-left"></i> เลือกซื้อสินค้าเพิ่ม
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
