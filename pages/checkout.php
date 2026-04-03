<?php
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "index.php?page=login");
    exit;
}
$cartItems = $_SESSION['cart'] ?? [];
$cartTotal = getCartTotal();
$cartCount = getCartCount();
if (empty($cartItems)) {
    header("Location: " . BASE_URL . "index.php?page=cart");
    exit;
}
$stmtAddr = $conn->prepare("SELECT * FROM tb_addresses WHERE u_id = ? ORDER BY is_default DESC, created_at DESC");
$stmtAddr->bind_param("i", $_SESSION['u_id']);
$stmtAddr->execute();
$addresses = $stmtAddr->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($addresses)) {
    setFlash('error', 'กรุณาเพิ่มที่อยู่ก่อนสั่งซื้อ');
    header("Location: " . BASE_URL . "index.php?page=profile");
    exit;
}
$defaultAddress = $addresses[0];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง';
    } else {
        $selected_addr_id = (int)($_POST['addr_id'] ?? 0);
        $selectedAddress = null;
        foreach ($addresses as $a) {
            if ($a['addr_id'] == $selected_addr_id) {
                $selectedAddress = $a;
                break;
            }
        }

        if (!$selectedAddress) {
            $error = 'กรุณาเลือกที่อยู่สำหรับจัดส่ง';
        } else {
            $fullname = $selectedAddress['addr_fullname'];
            $tel = $selectedAddress['addr_phone'];
            $address = $selectedAddress['addr_detail'];
            $addr_id = $selectedAddress['addr_id'];
            
            try {
                $conn->begin_transaction();
                foreach ($cartItems as $item) {
                    $color_id = ($item['color_id'] > 0) ? (int)$item['color_id'] : null;
                    $stmtCheck = $conn->prepare("SELECT qty FROM tb_stock WHERE p_id = ? AND (color_id = ? OR (color_id IS NULL AND ? IS NULL)) AND size_number = ? FOR UPDATE");
                    $stmtCheck->bind_param("iiis", $item['p_id'], $color_id, $color_id, $item['size']);
                    $stmtCheck->execute();
                    $resultCheck = $stmtCheck->get_result();
                    $stock = $resultCheck->fetch_assoc();
                    if (!$stock || $stock['qty'] < $item['qty']) {
                        throw new Exception("สินค้า {$item['p_name']} ไซส์ {$item['size']} มีไม่เพียงพอ");
                    }
                }
                $statusPending = 'pending';
                $stmtOrder = $conn->prepare("INSERT INTO tb_orders (u_id, addr_id, o_fullname, o_phone, o_address, o_total, o_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $orderTotalWithShipping = $cartTotal + SHIPPING_FEE;
                $stmtOrder->bind_param("iisssds", $_SESSION['u_id'], $addr_id, $fullname, $tel, $address, $orderTotalWithShipping, $statusPending);
                $stmtOrder->execute();
                $orderId = $conn->insert_id;
                $stmtDetail = $conn->prepare("INSERT INTO tb_order_details (o_id, p_id, color_id, size_number, qty, price_at_order) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtStockUpdate = $conn->prepare("UPDATE tb_stock SET qty = qty - ? WHERE p_id = ? AND (color_id = ? OR (color_id IS NULL AND ? IS NULL)) AND size_number = ?");
                foreach ($cartItems as $item) {
                    $color_id = ($item['color_id'] > 0) ? (int)$item['color_id'] : null;
                    $stmtDetail->bind_param("iiisid", $orderId, $item['p_id'], $color_id, $item['size'], $item['qty'], $item['price']);
                    $stmtDetail->execute();
                    $stmtStockUpdate->bind_param("iiiis", $item['qty'], $item['p_id'], $color_id, $color_id, $item['size']);
                    $stmtStockUpdate->execute();
                }
                $conn->commit();
                unset($_SESSION['cart']);
                setFlash('success', 'สั่งซื้อสำเร็จ! กรุณาชำระเงิน');
                header("Location: " . BASE_URL . "index.php?page=payment&id=" . $orderId);
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="checkout-page">
    <div class="container">
        <div class="checkout-breadcrumb">
            <a href="<?php echo BASE_URL; ?>">หน้าแรก</a>
            <i class="fas fa-chevron-right"></i>
            <a href="<?php echo BASE_URL; ?>index.php?page=cart">ตะกร้า</a>
            <i class="fas fa-chevron-right"></i>
            <span>ชำระเงิน</span>
        </div>

        <h1 class="checkout-heading">ชำระเงิน</h1>

        <?php if ($error): ?>
            <div class="checkout-alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="checkout-grid">
            <div class="checkout-left">
                <form method="POST" id="checkoutForm">
                    <?php echo csrfField(); ?>

                    <div class="checkout-section" id="savedAddressSection">
                        <div class="checkout-section-header">
                            <h2 class="checkout-section-title">
                                <i class="fas fa-map-marker-alt"></i> ที่อยู่จัดส่ง
                            </h2>
                            <button type="button" class="checkout-change-btn" onclick="document.getElementById('changeAddressModal').style.display='flex'">เปลี่ยนที่อยู่</button>
                        </div>
                        <div class="delivery-card">
                            <div class="delivery-card-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="delivery-card-info">
                                <h4 id="displayFullname"><?php echo sanitize($defaultAddress['addr_fullname']); ?> <span style="font-size: 0.7rem; font-weight: normal; background: var(--gray-200); padding: 2px 8px; border-radius: 4px; margin-left: 6px;"><?php echo sanitize($defaultAddress['addr_label']); ?></span></h4>
                                <div class="delivery-phone">
                                    <i class="fas fa-phone-alt"></i> <span id="displayPhone"><?php echo sanitize($defaultAddress['addr_phone']); ?></span>
                                </div>
                                <div class="delivery-address" id="displayAddress"><?php echo nl2br(sanitize($defaultAddress['addr_detail'])); ?></div>
                            </div>
                            <div class="delivery-card-check">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <input type="hidden" name="addr_id" id="selectedAddrId" value="<?php echo $defaultAddress['addr_id']; ?>">
                    </div>

                    <div class="checkout-section">
                        <div class="checkout-section-header">
                            <h2 class="checkout-section-title">
                                <i class="fas fa-truck"></i> วิธีจัดส่ง
                            </h2>
                        </div>
                        <div class="shipping-card">
                            <div class="shipping-card-radio">
                                <div class="radio-check active"><i class="fas fa-check"></i></div>
                            </div>
                            <div class="shipping-card-info">
                                <strong>จัดส่งแบบมาตรฐาน (Standard)</strong>
                                <span>ระยะเวลาจัดส่ง 2-3 วันทำการ</span>
                            </div>
                            <div class="shipping-card-price"><?php echo formatPrice(SHIPPING_FEE); ?></div>
                        </div>
                    </div>

                    <button type="submit" class="checkout-submit-btn">
                        <i class="fas fa-lock"></i> ยืนยันคำสั่งซื้อ
                    </button>
                </form>
            </div>

            <div class="checkout-right">
                <div class="checkout-summary-card">
                    <h3 class="checkout-summary-title">สรุปคำสั่งซื้อ <span>(<?php echo $cartCount; ?> ชิ้น)</span></h3>
                    
                    <div class="checkout-items-list">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="checkout-item">
                            <div class="checkout-item-img">
                                <?php if ($item['p_img'] && $item['p_img'] !== 'no_image.png'): ?>
                                    <?php 
                                        $imgSrc = UPLOAD_URL . 'products/' . $item['p_img'];
                                        if (isset($item['color_id']) && $item['color_id'] > 0) {
                                            if (file_exists(UPLOAD_PATH . 'colors/' . $item['p_img'])) {
                                                $imgSrc = UPLOAD_URL . 'colors/' . $item['p_img'];
                                            }
                                        }
                                    ?>
                                    <img src="<?php echo $imgSrc; ?>" alt="">
                                <?php else: ?>
                                    <div class="checkout-item-noimg"><i class="fas fa-shoe-prints"></i></div>
                                <?php endif; ?>
                                <span class="checkout-item-qty-badge"><?php echo (int)$item['qty']; ?></span>
                            </div>
                            <div class="checkout-item-info">
                                <div class="checkout-item-name"><?php echo sanitize($item['p_name']); ?></div>
                                <div class="checkout-item-meta">
                                    <?php if (!empty($item['color_name'])): ?>
                                        สี: <?php echo sanitize($item['color_name']); ?> |
                                    <?php endif; ?>
                                    ไซส์: <?php echo sanitize($item['size']); ?>
                                </div>
                            </div>
                            <div class="checkout-item-price"><?php echo formatPrice($item['price'] * $item['qty']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="checkout-totals">
                        <div class="checkout-totals-line">
                            <span>ยอดรวมสินค้า</span>
                            <span><?php echo formatPrice($cartTotal); ?></span>
                        </div>
                        <div class="checkout-totals-line">
                            <span>ค่าจัดส่ง</span>
                            <span><?php echo formatPrice(SHIPPING_FEE); ?></span>
                        </div>
                        <div class="checkout-totals-final">
                            <span>ยอดสุทธิ</span>
                            <span><?php echo formatPrice($cartTotal + SHIPPING_FEE); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="changeAddressModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--white); width: 100%; max-width: 600px; border-radius: 20px; padding: 32px; position: relative; max-height: 80vh; overflow-y: auto;">
        <button type="button" onclick="document.getElementById('changeAddressModal').style.display='none'" style="position: absolute; top: 20px; right: 20px; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--gray-400);"><i class="fas fa-times"></i></button>
        <h3 style="font-size: 1.2rem; margin-bottom: 24px;">เลือกที่อยู่จัดส่ง</h3>
        
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($addresses as $index => $addr): ?>
            <label style="cursor: pointer; border: 2px solid <?php echo $index === 0 ? 'var(--black)' : 'var(--gray-200)'; ?>; border-radius: 12px; padding: 20px; display: flex; align-items: flex-start; gap: 16px; transition: 0.3s;" onclick="selectAddress(<?php echo $addr['addr_id']; ?>, this)">
                <input type="radio" name="modal_addr_id" value="<?php echo $addr['addr_id']; ?>" <?php echo $index === 0 ? 'checked' : ''; ?> style="margin-top: 4px;">
                <div style="flex: 1;">
                    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; font-size: 0.95rem;"><?php echo sanitize($addr['addr_fullname']); ?></span>
                        <span style="background: var(--gray-100); font-size: 0.7rem; padding: 2px 8px; border-radius: 4px;"><?php echo sanitize($addr['addr_label']); ?></span>
                        <?php if ($addr['is_default']): ?>
                            <span style="background: var(--black); color: var(--white); font-size: 0.65rem; padding: 2px 8px; border-radius: 4px;">ค่าเริ่มต้น</span>
                        <?php endif; ?>
                    </div>
                    <div style="color: var(--gray-600); font-size: 0.85rem; margin-bottom: 4px;"><i class="fas fa-phone-alt" style="margin-right: 4px;"></i><?php echo sanitize($addr['addr_phone']); ?></div>
                    <div style="color: var(--gray-500); font-size: 0.85rem; line-height: 1.5;"><?php echo nl2br(sanitize($addr['addr_detail'])); ?></div>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
        <div style="margin-top: 24px; text-align: center;">
            <a href="<?php echo BASE_URL; ?>index.php?page=profile" style="color: var(--gray-600); font-size: 0.85rem; text-decoration: underline;">+ จัดการที่อยู่ / เพิ่มที่อยู่ใหม่</a>
        </div>
    </div>
</div>

<script>
const addrData = <?php echo json_encode($addresses, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS); ?>;
function selectAddress(id, labelElement) {
    document.querySelectorAll('#changeAddressModal label').forEach(el => el.style.borderColor = 'var(--gray-200)');
    labelElement.style.borderColor = 'var(--black)';
    
    const addr = addrData.find(a => a.addr_id == id);
    if(addr) {
        document.getElementById('selectedAddrId').value = addr.addr_id;
        document.getElementById('displayFullname').innerHTML = addr.addr_fullname + ' <span style="font-size: 0.7rem; font-weight: normal; background: var(--gray-200); padding: 2px 8px; border-radius: 4px; margin-left: 6px;">' + addr.addr_label + '</span>';
        document.getElementById('displayPhone').innerText = addr.addr_phone;
        document.getElementById('displayAddress').innerHTML = addr.addr_detail.replace(/\n/g, "<br>");
    }
    
    setTimeout(() => {
        document.getElementById('changeAddressModal').style.display='none';
    }, 250);
}
</script>
