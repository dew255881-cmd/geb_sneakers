<?php
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง';
    } else {
        if ($_POST['action'] === 'status_update') {
            $o_id = (int)$_POST['o_id'];
            $new_status = $_POST['o_status'];
            $admin_note = sanitize($_POST['admin_note'] ?? '');
            
            // Get current status to prevent editing done/cancelled orders
            $stmtCheckCurrent = $conn->prepare("SELECT o_status FROM tb_orders WHERE o_id = ?");
            $stmtCheckCurrent->bind_param("i", $o_id);
            $stmtCheckCurrent->execute();
            $currentStatusStr = $stmtCheckCurrent->get_result()->fetch_assoc()['o_status'] ?? '';
            
            // Check if status valid
            $validStatuses = ['pending', 'confirmed', 'shipped', 'done', 'cancelled'];
            if (!in_array($new_status, $validStatuses)) {
                $error = 'สถานะไม่ถูกต้อง';
            } elseif ($currentStatusStr === 'done' || $currentStatusStr === 'cancelled') {
                $error = 'ออร์เดอร์ที่สำเร็จหรือยกเลิกไปแล้ว ไม่สามารถแก้ไขได้';
            } elseif ($new_status === 'cancelled' && empty(trim($admin_note))) {
                $error = 'กรุณาระบุหมายเหตุสาเหตุการยกเลิกออร์เดอร์';
            } else {
                // If moving to confirmed/shipped/done, must have approved payment
                if (in_array($new_status, ['confirmed', 'shipped', 'done'])) {
                    $stmtCheckPay = $conn->prepare("SELECT pay_status FROM tb_payments WHERE o_id = ? ORDER BY pay_id DESC LIMIT 1");
                    $stmtCheckPay->bind_param("i", $o_id);
                    $stmtCheckPay->execute();
                    $payStatus = $stmtCheckPay->get_result()->fetch_assoc()['pay_status'] ?? 'none';
                    if ($payStatus !== 'approved') {
                        $error = 'ไม่สามารถเปลี่ยนสถานะเป็น ' . $new_status . ' ได้ เนื่องจากยังไม่ได้ตรวจสอบและอนุมัติสลิปโอนเงิน';
                    }
                }
                
                if (empty($error)) {
                    // If cancelling, restore stock
                    if ($new_status === 'cancelled') {
                        $stmtCheck = $conn->prepare("SELECT o_status FROM tb_orders WHERE o_id = ?");
                        $stmtCheck->bind_param("i", $o_id);
                        $stmtCheck->execute();
                        $resultCheck = $stmtCheck->get_result();
                        $oldStatus = $resultCheck->fetch_assoc()['o_status'] ?? '';
                        
                        if ($oldStatus !== 'cancelled') {
                            $conn->begin_transaction();
                            try {
                                $cancelStatus = 'cancelled';
                                $stmtUpdate = $conn->prepare("UPDATE tb_orders SET o_status = ?, admin_note = ? WHERE o_id = ?");
                                $stmtUpdate->bind_param("ssi", $cancelStatus, $admin_note, $o_id);
                                $stmtUpdate->execute();
                                
                                $stmtDetails = $conn->prepare("SELECT p_id, color_id, size_number, qty FROM tb_order_details WHERE o_id = ?");
                                $stmtDetails->bind_param("i", $o_id);
                                $stmtDetails->execute();
                                $resultDetails = $stmtDetails->get_result();
                                $items = $resultDetails->fetch_all(MYSQLI_ASSOC);
                                
                                $stmtRestoreStock = $conn->prepare("UPDATE tb_stock SET qty = qty + ? WHERE p_id = ? AND (color_id = ? OR (color_id IS NULL AND ? IS NULL)) AND size_number = ?");
                                foreach ($items as $item) {
                                    $c_id = ($item['color_id'] > 0) ? (int)$item['color_id'] : null;
                                    $stmtRestoreStock->bind_param("iiiis", $item['qty'], $item['p_id'], $c_id, $c_id, $item['size_number']);
                                    $stmtRestoreStock->execute();
                                }
                                
                                $conn->commit();
                                $success = 'ยกเลิกออร์เดอร์และคืนสต็อกสำเร็จ';
                            } catch (Exception $e) {
                                $conn->rollback();
                                $error = 'เกิดข้อผิดพลาดในการยกเลิก: ' . $e->getMessage();
                            }
                        }
                    } else {
                        $stmt = $conn->prepare("UPDATE tb_orders SET o_status = ? WHERE o_id = ?");
                        $stmt->bind_param("si", $new_status, $o_id);
                        $stmt->execute();
                        $success = 'อัปเดตสถานะเปลี่ยนเป็น ' . $new_status . ' สำเร็จ';
                    }
                } // end if empty error
            } // end if valid status
        } elseif ($_POST['action'] === 'verify_payment') {
            $pay_id = (int)$_POST['pay_id'];
            $o_id = (int)$_POST['o_id'];
            $new_pay_status = $_POST['pay_status'];
            $admin_note = sanitize($_POST['admin_note'] ?? '');
            
            if ($new_pay_status === 'rejected' && empty(trim($admin_note))) {
                $error = 'กรุณาระบุหมายเหตุสาเหตุที่ปฏิเสธสลิป เพื่อให้ลูกค้าทำการแก้ไข';
            } elseif (in_array($new_pay_status, ['approved', 'rejected'])) {
                $conn->begin_transaction();
                try {
                    $stmtUpdatePay = $conn->prepare("UPDATE tb_payments SET pay_status = ?, admin_note = ? WHERE pay_id = ?");
                    $stmtUpdatePay->bind_param("ssi", $new_pay_status, $admin_note, $pay_id);
                    $stmtUpdatePay->execute();
                    
                    if ($new_pay_status === 'approved') {
                        $stmtUpdateOrder = $conn->prepare("UPDATE tb_orders SET o_status = 'confirmed' WHERE o_id = ? AND o_status = 'pending'");
                        $stmtUpdateOrder->bind_param("i", $o_id);
                        $stmtUpdateOrder->execute();
                    }
                    
                    $conn->commit();
                    $success = 'ตรวจสอบและอัปเดตสลิปโอนเงินเรียบร้อยแล้ว';
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = 'เกิดข้อผิดพลาดในการตรวจสอบสลิป: ' . $e->getMessage();
                }
            } else {
                $error = 'คำขอสถานะสลิปไม่ถูกต้อง';
            }
        }
    }
}

$resultOrders = $conn->query("SELECT o.*, u.u_fullname as user_name FROM tb_orders o LEFT JOIN tb_users u ON o.u_id = u.u_id ORDER BY o.o_id DESC");
$orders = $resultOrders->fetch_all(MYSQLI_ASSOC);

function getOrderItems($conn, $orderId) {
    $stmt = $conn->prepare("SELECT d.*, p.p_name, c.color_name FROM tb_order_details d JOIN tb_products p ON d.p_id = p.p_id LEFT JOIN tb_product_colors c ON d.color_id = c.color_id WHERE d.o_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>

<style>
.order-row {
    transition: background-color 0.2s ease, transform 0.2s ease;
}
.order-row:hover {
    background-color: #fafafa;
}
.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    outline: none;
}
.status-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
}
.status-pending { background-color: #fef3c7; color: #d97706; } /* Yellow */
.status-confirmed { background-color: #dbeafe; color: #2563eb; } /* Blue */
.status-shipped { background-color: #f3e8ff; color: #9333ea; } /* Purple */
.status-done { background-color: #dcfce3; color: #16a34a; } /* Green */
.status-cancelled { background-color: #fee2e2; color: #dc2626; } /* Red */

.slip-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background-color: var(--gray-100);
    color: var(--gray-700);
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    border: none;
}
.slip-icon-btn:hover {
    background-color: var(--gray-200);
    color: var(--black);
    transform: translateY(-2px);
}
.slip-icon-missing {
    color: var(--error);
    background-color: #fee2e2;
    cursor: default;
}
.slip-icon-missing:hover {
    transform: none;
    background-color: #fee2e2;
    color: var(--error);
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h1 class="admin-page-title" style="margin: 0;">รายการคำสั่งซื้อ</h1>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

<div class="admin-card">
    <div class="admin-card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>วันที่ / ลูกค้า</th>
                    <th>สินค้า</th>
                    <th>ยอดรวม</th>
                    <th style="text-align:center;">สลิปโอนเงิน</th>
                    <th style="text-align:center;">สถานะ (คลิกเพื่อเปลี่ยน)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <?php $items = getOrderItems($conn, $order['o_id']); ?>
                <tr class="order-row">
                    <td><strong>#<?php echo str_pad($order['o_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                    <td style="text-align: left; line-height: 1.6;">
                        <span style="font-size: 0.75rem; color: var(--gray-500);"><i class="far fa-calendar-alt" style="margin-right:4px;"></i><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span><br>
                        <span style="font-size: 0.85rem;">ผู้สั่ง: <strong><?php echo sanitize($order['user_name'] ?? $order['o_fullname']); ?></strong></span><br>
                        <span style="font-size:0.8rem;color:var(--gray-600);"><i class="fas fa-phone-alt" style="margin-right:4px; font-size:0.7rem;"></i><?php echo sanitize($order['o_phone']); ?></span>
                    </td>
                    <td style="text-align: left;">
                        <div style="max-width: 260px; font-size: 0.85rem; line-height: 1.5;">
                            <?php foreach ($items as $item): ?>
                                <div style="margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed var(--gray-200);">
                                    <strong style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo sanitize($item['p_name']); ?>"><?php echo sanitize($item['p_name']); ?></strong>
                                    <span style="color:var(--gray-500); font-size: 0.75rem;">
                                        <?php if (!empty($item['color_name'])): ?>
                                            สี: <?php echo sanitize($item['color_name']); ?> | 
                                        <?php endif; ?>
                                        ไซส์: <?php echo $item['size_number']; ?> | <span style="color:var(--black); font-weight:600;">x<?php echo $item['qty']; ?></span>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td style="white-space: nowrap;"><strong><?php echo formatPrice($order['o_total']); ?></strong></td>
                    <td style="text-align: center;">
                        <?php 
                        $stmtPay = $conn->prepare("SELECT pay_id, pay_amount, pay_status, pay_slip FROM tb_payments WHERE o_id = ? ORDER BY pay_id DESC LIMIT 1");
                        $stmtPay->bind_param("i", $order['o_id']);
                        $stmtPay->execute();
                        $resultPay = $stmtPay->get_result();
                        $payment = $resultPay->fetch_assoc();
                        $payStatus = $payment['pay_status'] ?? 'none';
                        ?>
                        <?php if ($payment && !empty($payment['pay_slip'])): ?>
                            <button type="button" class="slip-icon-btn view-slip-btn" 
                                data-src="<?php echo UPLOAD_URL . 'slips/' . $payment['pay_slip']; ?>" 
                                data-payid="<?php echo $payment['pay_id']; ?>"
                                data-oid="<?php echo $order['o_id']; ?>"
                                data-payamount="<?php echo $payment['pay_amount']; ?>"
                                data-ototal="<?php echo $order['o_total']; ?>"
                                data-status="<?php echo $payment['pay_status']; ?>"
                                title="สถานะชำระ: <?php echo $payment['pay_status']; ?>">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </button>
                        <?php elseif ($order['o_status'] === 'pending'): ?>
                            <div class="slip-icon-btn slip-icon-missing" title="ยังไม่แจ้งโอน">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        <?php else: ?>
                            <span style="color:var(--gray-400);">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php 
                        $btnClass = 'status-pending';
                        $statusText = 'รอดำเนินการ';
                        if ($order['o_status'] === 'confirmed') { $btnClass = 'status-confirmed'; $statusText = 'เตรียมจัดส่ง'; }
                        elseif ($order['o_status'] === 'shipped') { $btnClass = 'status-shipped'; $statusText = 'จัดส่งแล้ว'; }
                        elseif ($order['o_status'] === 'done') { $btnClass = 'status-done'; $statusText = 'สำเร็จ'; }
                        elseif ($order['o_status'] === 'cancelled') { $btnClass = 'status-cancelled'; $statusText = 'ยกเลิกแล้ว'; }
                        ?>
                        <?php if ($order['o_status'] !== 'cancelled' && $order['o_status'] !== 'done'): ?>
                            <button type="button" class="status-pill <?php echo $btnClass; ?>" onclick="openStatusModal(<?php echo $order['o_id']; ?>, '<?php echo $order['o_status']; ?>', '<?php echo $payStatus; ?>')">
                                <?php echo $statusText; ?>
                            </button>
                        <?php elseif ($order['o_status'] === 'done'): ?>
                            <div class="status-pill status-done" title="ออร์เดอร์นี้เสร็จสมบูรณ์แล้ว ไม่สามารถแก้ไขได้" style="cursor: default;">สำเร็จแล้ว</div>
                        <?php else: ?>
                            <div class="status-pill status-cancelled" title="ออร์เดอร์นี้ถูกยกเลิกแล้ว ไม่สามารถแก้ไขได้" style="cursor: default;">ยกเลิกแล้ว</div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Status -->
<div id="statusModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 400px;">
        <button type="button" class="modal-close" onclick="document.getElementById('statusModal').classList.remove('open')">&times;</button>
        <h3 style="margin-bottom: 20px;">อัปเดตสถานะคำสั่งซื้อ</h3>
        <form id="statusForm" method="POST" onsubmit="return confirmModalStatusChange()">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="status_update">
            <input type="hidden" name="o_id" id="modal_o_id" value="">
            <input type="hidden" id="modal_current_status" value="">
            
            <div class="form-group">
                <label class="form-label">เลือกสถานะใหม่:</label>
                <select name="o_status" id="modal_o_status" class="form-control" style="padding: 10px; font-size: 0.9rem;" onchange="toggleCancelNote(this.value)">
                    <option value="pending">รอดำเนินการ (Pending)</option>
                    <option value="confirmed">เตรียมจัดส่ง (Confirmed)</option>
                    <option value="shipped">จัดส่งแล้ว (Shipped)</option>
                    <option value="done">สำเร็จ (Done)</option>
                    <option value="cancelled">ยกเลิกและคืนสต็อก (Cancelled)</option>
                </select>
            </div>
            
            <div class="form-group" id="cancelNoteGroup" style="display:none; margin-top: 16px; text-align: left; flex-direction: column; align-items: stretch; gap: 8px;">
                <label class="form-label" style="font-weight: 600;">หมายเหตุการยกเลิก <span style="color:var(--error);">*</span></label>
                <textarea name="admin_note" id="cancel_admin_note" class="form-control" rows="3" placeholder="ระบุสาเหตุที่ยกเลิกออร์เดอร์ เพื่อแจ้งให้ลูกค้าทราบ..." style="width: 100%; box-sizing: border-box; resize: vertical; padding: 10px; border-radius: 6px; border: 1px solid var(--gray-300); font-family: inherit; font-size: 0.9rem;"></textarea>
            </div>
            
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top: 24px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('statusModal').classList.remove('open')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">บันทึกสถานะ</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Slip -->
<div id="slipModal" class="modal-overlay">
    <div class="modal-box" style="text-align:center; max-width: 500px;">
        <button type="button" class="modal-close" onclick="document.getElementById('slipModal').classList.remove('open')">&times;</button>
        <h3 style="margin-bottom: 16px; margin-top: 0;">หลักฐานการโอนเงิน <span id="slipStatusBadge" style="font-size: 0.8rem; padding: 4px 8px; border-radius: 4px; vertical-align: middle;"></span></h3>
        <img id="slipImage" src="" class="slip-preview" alt="Slip Preview" style="max-width: 100%; border-radius: 8px; max-height: 50vh; object-fit: contain; margin-bottom: 16px; border: 1px solid var(--gray-200);">
        
        <div id="slipWarningBox" style="display:none; background-color: #fee2e2; border-left: 4px solid #dc2626; padding: 12px; margin-bottom: 20px; text-align: left; border-radius: 4px;">
            <strong style="color: #991b1b;"><i class="fas fa-exclamation-triangle"></i> ⚠️ ยอดโอนไม่ครบ</strong>
            <p id="slipWarningText" style="margin: 4px 0 0 0; font-size: 0.9rem; color: #7f1d1d;"></p>
        </div>

        <div id="slipVerificationBox">
            <form id="slipForm" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="verify_payment">
                <input type="hidden" name="pay_id" id="slip_pay_id" value="">
                <input type="hidden" name="o_id" id="slip_o_id" value="">
                <input type="hidden" name="pay_status" id="slip_pay_status" value="">
                <input type="hidden" id="slip_is_short" value="0">
                
                <div class="form-group" style="text-align: left; margin-bottom: 20px; display: flex; flex-direction: column; gap: 8px;">
                    <label class="form-label" style="font-size:0.85rem; font-weight: 600; margin:0;">หมายเหตุถึงลูกค้า <span style="color:var(--gray-500); font-weight: normal; font-size:0.75rem;">(บังคับใส่เมื่อกดปฏิเสธ)</span></label>
                    <textarea name="admin_note" id="slip_admin_note" class="form-control" rows="2" placeholder="เช่น รูปสลิปไม่ชัดเจน, ยอดโอนไม่ตรง..." style="width: 100%; box-sizing: border-box; resize: vertical; padding: 10px; border-radius: 6px; border: 1px solid var(--gray-300); font-family: inherit; font-size: 0.9rem;"></textarea>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="button" class="btn" onclick="submitSlipForm('rejected')" style="background-color: #ef4444; color: white; flex: 1; border: none; padding: 10px; border-radius: 8px; transition: 0.2s; cursor: pointer;"><i class="fas fa-times"></i> ปฏิเสธสลิป</button>
                    <button type="button" class="btn" onclick="submitSlipForm('approved')" style="background-color: #10b981; color: white; flex: 1; border: none; padding: 10px; border-radius: 8px; transition: 0.2s; cursor: pointer;"><i class="fas fa-check"></i> อนุมัติ (เตรียมจัดส่ง)</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openStatusModal(o_id, currentStatus, payStatus) {
    document.getElementById('modal_o_id').value = o_id;
    document.getElementById('modal_current_status').value = currentStatus;
    document.getElementById('modal_o_status').value = currentStatus;
    
    let confirmedOption = document.querySelector('#modal_o_status option[value="confirmed"]');
    let shippedOption = document.querySelector('#modal_o_status option[value="shipped"]');
    let doneOption = document.querySelector('#modal_o_status option[value="done"]');
    
    if (payStatus !== 'approved' && currentStatus === 'pending') {
        confirmedOption.disabled = true;
        confirmedOption.text = 'เตรียมจัดส่ง (❌ อนุมัติสลิปก่อน)';
        shippedOption.disabled = true;
        doneOption.disabled = true;
    } else {
        confirmedOption.disabled = false;
        confirmedOption.text = 'เตรียมจัดส่ง (Confirmed)';
        shippedOption.disabled = false;
        doneOption.disabled = false;
    }
    
    document.getElementById('statusModal').classList.add('open');
    toggleCancelNote(currentStatus);
}

function toggleCancelNote(status) {
    var noteGroup = document.getElementById('cancelNoteGroup');
    var noteInput = document.getElementById('cancel_admin_note');
    if (status === 'cancelled') {
        noteGroup.style.display = 'flex';
        noteInput.required = true;
    } else {
        noteGroup.style.display = 'none';
        noteInput.required = false;
        noteInput.value = '';
    }
}

function confirmModalStatusChange() {
    var currentStatus = document.getElementById('modal_current_status').value;
    var newStatus = document.getElementById('modal_o_status').value;
    var adminNote = document.getElementById('cancel_admin_note').value.trim();
    
    if (newStatus === currentStatus) {
        document.getElementById('statusModal').classList.remove('open');
        return false;
    }
    
    if (newStatus === 'cancelled') {
        return confirm('การยกเลิกออร์เดอร์จะเป็นการ "คืนสินค้าเข้าสต็อก" ยืนยันใช่หรือไม่?');
    }
    
    if (newStatus === 'done') {
        return confirm('⚠️ ยืนยันว่าจัดส่งและลูกค้าได้รับสินค้าสำเร็จแล้ว?\n(หากยืนยันแล้วจะไม่สามารถแก้ไขสถานะหรือยกเลิกได้อีก)');
    }
    
    return true; // No confirm on simple change, just seamless ux
}

function submitSlipForm(action) {
    const isShort = document.getElementById('slip_is_short').value === '1';
    const adminNote = document.getElementById('slip_admin_note').value.trim();
    
    if (action === 'approved') {
        if (isShort) {
            if (!confirm('⚠️ ยอดโอนไม่ครบตามยอดออร์เดอร์\nยืนยันที่จะ *อนุมัติสลิป* ทั้งที่ยอดไม่ครบใช่หรือไม่?')) return;
        } else {
            if (!confirm('ยืนยันอนุมัติสลิปโอนเงินนี้? สถานะออร์เดอร์จะถูกเปลี่ยนเป็นเตรียมจัดส่งทันที')) return;
        }
    } else if (action === 'rejected') {
        if (adminNote === '') {
            alert('กรุณาระบุหมายเหตุสาเหตุที่ปฏิเสธสลิป เพื่อแจ้งให้ลูกค้าแก้ไข');
            document.getElementById('slip_admin_note').focus();
            return;
        }
        if (!confirm('ปฏิเสธสลิปโอนเงินนี้? (ระบบจะแจ้งให้ลูกค้าอัปโหลดสลิปใหม่)')) return;
    }
    
    document.getElementById('slip_pay_status').value = action;
    document.getElementById('slipForm').submit();
}

document.querySelectorAll('.view-slip-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        var src = this.getAttribute('data-src');
        if (src) {
            document.getElementById('slipImage').src = src;
            
            // Populate hidden inputs
            document.getElementById('slip_pay_id').value = this.getAttribute('data-payid');
            document.getElementById('slip_o_id').value = this.getAttribute('data-oid');
            
            // Calculate shortage
            const payAmount = parseFloat(this.getAttribute('data-payamount'));
            const oTotal = parseFloat(this.getAttribute('data-ototal'));
            const payStatus = this.getAttribute('data-status');
            
            // Format currency helper
            const formatTHB = (n) => '฿' + n.toLocaleString('en-US', {minimumFractionDigits: 2});
            
            const warningBox = document.getElementById('slipWarningBox');
            const warningText = document.getElementById('slipWarningText');
            const isShortInput = document.getElementById('slip_is_short');
            
            if (payAmount < oTotal) {
                warningBox.style.display = 'block';
                const shortAmt = oTotal - payAmount;
                warningText.innerHTML = `โอนมา <strong>${formatTHB(payAmount)}</strong> | ยอดจริง <strong>${formatTHB(oTotal)}</strong> | ຂาด <strong>${formatTHB(shortAmt)}</strong>`;
                isShortInput.value = '1';
            } else {
                warningBox.style.display = 'none';
                isShortInput.value = '0';
            }
            
            // Status badge styling
            const badge = document.getElementById('slipStatusBadge');
            if (payStatus === 'pending') {
                badge.textContent = 'รอตรวจสอบ';
                badge.style.backgroundColor = '#fef3c7'; badge.style.color = '#d97706';
                document.getElementById('slipVerificationBox').style.display = 'block';
            } else if (payStatus === 'approved') {
                badge.textContent = '✅ อนุมัติแล้ว';
                badge.style.backgroundColor = '#dcfce3'; badge.style.color = '#16a34a';
                document.getElementById('slipVerificationBox').style.display = 'none'; // Hide buttons if already approved
            } else if (payStatus === 'rejected') {
                badge.textContent = '❌ ถูกปฏิเสธ';
                badge.style.backgroundColor = '#fee2e2'; badge.style.color = '#dc2626';
                document.getElementById('slipVerificationBox').style.display = 'block'; // Allow re-try
            }

            document.getElementById('slipModal').classList.add('open');
        }
    });
});
</script>
