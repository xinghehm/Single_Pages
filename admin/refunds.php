<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
$config = getConfig();

$msg = ''; $msgType = '';
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    if ($action === 'delete') {
        $result = deleteRefund($id);
    } else {
        $remark = $_GET['remark'] ?? '';
        $result = processRefund($id, $action, $remark);
    }
    $msg = $result['msg'];
    $msgType = $result['success'] ? 'success' : 'error';
}

$statusFilter = isset($_GET['status']) ? intval($_GET['status']) : null;
$refunds = getAllRefunds($statusFilter);
$pendingCount = count(array_filter($refunds, fn($r)=>$r['status']==0));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>退款管理 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=246">
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-refund-line"></i></div><span>退款管理</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<?php include 'sidebar.php'; ?>

<main>
<div class="page-header">
    <div><h1>退款管理</h1><p class="subtitle">共 <?= count($refunds) ?> 条退款申请<?= $pendingCount > 0 ? "，<span style='color:#f59e0b;'>{$pendingCount}条待处理</span>" : '' ?></p></div>
</div>

<?php if ($msg): ?><div class="msg <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="refunds.php" class="btn-secondary btn-sm <?= $statusFilter === null ? 'active' : '' ?>">全部</a>
    <a href="refunds.php?status=0" class="btn-secondary btn-sm <?= $statusFilter === 0 ? 'active' : '' ?>">待处理 (<?= $pendingCount ?>)</a>
    <a href="refunds.php?status=1" class="btn-secondary btn-sm <?= $statusFilter === 1 ? 'active' : '' ?>">已退款</a>
    <a href="refunds.php?status=2" class="btn-secondary btn-sm <?= $statusFilter === 2 ? 'active' : '' ?>">已拒绝</a>
</div>

<?php if (empty($refunds)): ?>
<div class="card" style="text-align:center;padding:60px 20px;">
    <i class="ri-inbox-line" style="font-size:48px;color:#d1d5db;margin-bottom:16px;display:block;"></i>
    <p style="color:#6b7280;font-size:15px;">暂无退款申请</p>
</div>
<?php else: ?>
<div class="table-wrap">
<table>
<thead>
<tr>
    <th>ID</th><th>用户</th><th>订单号</th><th>类型</th><th>订单金额</th><th>退款金额</th><th>收款方式</th><th>账号/实名</th><th>状态</th><th>申请时间</th><th>操作</th>
</tr>
</thead>
<tbody>
<?php foreach ($refunds as $r): ?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['username'] ?? '未知') ?></td>
    <td><code style="font-size:11px;"><?= htmlspecialchars($r['out_trade_no'] ?? '-') ?></code></td>
    <td><?= $r['order_type'] == 'balance' ? '<span class="badge" style="background:#fef3c7;color:#92400e;">余额</span>' : '<span class="badge" style="background:#dbeafe;color:#1e40af;">会员</span>' ?></td>
    <td>¥<?= number_format($r['order_money'] ?? $r['refund_amount'], 2) ?></td>
    <td style="font-weight:700;color:#16a34a;">¥<?= number_format($r['actual_refund'], 2) ?></td>
    <td><?= $r['refund_method'] == 'wechat' ? '<i class="ri-wechat-pay-line" style="color:#07c160;"></i> 微信' : '<i class="ri-alipay-line" style="color:#1677ff;"></i> 支付宝' ?></td>
    <td style="font-size:12px;">
        <div><?= htmlspecialchars($r['refund_account']) ?></div>
        <div style="color:#6b7280;"><?= htmlspecialchars($r['real_name']) ?></div>
    </td>
    <td>
        <?php if ($r['status'] == 0): ?><span class="badge" style="background:#fef3c7;color:#92400e;">待处理</span>
        <?php elseif ($r['status'] == 1): ?><span class="badge badge-success">已退款</span>
        <?php else: ?><span class="badge" style="background:#fee2e2;color:#991b1b;">已拒绝</span><?php endif; ?>
    </td>
    <td class="text-small text-muted"><?= date('Y-m-d H:i', $r['created_at']) ?></td>
    <td>
        <?php if ($r['status'] == 0): ?>
        <div style="display:flex;gap:4px;">
            <a href="?action=approve&id=<?= $r['id'] ?>" onclick="return confirm('确定同意退款¥<?= number_format($r['actual_refund'], 2) ?>？将扣除对应用户余额/取消会员。')" class="btn-success btn-sm" style="background:#16a34a;color:#fff;border:none;padding:6px 10px;border-radius:8px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;"><i class="ri-check-line"></i> 同意</a>
            <a href="?action=reject&id=<?= $r['id'] ?>" onclick="return confirm('确定拒绝该退款申请？')" class="btn-danger btn-sm"><i class="ri-close-line"></i> 拒绝</a>
        </div>
        <?php else: ?>
        <div style="display:flex;gap:4px;align-items:center;">
            <span class="text-muted" style="font-size:12px;"><?= $r['processed_at'] ? date('m-d H:i', $r['processed_at']) : '-' ?></span>
            <a href="?action=delete&id=<?= $r['id'] ?>" onclick="return confirm('确定删除该退款记录？仅清理记录，不影响用户余额。')" class="btn-danger btn-sm" title="删除记录"><i class="ri-delete-bin-line"></i></a>
        </div>
        <?php endif; ?>
    </td>
</tr>
<?php if (!empty($r['reason'])): ?>
<tr style="background:#f9fafb;"><td colspan="11" style="font-size:12px;color:#6b7280;padding:8px 16px;"><i class="ri-chat-3-line"></i> 退款原因：<?= htmlspecialchars($r['reason']) ?></td></tr>
<?php endif; ?>
<?php endforeach; ?>
</tbody>
</table>
</div>
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

