<?php
$resultTotalSales = $conn->query("SELECT SUM(o_total) as total FROM tb_orders WHERE o_status IN ('confirmed', 'shipped', 'done')");
$totalSales = $resultTotalSales->fetch_assoc()['total'] ?: 0;

$resultTotalOrders = $conn->query("SELECT COUNT(*) as count FROM tb_orders");
$totalOrders = $resultTotalOrders->fetch_assoc()['count'];

$resultPendingOrders = $conn->query("SELECT COUNT(*) as count FROM tb_orders WHERE o_status = 'pending'");
$pendingOrders = $resultPendingOrders->fetch_assoc()['count'];

$resultLowStock = $conn->query("SELECT COUNT(*) as count FROM tb_stock WHERE qty <= 2 AND qty > 0");
$lowStock = $resultLowStock->fetch_assoc()['count'];

$resultRecentOrders = $conn->query("SELECT * FROM tb_orders ORDER BY o_id DESC LIMIT 5");
$recentOrders = $resultRecentOrders->fetch_all(MYSQLI_ASSOC);
?>

<h1 class="admin-page-title">ภาพรวมระบบ</h1>

<div class="stats-grid">
    <a href="?page=orders" class="stat-card">
        <div class="stat-icon" style="color:var(--success);background:#f0fdf4;"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-value"><?php echo number_format($totalSales, 2); ?></div>
        <div class="stat-label">ยอดขายรวมสุทธิ (฿)</div>
    </a>
    
    <a href="?page=orders" class="stat-card">
        <div class="stat-icon" style="color:var(--info);background:#eff6ff;"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-value"><?php echo $totalOrders; ?></div>
        <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
    </a>
    
    <a href="?page=orders" class="stat-card">
        <div class="stat-icon" style="color:var(--warning);background:#fff7ed;"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?php echo $pendingOrders; ?></div>
        <div class="stat-label">รอดำเนินการ</div>
    </a>
    
    <a href="?page=stock" class="stat-card">
        <div class="stat-icon" style="color:var(--error);background:#fef2f2;"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-value"><?php echo $lowStock; ?></div>
        <div class="stat-label">สินค้าใกล้หมด (สต็อก <= 2)</div>
    </a>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>คำสั่งซื้อล่าสุด</h3>
        <a href="?page=orders" class="btn btn-sm btn-secondary">ดูทั้งหมด</a>
    </div>
    <div class="admin-card-body" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>ลูกค้า</th>
                    <th>ยอดรวม</th>
                    <th>วันเวลา</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $recentOrder): ?>
                <tr>
                    <td>#<?php echo str_pad($recentOrder['o_id'], 5, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo sanitize($recentOrder['o_fullname']); ?></td>
                    <td><?php echo formatPrice($recentOrder['o_total']); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($recentOrder['created_at'])); ?></td>
                    <td><?php echo getStatusBadge($recentOrder['o_status']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
