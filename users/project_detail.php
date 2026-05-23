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
</head>
<body class="flex min-h-screen">
<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div>
        <div class="flex items-center gap-2 mb-8">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div>
            <div><div class="font-bold"><?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></div><div class="text-xs text-gray-500">Pages</div></div>
        </div>
        <nav class="space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 控制台</a>
            <a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 我的项目</a>
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
        <div class="flex gap-3">
           <a href="/<?= $proId ?>" target="_blank" class="btn-secondary">预览站点</a>
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