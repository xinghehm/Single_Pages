<?php
require_once '../functions.php';
if (!isAdminLoggedIn()) { header('Location: login.php'); exit; }
$config = getConfig();

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
<title>登录日志 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-history-line"></i></div><span>登录日志</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('show');document.querySelector('.sidebar-overlay').classList.toggle('show');}</script>
<?php include 'sidebar.php'; ?>

<main>
<div class="page-header">
<div><h1>登录日志</h1><p class="subtitle">共 <?= $total ?> 条记录</p></div>
</div>

<div class="table-wrap">
<table>
<thead>
<tr>
<th>ID</th><th>用户</th><th>登录名</th><th>状态</th><th>IP</th><th>User-Agent</th><th>失败原因</th><th>时间</th>
</tr>
</thead>
<tbody>
<?php if (empty($logs)): ?>
<tr><td colspan="8" class="text-center" style="padding:32px;color:#9ca3af">暂无记录</td></tr>
<?php else: foreach ($logs as $log): ?>
<tr>
<td><?= $log['id'] ?></td>
<td><?= $log['user_id'] ? 'UID:' . $log['user_id'] : '-' ?></td>
<td><?= htmlspecialchars($log['username'] ?? '') ?></td>
<td><?= $log['status'] == 1 ? '<span class="badge badge-success">成功</span>' : '<span class="badge badge-danger">失败</span>' ?></td>
<td><?= htmlspecialchars($log['ip'] ?? '') ?></td>
<td style="font-size:11px;color:#6b7280;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($log['user_agent'] ?? '') ?>"><?= htmlspecialchars(mb_substr($log['user_agent'] ?? '', 0, 40)) ?></td>
<td style="font-size:11px;color:#ef4444"><?= htmlspecialchars($log['fail_reason'] ?? '') ?></td>
<td style="font-size:11px"><?= $log['created_at'] ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
<?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>" class="btn-secondary btn-sm">上一页</a><?php endif; ?>
<span class="page-info"><?= $page ?> / <?= $totalPages ?></span>
<?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?>" class="btn-secondary btn-sm">下一页</a><?php endif; ?>
</div>
<?php endif; ?>
</main>
</body>
</html>
