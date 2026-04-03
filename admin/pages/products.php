<?php
$error = '';
$success = '';

$resultBrands = $conn->query("SELECT * FROM tb_brands ORDER BY b_name ASC");
$brands = $resultBrands->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง';
    } else {
        $action = $_POST['action'];
        $p_name = sanitize($_POST['p_name'] ?? '');
        $b_id = !empty($_POST['b_id']) ? (int)$_POST['b_id'] : null;
        $p_price = (float)($_POST['p_price'] ?? 0);
        $p_detail = sanitize($_POST['p_detail'] ?? '');
        $p_status = $_POST['p_status'] ?? 'active';
        
        if ($action === 'add' || $action === 'edit') {
            if (empty($p_name) || $p_price <= 0) {
                $error = 'กรุณากรอกชื่อสินค้าและราคาให้ถูกต้อง';
            } else {
                $imgName = false;
                if (isset($_FILES['p_img']) && $_FILES['p_img']['error'] === UPLOAD_ERR_OK) {
                    $imgName = uploadImage($_FILES['p_img'], 'products');
                    if (!$imgName) $error = 'อัปโหลดรูปภาพหลักไม่สำเร็จ รองรับ JPG, PNG, WEBP';
                }
                
                if (empty($error)) {
                    if ($action === 'add') {
                        $p_img = $imgName ? $imgName : 'no_image.png';
                        $stmt = $conn->prepare("INSERT INTO tb_products (p_name, b_id, p_price, p_detail, p_img, p_status) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sidsss", $p_name, $b_id, $p_price, $p_detail, $p_img, $p_status);
                        $stmt->execute();
                        $p_id = $conn->insert_id;
                        $success = 'เพิ่มสินค้าใหม่เรียบร้อย';
                    } else {
                        $p_id = (int)$_POST['p_id'];
                        if ($imgName) {
                            $stmt = $conn->prepare("UPDATE tb_products SET p_name=?, b_id=?, p_price=?, p_detail=?, p_img=?, p_status=? WHERE p_id=?");
                            $stmt->bind_param("sidsssi", $p_name, $b_id, $p_price, $p_detail, $imgName, $p_status, $p_id);
                            $stmt->execute();
                        } else {
                            $stmt = $conn->prepare("UPDATE tb_products SET p_name=?, b_id=?, p_price=?, p_detail=?, p_status=? WHERE p_id=?");
                            $stmt->bind_param("sidssi", $p_name, $b_id, $p_price, $p_detail, $p_status, $p_id);
                            $stmt->execute();
                        }
                        $success = 'อัปเดตสินค้าเรียบร้อย';
                    }
                    
                    // Handle Base Product Gallery Image Upload
                    if (isset($_FILES['gallery_imgs']) && count($_FILES['gallery_imgs']['name']) > 0 && !empty($_FILES['gallery_imgs']['name'][0])) {
                        $galleryCount = count($_FILES['gallery_imgs']['name']);
                        $stmtGallery = $conn->prepare("INSERT INTO tb_product_gallery (p_id, color_id, g_img) VALUES (?, NULL, ?)");
                        
                        for ($i = 0; $i < $galleryCount; $i++) {
                            if ($_FILES['gallery_imgs']['error'][$i] === UPLOAD_ERR_OK) {
                                $mockFile = [
                                    'name' => $_FILES['gallery_imgs']['name'][$i],
                                    'type' => $_FILES['gallery_imgs']['type'][$i],
                                    'tmp_name' => $_FILES['gallery_imgs']['tmp_name'][$i],
                                    'error' => $_FILES['gallery_imgs']['error'][$i],
                                    'size' => $_FILES['gallery_imgs']['size'][$i]
                                ];
                                
                                $gUploaded = uploadImage($mockFile, 'products');
                                if ($gUploaded) {
                                    $stmtGallery->bind_param("is", $p_id, $gUploaded);
                                    $stmtGallery->execute();
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($action === 'delete') {
            $p_id = (int)$_POST['p_id'];
            $stmtCheck = $conn->prepare("SELECT COUNT(*) as count FROM tb_order_details WHERE p_id = ?");
            $stmtCheck->bind_param("i", $p_id);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            if ($resultCheck->fetch_assoc()['count'] > 0) {
                $error = 'ไม่สามารถลบสินค้าได้ เนื่องจากมีประวัติคำสั่งซื้อ (แนะนำให้เปลี่ยนสถานะเป็น Inactive แทน)';
            } else {
                $stmt = $conn->prepare("DELETE FROM tb_products WHERE p_id = ?");
                $stmt->bind_param("i", $p_id);
                $stmt->execute();
                
                $stmtGalDel = $conn->prepare("DELETE FROM tb_product_gallery WHERE p_id = ? AND color_id IS NULL");
                $stmtGalDel->bind_param("i", $p_id);
                $stmtGalDel->execute();
                
                $success = 'ลบสินค้าและข้อมูลเรียบร้อย';
            }
        } elseif ($action === 'delete_base_gal') {
            $g_id = (int)$_POST['g_id'];
            $p_id_edit = (int)$_POST['p_id'];
            $stmtGal = $conn->prepare("SELECT g_img FROM tb_product_gallery WHERE g_id = ? AND color_id IS NULL");
            $stmtGal->bind_param("i", $g_id);
            $stmtGal->execute();
            $resGal = $stmtGal->get_result()->fetch_assoc();
            if ($resGal) {
                $path = UPLOAD_PATH . 'products/' . $resGal['g_img'];
                if (file_exists($path)) @unlink($path);
                
                $stmtDelGal = $conn->prepare("DELETE FROM tb_product_gallery WHERE g_id = ? AND color_id IS NULL");
                $stmtDelGal->bind_param("i", $g_id);
                $stmtDelGal->execute();
                $success = 'ลบภาพแกลลอรี่ออกเรียบร้อย';
                $_GET['edit'] = $p_id_edit; // Stay on edit page
            }
        }
    }
}

$resultProducts = $conn->query("SELECT p.*, b.b_name FROM tb_products p LEFT JOIN tb_brands b ON p.b_id = b.b_id ORDER BY p.p_id DESC");
$products = $resultProducts->fetch_all(MYSQLI_ASSOC);

$isEdit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editProduct = null;
$baseGalleries = [];
if ($isEdit > 0) {
    $stmtEdit = $conn->prepare("SELECT * FROM tb_products WHERE p_id = ?");
    $stmtEdit->bind_param("i", $isEdit);
    $stmtEdit->execute();
    $resultEdit = $stmtEdit->get_result();
    $editProduct = $resultEdit->fetch_assoc();
    
    // Fetch Base Galleries
    $stmtBaseGal = $conn->prepare("SELECT * FROM tb_product_gallery WHERE p_id = ? AND color_id IS NULL ORDER BY g_id ASC");
    $stmtBaseGal->bind_param("i", $isEdit);
    $stmtBaseGal->execute();
    $baseGalleries = $stmtBaseGal->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h1 class="admin-page-title" style="margin: 0;"><?php echo $isEdit ? 'แก้ไขสินค้า' : 'จัดการสินค้า'; ?></h1>
    <?php if ($isEdit): ?>
        <a href="?page=products" class="btn btn-secondary">ย้อนกลับ</a>
    <?php endif; ?>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php if (!$isEdit && $_POST['action'] !== 'delete_base_gal'): ?>
        <script>setTimeout(()=>window.location='?page=products', 1500);</script>
    <?php endif; ?>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><?php echo $isEdit ? 'ฟอร์มแก้ไขสินค้า' : 'ฟอร์มเพิ่มสินค้าใหม่'; ?></h3>
    </div>
    <div class="admin-card-body">
        <form method="POST" enctype="multipart/form-data" class="admin-form" id="productForm">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="<?php echo $isEdit ? 'edit' : 'add'; ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="p_id" value="<?php echo $editProduct['p_id']; ?>">
            <?php endif; ?>
            
            <div class="admin-row">
                <!-- Main Content Column -->
                <div class="admin-col-main">
                    <div class="form-group">
                        <label class="form-label">ชื่อสินค้า *</label>
                        <input type="text" name="p_name" class="form-control" required value="<?php echo $editProduct ? sanitize($editProduct['p_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">แบรนด์</label>
                        <select name="b_id" class="form-control">
                            <option value="">-- ไม่ระบุ --</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['b_id']; ?>" <?php echo ($editProduct && $editProduct['b_id'] == $brand['b_id']) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($brand['b_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">ราคา (฿) *</label>
                        <input type="number" step="0.01" name="p_price" class="form-control" required value="<?php echo $editProduct ? $editProduct['p_price'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">รายละเอียด</label>
                        <textarea name="p_detail" class="form-control" style="min-height: 156px;"><?php echo $editProduct ? sanitize($editProduct['p_detail']) : ''; ?></textarea>
                    </div>
                </div>
                
                <!-- Sidebar Column -->
                <div class="admin-col-side">
                    <div class="form-group">
                        <label class="form-label">สถานะสินค้า</label>
                        <select name="p_status" class="form-control">
                            <option value="active" <?php echo ($editProduct && $editProduct['p_status'] == 'active') ? 'selected' : ''; ?>>Active (แสดง)</option>
                            <option value="inactive" <?php echo ($editProduct && $editProduct['p_status'] == 'inactive') ? 'selected' : ''; ?>>Inactive (ซ่อน)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">รูปภาพสินค้าหลัก</label>
                        <div style="background: var(--gray-50); border: 2px dashed var(--gray-200); padding: 20px; text-align: center; margin-bottom: 12px; border-radius:8px;">
                            <?php if ($editProduct && $editProduct['p_img'] && $editProduct['p_img'] !== 'no_image.png'): ?>
                                <img src="<?php echo UPLOAD_URL . 'products/' . $editProduct['p_img']; ?>" style="max-height: 150px; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto;">
                            <?php else: ?>
                                <i class="fas fa-image" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 12px; display: block;"></i>
                                <span style="font-size: 0.8rem; color: var(--gray-500);">ยังไม่มีรูปภาพหลัก</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="p_img" accept="image/jpeg, image/png, image/webp" class="form-control" style="padding:4px;">
                        <small style="color:var(--gray-500);display:block;margin-top:8px; line-height: 1.4;">
                            รองรับ JPG, PNG, WEBP <?php echo $isEdit ? '(เว้นไว้ถ้าไม่เปลี่ยน)' : ''; ?>
                        </small>
                    </div>

                    <div class="form-group" style="padding:16px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; margin-top:20px;">
                        <label class="form-label"><i class="fas fa-images"></i> รูปภาพมุมกล้องเพิ่มเติม (สำหรับสินค้าหน้าเดียว/ไม่มีตัวเลือกสี)</label>
                        <input type="file" name="gallery_imgs[]" class="form-control" accept="image/*" multiple form="productForm">
                        <span style="font-size:0.75rem; color:#666; display:block; margin-top:4px;">เลือกไฟล์หลายไฟล์ได้ เพื่อโชว์มุมกล้องอื่นๆ ให้กับสินค้านี้โดยตรง</span>
                        
                        <?php if ($isEdit && !empty($baseGalleries)): ?>
                            <div style="margin-top: 12px; display:flex; flex-wrap:wrap; gap:8px;">
                                <?php foreach ($baseGalleries as $g): ?>
                                    <div style="position:relative; width: 60px; height: 60px;">
                                        <img src="<?php echo UPLOAD_URL . 'products/' . $g['g_img']; ?>" style="width:100%; height:100%; object-fit:cover; border-radius:4px; border: 1px solid #cbd5e1;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <span style="font-size:0.7rem; color:var(--error);display:block;margin-top:4px;">(ลบรูปเพิ่มเติมที่ด้านล่างกรอบนี้)</span>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--gray-100);">
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px;" form="productForm">
                            <i class="fas fa-save"></i> <?php echo $isEdit ? 'อัปเดตข้อมูลสินค้า' : 'บันทึกสินค้าใหม่'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <?php if ($isEdit && !empty($baseGalleries)): ?>
        <hr style="margin:24px 0; border:none; border-top:1px solid #e2e8f0;">
        <h4 style="margin-bottom:16px;">จัดการรูปลบมุมกล้อง</h4>
        <div style="display:flex; flex-wrap:wrap; gap:12px;">
            <?php foreach ($baseGalleries as $g): ?>
                <div style="position:relative; width: 80px; height: 80px; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;">
                    <img src="<?php echo UPLOAD_URL . 'products/' . $g['g_img']; ?>" style="width:100%; height:100%; object-fit:cover;">
                    <form method="POST" style="position:absolute; top:4px; right:4px;" onsubmit="return confirm('ยืนยันลบรูปนี้?');">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="delete_base_gal">
                        <input type="hidden" name="p_id" value="<?php echo $editProduct['p_id']; ?>">
                        <input type="hidden" name="g_id" value="<?php echo $g['g_id']; ?>">
                        <button type="submit" style="background:#ef4444; color:white; border:none; border-radius:4px; width:24px; height:24px; cursor:pointer;" title="ลบรูป">X</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isEdit): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h3>รายการสินค้าทั้งหมด</h3>
    </div>
    <div class="admin-card-body" style="padding:0; overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>รูปภาพ</th>
                    <th>ชื่อสินค้า / แบรนด์</th>
                    <th>ราคา</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <?php if ($product['p_img'] && $product['p_img'] !== 'no_image.png'): ?>
                            <img src="<?php echo UPLOAD_URL . 'products/' . $product['p_img']; ?>" class="product-thumb">
                        <?php else: ?>
                            <div class="product-thumb" style="display:flex;align-items:center;justify-content:center;font-size:1.5rem;">&#128095;</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo sanitize($product['p_name']); ?></strong><br>
                        <span style="font-size:0.75rem;color:var(--gray-500);"><?php echo sanitize($product['b_name'] ?? 'ไม่มีแบรนด์'); ?></span>
                    </td>
                    <td><strong><?php echo formatPrice($product['p_price']); ?></strong></td>
                    <td>
                        <?php if ($product['p_status'] === 'active'): ?>
                            <span class="badge badge-done">Active</span>
                        <?php else: ?>
                            <span class="badge badge-cancelled">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="action-btns">
                        <a href="?page=products&edit=<?php echo $product['p_id']; ?>" class="btn btn-sm btn-secondary" title="แก้ไข">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?page=colors&pid=<?php echo $product['p_id']; ?>" class="btn btn-sm btn-primary" style="background-color: #3b82f6; border-color: #3b82f6;" title="จัดการสี/ลวดลาย">
                            <i class="fas fa-palette"></i>
                        </a>
                        <a href="?page=stock&pid=<?php echo $product['p_id']; ?>" class="btn btn-sm btn-success" title="จัดการสต็อกไซส์">
                            <i class="fas fa-cubes"></i>
                        </a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันลบสินค้านี้? *หากมีออร์เดอร์จะไม่สามารถลบได้*');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="p_id" value="<?php echo $product['p_id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="ลบ"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
