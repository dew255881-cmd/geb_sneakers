<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GEB SNEAKERS | Admin Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        .dataTables_wrapper { padding: 20px 28px; font-size: 0.85rem; font-family: 'Kanit', sans-serif; }
        .dataTables_wrapper .dataTables_length { float: left; margin-bottom: 20px; }
        .dataTables_wrapper .dataTables_length select { padding: 8px 12px; border: 2px solid var(--gray-200); border-radius: 10px; outline: none; background: var(--white); cursor: pointer; font-family: var(--font); }
        .dataTables_wrapper .dataTables_length select:focus { border-color: var(--black); }
        .dataTables_wrapper .dataTables_filter { float: right; margin-bottom: 20px; font-weight: 500; }
        .dataTables_wrapper .dataTables_filter input { padding: 10px 18px; border: 2px solid var(--gray-200); border-radius: 50px; margin-left: 10px; outline: none; transition: var(--ease); background: var(--gray-50); font-family: var(--font); }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: var(--black); background: var(--white); box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
        table.dataTable { border-collapse: collapse !important; border-spacing: 0; width: 100% !important; margin-bottom: 20px !important; }
        table.dataTable thead th, table.dataTable thead td { border-bottom: 2px solid var(--gray-200) !important; padding: 14px 16px; font-weight: 600; text-transform: uppercase; font-size: 0.72rem; text-align: center !important; letter-spacing: 1px; color: var(--gray-500); }
        table.dataTable tbody th, table.dataTable tbody td { text-align: center !important; vertical-align: middle; }
        table.dataTable.no-footer { border-bottom: 1px solid var(--gray-200) !important; }
        .dataTables_wrapper .dataTables_info { padding-top: 20px; color: var(--gray-500); font-size: 0.82rem; clear: both; float: left; }
        .dataTables_wrapper .dataTables_paginate { padding-top: 15px; float: right; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 7px 14px; margin-left: 5px; border: 1px solid transparent; border-radius: 8px; color: var(--gray-600) !important; cursor: pointer; background: transparent; transition: var(--ease); font-size: 0.82rem; font-family: var(--font); }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: var(--gray-100) !important; border-color: var(--gray-200) !important; color: var(--black) !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { background: var(--black) !important; color: var(--white) !important; border-color: var(--black) !important; border-radius: 8px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled { opacity: 0.4; cursor: not-allowed; }
    </style>
</head>
<body>

<header class="admin-header">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="admin-mobile-toggle" onclick="document.querySelector('.admin-sidebar').classList.toggle('open');document.querySelector('.admin-sidebar-overlay').classList.toggle('open');" style="display:none;background:none;border:none;color:#fff;font-size:1.3rem;cursor:pointer;padding:4px;">
            <i class="fas fa-bars"></i>
        </button>
        <div class="admin-header-brand">
            <a href="<?php echo BASE_URL; ?>admin/">GEB <span>SNEAKERS</span> <span style="font-size:0.65rem;opacity:0.5;margin-left:8px;letter-spacing:2px;">ADMIN</span></a>
        </div>
    </div>
    
    <div class="admin-header-actions">
        <?php
        // Pull latest avatar if missing from session
        if (!isset($_SESSION['u_avatar'])) {
            $stmt_avatar = $conn->prepare("SELECT u_avatar FROM tb_users WHERE u_id = ?");
            $stmt_avatar->bind_param("i", $_SESSION['u_id']);
            $stmt_avatar->execute();
            $res_avatar = $stmt_avatar->get_result()->fetch_assoc();
            $_SESSION['u_avatar'] = $res_avatar['u_avatar'] ?? 'default_avatar.png';
        }
        $adminAvatar = $_SESSION['u_avatar'] ?: 'default_avatar.png';
        $avatarPath = UPLOAD_URL . 'avatars/' . $adminAvatar;
        ?>
        <span>
            <img src="<?php echo $avatarPath; ?>" alt="Admin" class="admin-user-avatar">
            สวัสดี, <?php echo sanitize($_SESSION['u_fullname'] ?? 'Admin'); ?>
        </span>
        <a href="<?php echo BASE_URL; ?>" target="_blank"><i class="fas fa-external-link-alt"></i> ดูหน้าเว็บ</a>
        <a href="<?php echo BASE_URL; ?>index.php?page=logout" title="ออกจากระบบ"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</header>

<div class="admin-sidebar-overlay" onclick="document.querySelector('.admin-sidebar').classList.remove('open');this.classList.remove('open');"></div>
