<?php
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

$resultOrders = $conn->query("SELECT COUNT(*) as count FROM tb_orders WHERE o_status = 'pending'");
$pendingOrdersCount = $resultOrders->fetch_assoc()['count'];

$resultPayments = $conn->query("SELECT COUNT(*) as count FROM tb_payments WHERE pay_status = 'pending'");
$pendingPaymentsCount = $resultPayments->fetch_assoc()['count'];

$resultSuspendedUsers = $conn->query("SELECT COUNT(*) as count FROM tb_users WHERE u_status = 'suspended'");
$suspendedUsersCount = $resultSuspendedUsers->fetch_assoc()['count'];
?>

<aside class="admin-sidebar">
    <nav class="sidebar-nav">
        <a href="?page=dashboard" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-chart-pie"></i></span> ภาพรวมระบบ
        </a>
        <a href="?page=brands" class="<?php echo $currentPage === 'brands' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-tags"></i></span> จัดการแบรนด์
        </a>
        <a href="?page=products" class="<?php echo $currentPage === 'products' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-box-open"></i></span> จัดการสินค้า
        </a>
        <a href="?page=colors" class="<?php echo $currentPage === 'colors' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-palette"></i></span> จัดการสีสินค้า
        </a>
        <a href="?page=stock" class="<?php echo $currentPage === 'stock' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-cubes"></i></span> จัดการสต็อกไซส์
        </a>
        <a href="?page=orders" class="<?php echo $currentPage === 'orders' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-shopping-cart"></i></span> คำสั่งซื้อ
            <?php if ($pendingOrdersCount > 0): ?>
                <span class="sidebar-badge"><?php echo $pendingOrdersCount; ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=users" class="<?php echo $currentPage === 'users' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-users"></i></span> จัดการผู้ใช้
            <?php if ($suspendedUsersCount > 0): ?>
                <span class="sidebar-badge" style="background:var(--error);"><?php echo $suspendedUsersCount; ?></span>
            <?php endif; ?>
        </a>
    </nav>
</aside>
