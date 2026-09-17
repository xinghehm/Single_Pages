<?php session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
$pdo = getDB();
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$projectCount = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$fileCount = $pdo->query("SELECT COUNT(*) FROM project_files")->fetchColumn();
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>后台仪表盘 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><link rel="stylesheet" href="../style.css"><style>
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
    <div><div class="flex items-center gap-2 mb-8"><div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div><div><div class="font-bold"><?= htmlspecialchars($config['site_name']) ?></div><div class="text-xs text-gray-500">Admin</div></div></div>
    <nav class="space-y-2"><a href="index.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-dashboard-line"></i> 仪表盘</a><a href="users.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-user-line"></i> 用户管理</a><a href="groups.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-group-line"></i> 用户组管理</a><a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 项目管理</a><a href="config.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-settings-line"></i> 网站配置</a></nav></div>
    <div><a href="logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>
<main class="flex-1 p-6" style="min-width: 0; overflow-x: hidden;"><h1 class="text-2xl font-bold mb-6">👋 晚上好，管理员</h1><div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6"><div class="glass-card p-4 text-center" style="overflow: hidden;"><div class="text-gray-500 text-sm">用户总数</div><div class="text-3xl font-bold"><?= $userCount ?></div></div><div class="glass-card p-4 text-center" style="overflow: hidden;"><div class="text-gray-500 text-sm">项目总数</div><div class="text-3xl font-bold"><?= $projectCount ?></div></div><div class="glass-card p-4 text-center" style="overflow: hidden;"><div class="text-gray-500 text-sm">文件总数</div><div class="text-3xl font-bold"><?= $fileCount ?></div></div><div class="glass-card p-4 text-center" style="overflow: hidden;"><div class="text-gray-500 text-sm">存储总量</div><div class="text-3xl font-bold">-</div></div></div>
<div class="glass-card p-4" style="overflow: hidden;"><h3 class="font-medium mb-4">平台趋势（近7日）</h3><canvas id="trendChart" height="100"></canvas></div>
<script>new Chart(document.getElementById('trendChart'), { type: 'line', data: { labels: ['04-29', '04-30', '05-01', '05-02', '05-03', '05-04', '05-05'], datasets: [{ label: '新增用户', data: [0,0,0,0,0,0,0], borderColor: '#1677ff', tension: 0.3 }, { label: '新增项目', data: [0,0,0,0,0,0,0], borderColor: '#52c41a', tension: 0.3 }] }, options: { responsive: true, plugins: { legend: { position: 'top' } } } });</script>
</body></html>