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
<html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>后台仪表盘 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><link rel="stylesheet" href="../style.css"></head>
<body class="flex min-h-screen">
<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div><div class="flex items-center gap-2 mb-8"><div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div><div><div class="font-bold"><?= htmlspecialchars($config['site_name']) ?></div><div class="text-xs text-gray-500">Admin</div></div></div>
    <nav class="space-y-2"><a href="index.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-dashboard-line"></i> 仪表盘</a><a href="users.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-user-line"></i> 用户管理</a><a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 项目管理</a><a href="config.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-settings-line"></i> 网站配置</a></nav></div>
    <div><a href="logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>
<main class="flex-1 p-6"><h1 class="text-2xl font-bold mb-6">👋 晚上好，管理员</h1><div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6"><div class="glass-card p-4 text-center"><div class="text-gray-500 text-sm">用户总数</div><div class="text-3xl font-bold"><?= $userCount ?></div></div><div class="glass-card p-4 text-center"><div class="text-gray-500 text-sm">项目总数</div><div class="text-3xl font-bold"><?= $projectCount ?></div></div><div class="glass-card p-4 text-center"><div class="text-gray-500 text-sm">文件总数</div><div class="text-3xl font-bold"><?= $fileCount ?></div></div><div class="glass-card p-4 text-center"><div class="text-gray-500 text-sm">存储总量</div><div class="text-3xl font-bold">-</div></div></div>
<div class="glass-card p-4"><h3 class="font-medium mb-4">平台趋势（近7日）</h3><canvas id="trendChart" height="100"></canvas></div>
<script>new Chart(document.getElementById('trendChart'), { type: 'line', data: { labels: ['04-29', '04-30', '05-01', '05-02', '05-03', '05-04', '05-05'], datasets: [{ label: '新增用户', data: [0,0,0,0,0,0,0], borderColor: '#1677ff', tension: 0.3 }, { label: '新增项目', data: [0,0,0,0,0,0,0], borderColor: '#52c41a', tension: 0.3 }] }, options: { responsive: true, plugins: { legend: { position: 'top' } } } });</script>
</body></html>