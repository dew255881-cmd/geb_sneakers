<?php
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "index.php?page=login");
    exit;
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    header("Location: " . BASE_URL . "index.php?page=my_orders");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM tb_orders WHERE o_id = ? AND u_id = ?");
$stmt->bind_param("ii", $orderId, $_SESSION['u_id']);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header("Location: " . BASE_URL . "index.php?page=my_orders");
    exit;
}

if ($order['o_status'] !== 'pending') {
    setFlash('warning', 'คำสั่งซื้อนี้ถูกยกเลิก จัดส่ง หรือสถานะเปลี่ยนไปแล้ว ไม่สามารถแจ้งชำระเงินได้');
    header("Location: " . BASE_URL . "index.php?page=my_orders");
    exit;
}

$stmtCheckPay = $conn->prepare("SELECT pay_id, pay_status FROM tb_payments WHERE o_id = ? ORDER BY pay_id DESC LIMIT 1");
$stmtCheckPay->bind_param("i", $orderId);
$stmtCheckPay->execute();
$payment = $stmtCheckPay->get_result()->fetch_assoc();

if ($payment) {
    if ($payment['pay_status'] === 'approved') {
        setFlash('warning', 'คำสั่งซื้อนี้ได้รับการยืนยันการชำระเงินแล้ว');
        header("Location: " . BASE_URL . "index.php?page=my_orders");
        exit;
    } elseif ($payment['pay_status'] === 'pending') {
        setFlash('warning', 'คุณได้แจ้งชำระเงินแล้ว อยู่ระหว่างรอผู้ดูแลระบบตรวจสอบ');
        header("Location: " . BASE_URL . "index.php?page=my_orders");
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง';
    } else {
        $payAmount = (float)($_POST['pay_amount'] ?? 0);
        $payDate = $_POST['pay_date'] ?? date('Y-m-d\TH:i');
        
        if ($payAmount <= 0) {
            $error = 'กรุณาระบุยอดเงินที่โอน';
        } elseif (!isset($_FILES['slip']) || $_FILES['slip']['error'] !== UPLOAD_ERR_OK) {
            $error = 'กรุณาอัปโหลดสลิปโอนเงิน';
        } else {
            $slipName = uploadImage($_FILES['slip'], 'slips');
            if (!$slipName) {
                $error = 'ไฟล์สลิปไม่ถูกต้อง รองรับเฉพาะ JPG, PNG, WEBP';
            } else {
                $statusPending = 'pending';
                $dateFormatted = date('Y-m-d H:i:s', strtotime($payDate));
                
                if ($payment && $payment['pay_status'] === 'rejected') {
                    // Re-upload: Update the existing rejected record
                    $stmtPay = $conn->prepare("UPDATE tb_payments SET pay_slip = ?, pay_amount = ?, pay_date = ?, pay_status = ?, admin_note = NULL WHERE o_id = ?");
                    $stmtPay->bind_param("sdssi", $slipName, $payAmount, $dateFormatted, $statusPending, $orderId);
                    $stmtPay->execute();
                    setFlash('success', 'แจ้งโอนเงินใหม่เรียบร้อย รอผู้ดูแลระบบตรวจสอบอีกครั้ง');
                } else {
                    // First time upload
                    $stmtPay = $conn->prepare("INSERT INTO tb_payments (o_id, pay_slip, pay_amount, pay_date, pay_status) VALUES (?, ?, ?, ?, ?)");
                    $stmtPay->bind_param("isdss", $orderId, $slipName, $payAmount, $dateFormatted, $statusPending);
                    $stmtPay->execute();
                    setFlash('success', 'อัปโหลดสลิปสำเร็จ รอผู้ดูแลระบบตรวจสอบ');
                }
                
                header("Location: " . BASE_URL . "index.php?page=my_orders");
                exit;
            }
        }
    }
}
?>

<div class="payment-page">
    <div class="container">
        <div class="payment-breadcrumb">
            <a href="<?php echo BASE_URL; ?>">หน้าแรก</a>
            <i class="fas fa-chevron-right"></i>
            <a href="<?php echo BASE_URL; ?>index.php?page=my_orders">คำสั่งซื้อ</a>
            <i class="fas fa-chevron-right"></i>
            <span>แจ้งชำระเงิน</span>
        </div>

        <div class="payment-grid">
            <div class="payment-left">
                <div class="payment-card">
                    <div class="payment-card-header">
                        <h1 class="payment-title">แจ้งชำระเงิน</h1>
                        <span class="payment-order-id">Order #<?php echo str_pad($orderId, 5, '0', STR_PAD_LEFT); ?></span>
                    </div>

                    <?php if ($error): ?>
                        <div class="payment-alert"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="paymentForm">
                        <?php echo csrfField(); ?>

                        <div class="slip-upload-zone" id="slipUploadZone">
                            <input type="file" name="slip" id="slipInput" accept="image/jpeg, image/png, image/webp" required>
                            <div class="slip-upload-content" id="slipUploadContent">
                                <div class="slip-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="slip-upload-title">อัปโหลดสลิปการโอนเงิน</div>
                                <div class="slip-upload-hint">คลิกเพื่อเลือกไฟล์ หรือลากไฟล์มาวางที่นี่</div>
                                <div class="slip-upload-formats">รองรับ JPG, PNG, WEBP (สูงสุด 5MB)</div>
                            </div>
                            <div class="slip-preview" id="slipPreview" style="display:none;">
                                <img src="" alt="Preview" id="slipPreviewImg">
                                <button type="button" class="slip-preview-remove" id="slipRemoveBtn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div class="payment-form-grid">
                            <div class="payment-field">
                                <label class="payment-label">ยอดเงินที่โอนจริง *</label>
                                <div class="payment-input-wrap">
                                    <span class="payment-input-prefix">฿</span>
                                    <input type="number" step="0.01" name="pay_amount" class="payment-input" placeholder="0.00" value="<?php echo sanitize($_POST['pay_amount'] ?? $order['o_total']); ?>" required>
                                </div>
                            </div>
                            <div class="payment-field">
                                <label class="payment-label">วันเวลาที่โอน *</label>
                                <input type="datetime-local" name="pay_date" class="payment-input payment-input-full" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                            </div>
                        </div>

                        <button type="submit" class="payment-submit-btn">
                            <i class="fas fa-paper-plane"></i> ยืนยันการแจ้งชำระเงิน
                        </button>
                    </form>
                </div>
            </div>

            <div class="payment-right">
                <div class="payment-info-card">
                    <h3 class="payment-info-title">ข้อมูลการชำระเงิน</h3>
                    
                    <div class="bank-card">
                        <div class="bank-card-logo">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="bank-card-details">
                            <div class="bank-card-name">ธนาคารกสิกรไทย (KBank)</div>
                            <div class="bank-card-account">123-4-56789-0</div>
                            <div class="bank-card-holder">บจก. เกิบ สนิคเกอร์</div>
                        </div>
                    </div>

                    <div class="payment-amount-card">
                        <div class="payment-amount-label">ยอดที่ต้องชำระ</div>
                        <div class="payment-amount-value"><?php echo formatPrice($order['o_total']); ?></div>
                    </div>

                    <div class="payment-steps">
                        <div class="payment-step">
                            <div class="payment-step-num">1</div>
                            <div class="payment-step-text">โอนเงินไปยังบัญชีด้านบน</div>
                        </div>
                        <div class="payment-step">
                            <div class="payment-step-num">2</div>
                            <div class="payment-step-text">อัปโหลดสลิปการโอนเงิน</div>
                        </div>
                        <div class="payment-step">
                            <div class="payment-step-num">3</div>
                            <div class="payment-step-text">รอแอดมินตรวจสอบและยืนยัน</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var slipInput = document.getElementById('slipInput');
    var slipPreview = document.getElementById('slipPreview');
    var slipPreviewImg = document.getElementById('slipPreviewImg');
    var slipUploadContent = document.getElementById('slipUploadContent');
    var slipRemoveBtn = document.getElementById('slipRemoveBtn');
    var slipUploadZone = document.getElementById('slipUploadZone');

    slipInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                slipPreviewImg.src = e.target.result;
                slipPreview.style.display = 'flex';
                slipUploadContent.style.display = 'none';
                slipUploadZone.classList.add('has-file');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    slipRemoveBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        slipInput.value = '';
        slipPreview.style.display = 'none';
        slipUploadContent.style.display = 'flex';
        slipUploadZone.classList.remove('has-file');
    });

    slipUploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        slipUploadZone.classList.add('drag-over');
    });

    slipUploadZone.addEventListener('dragleave', function() {
        slipUploadZone.classList.remove('drag-over');
    });

    slipUploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        slipUploadZone.classList.remove('drag-over');
        if (e.dataTransfer.files.length > 0) {
            slipInput.files = e.dataTransfer.files;
            slipInput.dispatchEvent(new Event('change'));
        }
    });
});
</script>
