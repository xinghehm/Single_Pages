<?php require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$logs = getUserLoginLogs($user['id'], 50);
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>登录日志 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><link rel="stylesheet" href="../style.css?v=241"><?= outputUserBgStyle() ?>
<style>
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

<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div><div class="flex items-center gap-2 mb-8"><div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div><div><div class="font-bold"><?= htmlspecialchars($config['site_name']) ?></div><div class="text-xs text-gray-500">Pages</div></div></div>
    <nav class="space-y-2"><a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 控制台</a><a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 我的项目</a><a href="login_logs.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-history-line"></i> 登录日志</a><a href="buy_group.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-vip-crown-line"></i> 购买用户组</a><a href="profile.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-user-line"></i> 个人中心</a></nav></div>
    <div><a href="../logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>

<main class="flex-1 p-6">
<div class="mb-6"><h1 class="text-2xl font-bold">登录日志</h1><p class="text-gray-500">最近 <?= count($logs) ?> 条登录记录</p></div>
<div class="glass-card p-4">
<?php if (empty($logs)): ?>
<div class="text-center py-12"><i class="ri-history-line text-6xl text-gray-300 mb-4"></i><p>暂无登录记录</p></div>
<?php else: ?>
<div class="overflow-x-auto">
<table class="w-full text-sm">
<thead class="text-gray-500 border-b"><tr><th class="text-left p-3">时间</th><th class="text-left p-3">IP</th><th class="text-left p-3">状态</th><th class="text-left p-3">设备/浏览器</th></tr></thead>
<tbody>
<?php foreach ($logs as $log): ?>
<tr class="border-t">
<td class="p-3"><?= date('Y-m-d H:i:s', $log['created_at']) ?><br><span class="text-xs text-gray-400"><?= formatTimeAgo($log['created_at']) ?></span></td>
<td class="p-3"><code class="text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($log['ip']) ?></code></td>
<td class="p-3"><?= $log['status'] ? '<span class="text-green-600"><i class="ri-checkbox-circle-line"></i> 成功</span>' : '<span class="text-red-500"><i class="ri-close-circle-line"></i> 失败</span>' ?><?= $log['fail_reason'] ? '<br><span class="text-xs text-gray-400">' . htmlspecialchars($log['fail_reason']) . '</span>' : '' ?></td>
<td class="p-3 text-xs text-gray-500 max-w-xs truncate" title="<?= htmlspecialchars($log['user_agent']) ?>"><?= htmlspecialchars(substr($log['user_agent'], 0, 80)) ?>...</td>
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
