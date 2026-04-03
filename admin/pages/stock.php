<?php
$pid = isset($_GET['pid']) ? (int)$_GET['pid'] : null;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง';
    } else {
        $action = $_POST['action'];
        $p_id = (int)($_POST['p_id'] ?? 0);
        
        if ($action === 'add') {
            $size_number = sanitize($_POST['size_number'] ?? '');
            $qty = (int)($_POST['qty'] ?? 0);
            $color_id = (int)($_POST['color_id'] ?? 0);

            // color_id is now optional for base products
            if (empty($size_number) || $qty < 0 || $p_id <= 0) {
                $error = 'กรุณากรอกขนาด และ จำนวนให้ครบถ้วน';
            } else {
                $final_color_id = ($color_id > 0) ? $color_id : null;
                $stmtCheck = $conn->prepare("SELECT COUNT(*) as count FROM tb_stock WHERE p_id = ? AND (color_id = ? OR (color_id IS NULL AND ? IS NULL)) AND size_number = ?");
                $stmtCheck->bind_param("iiis", $p_id, $final_color_id, $final_color_id, $size_number);
                $stmtCheck->execute();
                $resultCheck = $stmtCheck->get_result();
                if ($resultCheck->fetch_assoc()['count'] > 0) {
                    $error = 'สินค้านี้มีไซส์ ' . $size_number . ' ของตัวเลือกที่คุณเลือกอยู่ในระบบแล้ว กรุณาใช้วิธีแก้ไขจำนวนแทน';
                } else {
                    $stmt = $conn->prepare("INSERT INTO tb_stock (p_id, color_id, size_number, qty) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iisi", $p_id, $final_color_id, $size_number, $qty);
                    $stmt->execute();
                    $success = 'เพิ่มไซส์เรียบร้อย';
                    $pid = $p_id; 
                }
            }
        } elseif ($action === 'update_all') {
            $p_id = (int)($_POST['p_id'] ?? 0);
            $quantities = $_POST['qty_update'] ?? [];
            
            if (!empty($quantities) && is_array($quantities)) {
                $stmtUpdate = $conn->prepare("UPDATE tb_stock SET qty = ? WHERE s_id = ? AND p_id = ?");
                foreach ($quantities as $s_id => $new_qty) {
                    $qty_val = (int)$new_qty;
                    $sid_val = (int)$s_id;
                    $stmtUpdate->bind_param("iii", $qty_val, $sid_val, $p_id);
                    $stmtUpdate->execute();
                }
                $success = 'อัปเดตสต็อกเรียบร้อย';
                $pid = $p_id;
            }
        } elseif ($action === 'delete') {
            $s_id = (int)$_POST['s_id'];
            $p_id = (int)$_POST['p_id'];
            $stmt = $conn->prepare("DELETE FROM tb_stock WHERE s_id = ? AND p_id = ?");
            $stmt->bind_param("ii", $s_id, $p_id);
            $stmt->execute();
            $success = 'ลบไซส์ออกจากสต็อกเรียบร้อย';
            $pid = $p_id;
        }
    }
}

$colors = [];
if ($pid) {
    $stmtItem = $conn->prepare("SELECT p_name, p_img FROM tb_products WHERE p_id = ?");
    $stmtItem->bind_param("i", $pid);
    $stmtItem->execute();
    $resultItem = $stmtItem->get_result();
    $product = $resultItem->fetch_assoc();
    
    if (!$product) {
        $error = 'ไม่พบสินค้า';
        $pid = null;
    } else {
        // Get Colors for this product
        $stmtColors = $conn->prepare("SELECT color_id, color_name FROM tb_product_colors WHERE p_id = ?");
        $stmtColors->bind_param("i", $pid);
        $stmtColors->execute();
        $colors = $stmtColors->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get Stock grouped by Color
        $stmtStocks = $conn->prepare("
            SELECT s.*, c.color_name 
            FROM tb_stock s 
            LEFT JOIN tb_product_colors c ON s.color_id = c.color_id 
            WHERE s.p_id = ? 
            ORDER BY c.color_name ASC, CAST(s.size_number AS UNSIGNED) ASC
        ");
        $stmtStocks->bind_param("i", $pid);
        $stmtStocks->execute();
        $resultStocks = $stmtStocks->get_result();
        $stocks = $resultStocks->fetch_all(MYSQLI_ASSOC);
    }
}

$resultAllProducts = $conn->query("SELECT p_id, p_name FROM tb_products ORDER BY p_id DESC");
$allProducts = $resultAllProducts->fetch_all(MYSQLI_ASSOC);
?>

<style>
/* Hide the default number input spin buttons */
.qty-stepper input[type=number]::-webkit-inner-spin-button, 
.qty-stepper input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
}
.qty-stepper input[type=number] {
    -moz-appearance: textfield; /* Firefox */
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h1 class="admin-page-title" style="margin: 0;">จัดการสต็อก & ไซส์/สี</h1>
    <?php if ($pid): ?>
        <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')">
            <i class="fas fa-plus"></i> เพิ่มสต็อก (ไซส์/สี)
        </button>
    <?php endif; ?>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

<div class="admin-card">
    <div class="admin-card-body">
        <form method="GET" class="admin-form" style="max-width:100%;">
            <input type="hidden" name="page" value="stock">
            <div class="form-group" style="display:flex;gap:12px;align-items:flex-end;">
                <div style="flex:1;">
                    <label class="form-label">เลือกสินค้าเพื่อจัดการสต็อก</label>
                    <select name="pid" class="form-control" onchange="this.form.submit()">
                        <option value="">-- กรุณาเลือกสินค้า --</option>
                        <?php foreach ($allProducts as $p): ?>
                            <option value="<?php echo $p['p_id']; ?>" <?php echo ($pid == $p['p_id']) ? 'selected' : ''; ?>>
                                [ID: <?php echo $p['p_id']; ?>] <?php echo sanitize($p['p_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($pid && isset($product)): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <div style="display:flex;align-items:center;gap:16px;">
            <?php if ($product['p_img'] && $product['p_img'] !== 'no_image.png'): ?>
                <img src="<?php echo UPLOAD_URL . 'products/' . $product['p_img']; ?>" style="width:50px;height:50px;object-fit:cover;border:1px solid #ddd;">
            <?php else: ?>
                <div style="width:50px;height:50px;background:#eee;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">&#128095;</div>
            <?php endif; ?>
            <h3>สต็อกของ: <?php echo sanitize($product['p_name']); ?></h3>
        </div>
    </div>
    
    <div class="admin-card-body" style="padding:0;">
        <?php if (empty($stocks)): ?>
            <div style="padding:40px;text-align:center;color:var(--gray-500);">
                <i class="fas fa-box-open" style="font-size:3rem;margin-bottom:16px;opacity:0.3;"></i><br>
                สินค้านี้ยังไม่มีไซส์ในสต็อก กรุณาเพิ่มไซส์และสี
            </div>
        <?php else: ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_all">
                <input type="hidden" name="p_id" value="<?php echo $pid; ?>">
                
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>สี (Color)</th>
                                <th>ไซส์ (Size)</th>
                                <th width="150">จำนวนคงเหลือ (Qty)</th>
                                <th>อัปเดตล่าสุด</th>
                                <th style="width:100px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stocks as $stock): ?>
                            <tr>
                                <td><span class="badge badge-pending" style="background:#444; color:#fff; padding:6px 12px; font-weight:normal; border-radius:4px;"><?php echo sanitize($stock['color_name'] ?? 'ไม่มีสี'); ?></span></td>
                                <td><strong><?php echo sanitize($stock['size_number']); ?></strong></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div class="qty-stepper" style="display:inline-flex; align-items:center; border: 1px solid var(--gray-300); border-radius: 8px; overflow: hidden; height: 36px; background: var(--white);">
                                            <button type="button" style="background:var(--gray-50); border:none; border-right: 1px solid var(--gray-200); padding: 0 12px; height: 100%; cursor:pointer; color: var(--gray-600); transition: 0.2s;" onmouseover="this.style.background='var(--gray-200)'" onmouseout="this.style.background='var(--gray-50)'" onclick="this.nextElementSibling.stepDown()">
                                                <i class="fas fa-minus" style="font-size: 0.7rem;"></i>
                                            </button>
                                            <input type="number" name="qty_update[<?php echo $stock['s_id']; ?>]" value="<?php echo (int)$stock['qty']; ?>" style="width:50px; text-align:center; border:none; box-shadow:none; font-weight: 600; font-size: 0.95rem; padding: 0; font-family: var(--font);" min="0">
                                            <button type="button" style="background:var(--gray-50); border:none; border-left: 1px solid var(--gray-200); padding: 0 12px; height: 100%; cursor:pointer; color: var(--gray-600); transition: 0.2s;" onmouseover="this.style.background='var(--gray-200)'" onmouseout="this.style.background='var(--gray-50)'" onclick="this.previousElementSibling.stepUp()">
                                                <i class="fas fa-plus" style="font-size: 0.7rem;"></i>
                                            </button>
                                        </div>
                                        <?php if ($stock['qty'] <= 2): ?>
                                            <span class="badge badge-cancelled" style="font-size:0.7rem; padding: 4px 8px;">ใกล้หมด</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="font-size:0.85rem; color:#666;"><?php echo date('d/m/Y H:i', strtotime($stock['updated_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteStock(<?php echo $stock['s_id']; ?>)" title="ลบ" style="width: 32px; padding: 0; justify-content: center;"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="padding:24px;border-top:1px solid var(--gray-200);text-align:right;">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> บันทึกจำนวนสต็อกทั้งหมด</button>
                </div>
            </form>
            
            <form id="deleteForm" method="POST" style="display:none;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="p_id" value="<?php echo $pid; ?>">
                <input type="hidden" name="s_id" id="delete_s_id" value="">
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <button class="modal-close" type="button" onclick="document.getElementById('addModal').classList.remove('open')">&times;</button>
        <h3>เพิ่มไซส์และสีใหม่ให้: <br><span style="font-size:0.9rem;color:#666;font-weight:normal;"><?php echo sanitize($product['p_name']); ?></span></h3>
        <form method="POST" class="admin-form mt-20" style="max-width:100%;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="p_id" value="<?php echo $pid; ?>">
            
            <div class="form-group">
                <label class="form-label">เลือกสี/ลวดลาย (เว้นไว้หากเป็นสินค้าสีเดียว)</label>
                <select name="color_id" class="form-control">
                    <option value="0">-- สีพื้นฐาน / ไม่มีระบุสี --</option>
                    <?php foreach ($colors as $c): ?>
                        <option value="<?php echo $c['color_id']; ?>"><?php echo sanitize($c['color_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#666;">หากแบรนด์นี้มีหลายสี แนะนำให้ <a href="?page=colors&pid=<?php echo $pid; ?>" target="_blank">สร้างข้อมูลสีก่อน</a> เพื่อให้ลูกค้าเลือกได้ถูกต้อง</small>
            </div>

            <div class="form-group">
                <label class="form-label">ไซส์ (เช่น 40, 40.5, 41, 8US) *</label>
                <input type="text" name="size_number" class="form-control" required placeholder="เช่น 42">
            </div>
            
            <div class="form-group">
                <label class="form-label">จำนวนตั้งต้น *</label>
                <input type="number" name="qty" class="form-control" required min="0" value="10">
            </div>
            
            <div class="form-group action-btns mt-20" style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addModal').classList.remove('open')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">บันทึกไซส์นี้</button>
            </div>
        </form>
    </div>
</div>

<script>
function deleteStock(s_id) {
    if (confirm('ยืนยันลบไซส์/สีนี้ออกจากสต็อก? (หากมีการสั่งซื้อไปแล้ว สินค้าในประวัติออร์เดอร์จะไม่หาย แต่ชิ้นนี้จะหายไปจากคลัง)')) {
        document.getElementById('delete_s_id').value = s_id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
<?php endif; ?>
