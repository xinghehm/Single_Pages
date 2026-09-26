<?php
require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$proId = $_GET['pro'] ?? '';
if (!isProjectOwner($proId, $user['id'])) die('无权操作');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file'])) {
        $error = '请选择文件';
    } else {
        $check = validateUploadFile($_FILES['file']);
        if (!$check['valid']) {
            $error = $check['message'];
        } else {
            $destDir = __DIR__ . "/../users/{$user['username']}/projects/$proId";
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);

            $subdir = trim($_POST['subdir'] ?? '', '/');
            if ($subdir && strpos($subdir, '..') === false) {
                $destDir .= '/' . $subdir;
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            }

            $filename = basename($_FILES['file']['name']);
            $target = $destDir . '/' . $filename;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                $content = file_get_contents($target);
                $relative = ($subdir ? $subdir . '/' : '') . $filename;
                addOrUpdateFile($proId, $relative, $content, 0);
                $success = "文件 {$filename} 上传成功";
            } else {
                $error = '文件保存失败';
            }
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
    <title>上传文件</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=245">

<style>
/* 移动端顶部导航 - 内联防止CSS丢失 */
.mobile-topbar {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    background: #fff;
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
    background: #2563eb;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 18px;
}
.hamburger-btn {
    width: 40px; height: 40px;
    background: #f3f4f6;
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
<body class="min-h-screen flex items-center justify-center p-4">
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

    <div class="glass-card max-w-md w-full p-6">
        <h2 class="text-xl font-bold mb-4"><i class="ri-upload-2-line"></i> 上传文件</h2>
        <?php if ($error): ?><div class="error mb-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success mb-4"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm mb-4">
            <p class="font-medium mb-1">支持的文件类型：</p>
            <p class="text-gray-600">图片：jpg / jpeg / png / gif / webp / svg / ico</p>
            <p class="text-gray-600">样式脚本：css / js / json</p>
            <p class="text-gray-600">字体：woff / woff2 / ttf / eot</p>
            <p class="text-gray-600">其他：txt / xml / pdf / mp3 / mp4</p>
            <p class="text-red-500 mt-2">禁止上传：apk / exe / php / sh 等可执行文件</p>
        </div>

        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">选择文件</label>
                <input type="file" name="file" required
                       class="w-full px-4 py-2 rounded-lg bg-white border border-gray-300">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">子目录（可选）</label>
                <input type="text" name="subdir" placeholder="如 assets/images"
                       class="w-full px-4 py-2 rounded-lg">
                <p class="text-xs text-gray-500 mt-1">留空则放在项目根目录</p>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">上传</button>
                <a href="project_detail.php?pro=<?= $proId ?>" class="btn-secondary">取消</a>
            </div>
        </form>
    </div>
</body>
</html>