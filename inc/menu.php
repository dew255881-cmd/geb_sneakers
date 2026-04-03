<?php
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'home';
$cartCount = getCartCount();
?>
<nav class="navbar">
    <a href="<?php echo BASE_URL; ?>" class="navbar-brand">GEB <span>SNEAKERS</span></a>

    <div class="navbar-center">
        <a href="<?php echo BASE_URL; ?>" class="<?php echo $currentPage === 'home' ? 'active' : ''; ?>">หน้าแรก</a>
        <a href="<?php echo BASE_URL; ?>index.php?page=products" class="<?php echo $currentPage === 'products' ? 'active' : ''; ?>">สินค้า</a>
        <a href="<?php echo BASE_URL; ?>index.php?page=size_guide" class="<?php echo $currentPage === 'size_guide' ? 'active' : ''; ?>">ตารางไซส์</a>
    </div>

    <div class="navbar-right">
        <form action="<?php echo BASE_URL; ?>index.php" method="GET" class="search-form" id="mainSearchForm">
            <input type="hidden" name="page" value="products">
            <div class="search-box" id="searchBox">
                <input type="text" name="search" class="search-input" id="searchInput" placeholder="ค้นหาสินค้า..." value="<?php echo sanitize($_GET['search'] ?? ''); ?>">
                <button type="button" class="search-btn" id="searchBtn"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchBtn = document.getElementById('searchBtn');
            const searchInput = document.getElementById('searchInput');
            const searchBox = document.getElementById('searchBox');
            const searchForm = document.getElementById('mainSearchForm');

            searchBtn.addEventListener('click', (e) => {
                if (!searchBox.classList.contains('active')) {
                    searchBox.classList.add('active');
                    searchInput.focus();
                } else {
                    if (searchInput.value.trim() !== '') {
                        searchForm.submit();
                    } else {
                        searchBox.classList.remove('active');
                        searchInput.blur();
                    }
                }
            });

            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (searchInput.value.trim() !== '') {
                        searchForm.submit();
                    }
                }
            });

            document.addEventListener('click', (e) => {
                if (!searchBox.contains(e.target) && searchInput.value.trim() === '') {
                    searchBox.classList.remove('active');
                }
            });

            if (searchInput.value.trim() !== '') {
                searchBox.classList.add('active');
            }
        });
        </script>
        <?php if (isLoggedIn()): ?>
            <?php
            // Pull latest avatar if missing from session (for currently logged in users)
            if (!isset($_SESSION['u_avatar'])) {
                $stmt_avatar = $conn->prepare("SELECT u_avatar FROM tb_users WHERE u_id = ?");
                $stmt_avatar->bind_param("i", $_SESSION['u_id']);
                $stmt_avatar->execute();
                $res_avatar = $stmt_avatar->get_result()->fetch_assoc();
                $_SESSION['u_avatar'] = $res_avatar['u_avatar'] ?? 'default_avatar.png';
            }
            $userAvatar = $_SESSION['u_avatar'] ?: 'default_avatar.png';
            $avatarPath = UPLOAD_URL . 'avatars/' . $userAvatar;
            ?>
            <a href="<?php echo BASE_URL; ?>index.php?page=my_orders" class="nav-text">คำสั่งซื้อ</a>
            <a href="<?php echo BASE_URL; ?>index.php?page=cart" class="cart-link">
                ตะกร้า
                <span class="cart-badge" id="cart-badge" <?php echo ($cartCount > 0) ? '' : 'style="display:none;"'; ?>><?php echo $cartCount; ?></span>
            </a>
            <?php if (isAdmin()): ?>
                <a href="<?php echo BASE_URL; ?>admin/" class="nav-text">แอดมิน</a>
            <?php endif; ?>
            <div class="user-dropdown">
                <button class="user-dropdown-toggle" id="userDropdownBtn">
                    <?php if ($userAvatar && $userAvatar !== 'default_avatar.png'): ?>
                        <img src="<?php echo $avatarPath; ?>" alt="Profile">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                    <span><?php echo sanitize($_SESSION['u_fullname'] ?? $_SESSION['u_username']); ?></span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div class="user-dropdown-menu" id="userDropdownMenu">
                    <a href="<?php echo BASE_URL; ?>index.php?page=profile" class="dropdown-item">
                        <i class="fas fa-user"></i> โปรไฟล์ส่วนตัว
                    </a>
                    <a href="<?php echo BASE_URL; ?>index.php?page=my_orders" class="dropdown-item">
                        <i class="fas fa-box"></i> คำสั่งซื้อของฉัน
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo BASE_URL; ?>index.php?page=logout" class="dropdown-item dropdown-item-danger">
                        <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                    </a>
                </div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var btn = document.getElementById('userDropdownBtn');
                var menu = document.getElementById('userDropdownMenu');
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    menu.classList.toggle('show');
                    btn.classList.toggle('active');
                });
                document.addEventListener('click', function(e) {
                    if (!btn.contains(e.target) && !menu.contains(e.target)) {
                        menu.classList.remove('show');
                        btn.classList.remove('active');
                    }
                });
            });
            </script>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>index.php?page=login" class="nav-text">เข้าสู่ระบบ</a>
            <a href="<?php echo BASE_URL; ?>index.php?page=register" class="nav-text">สมัครสมาชิก</a>
        <?php endif; ?>
        <button class="mobile-toggle">&#9776;</button>
    </div>
</nav>

<div class="mobile-menu">
    <div class="mobile-search" style="padding: 0 0 20px 0;">
        <form action="<?php echo BASE_URL; ?>index.php" method="GET" class="mobile-search-form">
            <input type="hidden" name="page" value="products">
            <div class="mobile-search-box" style="position:relative;">
                <input type="text" name="search" placeholder="ค้นหารองเท้าที่คุณต้องการ..." style="width:100%; padding:12px 40px 12px 16px; background:var(--gray-900); border:1px solid var(--gray-800); color:var(--white); border-radius:8px; font-family:var(--font);" value="<?php echo sanitize($_GET['search'] ?? ''); ?>">
                <button type="submit" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--gray-400); cursor:pointer;"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
    <a href="<?php echo BASE_URL; ?>">หน้าแรก</a>
    <a href="<?php echo BASE_URL; ?>index.php?page=products">สินค้าทั้งหมด</a>
    <a href="<?php echo BASE_URL; ?>index.php?page=size_guide">ตารางไซส์</a>
    <?php if (isLoggedIn()): ?>
        <a href="<?php echo BASE_URL; ?>index.php?page=profile">โปรไฟล์ส่วนตัว</a>
        <a href="<?php echo BASE_URL; ?>index.php?page=cart">ตะกร้า (<?php echo $cartCount; ?>)</a>
        <a href="<?php echo BASE_URL; ?>index.php?page=my_orders">คำสั่งซื้อ</a>
        <?php if (isAdmin()): ?>
            <a href="<?php echo BASE_URL; ?>admin/">แอดมิน</a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>index.php?page=logout">ออกจากระบบ</a>
    <?php else: ?>
        <a href="<?php echo BASE_URL; ?>index.php?page=login">เข้าสู่ระบบ</a>
        <a href="<?php echo BASE_URL; ?>index.php?page=register">สมัครสมาชิก</a>
    <?php endif; ?>
</div>

<?php
$flash = getFlash();
if ($flash):
?>
<div class="container">
    <div class="alert alert-<?php echo $flash['type']; ?> flash-message" style="margin-top:20px;">
        <?php echo $flash['message']; ?>
    </div>
</div>
<?php endif; ?>
