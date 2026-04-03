<?php
if (!isLoggedIn() || !isAdmin()) {
    die("Access Denied");
}

// Handle AJAX Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];

    if ($action === 'get_user_details') {
        $u_id = (int)$_POST['u_id'];
        
        // 1. Basic Info
        $stmt = $conn->prepare("SELECT u_id, u_username, u_fullname, u_tel, u_level, u_status, created_at, u_avatar FROM tb_users WHERE u_id = ?");
        $stmt->bind_param("i", $u_id);
        $stmt->execute();
        $userData = $stmt->get_result()->fetch_assoc();

        if (!$userData) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ใช้']);
            exit;
        }

        // 2. Addresses
        $stmtAddr = $conn->prepare("SELECT * FROM tb_addresses WHERE u_id = ? ORDER BY is_default DESC");
        $stmtAddr->bind_param("i", $u_id);
        $stmtAddr->execute();
        $addresses = $stmtAddr->get_result()->fetch_all(MYSQLI_ASSOC);

        // 3. Recent Orders (Last 5)
        $stmtOrders = $conn->prepare("SELECT o_id, o_total, o_status, created_at FROM tb_orders WHERE u_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmtOrders->bind_param("i", $u_id);
        $stmtOrders->execute();
        $orders = $stmtOrders->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'user' => $userData,
            'addresses' => $addresses,
            'orders' => $orders
        ]);
        exit;
    }

    if ($action === 'toggle_status') {
        $u_id = (int)$_POST['u_id'];
        
        // Safety Checks
        if ($u_id === (int)$_SESSION['u_id']) {
            echo json_encode(['success' => false, 'message' => 'คุณไม่สามารถระงับบัญชีของตัวเองได้']);
            exit;
        }

        $stmtCheck = $conn->prepare("SELECT u_level, u_status, u_username FROM tb_users WHERE u_id = ?");
        $stmtCheck->bind_param("i", $u_id);
        $stmtCheck->execute();
        $target = $stmtCheck->get_result()->fetch_assoc();

        if ($target['u_level'] === 'admin') {
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถระงับบัญชีของแอดมินคนอื่นได้']);
            exit;
        }

        $newStatus = ($target['u_status'] === 'suspended') ? 'active' : 'suspended';
        $stmtUpdate = $conn->prepare("UPDATE tb_users SET u_status = ? WHERE u_id = ?");
        $stmtUpdate->bind_param("si", $newStatus, $u_id);
        
        if ($stmtUpdate->execute()) {
            echo json_encode([
                'success' => true, 
                'new_status' => $newStatus, 
                'message' => ($newStatus === 'active' ? 'เปิดใช้งานบัญชีเรียบร้อย' : 'ระงับบัญชีเรียบร้อย')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตสถานะ']);
        }
        exit;
    }

    if ($action === 'reset_password') {
        $u_id = (int)$_POST['u_id'];
        
        // Generate a random password (8 chars)
        $charPool = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $newPassword = substr(str_shuffle($charPool), 0, 10);
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmtReset = $conn->prepare("UPDATE tb_users SET u_password = ? WHERE u_id = ?");
        $stmtReset->bind_param("si", $hashedPassword, $u_id);
        
        if ($stmtReset->execute()) {
            echo json_encode([
                'success' => true, 
                'new_password' => $newPassword,
                'message' => 'รีเซ็ตรหัสผ่านสำเร็จ'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน']);
        }
        exit;
    }
}

// Main Page View
$users = $conn->query("
    SELECT u.*, 
    (SELECT COUNT(o_id) FROM tb_orders WHERE tb_orders.u_id = u.u_id) as order_count 
    FROM tb_users u 
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-page-header">
    <h2 class="admin-page-title">จัดการผู้ใช้</h2>
</div>

<div class="admin-card">
    <div class="admin-card-body">
        <table id="usersTable" class="display admin-table" style="width:100%">
            <thead>
                <tr>
                    <th>ผู้ใช้</th>
                    <th>Username</th>
                    <th>เบอร์โทร</th>
                    <th>ระดับ</th>
                    <th>คำสั่งซื้อ</th>
                    <th>สถานะ</th>
                    <th>วันที่สมัคร</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <img src="<?php echo UPLOAD_URL . 'avatars/' . ($u['u_avatar'] ?: 'default_avatar.png'); ?>" 
                                 style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
                            <span><?php echo sanitize($u['u_fullname']); ?></span>
                        </div>
                    </td>
                    <td>@<?php echo sanitize($u['u_username']); ?></td>
                    <td><?php echo sanitize($u['u_tel'] ?: '-'); ?></td>
                    <td>
                        <span class="badge <?php echo $u['u_level'] === 'admin' ? 'badge-confirmed' : 'badge-pending'; ?>" style="font-size:0.6rem;">
                            <?php echo strtoupper($u['u_level']); ?>
                        </span>
                    </td>
                    <td><strong><?php echo $u['order_count']; ?></strong></td>
                    <td id="status-cell-<?php echo $u['u_id']; ?>">
                        <?php if ($u['u_status'] === 'suspended'): ?>
                            <span class="badge badge-cancelled">ระงับแล้ว</span>
                        <?php else: ?>
                            <span class="badge badge-done">ปกติ</span>
                        <?php endif; ?>
                    </td>
                    <td><span style="font-size:0.75rem; color:var(--gray-500);"><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></span></td>
                    <td>
                        <div class="action-btns">
                            <button onclick="viewUserDetails(<?php echo $u['u_id']; ?>)" class="btn-sm btn-secondary" title="ดูข้อมูล">
                                <i class="fas fa-eye"></i>
                            </button>
                            
                            <?php if ($u['u_id'] != $_SESSION['u_id'] && $u['u_level'] !== 'admin'): ?>
                                <button onclick="toggleUserStatus(<?php echo $u['u_id']; ?>, '<?php echo $u['u_username']; ?>', '<?php echo $u['u_status']; ?>')" 
                                        class="btn-sm <?php echo $u['u_status'] === 'suspended' ? 'btn-success' : 'btn-danger'; ?>" 
                                        id="btn-toggle-<?php echo $u['u_id']; ?>"
                                        title="<?php echo $u['u_status'] === 'suspended' ? 'เปิดใช้งาน' : 'ระงับบัญชี'; ?>">
                                    <i class="fas <?php echo $u['u_status'] === 'suspended' ? 'fa-user-check' : 'fa-user-slash'; ?>"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn-sm btn-secondary" disabled style="opacity:0.3; cursor:not-allowed;" 
                                        title="<?php echo $u['u_id'] == $_SESSION['u_id'] ? 'ไม่สามารถจัดการตัวเองได้' : 'ไม่สามารถจัดการแอดมินคนอื่นได้'; ?>">
                                    <i class="fas fa-user-lock"></i>
                                </button>
                            <?php endif; ?>

                            <button onclick="resetUserPassword(<?php echo $u['u_id']; ?>, '<?php echo $u['u_username']; ?>')" class="btn-sm btn-warning" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal User Details -->
<div id="userDetailModal" class="modal-overlay">
    <div class="modal-box" style="max-width:700px;">
        <button class="modal-close" onclick="closeModal('userDetailModal')">&times;</button>
        <h3><i class="fas fa-user-circle"></i> รายละเอียดผู้ใช้</h3>
        
        <div id="userDetailContent">
            <!-- Dynamic Content -->
        </div>
    </div>
</div>

<!-- Modal Reset Success (Show Once) -->
<div id="resetSuccessModal" class="modal-overlay">
    <div class="modal-box" style="max-width:400px; text-align:center;">
        <div style="font-size:3rem; color:var(--success); margin-bottom:15px;"><i class="fas fa-check-circle"></i></div>
        <h3 style="justify-content:center; margin-bottom:10px;">Reset สำเร็จ!</h3>
        <p style="font-size:0.9rem; color:var(--gray-600); margin-bottom:20px;">รหัสผ่านใหม่สำหรับคุณ <strong id="resetTargetUser"></strong> คือ:</p>
        
        <div style="background:var(--gray-100); padding:15px; border-radius:12px; margin-bottom:15px; position:relative; display:flex; align-items:center; justify-content:center; border:2px dashed var(--gray-300);">
            <span id="newPasswordText" style="font-family:monospace; font-size:1.4rem; font-weight:700; letter-spacing:2px; color:var(--black);">XXXXXXXX</span>
            <button onclick="copyNewPassword()" style="margin-left:15px; background:var(--black); color:var(--white); border:none; padding:8px 12px; border-radius:8px; cursor:pointer; font-size:0.8rem;">
                <i class="fas fa-copy"></i> คัดลอก
            </button>
        </div>
        
        <p style="font-size:0.75rem; color:var(--gray-500); line-height:1.4;">
            <i class="fas fa-exclamation-triangle" style="color:var(--warning);"></i> 
            Password นี้จะไม่ถูกแสดงอีก กรุณาคัดลอกและแจ้งลูกค้าทันทีก่อนปิดหน้านี้
        </p>
        
        <button onclick="closeModal('resetSuccessModal')" class="btn btn-primary btn-block mt-20">ปิดหน้าต่างนี้</button>
    </div>
</div>

<script>
// Specialized JS for User Management page
// NOTE: jQuery and DataTables are already loaded in footer.php

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

function viewUserDetails(u_id) {
    $.post('', { ajax_action: 'get_user_details', u_id: u_id }, function(res) {
        if (res.success) {
            const u = res.user;
            let html = `
                <div style="display:flex; gap:20px; margin-bottom:30px; align-items:center;">
                    <img src="<?php echo UPLOAD_URL; ?>avatars/${u.u_avatar || 'default_avatar.png'}" style="width:80px; height:80px; border-radius:50%; border:3px solid var(--gray-100); object-fit:cover;">
                    <div>
                        <h4 style="font-size:1.2rem; margin-bottom:4px;">${u.u_fullname}</h4>
                        <p style="color:var(--gray-500); font-size:0.9rem;">@${u.u_username} | ${u.u_tel || 'ไม่มีเบอร์โทร'}</p>
                        <div style="margin-top:8px;">
                            <span class="badge ${u.u_status === 'active' ? 'badge-done' : 'badge-cancelled'}">${u.u_status === 'active' ? 'ปกติ' : 'ระงับแล้ว'}</span>
                            <span class="badge badge-confirmed">${u.u_level.toUpperCase()}</span>
                        </div>
                    </div>
                </div>

                <div class="admin-row">
                    <div class="admin-col-main">
                        <h5 style="margin-bottom:15px; border-bottom:1px solid var(--gray-100); padding-bottom:10px;">
                            <i class="fas fa-map-marker-alt"></i> สมุดที่อยู่
                        </h5>
                        <div style="display:flex; flex-direction:column; gap:10px; max-height:300px; overflow-y:auto;">
                            ${res.addresses.length > 0 ? res.addresses.map(a => `
                                <div style="border:1px solid var(--gray-200); padding:12px; border-radius:10px; background:var(--gray-50); position:relative;">
                                    ${a.is_default ? '<span style="position:absolute; top:8px; right:8px; font-size:0.6rem; background:var(--black); color:var(--white); padding:2px 6px; border-radius:4px;">ค่าเริ่มต้น</span>' : ''}
                                    <div style="font-weight:600; font-size:0.85rem; margin-bottom:4px;">[${a.addr_label}] ${a.addr_fullname}</div>
                                    <div style="font-size:0.8rem; color:var(--gray-600); line-height:1.4;">${a.addr_detail}<br>โทร: ${a.addr_phone}</div>
                                </div>
                            `).join('') : '<p style="color:var(--gray-400); font-size:0.85rem;">ยังไม่มีข้อมูลที่อยู่</p>'}
                        </div>
                    </div>
                    <div class="admin-col-side">
                        <h5 style="margin-bottom:15px; border-bottom:1px solid var(--gray-100); padding-bottom:10px;">
                            <i class="fas fa-shopping-bag"></i> ประวัติล่าสุด
                        </h5>
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            ${res.orders.length > 0 ? res.orders.map(o => `
                                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid var(--gray-100);">
                                    <div style="font-size:0.8rem;">
                                        <strong>#${o.o_id}</strong><br>
                                        <span style="color:var(--gray-500); font-size:0.7rem;">${new Date(o.created_at).toLocaleDateString()}</span>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-weight:700; font-size:0.85rem;">฿${Number(o.o_total).toLocaleString()}</div>
                                        <span class="badge badge-${o.o_status}" style="font-size:0.6rem;">${o.o_status}</span>
                                    </div>
                                </div>
                            `).join('') : '<p style="color:var(--gray-400); font-size:0.85rem;">ไม่มีประวัติ</p>'}
                        </div>
                    </div>
                </div>
            `;
            $('#userDetailContent').html(html);
            $('#userDetailModal').addClass('open');
        }
    });
}

function toggleUserStatus(u_id, username, currentStatus) {
    const actionText = currentStatus === 'suspended' ? 'เปิดใช้งาน' : 'ระงับการใช้งาน';
    if (confirm(`ยืนยัน${actionText}บัญชีของ @${username}?`)) {
        $.post('', { ajax_action: 'toggle_status', u_id: u_id }, function(res) {
            if (res.success) {
                location.reload(); 
            } else {
                alert(res.message);
            }
        });
    }
}

function resetUserPassword(u_id, username) {
    if (confirm(`คุณต้องการยืนยันการ Reset Password ของ @${username} ใช่หรือไม่?\n(รหัสผ่านเดิมจะถูกเปลี่ยนทันที)`)) {
        $.post('', { ajax_action: 'reset_password', u_id: u_id }, function(res) {
            if (res.success) {
                $('#resetTargetUser').text(`@${username}`);
                $('#newPasswordText').text(res.new_password);
                $('#resetSuccessModal').addClass('open');
            } else {
                alert(res.message);
            }
        });
    }
}

function copyNewPassword() {
    const text = $('#newPasswordText').text();
    navigator.clipboard.writeText(text).then(() => {
        const btn = $('#resetSuccessModal button:contains("คัดลอก")');
        const originalText = btn.html();
        btn.html('<i class="fas fa-check"></i> คัดลอกแล้ว!').css('background', 'var(--success)');
        setTimeout(() => {
            btn.html(originalText).css('background', '');
        }, 2000);
    });
}

// Special override for Newest First ordering if footer already initialized
$(document).ready(function() {
    // If footer initialized it, we might need to re-sort or destroy/re-init
    // But since this page loads after footer.php's simple init usually,
    // we can check if it's already a DataTable.
    if ($.fn.DataTable.isDataTable('#usersTable')) {
        $('#usersTable').DataTable().order([6, 'desc']).draw();
    }
});
</script>
