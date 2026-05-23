<?php require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$projects = getUserProjects($user['id']);
$stats = getUserStats($user['id']);
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>控制台 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><link rel="stylesheet" href="../style.css"></head>
<body class="flex min-h-screen">
<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div><div class="flex items-center gap-2 mb-8"><div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div><div><div class="font-bold"><?= htmlspecialchars($config['site_name']) ?></div><div class="text-xs text-gray-500">Pages</div></div></div>
    <nav class="space-y-2"><a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-dashboard-line"></i> 控制台</a><a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 我的项目</a><a href="profile.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-user-line"></i> 个人中心</a></nav></div>
    <div><a href="../logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>
<main class="flex-1 p-6">
    <div class="flex justify-between items-center mb-6 flex-wrap gap-3"><div class="glass-card p-4 flex-1"><h1 class="text-xl font-bold">欢迎回来，<?= htmlspecialchars($user['username']) ?></h1><p class="text-gray-500 text-sm"><?= htmlspecialchars($config['tagline']) ?></p></div><button onclick="newProject()" class="btn-primary"><i class="ri-add-line"></i> 新建项目</button></div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6"><div class="glass-card p-4 text-center"><div class="text-gray-500 text-sm">我的项目</div><div class="text-2xl font-bold"><?= $stats['project_count'] ?></div></div><div class="glass-card p-4 text-center"><div class="text-gray-500 text-sm">已用空间</div><div class="text-2xl font-bold"><?= $stats['total_size_mb'] ?> MB</div></div><div class="glass-card p-4 text-center"><div class="text-gray-500 text-sm">总配额</div><div class="text-2xl font-bold text-orange-500">500 MB</div></div><div class="glass-card p-4 text-center"><div class="text-gray-500 text-sm">使用率</div><div class="text-2xl font-bold"><?= round(($stats['total_size_mb'] / 500) * 100, 1) ?>%</div></div></div>
    <div class="glass-card p-4 mb-6"><h3 class="font-medium mb-4">空间使用趋势（近7日）</h3><canvas id="usageChart" height="120"></canvas></div>
    <div class="glass-card p-4"><div class="flex justify-between items-center mb-4"><h3 class="font-medium">最近项目</h3><a href="projects.php" class="text-blue-600 text-sm">查看全部</a></div><?php if (empty($projects)): ?><div class="text-center py-8 text-gray-500">暂无项目，点击右上角创建</div><?php else: ?><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><?php foreach (array_slice($projects, 0, 3) as $pro): ?><div class="bg-white/50 dark:bg-gray-800/50 rounded-xl p-4"><div class="font-bold"><?= htmlspecialchars($pro['name']) ?></div><div class="flex justify-between items-center mt-3"><span class="text-xs text-green-600 flex items-center gap-1"><span class="w-2 h-2 bg-green-500 rounded-full"></span> 在线</span><a href="project_detail.php?pro=<?= $pro['pro_id'] ?>" class="text-blue-600 text-sm">管理文件</a></div></div><?php endforeach; ?></div><?php endif; ?></div>
</main>
<script>
    new Chart(document.getElementById('usageChart'), { type: 'line', data: { labels: ['周一', '周二', '周三', '周四', '周五', '周六', '周日'], datasets: [{ label: '空间使用 (MB)', data: [20, 25, 22, 32, 28, 35, 30], borderColor: '#1677ff', backgroundColor: 'rgba(22,119,255,0.1)', fill: true, tension: 0.3 }] }, options: { responsive: true, plugins: { legend: { display: false } } } });
    function newProject() { let name = prompt("请输入项目名（仅英文数字）", ""); if (name && /^[a-zA-Z0-9]+$/.test(name)) location.href = "project_new.php?name=" + encodeURIComponent(name); else alert("项目名只能包含英文数字"); }
</script>
</body>
</html>