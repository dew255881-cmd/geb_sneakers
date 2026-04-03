<?php
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง';
    } else {
        $b_name = sanitize($_POST['b_name'] ?? '');
        
        if ($_POST['action'] === 'add') {
            if (empty($b_name)) {
                $error = 'กรุณากรอกชื่อแบรนด์';
            } else {
                $stmt = $conn->prepare("INSERT INTO tb_brands (b_name) VALUES (?)");
                $stmt->bind_param("s", $b_name);
                $stmt->execute();
                $success = 'เพิ่มแบรนด์เรียบร้อย';
            }
        } elseif ($_POST['action'] === 'edit') {
            $b_id = (int)$_POST['b_id'];
            if (empty($b_name)) {
                $error = 'กรุณากรอกชื่อแบรนด์';
            } else {
                $stmt = $conn->prepare("UPDATE tb_brands SET b_name = ? WHERE b_id = ?");
                $stmt->bind_param("si", $b_name, $b_id);
                $stmt->execute();
                $success = 'แก้ไขแบรนด์เรียบร้อย';
            }
        } elseif ($_POST['action'] === 'delete') {
            $b_id = (int)$_POST['b_id'];
            // เช็คว่ามีสินค้าแบรนด์นี้หรือไม่
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tb_products WHERE b_id = ?");
            $stmt->bind_param("i", $b_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->fetch_assoc()['count'] > 0) {
                $error = 'ไม่สามารถลบแบรนด์นี้ได้ เนื่องจากมีสินค้าผูกอยู่ (ให้เซ็ต B_ID ในสินค้าเป็น NULL ก่อน)';
            } else {
                $stmt = $conn->prepare("DELETE FROM tb_brands WHERE b_id = ?");
                $stmt->bind_param("i", $b_id);
                $stmt->execute();
                $success = 'ลบแบรนด์เรียบร้อย';
            }
        }
    }
}

$resultBrands = $conn->query("SELECT b.*, (SELECT COUNT(*) FROM tb_products p WHERE p.b_id = b.b_id) as p_count FROM tb_brands b ORDER BY b_name ASC");
$brands = $resultBrands->fetch_all(MYSQLI_ASSOC);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h1 class="admin-page-title" style="margin: 0;">จัดการแบรนด์</h1>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')">
        <i class="fas fa-plus"></i> เพิ่มแบรนด์
    </button>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

<div class="admin-card">
    <div class="admin-card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อแบรนด์</th>
                    <th>จำนวนสินค้า</th>
                    <th>อัปเดตล่าสุด</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brands as $brand): ?>
                <tr>
                    <td><?php echo $brand['b_id']; ?></td>
                    <td><strong><?php echo sanitize($brand['b_name']); ?></strong></td>
                    <td><?php echo $brand['p_count']; ?> รายการ</td>
                    <td><?php echo date('d/m/Y H:i', strtotime($brand['updated_at'])); ?></td>
                    <td class="action-btns">
                        <button class="btn btn-sm btn-secondary" onclick="editBrand(<?php echo $brand['b_id']; ?>, '<?php echo sanitize(addslashes($brand['b_name'])); ?>')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันที่จะลบแบรนด์นี้?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="b_id" value="<?php echo $brand['b_id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">&times;</button>
        <h3>เพิ่มแบรนด์ใหม่</h3>
        <form method="POST" class="admin-form" style="max-width:100%;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">ชื่อแบรนด์ *</label>
                <input type="text" name="b_name" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">บันทึกข้อมูล</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('open')">&times;</button>
        <h3>แก้ไขแบรนด์</h3>
        <form method="POST" class="admin-form" style="max-width:100%;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="b_id" id="edit_b_id" value="">
            
            <div class="form-group">
                <label class="form-label">ชื่อแบรนด์ *</label>
                <input type="text" name="b_name" id="edit_b_name" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">อัปเดตข้อมูล</button>
        </form>
    </div>
</div>

<script>
function editBrand(id, name) {
    document.getElementById('edit_b_id').value = id;
    document.getElementById('edit_b_name').value = name;
    document.getElementById('editModal').classList.add('open');
}
</script>
