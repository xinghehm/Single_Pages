<?php session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
$pdo = getDB();
if (isset($_GET['delete'])) {
    $proId = $_GET['delete'];
    $pdo->prepare("DELETE FROM project_files WHERE pro_id = ?")->execute([$proId]);
    $pdo->prepare("DELETE FROM projects WHERE pro_id = ?")->execute([$proId]);
    header('Location: projects.php');
    exit;
}
$projects = $pdo->query("SELECT p.*, u.username FROM projects p JOIN users u ON p.user_id = u.id ORDER BY p.id DESC")->fetchAll();
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>项目管理 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><link rel="stylesheet" href="../style.css"></head>
<body class="flex min-h-screen">
<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div><div class="flex items-center gap-2 mb-8"><div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div><div><div class="font-bold"><?= htmlspecialchars($config['site_name']) ?></div><div class="text-xs text-gray-500">Admin</div></div></div>
    <nav class="space-y-2"><a href="index.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 仪表盘</a><a href="users.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-user-line"></i> 用户管理</a><a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-folder-line"></i> 项目管理</a><a href="config.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-settings-line"></i> 网站配置</a></nav></div>
    <div><a href="logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>
<main class="flex-1 p-6"><div class="glass-card p-4"><h1 class="text-lg font-bold mb-4">项目管理</h1><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="text-gray-500 border-b"><tr><th class="text-left p-3">项目ID</th><th class="text-left p-3">项目名</th><th class="text-left p-3">所属用户</th><th class="text-left p-3">创建时间</th><th class="text-left p-3">操作</th></tr></thead><tbody><?php foreach ($projects as $pro): ?><tr class="border-t"><td class="p-3"><?= htmlspecialchars($pro['pro_id']) ?></td><td class="p-3"><?= htmlspecialchars($pro['name']) ?></td><td class="p-3"><?= htmlspecialchars($pro['username']) ?></td><td class="p-3"><?= date('Y-m-d', $pro['created_at']) ?></td><td class="p-3"><a href="?delete=<?= $pro['pro_id'] ?>" onclick="return confirm('删除项目及所有文件？')" class="text-red-500">删除</a></td></tr><?php endforeach; ?></tbody></table></div></div></main>
</body></html>