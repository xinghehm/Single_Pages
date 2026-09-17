<?php
require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$proId = $_GET['pro'] ?? '';
if (!isProjectOwner($proId, $user['id'])) die('无权访问');
$project = getProjectByProId($proId);
$files = getProjectFiles($proId);
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['name']) ?> - 项目详情</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
<?= outputUserBgStyle() ?>
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
            <div><div class="font-bold"><?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></div><div class="text-xs text-gray-500">Pages</div></div>
        </div>
        <nav class="space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 控制台</a>
            <a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 我的项目</a><a href="buy_group.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-vip-crown-line"></i> 购买用户组</a>
        </nav>
    </div>
    <div><a href="../logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>
<main class="flex-1 p-6">
    <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
        <div>
            <a href="projects.php" class="text-gray-500 hover:text-blue-600"><i class="ri-arrow-left-line"></i> 返回项目列表</a>
            <h1 class="text-2xl font-bold mt-2"><?= htmlspecialchars($project['name']) ?></h1>
        </div>
        <div class="flex gap-3 flex-wrap">
           <a href="/<?= $proId ?>" target="_blank" class="btn-secondary">预览站点</a>
            <a href="upload_zip.php?pro=<?= $proId ?>" class="btn-secondary"><i class="ri-file-zip-line"></i> 上传 ZIP</a>
            <a href="upload_file.php?pro=<?= $proId ?>" class="btn-secondary"><i class="ri-image-line"></i> 上传文件</a>
            <a href="file_new.php?pro=<?= $proId ?>" class="btn-primary"><i class="ri-add-line"></i> 新建文件</a>
        </div>
    </div>
    <div class="glass-card p-4">
        <div class="flex items-center gap-2 text-gray-600 mb-4"><i class="ri-file-list-line"></i> 文件列表</div>
        <?php if (empty($files)): ?>
            <div class="text-center py-12"><i class="ri-file-line text-6xl text-gray-300 mb-4"></i><p>暂无文件，点击新建文件</p></div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-gray-500 border-b"><tr><th class="text-left p-3">文件名</th><th class="text-left p-3">首页</th><th class="text-left p-3">更新时间</th><th class="text-left p-3">操作</th></tr></thead>
                    <tbody>
                    <?php foreach ($files as $file): ?>
                        <tr class="border-t">
                            <td class="p-3"><?= htmlspecialchars($file['filename']) ?></td>
                            <td class="p-3"><?= $file['is_index'] ? '<span class="bg-green-100 text-green-600 px-2 py-0.5 rounded text-xs">首页</span>' : '' ?></td>
                            <td class="p-3"><?= date('Y-m-d H:i', $file['updated_at']) ?></td>
                            <td class="p-3 space-x-2">
                                <a href="file_edit.php?pro=<?= $proId ?>&file=<?= urlencode($file['filename']) ?>" class="text-blue-600">编辑</a>
                                <?php if (!$file['is_index']): ?>
                                    <a href="file_delete.php?pro=<?= $proId ?>&file=<?= urlencode($file['filename']) ?>" onclick="return confirm('确定删除？')" class="text-red-500">删除</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>