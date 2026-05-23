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
</head>
<body class="flex min-h-screen">
<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div>
        <div class="flex items-center gap-2 mb-8">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div>
            <div><div class="font-bold"><?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></div><div class="text-xs text-gray-500">Admin</div></div>
        </div>
        <nav class="space-y-2">
            <a href="index.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 仪表盘</a>
            <a href="users.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-user-line"></i> 用户管理</a>
            <a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 项目管理</a>
            <a href="config.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-settings-line"></i> 网站配置</a>
            <a href="change_password.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-lock-line"></i> 修改密码</a>
        </nav>
    </div>
    <div><a href="logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>
<main class="flex-1 p-6">
    <div class="glass-card max-w-md mx-auto p-6">
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