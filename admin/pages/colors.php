<?php
$pid = isset($_GET['pid']) ? (int)$_GET['pid'] : null;

$error = '';
$success = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง';
    } else {
        $action = $_POST['action'];
        $p_id = (int)($_POST['p_id'] ?? 0);
        
        if ($action === 'add') {
            $color_name = sanitize($_POST['color_name'] ?? '');
            
            if (empty($color_name) || $p_id <= 0) {
                $error = 'กรุณากรอกชื่อสีและเลือกสินค้าให้ถูกต้อง';
            } else {
                // Check duplicate color name for this product
                $stmtCheck = $conn->prepare("SELECT COUNT(*) as count FROM tb_product_colors WHERE p_id = ? AND color_name = ?");
                $stmtCheck->bind_param("is", $p_id, $color_name);
                $stmtCheck->execute();
                $resultCheck = $stmtCheck->get_result();
                
                if ($resultCheck->fetch_assoc()['count'] > 0) {
                    $error = 'สินค้านี้มีสี ' . $color_name . ' สมัครอยู่แล้ว';
                } else {
                    $imgName = 'no_image.png'; // default
                    
                    if (isset($_FILES['color_img']) && $_FILES['color_img']['error'] === UPLOAD_ERR_OK) {
                        $uploaded = uploadImage($_FILES['color_img'], 'colors');
                        if ($uploaded) {
                            $imgName = $uploaded;
                        } else {
                            $error = 'อัปโหลดรูปภาพหลักมีปัญหา รองรับ JPG, PNG, WEBP เท่านั้น';
                        }
                    }
                    
                    if (empty($error)) {
                        $stmt = $conn->prepare("INSERT INTO tb_product_colors (p_id, color_name, color_img) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $p_id, $color_name, $imgName);
                        $stmt->execute();
                        $new_color_id = $conn->insert_id;
                        
                        // Handle Gallery Images
                        if (isset($_FILES['gallery_imgs']) && count($_FILES['gallery_imgs']['name']) > 0 && !empty($_FILES['gallery_imgs']['name'][0])) {
                            $galleryCount = count($_FILES['gallery_imgs']['name']);
                            $stmtGallery = $conn->prepare("INSERT INTO tb_product_gallery (p_id, color_id, g_img) VALUES (?, ?, ?)");
                            
                            for ($i = 0; $i < $galleryCount; $i++) {
                                if ($_FILES['gallery_imgs']['error'][$i] === UPLOAD_ERR_OK) {
                                    $mockFile = [
                                        'name' => $_FILES['gallery_imgs']['name'][$i],
                                        'type' => $_FILES['gallery_imgs']['type'][$i],
                                        'tmp_name' => $_FILES['gallery_imgs']['tmp_name'][$i],
                                        'error' => $_FILES['gallery_imgs']['error'][$i],
                                        'size' => $_FILES['gallery_imgs']['size'][$i]
                                    ];
                                    
                                    $gUploaded = uploadImage($mockFile, 'colors');
                                    if ($gUploaded) {
                                        $stmtGallery->bind_param("iis", $p_id, $new_color_id, $gUploaded);
                                        $stmtGallery->execute();
                                    }
                                }
                            }
                        }
                        
                        $success = 'เพิ่มสีและรูปรองเท้าเรียบร้อยแล้ว';
                        $pid = $p_id;
                    }
                }
            }
        } elseif ($action === 'delete') {
            $color_id = (int)$_POST['color_id'];
            $p_id = (int)$_POST['p_id'];
            
            // Check if stock exists for this color
            $stmtCheckStock = $conn->prepare("SELECT COUNT(*) as count FROM tb_stock WHERE color_id = ?");
            $stmtCheckStock->bind_param("i", $color_id);
            $stmtCheckStock->execute();
            if ($stmtCheckStock->get_result()->fetch_assoc()['count'] > 0) {
                $error = 'ไม่สามารถลบสีนี้ได้ เนื่องจากมีสต็อกไซส์ผูกอยู่ กรุณาลบสต็อกก่อน';
            } else {
                // Delete gallery images
                $stmtGal = $conn->prepare("SELECT g_img FROM tb_product_gallery WHERE color_id = ?");
                $stmtGal->bind_param("i", $color_id);
                $stmtGal->execute();
                $galImages = $stmtGal->get_result()->fetch_all(MYSQLI_ASSOC);
                foreach ($galImages as $gi) {
                    $gPath = UPLOAD_PATH . 'colors' . DIRECTORY_SEPARATOR . $gi['g_img'];
                    if (file_exists($gPath)) @unlink($gPath);
                }
                $stmtDelGal = $conn->prepare("DELETE FROM tb_product_gallery WHERE color_id = ?");
                $stmtDelGal->bind_param("i", $color_id);
                $stmtDelGal->execute();

                // Delete main color image
                $stmtImg = $conn->prepare("SELECT color_img FROM tb_product_colors WHERE color_id = ?");
                $stmtImg->bind_param("i", $color_id);
                $stmtImg->execute();
                $imgRes = $stmtImg->get_result()->fetch_assoc();
                if ($imgRes && $imgRes['color_img'] !== 'no_image.png') {
                    $filePath = UPLOAD_PATH . 'colors' . DIRECTORY_SEPARATOR . $imgRes['color_img'];
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
                
                $stmtDel = $conn->prepare("DELETE FROM tb_product_colors WHERE color_id = ? AND p_id = ?");
                $stmtDel->bind_param("ii", $color_id, $p_id);
                $stmtDel->execute();
                $success = 'ลบสีและรูปภาพแบบทั้งหมดเรียบร้อย';
            }
            $pid = $p_id;
        } elseif ($action === 'delete_gallery_img') {
            // Optional utility if we want them to delete specifically one image.
            // Simplified version won't have the button, but keeping the endpoint just in case.
        }
    }
}

// Fetch context data
$galleries = [];
if ($pid) {
    $stmtItem = $conn->prepare("SELECT p_name, p_img FROM tb_products WHERE p_id = ?");
    $stmtItem->bind_param("i", $pid);
    $stmtItem->execute();
    $product = $stmtItem->get_result()->fetch_assoc();
    
    if (!$product) {
        $error = 'ไม่พบสินค้า';
        $pid = null;
    } else {
        $stmtColors = $conn->prepare("SELECT * FROM tb_product_colors WHERE p_id = ? ORDER BY color_name ASC");
        $stmtColors->bind_param("i", $pid);
        $stmtColors->execute();
        $colorsList = $stmtColors->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Fetch all galleries for this product
        $stmtAllGal = $conn->prepare("SELECT color_id, g_img FROM tb_product_gallery WHERE p_id = ?");
        $stmtAllGal->bind_param("i", $pid);
        $stmtAllGal->execute();
        $resAllGal = $stmtAllGal->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($resAllGal as $g) {
            $galleries[$g['color_id']][] = $g['g_img'];
        }
    }
}

$resultAllProducts = $conn->query("SELECT p_id, p_name FROM tb_products ORDER BY p_id DESC");
$allProducts = $resultAllProducts->fetch_all(MYSQLI_ASSOC);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h1 class="admin-page-title" style="margin: 0;">จัดการสีสินค้าและแกลลอรี</h1>
    <?php if ($pid): ?>
        <button class="btn btn-primary" onclick="document.getElementById('addColorModal').classList.add('open')">
            <i class="fas fa-plus"></i> เพิ่มสีใหม่
        </button>
    <?php endif; ?>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

<div class="admin-card">
    <div class="admin-card-body">
        <form method="GET" class="admin-form" style="max-width:100%;">
            <input type="hidden" name="page" value="colors">
            <div class="form-group" style="display:flex;gap:12px;align-items:flex-end;">
                <div style="flex:1;">
                    <label class="form-label" style="margin-bottom:8px;">เลือกสินค้าเพื่อจัดการสี</label>
                    <select name="pid" class="form-control" onchange="this.form.submit()">
                        <option value="">-- กรุณาเลือกสินค้า --</option>
                        <?php foreach ($allProducts as $p): ?>
                            <option value="<?php echo $p['p_id']; ?>" <?php echo ($pid == $p['p_id']) ? 'selected' : ''; ?>>
                                #<?php echo str_pad($p['p_id'], 4, '0', STR_PAD_LEFT); ?> - <?php echo sanitize($p['p_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($pid && $product): ?>
<div class="admin-card mt-20">
    <div class="admin-card-header" style="display:flex; gap:16px; align-items:center;">
        <?php if ($product['p_img'] && $product['p_img'] !== 'no_image.png'): ?>
            <img src="<?php echo UPLOAD_URL . 'products/' . $product['p_img']; ?>" alt="Product Img" style="width:60px; height:60px; object-fit:cover; border-radius:8px;">
        <?php endif; ?>
        <div>
            <h3 style="margin:0; font-size:1.1rem;"><?php echo sanitize($product['p_name']); ?></h3>
            <span style="font-size:0.85rem; color:#666;">จัดการลวดลาย สีสัน และอัปโหลดมุมกล้องอื่นๆ ของสินค้านี้</span>
        </div>
    </div>
    
    <div class="admin-card-body">
        <?php if (empty($colorsList)): ?>
            <div class="empty-state">
                <p>ยังไม่มีสีสำหรับสินค้านี้</p>
                <button class="btn btn-outline" onclick="document.getElementById('addColorModal').classList.add('open')">เพิ่มสีแรก</button>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th width="80">รูปตัวอย่างสี</th>
                            <th>ชื่อสี / ลวดลาย</th>
                            <th>รูปมุมอื่นๆ (Gallery)</th>
                            <th width="100" class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($colorsList as $colorRow): ?>
                        <tr>
                            <td>
                                <img src="<?php echo UPLOAD_URL . 'colors/' . $colorRow['color_img']; ?>" alt="Color" style="width:50px; height:50px; object-fit:cover; border-radius:4px; border:1px solid #eee;">
                            </td>
                            <td><strong><?php echo sanitize($colorRow['color_name']); ?></strong></td>
                            <td>
                                <?php 
                                $c_id = $colorRow['color_id'];
                                if (!empty($galleries[$c_id])): ?>
                                    <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                        <?php foreach ($galleries[$c_id] as $gImg): ?>
                                            <img src="<?php echo UPLOAD_URL . 'colors/' . $gImg; ?>" style="width:40px; height:40px; object-fit:cover; border-radius:4px; border:1px solid #ddd;" title="Gallery Image">
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#999;font-size:0.85rem;">ไม่มีรูปมุมกล้องเพิ่มเติม</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <form method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันประสงค์การลบสีนี้ รวมถึงรูปรองเท้ามุมอื่นๆ ทั้งหมดด้วย?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="p_id" value="<?php echo $pid; ?>">
                                    <input type="hidden" name="color_id" value="<?php echo $colorRow['color_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="ลบสีทั้งชุด"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Add Color Modal -->
<?php if ($pid): ?>
<div class="modal-overlay" id="addColorModal">
    <div class="modal-box" style="max-width:500px;">
        <h3>เพิ่มสีใหม่สำหรับ: <?php echo sanitize($product['p_name']); ?></h3>
        <form method="POST" enctype="multipart/form-data" class="admin-form mt-20">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="p_id" value="<?php echo $pid; ?>">
            
            <div class="form-group">
                <label class="form-label">ชื่อสี * (เช่น Black/White, Triple Red)</label>
                <input type="text" name="color_name" class="form-control" required placeholder="ระบุชื่อสี...">
            </div>
            
            <div class="form-group">
                <label class="form-label">รูปภาพแสดงสีนี้ (หลัก) *</label>
                <input type="file" name="color_img" class="form-control" accept="image/*" required>
                <span style="font-size:0.75rem; color:#666; display:block; margin-top:4px;">แนะนำให้อัปโหลดรูปรองเท้ามุมข้างชัดเจน</span>
            </div>
            
            <div class="form-group" style="padding:16px; border:1px dashed #ccc; border-radius:8px; background:#fafafa;">
                <label class="form-label"><i class="fas fa-images"></i> รูปมุมกล้องเพิ่มเติม (หลายรูปได้)</label>
                <input type="file" name="gallery_imgs[]" class="form-control" accept="image/*" multiple>
                <span style="font-size:0.75rem; color:#666; display:block; margin-top:4px;">เลือกไฟล์ได้หลายไฟล์พร้อมกัน เพื่อโชว์ในแถบ Gallery ด้านล่างรูปรองเท้า</span>
                <span style="font-size:0.75rem; color:#999; display:block;">กด Ctrl หรือ Shift ค้างไว้ตอนเลือกไฟล์</span>
            </div>
            
            <div class="form-group action-btns mt-20" style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addColorModal').classList.remove('open')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">บันทึกสี + แกลลอรี่</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
