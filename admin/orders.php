<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
requireAdminPermission('manage_orders');
$config = getConfig();

// 处理订单状态变更
$msg = '';
$msgType = '';
if (isset($_GET['toggle_status'])) {
    $orderId = intval($_GET['toggle_status']);
    $result = updateOrderStatus($orderId, isset($_GET['to_paid']) ? 1 : 0);
    $msg = $result['msg'];
    $msgType = $result['success'] ? 'success' : 'error';
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$orders = getAllOrders($page, $perPage);
$total = getAllOrderCount();
$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>订单管理 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-file-list-3-line"></i></div><span>订单管理</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<?php include 'sidebar.php'; ?>

<main>
<div class="page-header">
    <div><h1>订单管理</h1><p class="subtitle">共 <?= $total ?> 条订单</p></div>
</div>

<?php if ($msg): ?><div class="msg <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="grid grid-cols-3 gap-4 mb-6" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <div class="card" style="text-align:center;padding:20px;">
        <div style="font-size:28px;font-weight:700;color:#1a1a2e;"><?= $total ?></div>
        <div style="font-size:13px;color:#6b7280;margin-top:4px;">总订单数</div>
    </div>
    <div class="card" style="text-align:center;padding:20px;">
        <div style="font-size:28px;font-weight:700;color:#16a34a;"><?= $paidCount = count(array_filter($orders, fn($o) => $o['status']==1)) ?></div>
        <div style="font-size:13px;color:#6b7280;margin-top:4px;">已支付（本页）</div>
    </div>
    <div class="card" style="text-align:center;padding:20px;">
        <div style="font-size:28px;font-weight:700;color:#2563eb;">¥<?= number_format(array_sum(array_column(array_filter($orders, fn($o) => $o['status']==1), 'money')), 2) ?></div>
        <div style="font-size:13px;color:#6b7280;margin-top:4px;">已支付金额（本页）</div>
    </div>
</div>

<?php if (empty($orders)): ?>
<div class="card" style="text-align:center;padding:60px 20px;">
    <i class="ri-inbox-line" style="font-size:48px;color:#d1d5db;margin-bottom:16px;display:block;"></i>
    <p style="color:#6b7280;font-size:15px;">暂无订单记录</p>
</div>
<?php else: ?>
<div class="table-wrap">
<table>
<thead>
<tr>
    <th>ID</th><th>用户</th><th>订单号</th><th>商品</th><th>金额</th><th>支付方式</th><th>状态</th><th>生成时间</th><th>支付时间</th><th>操作</th>
</tr>
</thead>
<tbody>
<?php foreach ($orders as $o): ?>
<tr>
    <td><?= $o['id'] ?></td>
    <td><?= htmlspecialchars($o['user_name'] ?? '未知') ?></td>
    <td><code style="font-size:12px;"><?= htmlspecialchars($o['out_trade_no']) ?></code></td>
    <td><?= htmlspecialchars($o['group_name'] ?? '未知') ?>用户组</td>
    <td style="font-weight:600;">¥<?= number_format($o['money'], 2) ?></td>
    <td><?= getPayMethodName($o['type']) ?></td>
    <td>
        <?php if ($o['status'] == 1): ?>
        <span class="badge badge-success">已支付</span>
        <?php else: ?>
        <span class="badge badge-gray">待支付</span>
        <?php endif; ?>
    </td>
    <td class="text-small text-muted"><?= date('Y-m-d H:i:s', $o['created_at']) ?></td>
    <td class="text-small text-muted"><?= $o['paid_at'] ? date('Y-m-d H:i:s', $o['paid_at']) : '-' ?></td>
    <td>
        <?php if ($o['status'] == 1): ?>
        <a href="?toggle_status=<?= $o['id'] ?>" onclick="return confirm('确定将此订单标记为未支付？将取消对应用户的会员权限！')" class="btn-danger btn-sm"><i class="ri-close-line"></i> 取消支付</a>
        <?php else: ?>
        <a href="?toggle_status=<?= $o['id'] ?>&to_paid=1" onclick="return confirm('确定将此订单标记为已支付？将为对应用户开通会员！')" class="btn-success btn-sm" style="background:#16a34a;color:#fff;border:none;padding:6px 12px;border-radius:8px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;text-decoration:none;"><i class="ri-check-line"></i> 标记已付</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination" style="margin-top:20px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?>" class="btn-secondary btn-sm">上一页</a>
    <?php endif; ?>
    <span style="padding:8px 14px;font-size:13px;color:#6b7280;"><?= $page ?> / <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?>
    <a href="?page=<?= $page + 1 ?>" class="btn-secondary btn-sm">下一页</a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
</main>
<script>
function toggleSidebar(){
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}
</script>
</body>
</html>


