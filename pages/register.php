<?php
if (isLoggedIn()) {
    header("Location: " . BASE_URL);
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $fullname = sanitize($_POST['fullname'] ?? '');
        $tel = sanitize($_POST['tel'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        if (empty($username) || empty($password) || empty($fullname)) {
            $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบ';
        } elseif (strlen($username) < 4) {
            $error = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 4 ตัวอักษร';
        } elseif (strlen($password) < 6) {
            $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
        } elseif ($password !== $confirmPassword) {
            $error = 'รหัสผ่านไม่ตรงกัน';
        } else {
            $stmt = $conn->prepare("SELECT u_id FROM tb_users WHERE u_username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->fetch_assoc()) {
                $error = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO tb_users (u_username, u_password, u_fullname, u_tel, u_address) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $username, $hashedPassword, $fullname, $tel, $address);
                $stmt->execute();
                setFlash('success', 'สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ');
                header("Location: " . BASE_URL . "index.php?page=login");
                exit;
            }
        }
    }
}
?>

<div class="auth-page">
    <div class="auth-card auth-card-wide">
        <div class="auth-card-inner">
            <div class="auth-logo">GEB <span>SNEAKERS</span></div>
            <h1 class="auth-title">สมัครสมาชิก</h1>
            <p class="auth-subtitle">สร้างบัญชีเพื่อเริ่มช้อปปิ้ง</p>

            <?php if ($error): ?>
                <div class="auth-alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="auth-row">
                    <div class="auth-field">
                        <label class="auth-label">ชื่อผู้ใช้ *</label>
                        <div class="auth-input-wrap">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" class="auth-input" placeholder="อย่างน้อย 4 ตัวอักษร" value="<?php echo sanitize($_POST['username'] ?? ''); ?>" required minlength="4">
                        </div>
                    </div>
                    <div class="auth-field">
                        <label class="auth-label">ชื่อ-นามสกุล *</label>
                        <div class="auth-input-wrap">
                            <i class="fas fa-id-card"></i>
                            <input type="text" name="fullname" class="auth-input" placeholder="ชื่อจริง นามสกุล" value="<?php echo sanitize($_POST['fullname'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="auth-row">
                    <div class="auth-field">
                        <label class="auth-label">เบอร์โทรศัพท์</label>
                        <div class="auth-input-wrap">
                            <i class="fas fa-phone"></i>
                            <input type="text" name="tel" class="auth-input" placeholder="เช่น 0812345678" value="<?php echo sanitize($_POST['tel'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="auth-field">
                        <label class="auth-label">ที่อยู่สำหรับจัดส่ง</label>
                        <div class="auth-input-wrap auth-input-wrap-top">
                            <i class="fas fa-map-marker-alt"></i>
                            <textarea name="address" class="auth-input auth-textarea" rows="2" placeholder="บ้านเลขที่, ตำบล, อำเภอ, จังหวัด"><?php echo sanitize($_POST['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="auth-row">
                    <div class="auth-field">
                        <label class="auth-label">รหัสผ่าน *</label>
                        <div class="auth-input-wrap">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="auth-input auth-password-input" placeholder="อย่างน้อย 6 ตัวอักษร" required minlength="6">
                            <button type="button" class="auth-eye-btn" aria-label="Toggle password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="auth-field">
                        <label class="auth-label">ยืนยันรหัสผ่าน *</label>
                        <div class="auth-input-wrap">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" class="auth-input auth-password-input" placeholder="กรอกรหัสผ่านอีกครั้ง" required>
                            <button type="button" class="auth-eye-btn" aria-label="Toggle password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button type="submit" class="auth-submit">สมัครสมาชิก</button>
            </form>

            <div class="auth-divider">
                <span>หรือ</span>
            </div>

            <a href="<?php echo BASE_URL; ?>index.php?page=login" class="auth-alt-btn">มีบัญชีแล้ว? เข้าสู่ระบบ</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.auth-eye-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = btn.parentElement.querySelector('.auth-password-input');
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
