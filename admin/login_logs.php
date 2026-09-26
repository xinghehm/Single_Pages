<?php
require_once '../functions.php';
requireAdmin();

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;
$total = getLoginLogCount();
$logs = getAllLoginLogs($perPage, $offset);
$totalPages = max(1, ceil($total / $perPage));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录日志 - 管理后台</title>
    
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=243">
</head>
<body class="min-h-screen">
    <?php include 'sidebar.php'; ?>
    <div class="md:ml-64 p-6">
        <h1 class="text-2xl font-bold mb-4">登录日志</h1>
        <p class="text-gray-500 text-sm mb-4">共 <?= $total ?> 条记录</p>
        <div class="glass-card p-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2 px-3">ID</th>
                        <th class="text-left py-2 px-3">用户</th>
                        <th class="text-left py-2 px-3">登录名</th>
                        <th class="text-left py-2 px-3">状态</th>
                        <th class="text-left py-2 px-3">IP</th>
                        <th class="text-left py-2 px-3">User-Agent</th>
                        <th class="text-left py-2 px-3">失败原因</th>
                        <th class="text-left py-2 px-3">时间</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="8" class="text-center py-8 text-gray-400">暂无记录</td></tr>
                <?php else: foreach ($logs as $log): ?>
                    <tr class="border-b hover:bg-white/30">
                        <td class="py-2 px-3"><?= $log['id'] ?></td>
                        <td class="py-2 px-3"><?= $log['user_id'] ? 'UID:' . $log['user_id'] : '-' ?></td>
                        <td class="py-2 px-3"><?= htmlspecialchars($log['username'] ?? '') ?></td>
                        <td class="py-2 px-3">
                            <?php if ($log['status'] == 1): ?>
                                <span class="text-green-600">成功</span>
                            <?php else: ?>
                                <span class="text-red-600">失败</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-3"><?= htmlspecialchars($log['ip'] ?? '') ?></td>
                        <td class="py-2 px-3 text-xs text-gray-500 max-w-xs truncate" title="<?= htmlspecialchars($log['user_agent'] ?? '') ?>"><?= htmlspecialchars(mb_substr($log['user_agent'] ?? '', 0, 40)) ?></td>
                        <td class="py-2 px-3 text-xs text-red-500"><?= htmlspecialchars($log['fail_reason'] ?? '') ?></td>
                        <td class="py-2 px-3 text-xs"><?= $log['created_at'] ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center gap-2 mt-4">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="btn-secondary px-4 py-2 text-sm">上一页</a>
            <?php endif; ?>
            <span class="px-4 py-2 text-sm text-gray-500"><?= $page ?> / <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>" class="btn-secondary px-4 py-2 text-sm">下一页</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
