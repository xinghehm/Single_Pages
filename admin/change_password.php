<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../functions.php';

$pdo = getDB();
$error = '';
$success = '';

// 获取当前管理员信息
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证旧密码
    if (!password_verify($old_password, $admin['password'])) {
        $error = '原密码错误';
    } elseif (strlen($new_password) < 6) {
        $error = '新密码至少6位';
    } elseif ($new_password !== $confirm_password) {
        $error = '两次输入的新密码不一致';
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hash, $_SESSION['admin_id']])) {
            $success = '密码修改成功，请重新登录';
            // 退出登录
            session_destroy();
            header('refresh:2;url=login.php');
            exit;
        } else {
            $error = '修改失败，请重试';
        }
    }
}

$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密码 - <?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
<style>
/* 移动端顶部导航 - 内联防止CSS丢失 */
.mobile-topbar {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(0,0,0,0.06);
    z-index: 999;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
}
.mobile-topbar .mobile-logo {
    display: flex; align-items: center; gap: 8px;
    font-weight: 600; font-size: 15px;
}
.mobile-topbar .logo-icon {
    width: 32px; height: 32px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 18px;
}
.hamburger-btn {
    width: 40px; height: 40px;
    background: rgba(0,0,0,0.05);
    border: none; border-radius: 10px;
    cursor: pointer;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 5px;
    transition: all 0.3s ease;
}
.hamburger-btn span {
    display: block;
    width: 20px; height: 2px;
    background: #333;
    border-radius: 2px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hamburger-btn.active span:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}
.hamburger-btn.active span:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
}
.hamburger-btn.active span:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(2px);
    z-index: 998;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.sidebar-overlay.show {
    display: block;
    opacity: 1;
}
@media (max-width: 768px) {
    .mobile-topbar { display: flex; }
    .sidebar {
        position: fixed !important;
        left: -280px !important;
        top: 0; bottom: 0;
        z-index: 1000 !important;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        width: 280px !important;
        box-shadow: 4px 0 24px rgba(0,0,0,0.12);
        padding-top: 70px !important;
    }
    .sidebar.show { left: 0 !important; }
    main {
        padding: 16px !important;
        padding-top: 72px !important;
        min-width: 0 !important;
        overflow-x: hidden !important;
    }
    body { overflow-x: hidden !important; }
    .overflow-x-auto {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        width: 100% !important;
    }
    .overflow-x-auto table {
        min-width: 600px !important;
        width: auto !important;
    }
}
</style>
</head>
<body class="flex min-h-screen">
<div class="mobile-topbar">
    <div class="mobile-logo">
        <div class="logo-icon"><i class="ri-cloud-line"></i></div>
        <span><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></span>
    </div>
    <button class="hamburger-btn" onclick="toggleSidebar(this)" aria-label="菜单">
        <span></span><span></span><span></span>
    </button>
</div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>
function toggleSidebar(btn) {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
    if (btn) btn.classList.toggle('active');
    else document.querySelector('.hamburger-btn').classList.toggle('active');
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sidebar a').forEach(function(a) {
        a.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('show');
            document.querySelector('.sidebar-overlay').classList.remove('show');
            document.querySelector('.hamburger-btn').classList.remove('active');
        });
    });
});
</script>

<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div>
        <div class="flex items-center gap-2 mb-8">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div>
            <div><div class="font-bold"><?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></div><div class="text-xs text-gray-500">Admin</div></div>
        </div>
        <nav class="space-y-2">
            <a href="index.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 仪表盘</a>
            <a href="users.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-user-line"></i> 用户管理</a>
            <a href="groups.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-group-line"></i> 用户组管理</a>
            <a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 项目管理</a>
            <a href="config.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-settings-line"></i> 网站配置</a>
            <a href="change_password.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-lock-line"></i> 修改密码</a>
        </nav>
    </div>
    <div><a href="logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>
<main class="flex-1 p-6" style="min-width: 0; overflow-x: hidden;">
    <div class="glass-card max-w-md mx-auto p-6" style="overflow: hidden;">
        <h2 class="text-xl font-bold mb-6">修改后台密码</h2>
        <p class="text-gray-500 mb-4">当前账号：<strong><?= htmlspecialchars($admin['username']) ?></strong></p>
        
        <?php if ($error): ?>
            <div class="error mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">原密码</label>
                <input type="password" name="old_password" required class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">新密码</label>
                <input type="password" name="new_password" placeholder="至少6位" required class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">确认新密码</label>
                <input type="password" name="confirm_password" required class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
            </div>
            <button type="submit" class="btn-primary w-full py-2">确认修改</button>
        </form>
    </div>
</main>
</body>
</html>