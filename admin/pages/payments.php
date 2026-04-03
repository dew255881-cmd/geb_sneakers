<?php
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง';
    } else {
        $pay_id = (int)$_POST['pay_id'];
        $o_id = (int)$_POST['o_id'];
        $new_status = $_POST['pay_status'];
        $admin_note = sanitize($_POST['admin_note'] ?? '');
        
        $validStatuses = ['pending', 'approved', 'rejected'];
        if (!in_array($new_status, $validStatuses)) {
            $error = 'สถานะไม่ถูกต้อง';
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("UPDATE tb_payments SET pay_status = ?, admin_note = ? WHERE pay_id = ?");
                $stmt->bind_param("ssi", $new_status, $admin_note, $pay_id);
                $stmt->execute();
                
                if ($new_status === 'approved') {
                    $stmtCheck = $conn->prepare("SELECT o_status FROM tb_orders WHERE o_id = ?");
                    $stmtCheck->bind_param("i", $o_id);
                    $stmtCheck->execute();
                    $resultCheck = $stmtCheck->get_result();
                    $o_status = $resultCheck->fetch_assoc()['o_status'] ?? '';
                    
                    if ($o_status === 'pending') {
                        $stmtUpdateOrder = $conn->prepare("UPDATE tb_orders SET o_status = 'confirmed' WHERE o_id = ?");
                        $stmtUpdateOrder->bind_param("i", $o_id);
                        $stmtUpdateOrder->execute();
                    }
                }
                
                $conn->commit();
                $success = 'อัปเดตสถานะการชำระเงินเรียบร้อย';
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            }
        }
    }
}

$resultPayments = $conn->query("SELECT p.*, o.o_total, o.o_fullname, o.o_status 
                     FROM tb_payments p 
                     JOIN tb_orders o ON p.o_id = o.o_id 
                     ORDER BY p.pay_id DESC");
$payments = $resultPayments->fetch_all(MYSQLI_ASSOC);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h1 class="admin-page-title" style="margin: 0;">ตรวจสอบแจ้งชำระเงิน</h1>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

<div class="admin-card">
    <div class="admin-card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ข้อมูลโอน</th>
                    <th>ยอดสุทธิ Order</th>
                    <th>ยอดที่แจ้งโอน</th>
                    <th>หลักฐาน (Slip)</th>
                    <th>สถานะ</th>
                    <th>หมายเหตุแอดมิน</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $pay): ?>
                <tr>
                    <td>
                        Order: <a href="?page=orders" style="font-weight:700;text-decoration:underline;">#<?php echo str_pad($pay['o_id'], 5, '0', STR_PAD_LEFT); ?></a><br>
                        ลูกค้า: <?php echo sanitize($pay['o_fullname']); ?><br>
                        วันเวลาโอน: <span style="font-size:0.75rem;"><?php echo date('d/m/Y H:i', strtotime($pay['pay_date'])); ?></span>
                    </td>
                    <td><strong><?php echo formatPrice($pay['o_total']); ?></strong></td>
                    <td style="color: <?php echo $pay['pay_amount'] < $pay['o_total'] ? 'var(--error)' : 'var(--success)'; ?>; font-weight:700;">
                        <?php echo formatPrice($pay['pay_amount']); ?>
                        <?php if ($pay['pay_amount'] < $pay['o_total']): ?>
                            <br><span style="font-size:0.7rem;">(โอนไม่ครบ)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-secondary view-slip-btn" data-src="<?php echo UPLOAD_URL . 'slips/' . $pay['pay_slip']; ?>">ดูสลิป</button>
                    </td>
                    <td>
                        <?php echo getPayStatusBadge($pay['pay_status']); ?><br>
                        <span style="font-size:0.7rem;color:var(--gray-500);">Order: <?php echo $pay['o_status']; ?></span>
                    </td>
                    <td>
                        <form method="POST" style="display:flex;flex-direction:column;gap:4px;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="pay_id" value="<?php echo $pay['pay_id']; ?>">
                            <input type="hidden" name="o_id" value="<?php echo $pay['o_id']; ?>">
                            
                            <select name="pay_status" class="form-control" style="padding:2px 4px;font-size:0.75rem;">
                                <option value="pending" <?php echo $pay['pay_status'] === 'pending' ? 'selected' : ''; ?>>รอตรวจสอบ</option>
                                <option value="approved" <?php echo $pay['pay_status'] === 'approved' ? 'selected' : ''; ?>>อนุมัติ (Auto Confirm Order)</option>
                                <option value="rejected" <?php echo $pay['pay_status'] === 'rejected' ? 'selected' : ''; ?>>ปฏิเสธ (สลิปมีปัญหา)</option>
                            </select>
                            
                            <input type="text" name="admin_note" class="form-control" placeholder="หมายเหตุ (ถ้ามี)" value="<?php echo sanitize($pay['admin_note'] ?? ''); ?>" style="padding:2px 4px;font-size:0.75rem;">
                            
                            <button type="submit" class="btn btn-sm btn-primary" style="padding:2px 8px;">บันทึก</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Slip -->
<div id="slipModal" class="modal-overlay">
    <div class="modal-box" style="text-align:center;">
        <button class="modal-close">&times;</button>
        <h3>หลักฐานการโอนเงิน</h3>
        <img id="slipImage" src="" class="slip-preview" alt="Slip Preview">
    </div>
</div>
