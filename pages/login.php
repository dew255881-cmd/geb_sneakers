<?php
if (isLoggedIn()) {
    header("Location: " . BASE_URL);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'กรุณากรอกข้อมูลให้ครบ';
        } else {
            $stmt = $conn->prepare("SELECT * FROM tb_users WHERE u_username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['u_password'])) {
                if (($user['u_status'] ?? 'active') === 'suspended') {
                    $error = 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อแอดมิน';
                } else {
                    $_SESSION['u_id'] = $user['u_id'];
                    $_SESSION['u_username'] = $user['u_username'];
                    $_SESSION['u_fullname'] = $user['u_fullname'];
                    $_SESSION['u_avatar'] = $user['u_avatar'];
                    $_SESSION['u_level'] = $user['u_level'];

                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirectPage = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        header("Location: " . BASE_URL . "index.php?page=" . $redirectPage);
                        exit;
                    }

                    header("Location: " . BASE_URL);
                    exit;
                }
            } else {
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        }
    }
}
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-card-inner">
            <div class="auth-logo">GEB <span>SNEAKERS</span></div>
            <h1 class="auth-title">เข้าสู่ระบบ</h1>
            <p class="auth-subtitle">ยินดีต้อนรับกลับมา</p>

            <?php if ($error): ?>
                <div class="auth-alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="auth-field">
                    <label class="auth-label">ชื่อผู้ใช้</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" class="auth-input" placeholder="กรอกชื่อผู้ใช้ของคุณ" value="<?php echo sanitize($_POST['username'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="auth-field">
                    <label class="auth-label">รหัสผ่าน</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="auth-input auth-password-input" placeholder="กรอกรหัสผ่าน" required>
                        <button type="button" class="auth-eye-btn" aria-label="Toggle password">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="auth-submit">เข้าสู่ระบบ</button>
            </form>

            <div class="auth-divider">
                <span>หรือ</span>
            </div>

            <a href="<?php echo BASE_URL; ?>index.php?page=register" class="auth-alt-btn">สร้างบัญชีใหม่</a>
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
