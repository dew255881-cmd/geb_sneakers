<?php
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header("Location: " . BASE_URL . "index.php?page=products");
    exit;
}
$stmt = $conn->prepare("SELECT p.*, b.b_name FROM tb_products p LEFT JOIN tb_brands b ON p.b_id = b.b_id WHERE p.p_id = ? AND p.p_status = 'active'");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
if (!$product) {
    header("Location: " . BASE_URL . "index.php?page=products");
    exit;
}
$stmtColors = $conn->prepare("SELECT * FROM tb_product_colors WHERE p_id = ? ORDER BY color_name ASC");
$stmtColors->bind_param("i", $productId);
$stmtColors->execute();
$colors = $stmtColors->get_result()->fetch_all(MYSQLI_ASSOC);
$baseStock = [];
if (empty($colors)) {
    $stmtBaseStock = $conn->prepare("SELECT * FROM tb_stock WHERE p_id = ? AND color_id IS NULL AND qty > 0 ORDER BY CAST(size_number AS UNSIGNED) ASC");
    $stmtBaseStock->bind_param("i", $productId);
    $stmtBaseStock->execute();
    $baseStock = $stmtBaseStock->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmtBaseGal = $conn->prepare("SELECT g_img FROM tb_product_gallery WHERE p_id = ? AND color_id IS NULL ORDER BY g_id ASC");
$stmtBaseGal->bind_param("i", $productId);
$stmtBaseGal->execute();
$baseGalleries = $stmtBaseGal->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<section class="product-detail-section section">
    <div class="container">
        <div class="product-detail-layout">
            <div class="product-gallery-section">
                <div id="productGallery" class="product-gallery-vertical">
                    <?php if ($product['p_img'] && $product['p_img'] !== 'no_image.png'): ?>
                        <img src="<?php echo UPLOAD_URL . 'products/' . $product['p_img']; ?>" class="gallery-thumb active" onclick="swapMainImage(this, '<?php echo UPLOAD_URL . 'products/' . $product['p_img']; ?>')">
                    <?php endif; ?>
                    <?php foreach ($baseGalleries as $g): ?>
                        <img src="<?php echo UPLOAD_URL . 'products/' . $g['g_img']; ?>" class="gallery-thumb" onclick="swapMainImage(this, '<?php echo UPLOAD_URL . 'products/' . $g['g_img']; ?>')">
                    <?php endforeach; ?>
                </div>
                
                <div class="product-main-view">
                    <?php if ($product['p_img'] && $product['p_img'] !== 'no_image.png'): ?>
                        <img id="mainProductImg" src="<?php echo UPLOAD_URL . 'products/' . $product['p_img']; ?>" alt="<?php echo sanitize($product['p_name']); ?>" data-base-img="<?php echo UPLOAD_URL . 'products/' . $product['p_img']; ?>">
                    <?php else: ?>
                        <span class="no-img" id="mainProductImg" data-base-img="" style="font-size:6rem;">&#128095;</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="product-info-section">
                <div class="product-brand"><?php echo sanitize($product['b_name'] ?? 'GEB SNEAKERS'); ?></div>
                <h1 class="product-title"><?php echo sanitize($product['p_name']); ?></h1>
                <div class="product-price"><?php echo formatPrice($product['p_price']); ?></div>

                <form method="POST" id="addToCartForm">
                    <input type="hidden" name="action" value="add_to_cart">
                    <input type="hidden" name="color_id" id="selected_color" value="<?php echo empty($colors) ? '0' : ''; ?>">
                    <input type="hidden" name="size" id="selected_size" value="">
                    <?php echo csrfField(); ?>

                    <?php if (!empty($colors)): ?>
                        <span class="section-label" style="margin-bottom:8px;">สี (Color)</span>
                        <div class="color-grid">
                            <?php foreach ($colors as $c): ?>
                                <button type="button" class="color-btn" data-color-id="<?php echo $c['color_id']; ?>" data-color-img="<?php echo sanitize($c['color_img']); ?>" title="<?php echo sanitize($c['color_name']); ?>">
                                    <?php if ($c['color_img'] !== 'no_image.png'): ?>
                                        <img src="<?php echo UPLOAD_URL . 'colors/' . $c['color_img']; ?>" alt="<?php echo sanitize($c['color_name']); ?>">
                                    <?php else: ?>
                                        <span style="background:var(--black);color:var(--white);display:flex;align-items:center;justify-content:center;font-size:0.75rem;width:100%;height:100%;"><?php echo mb_substr(sanitize($c['color_name']), 0, 3); ?></span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <span class="section-label" style="margin-bottom:8px;">ไซส์ (Size)</span>
                    <div id="size-grid" class="size-grid">
                        <?php if (!empty($colors)): ?>
                            <div style="color:var(--gray-500);font-size:0.85rem;">กรุณาเลือกสีก่อน</div>
                        <?php else: ?>
                            <?php foreach ($baseStock as $stock): ?>
                                <button type="button" class="size-btn <?php echo (int)$stock['qty'] <= 0 ? 'disabled' : ''; ?>" data-size="<?php echo $stock['size_number']; ?>" data-qty="<?php echo $stock['qty']; ?>" onclick="selectSize(this)" <?php echo (int)$stock['qty'] <= 0 ? 'disabled' : ''; ?>>
                                    <?php echo sanitize($stock['size_number']); ?>
                                </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div id="stock-info" style="color:var(--success); font-size: 0.85rem; font-weight: 600; text-align: left; margin-top: 12px; margin-bottom: 12px; min-height: 20px;"></div>

                    <button type="submit" id="addToCartBtn" class="btn btn-primary btn-add-to-cart btn-block" style="border-radius:var(--radius-pill);" disabled>
                        เพิ่มลงตะกร้า (ADD TO CART)
                    </button>
                    
                    <div class="trust-badges">
                        <div class="trust-badge">
                            <i class="fas fa-certificate"></i>
                            <span>รับประกันของแท้</span>
                        </div>
                        <div class="trust-badge">
                            <i class="fas fa-bolt"></i>
                            <span>จัดส่งรวดเร็ว</span>
                        </div>
                    </div>
                </form>

                <?php if (!empty($product['p_detail'])): ?>
                    <div class="product-desc-content">
                        <h4 class="section-label" style="margin-bottom:12px;">รายละเอียดสินค้า</h4>
                        <div style="color:var(--gray-600);font-size:0.95rem;line-height:1.7;"><?php echo nl2br(sanitize($product['p_detail'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="cart-modal-overlay" id="cartModal">
    <div class="cart-modal-container">
        <button class="cart-modal-close" onclick="closeCartModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="cart-modal-content">
            <div class="cart-modal-left">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
                    <h3 class="section-label" style="margin:0;">เพิ่มสินค้าสำเร็จ</h3>
                    <i class="fas fa-check-circle" style="color:var(--success);font-size:1.5rem;"></i>
                </div>
                <div class="added-item-box">
                    <img src="" alt="" class="added-item-img" id="modalItemImg">
                    <div class="added-item-info">
                        <h4 id="modalItemName">ชื่อสินค้า</h4>
                        <div style="font-size:1.1rem;font-weight:700;margin-bottom:12px;" id="modalItemPrice">฿0.00</div>
                        <div style="font-size:0.85rem;color:var(--gray-500);margin-bottom:12px;line-height:1.5;">
                            <div id="modalItemColor">สี: -</div>
                            <div id="modalItemSize">ไซส์: -</div>
                        </div>
                        <div class="modal-qty-box">
                            <button type="button" class="qty-control" style="background:none;border:none;" id="modalQtyDown"><i class="fas fa-minus"></i></button>
                            <div class="qty-number" id="modalQtyVal">1</div>
                            <button type="button" class="qty-control" style="background:none;border:none;" id="modalQtyUp"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="cart-modal-right">
                <h3 class="section-label" style="margin-bottom:24px;">สรุปคำสั่งซื้อ</h3>
                
                <div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:0.8rem;color:var(--gray-500);font-weight:600;text-transform:uppercase;">
                    <span>จำนวนสินค้าทั้งหมด</span>
                    <span id="modalCartCount" style="color:var(--black);">0</span>
                </div>
                
                <div style="display:flex;justify-content:space-between;padding-top:16px;border-top:1px solid var(--gray-200);font-size:1.15rem;font-weight:700;margin-bottom:32px;">
                    <span>ยอดรวมสุทธิ</span>
                    <span id="modalCartTotal">฿0.00</span>
                </div>
                
                <div>
                    <a href="index.php?page=checkout" class="btn btn-modal btn-modal-checkout" style="display:flex;align-items:center;justify-content:center;">ดำเนินการชำระเงิน</a>
                    <button type="button" onclick="closeCartModal()" class="btn btn-modal btn-modal-keep" style="cursor:pointer;">เลือกซื้อสินค้าต่อ</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const colorBtns = document.querySelectorAll('.color-btn');
    const sizeGrid = document.getElementById('size-grid');
    const colorInput = document.getElementById('selected_color');
    const sizeInput = document.getElementById('selected_size');
    const mainImg = document.getElementById('mainProductImg');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const pId = <?php echo $productId; ?>;
    const baseImgUrl = '<?php echo UPLOAD_URL; ?>';

    colorBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            colorBtns.forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            const colorId = this.getAttribute('data-color-id');
            const colorImg = this.getAttribute('data-color-img');
            colorInput.value = colorId;
            sizeInput.value = '';
            document.getElementById('stock-info').innerHTML = '';
            checkSubmitStatus();
            let targetImg = (colorImg && colorImg !== 'no_image.png') ? baseImgUrl + 'colors/' + colorImg : mainImg.getAttribute('data-base-img');
            const galleryContainer = document.getElementById('productGallery');
            galleryContainer.innerHTML = `<img src="${targetImg}" class="gallery-thumb active" onclick="swapMainImage(this, '${targetImg}')">`;
            if (mainImg.src !== targetImg) {
                mainImg.classList.add('fade-out');
                setTimeout(() => {
                    mainImg.src = targetImg;
                    mainImg.classList.remove('fade-out');
                }, 200);
            }
            sizeGrid.innerHTML = '<div class="text-muted small py-2">กำลังโหลดไซส์...</div>';
            fetch(`index.php?page=get_sizes&p_id=${pId}&color_id=${colorId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        sizeGrid.innerHTML = '';
                        data.data.forEach(stock => {
                            const isOut = stock.qty <= 0;
                            const btnHTML = `<button type="button" class="size-btn border ${isOut ? 'disabled' : ''}" data-size="${stock.size_number}" data-qty="${stock.qty}" onclick="selectSize(this)" ${isOut ? 'disabled' : ''}>${stock.size_number}</button>`;
                            sizeGrid.insertAdjacentHTML('beforeend', btnHTML);
                        });
                    } else {
                        sizeGrid.innerHTML = '<div class="text-danger small py-2">สินค้าสีนี้หมดชั่วคราว</div>';
                    }
                });
            fetch(`index.php?page=get_gallery&color_id=${colorId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        galleryContainer.innerHTML = '';
                        data.data.forEach((imgName, index) => {
                            const imgSrc = baseImgUrl + 'colors/' + imgName;
                            const imgHTML = `<img src="${imgSrc}" class="gallery-thumb ${index === 0 ? 'active' : ''}" onclick="swapMainImage(this, '${imgSrc}')">`;
                            galleryContainer.insertAdjacentHTML('beforeend', imgHTML);
                        });
                    }
                });
        });
    });

    window.swapMainImage = function(thumbElement, src) {
        if (mainImg.src === src) return;
        document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
        thumbElement.classList.add('active');
        mainImg.classList.add('fade-out');
        setTimeout(() => {
            mainImg.src = src;
            mainImg.classList.remove('fade-out');
        }, 200);
    };

    window.selectSize = function(btnElement) {
        if(btnElement.classList.contains('disabled')) return;
        document.querySelectorAll('.size-grid .size-btn').forEach(b => b.classList.remove('selected'));
        btnElement.classList.add('selected');
        sizeInput.value = btnElement.getAttribute('data-size');
        
        const qty = btnElement.getAttribute('data-qty');
        const stockInfo = document.getElementById('stock-info');
        if (qty) {
            if (parseInt(qty) > 0 && parseInt(qty) <= 5) {
                stockInfo.style.color = '#d97706'; // warning yellow for low stock
                stockInfo.innerHTML = `<i class="fas fa-exclamation-circle"></i> สินค้าใกล้หมด! (เหลือเพียง ${qty} ชิ้น)`;
            } else if (parseInt(qty) > 5) {
                stockInfo.style.color = 'var(--success)';
                stockInfo.innerHTML = `<i class="fas fa-box"></i> มีสินค้าพร้อมส่ง (เหลือ ${qty} ชิ้น)`;
            }
        }
        
        checkSubmitStatus();
    }

    let currentCartData = null;

    function checkSubmitStatus() {
        if (colorInput.value && sizeInput.value) {
            addToCartBtn.disabled = false;
        } else {
            addToCartBtn.disabled = true;
        }
    }

    const addForm = document.getElementById('addToCartForm');
    if (addForm) {
        addForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(addForm);
            formData.append('p_id', pId);
            const originalText = addToCartBtn.innerText;
            addToCartBtn.disabled = true;
            addToCartBtn.innerText = 'กำลังเพิ่ม...';
            fetch('index.php?page=ajax_add_to_cart', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                addToCartBtn.disabled = false;
                addToCartBtn.innerText = originalText;
                if (data.success) {
                    currentCartData = { p_id: pId, color_id: colorInput.value, size: sizeInput.value, qty: 1 };
                    updateModalUI(data);
                    openCartModal();
                } else {
                    if (data.redirect) window.location.href = data.redirect;
                    else alert(data.message || 'Error');
                }
            })
            .catch(err => {
                addToCartBtn.disabled = false;
                addToCartBtn.innerText = originalText;
            });
        });
    }

    function updateModalUI(data) {
        if (data.added_item) {
            document.getElementById('modalItemImg').src = data.added_item.img_url;
            document.getElementById('modalItemName').innerText = data.added_item.name;
            document.getElementById('modalItemColor').innerText = 'สี: ' + (data.added_item.color || '-').toUpperCase();
            document.getElementById('modalItemSize').innerText = 'ไซส์: ' + data.added_item.size;
            document.getElementById('modalItemPrice').innerText = data.added_item.price_formatted;
            document.getElementById('modalQtyVal').innerText = data.added_item.qty;
            if (currentCartData) currentCartData.qty = data.added_item.qty;
        }
        document.getElementById('modalCartCount').innerText = data.cart_summary.count;
        document.getElementById('modalCartTotal').innerText = data.cart_summary.total_formatted;
        const badge = document.getElementById('cart-badge-count');
        if (badge) {
            badge.innerText = data.cart_summary.count;
        }
    }

    window.updateCartQty = function(change) {
        if (!currentCartData) return;
        const newQty = parseInt(currentCartData.qty) + change;
        if (newQty < 1) return;
        const formData = new FormData();
        formData.append('action', 'update_qty');
        formData.append('p_id', currentCartData.p_id);
        formData.append('color_id', currentCartData.color_id);
        formData.append('size', currentCartData.size);
        formData.append('qty', newQty);
        fetch('index.php?page=ajax_cart_update', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentCartData.qty = newQty;
                document.getElementById('modalQtyVal').innerText = newQty;
                document.getElementById('modalCartCount').innerText = data.cart_summary.count;
                document.getElementById('modalCartTotal').innerText = data.cart_summary.total_formatted;
            }
        });
    }

    document.getElementById('modalQtyUp').onclick = () => updateCartQty(1);
    document.getElementById('modalQtyDown').onclick = () => updateCartQty(-1);
});

function openCartModal() {
    const modal = document.getElementById('cartModal');
    modal.style.display = 'flex';
    void modal.offsetWidth;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCartModal() {
    const modal = document.getElementById('cartModal');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}
</script>
