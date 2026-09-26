<?php require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$projects = getUserProjects($user['id']);
$stats = getUserStats($user['id']);
$currentGroup = getUserGroup($user['group_id'] ?? 1);
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>控制台 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><link rel="stylesheet" href="../style.css?v=245">
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
<body class="has-sidebar">
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

<aside class="sidebar">
    <div>
        <div class="brand">
            <div class="logo"><i class="ri-cloud-line"></i></div>
            <div><div class="name"><?= htmlspecialchars($config['site_name']) ?></div><div class="sub">Pages</div></div>
        </div>
        <nav>
            <a href="dashboard.php" class="active"><i class="ri-dashboard-line"></i> 控制台</a>
            <a href="templates.php"><i class="ri-apps-line"></i> 模板市场</a>
            <a href="projects.php"><i class="ri-folder-line"></i> 我的项目</a>
            <a href="login_logs.php"><i class="ri-history-line"></i> 登录日志</a>
            <a href="buy_group.php"><i class="ri-vip-crown-line"></i> 购买用户组</a>
            <a href="profile.php"><i class="ri-user-line"></i> 个人中心</a>
            </nav>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php" style="color:#dc2626;"><i class="ri-logout-box-line"></i> 退出登录</a>
    </div>
</aside>
<main class="flex-1 p-6">
    <div class="page-header">
    <div class="card" style="flex:1;">
        <h1 style="font-size:22px;font-weight:700;color:var(--text-1);margin-bottom:8px;">欢迎回来，<?= htmlspecialchars($user['username']) ?></h1>
        <p style="color:var(--text-3);font-size:14px;margin-bottom:4px;">
            当前用户组：<span style="color:var(--primary);font-weight:500;"><?= htmlspecialchars($currentGroup['name'] ?? '免费用户') ?></span>
            <?php if (!empty($user['group_expire_at'])): ?>（到期：<?= date('Y-m-d', $user['group_expire_at']) ?>）<?php endif; ?>
            <a href="buy_group.php" style="margin-left:8px;">升级</a>
        </p>
        <p style="color:var(--text-4);font-size:13px;"><?= htmlspecialchars($config['tagline']) ?></p>
    </div>
    <?php if (canCreateProject($user['id'])): ?>
    <button onclick="newProject()" class="btn-primary" style="align-self:flex-start;"><i class="ri-add-line"></i> 新建项目</button>
    <?php else: ?>
    <button onclick="alert('项目数量已达上限（<?= getUserProjectLimit($user['id']) ?>个），请升级用户组或删除旧项目')" class="btn-secondary" style="align-self:flex-start;"><i class="ri-lock-line"></i> 已达上限</button>
    <?php endif; ?>
</div>
    <div class="stats-grid">
    <div class="stat-card"><div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="ri-folder-line"></i></div><div class="stat-value"><?= $stats['project_count'] ?>/<?= getUserProjectLimit($user['id']) ?></div><div class="stat-label">我的项目</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="ri-hard-drive-2-line"></i></div><div class="stat-value"><?= $stats['total_size_mb'] ?> MB</div><div class="stat-label">已用空间</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="ri-database-line"></i></div><div class="stat-value">500 MB</div><div class="stat-label">总配额</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#fce7f3;color:#db2777;"><i class="ri-pie-chart-line"></i></div><div class="stat-value"><?= round(($stats['total_size_mb'] / 500) * 100, 1) ?>%</div><div class="stat-label">使用率</div></div>
</div>
    <div class="card mb-6"><h3 class="font-medium mb-4">空间使用趋势（近7日）</h3><canvas id="usageChart" height="120"></canvas></div>
    <div class="card"><div class="flex justify-between items-center mb-4"><h3 class="font-medium">最近项目</h3><a href="projects.php" class="text-blue-600 text-sm">查看全部</a></div><?php if (empty($projects)): ?><div class="text-center py-8 text-gray-500">暂无项目，点击右上角创建</div><?php else: ?><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><?php foreach (array_slice($projects, 0, 3) as $pro): ?><div class="card" style="padding:16px;"><div class="font-bold"><?= htmlspecialchars($pro['name']) ?></div><div class="flex justify-between items-center mt-3"><span class="text-xs text-green-600 flex items-center gap-1"><span class="w-2 h-2 bg-green-500 rounded-lg"></span> 在线</span><a href="project_detail.php?pro=<?= $pro['pro_id'] ?>" class="text-blue-600 text-sm">管理文件</a></div></div><?php endforeach; ?></div><?php endif; ?></div>
</main>
<script>
    new Chart(document.getElementById('usageChart'), { type: 'line', data: { labels: ['周一', '周二', '周三', '周四', '周五', '周六', '周日'], datasets: [{ label: '空间使用 (MB)', data: [20, 25, 22, 32, 28, 35, 30], borderColor: '#2563eb', backgroundColor: 'rgba(22,119,255,0.1)', fill: true, tension: 0.3 }] }, options: { responsive: true, plugins: { legend: { display: false } } } });
    function newProject() { let name = prompt("请输入项目名（仅英文数字）", ""); if (name && /^[a-zA-Z0-9]+$/.test(name)) location.href = "project_new.php?name=" + encodeURIComponent(name); else alert("项目名只能包含英文数字"); }
</script>
</body>
</html>