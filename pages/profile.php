<?php
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "index.php?page=login");
    exit;
}

$stmtUser = $conn->prepare("SELECT * FROM tb_users WHERE u_id = ?");
$stmtUser->bind_param("i", $_SESSION['u_id']);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();

$stmtAddr = $conn->prepare("SELECT * FROM tb_addresses WHERE u_id = ? ORDER BY is_default DESC, created_at DESC");
$stmtAddr->bind_param("i", $_SESSION['u_id']);
$stmtAddr->execute();
$addresses = $stmtAddr->get_result()->fetch_all(MYSQLI_ASSOC);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $fullname = sanitize($_POST['fullname'] ?? '');
            $tel = sanitize($_POST['tel'] ?? '');

            if (empty($fullname)) {
                $error = 'กรุณากรอกชื่อ-นามสกุล';
            } else {
                $stmt = $conn->prepare("UPDATE tb_users SET u_fullname = ?, u_tel = ? WHERE u_id = ?");
                $stmt->bind_param("ssi", $fullname, $tel, $_SESSION['u_id']);
                $stmt->execute();
                $_SESSION['u_fullname'] = $fullname;
                $success = 'บันทึกข้อมูลสำเร็จ';

                $stmtUser = $conn->prepare("SELECT * FROM tb_users WHERE u_id = ?");
                $stmtUser->bind_param("i", $_SESSION['u_id']);
                $stmtUser->execute();
                $resultUser = $stmtUser->get_result();
                $user = $resultUser->fetch_assoc();
            }
        }

        if ($action === 'add_address' || $action === 'edit_address') {
            $addr_id = (int)($_POST['addr_id'] ?? 0);
            $addr_label = sanitize($_POST['addr_label'] ?? 'บ้าน');
            $addr_fullname = sanitize($_POST['addr_fullname'] ?? '');
            $addr_phone = sanitize($_POST['addr_phone'] ?? '');
            $addr_detail = sanitize($_POST['addr_detail'] ?? '');
            
            // For new address
            if (empty($addr_fullname) || empty($addr_phone) || empty($addr_detail)) {
                $error = 'กรุณากรอกข้อมูลที่อยู่ให้ครบถ้วน';
            } else {
                try {
                    $conn->begin_transaction();
                    
                    if ($action === 'add_address') {
                        // Check if it's the first address, if so, make it default automatically
                        $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM tb_addresses WHERE u_id = ?");
                        $checkStmt->bind_param("i", $_SESSION['u_id']);
                        $checkStmt->execute();
                        $checkResult = $checkStmt->get_result()->fetch_assoc();
                        $is_default = ($checkResult['cnt'] == 0) ? 1 : 0;

                        $stmt = $conn->prepare("INSERT INTO tb_addresses (u_id, addr_label, addr_fullname, addr_phone, addr_detail, is_default) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("issssi", $_SESSION['u_id'], $addr_label, $addr_fullname, $addr_phone, $addr_detail, $is_default);
                        $stmt->execute();
                        $success = 'เพิ่มที่อยู่สำเร็จ';
                    } else { // edit_address
                        $stmtCheck = $conn->prepare("SELECT addr_id FROM tb_addresses WHERE addr_id = ? AND u_id = ?");
                        $stmtCheck->bind_param("ii", $addr_id, $_SESSION['u_id']);
                        $stmtCheck->execute();
                        if ($stmtCheck->get_result()->num_rows > 0) {
                            $stmt = $conn->prepare("UPDATE tb_addresses SET addr_label = ?, addr_fullname = ?, addr_phone = ?, addr_detail = ? WHERE addr_id = ? AND u_id = ?");
                            $stmt->bind_param("ssssii", $addr_label, $addr_fullname, $addr_phone, $addr_detail, $addr_id, $_SESSION['u_id']);
                            $stmt->execute();
                            $success = 'แก้ไขที่อยู่สำเร็จ';
                        }
                    }
                    $conn->commit();
                } catch(Exception $e) {
                    $conn->rollback();
                    $error = 'เกิดข้อผิดพลาดในการบันทึกที่อยู่: ' . $e->getMessage();
                }
            }
        }

        if ($action === 'set_default_address') {
            $addr_id = (int)($_POST['addr_id'] ?? 0);
            try {
                $conn->begin_transaction();
                
                $stmt0 = $conn->prepare("UPDATE tb_addresses SET is_default = 0 WHERE u_id = ?");
                $stmt0->bind_param("i", $_SESSION['u_id']);
                $stmt0->execute();

                $stmt1 = $conn->prepare("UPDATE tb_addresses SET is_default = 1 WHERE addr_id = ? AND u_id = ?");
                $stmt1->bind_param("ii", $addr_id, $_SESSION['u_id']);
                $stmt1->execute();
                
                if ($stmt1->affected_rows === 0) {
                    throw new Exception("ไม่พบที่อยู่ดังกล่าว");
                }

                $conn->commit();
                $success = 'ตั้งค่าที่อยู่เริ่มต้นสำเร็จ';
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            }
        }

        if ($action === 'delete_address') {
            $addr_id = (int)($_POST['addr_id'] ?? 0);
            
            $stmtDef = $conn->prepare("SELECT is_default FROM tb_addresses WHERE addr_id = ? AND u_id = ?");
            $stmtDef->bind_param("ii", $addr_id, $_SESSION['u_id']);
            $stmtDef->execute();
            $resDef = $stmtDef->get_result()->fetch_assoc();

            if ($resDef) {
                $stmt = $conn->prepare("DELETE FROM tb_addresses WHERE addr_id = ? AND u_id = ?");
                $stmt->bind_param("ii", $addr_id, $_SESSION['u_id']);
                $stmt->execute();
                $success = 'ลบที่อยู่สำเร็จ';

                // If deleted address was default, set another one as default
                if ($resDef['is_default'] == 1) {
                    $stmtNewDef = $conn->prepare("UPDATE tb_addresses SET is_default = 1 WHERE u_id = ? LIMIT 1");
                    $stmtNewDef->bind_param("i", $_SESSION['u_id']);
                    $stmtNewDef->execute();
                }
            }
        }


        if ($action === 'update_avatar') {
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                $error = 'กรุณาเลือกรูปภาพ';
            } else {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed)) {
                    $error = 'รองรับเฉพาะไฟล์ JPG, PNG, WEBP';
                } elseif ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
                    $error = 'ไฟล์ต้องมีขนาดไม่เกิน 5MB';
                } else {
                    $newName = 'avatar_' . $_SESSION['u_id'] . '_' . time() . '.' . $ext;
                    $targetPath = UPLOAD_PATH . 'avatars' . DIRECTORY_SEPARATOR . $newName;

                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                        if ($user['u_avatar'] && $user['u_avatar'] !== 'default_avatar.png') {
                            $oldPath = UPLOAD_PATH . 'avatars' . DIRECTORY_SEPARATOR . $user['u_avatar'];
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }

                        $stmt = $conn->prepare("UPDATE tb_users SET u_avatar = ? WHERE u_id = ?");
                        $stmt->bind_param("si", $newName, $_SESSION['u_id']);
                        $stmt->execute();
                        $success = 'อัปเดตรูปโปรไฟล์สำเร็จ';

                        $stmtUser = $conn->prepare("SELECT * FROM tb_users WHERE u_id = ?");
                        $stmtUser->bind_param("i", $_SESSION['u_id']);
                        $stmtUser->execute();
                        $resultUser = $stmtUser->get_result();
                        $user = $resultUser->fetch_assoc();
                    } else {
                        $error = 'เกิดข้อผิดพลาดในการอัปโหลด';
                    }
                }
            }
        }

        if ($action === 'change_password') {
            $current_pass = $_POST['current_password'] ?? '';
            $new_pass = $_POST['new_password'] ?? '';
            $confirm_pass = $_POST['confirm_password'] ?? '';

            if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
                $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
            } elseif (!password_verify($current_pass, $user['u_password'])) {
                $error = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            } elseif ($new_pass !== $confirm_pass) {
                $error = 'รหัสผ่านใหม่ไม่ตรงกัน';
            } elseif (strlen($new_pass) < 6) {
                $error = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
            } else {
                $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE tb_users SET u_password = ? WHERE u_id = ?");
                $stmt->bind_param("si", $hashed, $_SESSION['u_id']);
                $stmt->execute();
                $success = 'เปลี่ยนรหัสผ่านสำเร็จ';
            }
        }
    }
}

$avatarSrc = UPLOAD_URL . 'avatars/' . ($user['u_avatar'] ?? 'default_avatar.png');
?>

<div class="profile-page">
    <div class="container">
        <div class="profile-header">
            <h1 class="profile-title">บัญชีของฉัน</h1>
            <p class="profile-subtitle">จัดการข้อมูลส่วนตัวและรูปโปรไฟล์</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="profile-grid">
            <div class="profile-avatar-section">
                <div class="avatar-card">
                    <div class="avatar-wrapper" id="avatarWrapper">
                        <img src="<?php echo $avatarSrc; ?>?v=<?php echo time(); ?>" alt="Avatar" id="avatarPreview">
                        <div class="avatar-overlay" id="avatarOverlay">
                            <i class="fas fa-camera"></i>
                            <span>เปลี่ยนรูป</span>
                        </div>
                    </div>
                    <div class="avatar-info">
                        <h3><?php echo sanitize($user['u_fullname'] ?? 'ไม่ระบุชื่อ'); ?></h3>
                        <p>@<?php echo sanitize($user['u_username']); ?></p>
                    </div>
                    <form method="POST" enctype="multipart/form-data" id="avatarForm">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="update_avatar">
                        <input type="file" name="avatar" id="avatarInput" accept="image/jpeg, image/png, image/webp" style="display:none;">
                    </form>
                    <button type="button" class="btn-save-avatar" id="btnSaveAvatar" style="display:none;">
                        <i class="fas fa-check"></i> บันทึกรูปโปรไฟล์
                    </button>
                    <button type="button" class="btn-cancel-avatar" id="btnCancelAvatar" style="display:none;">ยกเลิก</button>
                </div>
            </div>

            <div class="profile-form-section">
                <div class="form-card">
                    <h2 class="form-card-title">ข้อมูลส่วนตัว</h2>
                    <form method="POST" id="profileForm">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="pf-form-group">
                            <label class="pf-label">ชื่อผู้ใช้</label>
                            <input type="text" class="pf-input" value="<?php echo sanitize($user['u_username']); ?>" disabled>
                        </div>

                        <div class="pf-form-group">
                            <label class="pf-label">ชื่อ-นามสกุล <span class="required">*</span></label>
                            <input type="text" name="fullname" class="pf-input" value="<?php echo sanitize($user['u_fullname'] ?? ''); ?>" required>
                        </div>

                        <div class="pf-form-group">
                            <label class="pf-label">เบอร์โทรศัพท์</label>
                            <input type="text" name="tel" class="pf-input" value="<?php echo sanitize($user['u_tel'] ?? ''); ?>" placeholder="เช่น 0812345678">
                        </div>

                        <div class="pf-form-group">
                            <label class="pf-label">สมาชิกตั้งแต่</label>
                            <input type="text" class="pf-input" value="<?php echo date('d/m/Y', strtotime($user['created_at'])); ?>" disabled>
                        </div>

                        <button type="submit" class="btn-save-profile">
                            <i class="fas fa-save"></i> บันทึกข้อมูล
                        </button>
                    </form>
                </div>

                <div class="form-card" style="margin-top: 30px;">
                    <h2 class="form-card-title">เปลี่ยนรหัสผ่าน</h2>
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="change_password">

                        <style>
                            .pf-password-wrap {
                                position: relative;
                                display: flex;
                                align-items: center;
                            }
                            .pf-password-input {
                                padding-right: 45px !important;
                            }
                            .pf-eye-btn {
                                position: absolute;
                                right: 12px;
                                top: 50%;
                                transform: translateY(-50%);
                                background: none;
                                border: none;
                                color: var(--gray-400);
                                cursor: pointer;
                                padding: 5px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                transition: color 0.2s;
                                z-index: 5;
                            }
                            .pf-eye-btn:hover {
                                color: var(--black);
                            }
                        </style>

                        <div class="pf-form-group">
                            <label class="pf-label">รหัสผ่านปัจจุบัน</label>
                            <div class="pf-password-wrap">
                                <input type="password" name="current_password" class="pf-input pf-password-input" required>
                                <button type="button" class="pf-eye-btn" tabindex="-1">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pf-form-group">
                            <label class="pf-label">รหัสผ่านใหม่</label>
                            <div class="pf-password-wrap">
                                <input type="password" name="new_password" class="pf-input pf-password-input" required>
                                <button type="button" class="pf-eye-btn" tabindex="-1">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pf-form-group">
                            <label class="pf-label">ยืนยันรหัสผ่านใหม่</label>
                            <div class="pf-password-wrap">
                                <input type="password" name="confirm_password" class="pf-input pf-password-input" required>
                                <button type="button" class="pf-eye-btn" tabindex="-1">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-save-profile" style="background: var(--black); color: var(--white);">
                            <i class="fas fa-key"></i> อัปเดตรหัสผ่าน
                        </button>
                    </form>
                </div>

                <!-- ADDRESS BOOK SECTION -->
                <div class="form-card" style="margin-top: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 class="form-card-title" style="margin-bottom: 0;">สมุดที่อยู่</h2>
                        <button type="button" class="btn-primary" onclick="openAddressModal()" style="padding: 8px 16px; font-size: 0.8rem; border-radius: 8px;">
                            <i class="fas fa-plus"></i> เพิ่มที่อยู่ใหม่
                        </button>
                    </div>

                    <?php if (empty($addresses)): ?>
                        <div style="text-align: center; padding: 40px 20px; background: var(--gray-50); border-radius: 12px; border: 1px dashed var(--gray-300);">
                            <i class="fas fa-map-marker-alt" style="font-size: 2rem; color: var(--gray-300); margin-bottom: 10px;"></i>
                            <p style="color: var(--gray-500); font-size: 0.9rem;">ยังไม่มีที่อยู่บันทึกไว้</p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($addresses as $addr): ?>
                            <div style="border: 2px solid <?php echo $addr['is_default'] ? 'var(--black)' : 'var(--gray-200)'; ?>; border-radius: 12px; padding: 20px; background: var(--white); position: relative;">
                                <?php if ($addr['is_default']): ?>
                                    <span style="position: absolute; top: 16px; right: 20px; background: var(--black); color: var(--white); font-size: 0.65rem; padding: 4px 10px; border-radius: 20px; font-weight: 600;">ค่าเริ่มต้น</span>
                                <?php endif; ?>
                                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                                    <span style="background: var(--gray-100); color: var(--gray-700); font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 6px;"><?php echo sanitize($addr['addr_label']); ?></span>
                                    <strong style="font-size: 0.95rem;"><?php echo sanitize($addr['addr_fullname']); ?></strong>
                                </div>
                                <div style="color: var(--gray-600); font-size: 0.85rem; margin-bottom: 6px;">
                                    <i class="fas fa-phone-alt" style="margin-right: 6px; font-size: 0.75rem;"></i><?php echo sanitize($addr['addr_phone']); ?>
                                </div>
                                <div style="color: var(--gray-500); font-size: 0.85rem; line-height: 1.6; margin-bottom: 16px;">
                                    <?php echo nl2br(sanitize($addr['addr_detail'])); ?>
                                </div>
                                <div style="display: flex; gap: 10px; border-top: 1px solid var(--gray-100); padding-top: 16px;">
                                    <button type="button" style="background: none; border: none; color: var(--gray-600); font-size: 0.8rem; font-family: var(--font); font-weight: 600; cursor: pointer;" onclick="openAddressModal({
                                        id: <?php echo $addr['addr_id']; ?>,
                                        label: '<?php echo addslashes($addr['addr_label']); ?>',
                                        fullname: '<?php echo addslashes($addr['addr_fullname']); ?>',
                                        phone: '<?php echo addslashes($addr['addr_phone']); ?>',
                                        detail: `<?php echo addslashes($addr['addr_detail']); ?>`
                                    })"><i class="fas fa-edit"></i> แก้ไข</button>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('ยืนยันลบที่อยู่นี้?');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete_address">
                                        <input type="hidden" name="addr_id" value="<?php echo $addr['addr_id']; ?>">
                                        <button type="submit" style="background: none; border: none; color: var(--error); font-size: 0.8rem; font-family: var(--font); font-weight: 600; cursor: pointer;"><i class="fas fa-trash-alt"></i> ลบ</button>
                                    </form>

                                    <?php if (!$addr['is_default']): ?>
                                    <form method="POST" style="display: inline; margin-left: auto;">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="set_default_address">
                                        <input type="hidden" name="addr_id" value="<?php echo $addr['addr_id']; ?>">
                                        <button type="submit" style="background: none; border: none; color: var(--black); font-size: 0.8rem; font-family: var(--font); font-weight: 700; cursor: pointer;">ตั้งเป็นค่าเริ่มต้น</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Address Modal -->
<div id="addressModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--white); width: 100%; max-width: 500px; border-radius: 20px; padding: 32px; position: relative;">
        <button type="button" onclick="closeAddressModal()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--gray-400);"><i class="fas fa-times"></i></button>
        <h3 id="addrModalTitle" style="font-size: 1.2rem; margin-bottom: 24px;">เพิ่มที่อยู่ใหม่</h3>
        
        <form method="POST" id="addressFormObj">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" id="addrAction" value="add_address">
            <input type="hidden" name="addr_id" id="addrId" value="">
            
            <div class="pf-form-group">
                <label class="pf-label">ป้ายกำกับ (เช่น บ้าน, ที่ทำงาน)</label>
                <input type="text" name="addr_label" id="addrLabel" class="pf-input" value="บ้าน" required>
            </div>
            <div class="pf-form-group">
                <label class="pf-label">ชื่อ-นามสกุล (ผู้รับ)</label>
                <input type="text" name="addr_fullname" id="addrFullname" class="pf-input" required>
            </div>
            <div class="pf-form-group">
                <label class="pf-label">เบอร์โทรศัพท์</label>
                <input type="text" name="addr_phone" id="addrPhone" class="pf-input" required>
            </div>
            <div class="pf-form-group">
                <label class="pf-label">ที่อยู่จัดส่ง</label>
                <textarea name="addr_detail" id="addrDetail" class="pf-input pf-textarea" rows="3" required placeholder="บ้านเลขที่, ถนน, ตำบล, อำเภอ, จังหวัด, รหัสไปรษณีย์"></textarea>
            </div>
            
            <button type="submit" class="btn-save-profile" style="margin-top: 10px;">
                <i class="fas fa-save"></i> บันทึกที่อยู่
            </button>
        </form>
    </div>
</div>

<script>
function openAddressModal(data = null) {
    const modal = document.getElementById('addressModal');
    const title = document.getElementById('addrModalTitle');
    const action = document.getElementById('addrAction');
    const id = document.getElementById('addrId');
    const label = document.getElementById('addrLabel');
    const fullname = document.getElementById('addrFullname');
    const phone = document.getElementById('addrPhone');
    const detail = document.getElementById('addrDetail');

    if (data) {
        title.innerText = 'แก้ไขที่อยู่';
        action.value = 'edit_address';
        id.value = data.id;
        label.value = data.label;
        fullname.value = data.fullname;
        phone.value = data.phone;
        detail.value = data.detail;
    } else {
        title.innerText = 'เพิ่มที่อยู่ใหม่';
        action.value = 'add_address';
        id.value = '';
        label.value = 'บ้าน';
        fullname.value = '<?php echo addslashes($user['u_fullname']); ?>';
        phone.value = '<?php echo addslashes($user['u_tel']); ?>';
        detail.value = '';
    }
    
    modal.style.display = 'flex';
}

function closeAddressModal() {
    document.getElementById('addressModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    var avatarOverlay = document.getElementById('avatarOverlay');
    var avatarInput = document.getElementById('avatarInput');
    var avatarPreview = document.getElementById('avatarPreview');
    var avatarPlaceholder = document.getElementById('avatarPlaceholder');
    var avatarForm = document.getElementById('avatarForm');
    var btnSave = document.getElementById('btnSaveAvatar');
    var btnCancel = document.getElementById('btnCancelAvatar');
    var originalSrc = avatarPreview ? avatarPreview.src : '';

    avatarOverlay.addEventListener('click', function() {
        avatarInput.click();
    });

    avatarInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                avatarPreview.src = e.target.result;
                avatarPreview.style.display = 'block';
                if (avatarPlaceholder) {
                    avatarPlaceholder.style.display = 'none';
                }
                btnSave.style.display = 'block';
                btnCancel.style.display = 'block';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    btnSave.addEventListener('click', function() {
        avatarForm.submit();
    });

    btnCancel.addEventListener('click', function() {
        avatarInput.value = '';
        if (originalSrc && originalSrc !== window.location.href) {
            avatarPreview.src = originalSrc;
            avatarPreview.style.display = 'block';
        } else {
            avatarPreview.style.display = 'none';
            if (avatarPlaceholder) {
                avatarPlaceholder.style.display = 'flex';
            }
        }
        btnSave.style.display = 'none';
        btnCancel.style.display = 'none';
    });

    // Password Toggle
    document.querySelectorAll('.pf-eye-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = btn.parentElement.querySelector('.pf-password-input');
            var icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
});
</script>
