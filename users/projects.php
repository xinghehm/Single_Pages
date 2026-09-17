<?php require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$projects = getUserProjects($user['id']);
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>我的项目 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><link rel="stylesheet" href="../style.css"><?= outputUserBgStyle() ?>
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
    <div><div class="flex items-center gap-2 mb-8"><div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div><div><div class="font-bold"><?= htmlspecialchars($config['site_name']) ?></div><div class="text-xs text-gray-500">Pages</div></div></div>
    <nav class="space-y-2"><a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 控制台</a><a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-folder-line"></i> 我的项目</a><a href="buy_group.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-vip-crown-line"></i> 购买用户组</a><a href="profile.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-user-line"></i> 个人中心</a></nav></div>
    <div><a href="../logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>
<main class="flex-1 p-6"><div class="flex justify-between items-center mb-6 flex-wrap gap-3"><div><h1 class="text-2xl font-bold">我的项目</h1><p class="text-gray-500">共 <?= count($projects) ?> 个项目</p></div><button onclick="newProject()" class="btn-primary"><i class="ri-add-line"></i> 新建项目</button></div>
<div class="glass-card p-4"><?php if (empty($projects)): ?><div class="text-center py-12"><i class="ri-folder-line text-6xl text-gray-300 mb-4"></i><p>暂无项目，点击右上角创建</p></div><?php else: ?><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"><?php foreach ($projects as $pro): ?><div class="bg-white/60 dark:bg-gray-800/60 rounded-xl p-4 hover:shadow-lg transition"><div class="font-bold text-lg"><?= htmlspecialchars($pro['name']) ?></div><div class="text-gray-500 text-xs mb-3"><?= date('Y-m-d', $pro['created_at']) ?></div><div class="flex justify-between items-center"><span class="text-xs text-green-600 flex items-center gap-1"><span class="w-2 h-2 bg-green-500 rounded-full"></span> 在线</span><div class="space-x-2"><a href="project_detail.php?pro=<?= $pro['pro_id'] ?>" class="text-blue-600 text-sm">管理</a><a href="project_delete.php?pro=<?= $pro['pro_id'] ?>" onclick="return confirm('删除项目不可恢复')" class="text-red-500 text-sm">删除</a></div></div></div><?php endforeach; ?></div><?php endif; ?></div>
<script>function newProject() { let name = prompt("请输入项目名（仅英文数字）", ""); if (name && /^[a-zA-Z0-9]+$/.test(name)) location.href = "project_new.php?name=" + encodeURIComponent(name); else alert("项目名只能包含英文数字"); }</script>
</body>
</html>