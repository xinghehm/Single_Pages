<?php session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
$pdo = getDB();
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$projectCount = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$fileCount = $pdo->query("SELECT COUNT(*) FROM project_files")->fetchColumn();
$guestCount = $pdo->query("SELECT COUNT(*) FROM guest_projects")->fetchColumn();
$totalVisits = getAllProjectsTotalVisits();
$orderCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(money),0) FROM orders WHERE status=1")->fetchColumn();

// 近7日真实趋势
$trendLabels = []; $newUsers = []; $newProjects = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('m-d', strtotime($date));
    $nu = $pdo->prepare("SELECT COUNT(*) FROM users WHERE FROM_UNIXTIME(registered_at, '%Y-%m-%d') = ?");
    $nu->execute([$date]); $newUsers[] = intval($nu->fetchColumn());
    $np = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE FROM_UNIXTIME(created_at, '%Y-%m-%d') = ?");
    $np->execute([$date]); $newProjects[] = intval($np->fetchColumn());
}
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>后台仪表盘 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><link rel="stylesheet" href="../style.css?v=243"><style>
.mobile-topbar { display: none; position: fixed; top: 0; left: 0; right: 0; height: 56px; background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.06); z-index: 999; align-items: center; justify-content: space-between; padding: 0 16px; }
.mobile-topbar .mobile-logo { display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 15px; }
.mobile-topbar .logo-icon { width: 32px; height: 32px; background: #2563eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; }
.hamburger-btn { width: 40px; height: 40px; background: #f3f4f6; border: none; border-radius: 10px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; }
.hamburger-btn span { display: block; width: 20px; height: 2px; background: #333; border-radius: 2px; }
.sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 998; }
.sidebar-overlay.show { display: block; }
@media (max-width: 768px) {
    .mobile-topbar { display: flex; }
    .sidebar { position: fixed !important; left: -280px !important; top: 0; bottom: 0; z-index: 1000 !important; transition: left 0.3s !important; width: 280px !important; padding-top: 70px !important; }
    .sidebar.show { left: 0 !important; }
    main { padding: 16px !important; padding-top: 72px !important; }
}
</style>
</head>
<body class="has-sidebar">
<div class="mobile-topbar">
    <div class="mobile-logo"><div class="logo-icon"><i class="ri-cloud-line"></i></div><span><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></span></div>
    <button class="hamburger-btn" onclick="toggleSidebar(this)"><span></span><span></span><span></span></button>
</div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>function toggleSidebar(btn) { document.querySelector('.sidebar').classList.toggle('show'); document.querySelector('.sidebar-overlay').classList.toggle('show'); }</script>

<aside class="sidebar">
    <div>
        <div class="brand">
            <div class="logo"><i class="ri-cloud-line"></i></div>
            <div>
                <div class="name"><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></div>
                <div class="sub">管理后台</div>
            </div>
        </div>
        <nav>
            <a href="index.php" class="active"><i class="ri-dashboard-line"></i> 仪表盘</a>
            <a href="users.php"><i class="ri-user-line"></i> 用户管理</a>
            <a href="groups.php"><i class="ri-group-line"></i> 用户组管理</a>
            <a href="projects.php"><i class="ri-folder-line"></i> 项目管理</a>
            <a href="login_logs.php"><i class="ri-history-line"></i> 登录日志</a>
            <a href="system.php"><i class="ri-server-line"></i> 系统信息</a>
            <a href="config.php"><i class="ri-settings-line"></i> 网站配置</a>
            <a href="upgrade.php"><i class="ri-refresh-line"></i> 在线升级</a>
        </nav>
    </div>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="ri-logout-box-line"></i> 退出登录</a>
    </div>
</aside>

<main class="flex-1 p-6" style="min-width: 0; overflow-x: hidden;">
<div class="page-header"><div><h1>👋 你好，管理员</h1><p class="subtitle">平台运营数据概览</p></div></div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="ri-user-line"></i></div><div class="stat-value"><?= $userCount ?></div><div class="stat-label">用户总数</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="ri-folder-line"></i></div><div class="stat-value"><?= $projectCount + $guestCount ?></div><div class="stat-label">项目总数 <span style="font-size:12px;color:var(--text-4);">用户<?= $projectCount ?>/访客<?= $guestCount ?></span></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#f3e8ff;color:#9333ea;"><i class="ri-eye-line"></i></div><div class="stat-value"><?= $totalVisits ?></div><div class="stat-label">总访问量</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="ri-money-cny-circle-line"></i></div><div class="stat-value"><?= $orderCount ?></div><div class="stat-label">订单/收入 <span style="font-size:12px;color:var(--text-4);">已付¥<?= number_format($totalRevenue, 2) ?></span></div></div>
</div>

<div class="card mb-6"><h3 class="font-medium mb-4">平台趋势（近7日）</h3><canvas id="trendChart" height="100"></canvas></div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="card">
        <h3 class="font-medium mb-3"><i class="ri-file-list-line"></i> 快捷操作</h3>
        <div class="space-y-2">
            <a href="users.php" class="flex items-center justify-between p-3 rounded-lg transition" style="background:var(--border-light);"><span><i class="ri-user-add-line"></i> 管理用户</span><i class="ri-arrow-right-s-line text-gray-400"></i></a>
            <a href="projects.php" class="flex items-center justify-between p-3 rounded-lg transition" style="background:var(--border-light);"><span><i class="ri-folder-line"></i> 管理项目</span><i class="ri-arrow-right-s-line text-gray-400"></i></a>
            <a href="system.php" class="flex items-center justify-between p-3 rounded-lg transition" style="background:var(--border-light);"><span><i class="ri-database-line"></i> 数据库备份</span><i class="ri-arrow-right-s-line text-gray-400"></i></a>
            <a href="config.php" class="flex items-center justify-between p-3 rounded-lg transition" style="background:var(--border-light);"><span><i class="ri-settings-line"></i> 网站配置</span><i class="ri-arrow-right-s-line text-gray-400"></i></a>
        </div>
    </div>
    <div class="card">
        <h3 class="font-medium mb-3"><i class="ri-information-line"></i> 系统概览</h3>
        <?php $sys = getSystemInfo(); ?>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">PHP 版本</span><span><?= $sys['php_version'] ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">MySQL 版本</span><span><?= $sys['mysql_version'] ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">数据库大小</span><span><?= $sys['db_size_mb'] ?> MB</span></div>
            <div class="flex justify-between"><span class="text-gray-500">用户文件</span><span><?= $sys['users_dir_mb'] ?> MB</span></div>
            <div class="flex justify-between"><span class="text-gray-500">磁盘使用</span><span><?= $sys['disk_used_percent'] ?>% (剩余 <?= $sys['disk_free'] ?>)</span></div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($trendLabels) ?>,
        datasets: [
            { label: '新增用户', data: <?= json_encode($newUsers) ?>, borderColor: '#2563eb', tension: 0.3, fill: false },
            { label: '新增项目', data: <?= json_encode($newProjects) ?>, borderColor: '#52c41a', tension: 0.3, fill: false }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>
</body></html>
