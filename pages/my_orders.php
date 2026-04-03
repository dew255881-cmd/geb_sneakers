<?php
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "index.php?page=login");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM tb_orders WHERE u_id = ? ORDER BY o_id DESC");
$stmt->bind_param("i", $_SESSION['u_id']);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);

function getOrderDetails($conn, $orderId) {
    $stmt = $conn->prepare("SELECT d.*, p.p_name, p.p_img, c.color_name FROM tb_order_details d JOIN tb_products p ON d.p_id = p.p_id LEFT JOIN tb_product_colors c ON d.color_id = c.color_id WHERE d.o_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getOrderStatusInfo($status) {
    $map = [
        'pending'   => ['รอดำเนินการ', 'status-pending', 'fas fa-clock'],
        'confirmed' => ['เตรียมจัดส่ง', 'status-confirmed', 'fas fa-check'],
        'shipped'   => ['จัดส่งแล้ว', 'status-shipped', 'fas fa-truck'],
        'done'      => ['สำเร็จ', 'status-done', 'fas fa-check-double'],
        'cancelled' => ['ยกเลิกแล้ว', 'status-cancelled', 'fas fa-times'],
    ];
    return $map[$status] ?? [$status, 'status-pending', 'fas fa-clock'];
}
?>

<div class="orders-page">
    <div class="container">
        <div class="orders-breadcrumb">
            <a href="<?php echo BASE_URL; ?>">หน้าแรก</a>
            <i class="fas fa-chevron-right"></i>
            <span>คำสั่งซื้อของฉัน</span>
        </div>

        <h1 class="orders-heading">คำสั่งซื้อของฉัน</h1>
        
        <?php if (empty($orders)): ?>
            <div class="orders-empty">
                <div class="orders-empty-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <h3>ยังไม่มีประวัติการสั่งซื้อ</h3>
                <p>เมื่อคุณสั่งซื้อสินค้า รายการจะแสดงที่นี่</p>
                <a href="<?php echo BASE_URL; ?>index.php?page=products" class="btn btn-secondary">ไปช้อปปิ้งเลย</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                <?php 
                    $details = getOrderDetails($conn, $order['o_id']);
                    $statusInfo = getOrderStatusInfo($order['o_status']);
                    $stmtPayCheck = $conn->prepare("SELECT pay_id, pay_status, admin_note FROM tb_payments WHERE o_id = ? ORDER BY pay_id DESC LIMIT 1");
                    $stmtPayCheck->bind_param("i", $order['o_id']);
                    $stmtPayCheck->execute();
                    $resultPayCheck = $stmtPayCheck->get_result();
                    $payment = $resultPayCheck->fetch_assoc();
                ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <div class="order-card-id">
                            <span class="order-hash">#<?php echo str_pad($order['o_id'], 5, '0', STR_PAD_LEFT); ?></span>
                            <span class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="order-status-badge <?php echo $statusInfo[1]; ?>">
                            <i class="<?php echo $statusInfo[2]; ?>"></i>
                            <?php echo $statusInfo[0]; ?>
                        </div>
                    </div>
                    
                    <?php if ($order['o_status'] === 'cancelled' && !empty($order['admin_note'])): ?>
                    <div style="background-color: #fee2e2; border-left: 4px solid #dc2626; padding: 12px; margin: 0 20px 16px 20px; border-radius: 4px; font-size: 0.9rem;">
                        <strong style="color: #991b1b;"><i class="fas fa-exclamation-triangle"></i> ออร์เดอร์ถูกยกเลิก</strong>
                        <p style="margin: 4px 0 0 0; color: #7f1d1d; line-height: 1.5;"><?php echo nl2br(sanitize($order['admin_note'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($order['o_status'] === 'pending' && $payment && $payment['pay_status'] === 'rejected'): ?>
                    <div style="background-color: #fef9c3; border-left: 4px solid #ca8a04; padding: 12px; margin: 0 20px 16px 20px; border-radius: 4px; font-size: 0.9rem;">
                        <strong style="color: #854d0e;"><i class="fas fa-exclamation-circle"></i> สลิปโอนเงินถูกปฏิเสธ</strong>
                        <p style="margin: 4px 0 0 0; color: #713f12; line-height: 1.5;"><?php echo !empty($payment['admin_note']) ? nl2br(sanitize($payment['admin_note'])) : 'กรุณาอัปโหลดสลิปใหม่'; ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="order-card-items">
                        <?php foreach ($details as $detail): ?>
                        <div class="order-item-row">
                            <div class="order-item-thumb">
                                <?php if ($detail['p_img'] && $detail['p_img'] !== 'no_image.png'): ?>
                                    <?php
                                        $imgSrc = UPLOAD_URL . 'products/' . $detail['p_img'];
                                    ?>
                                    <img src="<?php echo $imgSrc; ?>" alt="">
                                <?php else: ?>
                                    <div class="order-item-noimg"><i class="fas fa-shoe-prints"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="order-item-detail">
                                <div class="order-item-name"><?php echo sanitize($detail['p_name']); ?></div>
                                <div class="order-item-meta">
                                    สี: <?php echo sanitize($detail['color_name'] ?? '-'); ?> | ไซส์: <?php echo sanitize($detail['size_number']); ?> | จำนวน: <?php echo $detail['qty']; ?>
                                </div>
                            </div>
                            <div class="order-item-amount"><?php echo formatPrice($detail['price_at_order'] * $detail['qty']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-card-footer">
                        <div class="order-total">
                            ยอดรวม: <strong><?php echo formatPrice($order['o_total']); ?></strong>
                        </div>
                        <div class="order-actions">
                            <?php if ($order['o_status'] === 'pending'): ?>
                                <?php if ($payment): ?>
                                    <?php if ($payment['pay_status'] === 'rejected'): ?>
                                        <a href="<?php echo BASE_URL; ?>index.php?page=payment&id=<?php echo $order['o_id']; ?>" class="order-action-btn order-action-primary" style="background-color:var(--black); border-color:var(--black);">แจ้งโอนเงินใหม่</a>
                                    <?php else: ?>
                                        <span class="order-action-badge">รอตรวจสอบสลิป</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>index.php?page=payment&id=<?php echo $order['o_id']; ?>" class="order-action-btn order-action-primary">แจ้งชำระเงิน</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
