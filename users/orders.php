<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
require_once '../functions.php';
$user = getCurrentUser();
$config = getConfig();
checkUserGroupExpire($user['id']);

// 处理退款申请
$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply_refund') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $method = $_POST['refund_method'] ?? '';
    $account = trim($_POST['refund_account'] ?? '');
    $realName = trim($_POST['real_name'] ?? '');
    $reason = trim($_POST['refund_reason'] ?? '');
    if (!in_array($method, ['wechat', 'alipay'])) {
        $msg = '请选择收款方式'; $msgType = 'error';
    } elseif (empty($account) || empty($realName)) {
        $msg = '请填写收款账号和真实姓名'; $msgType = 'error';
    } else {
        $result = applyRefund($orderId, $user['id'], $method, $account, $realName, $reason);
        $msg = $result['msg'];
        $msgType = $result['success'] ? 'success' : 'error';
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$orders = getUserOrders($user['id'], $page, $perPage);
$total = getUserOrderCount($user['id']);
$totalPages = ceil($total / $perPage);
$refunds = getUserRefunds($user['id']);
// 建立order_id => refund状态映射
$refundMap = [];
foreach ($refunds as $rf) { $refundMap[$rf['order_id']] = $rf; }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>我的订单 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=246">
<style>
.mobile-topbar{display:none;position:fixed;top:0;left:0;right:0;height:56px;background:#fff;border-bottom:1px solid rgba(0,0,0,0.06);z-index:999;align-items:center;justify-content:space-between;padding:0 16px}
.mobile-topbar .mobile-logo{display:flex;align-items:center;gap:8px;font-weight:600;font-size:15px}
.mobile-topbar .logo-icon{width:32px;height:32px;background:#2563eb;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px}
.hamburger-btn{width:40px;height:40px;background:#f3f4f6;border:none;border-radius:10px;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px}
.hamburger-btn span{display:block;width:20px;height:2px;background:#333;border-radius:2px}
.sidebar-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.4);z-index:998}
.sidebar-overlay.show{display:block}
@media(max-width:768px){
.mobile-topbar{display:flex}
.sidebar{position:fixed!important;left:-280px!important;top:0;bottom:0;z-index:1000!important;transition:left .3s!important;width:280px!important;padding-top:70px!important}
.sidebar.show{left:0!important}
main{padding:16px!important;padding-top:72px!important;min-width:0!important}
}
</style>
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-file-list-3-line"></i></div><span>我的订单</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<?php include 'sidebar.php'; ?>
<main>
<div class="page-header">
    <div><h1>我的订单</h1><p class="subtitle">共 <?= $total ?> 条订单记录</p></div>
</div>

<?php if ($msg): ?><div class="msg <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<?php if (empty($orders)): ?>
<div class="card" style="text-align:center;padding:60px 20px;">
    <i class="ri-inbox-line" style="font-size:48px;color:#d1d5db;margin-bottom:16px;display:block;"></i>
    <p style="color:#6b7280;font-size:15px;">暂无订单记录</p>
    <a href="buy_group.php" class="btn-primary" style="margin-top:16px;display:inline-flex;"><i class="ri-shopping-cart-line"></i> 去购买</a>
</div>
<?php else: ?>
<div class="table-wrap">
<table>
<thead>
<tr>
    <th>订单号</th><th>类型</th><th>商品</th><th>金额</th><th>支付方式</th><th>状态</th><th>生成时间</th><th>操作</th>
</tr>
</thead>
<tbody>
<?php foreach ($orders as $o): ?>
<tr>
    <td><code style="font-size:12px;"><?= htmlspecialchars($o['out_trade_no']) ?></code></td>
    <td><?= ($o['order_type'] ?? 'group') == 'balance' ? '<span class="badge" style="background:#fef3c7;color:#92400e;">余额</span>' : '<span class="badge" style="background:#dbeafe;color:#1e40af;">会员</span>' ?></td>
    <td><?= ($o['order_type'] ?? 'group') == 'balance' ? '余额充值' : htmlspecialchars($o['group_name'] ?? '未知') . '用户组' ?></td>
    <td style="font-weight:600;">¥<?= number_format($o['money'], 2) ?></td>
    <td><?= getPayMethodName($o['type']) ?></td>
    <td>
        <?php if ($o['status'] == 1): ?><span class="badge badge-success">已支付</span>
        <?php elseif ($o['status'] == 2): ?><span class="badge" style="background:#fee2e2;color:#991b1b;">已退款</span>
        <?php else: ?><span class="badge badge-gray">待支付</span><?php endif; ?>
    </td>
    <td class="text-small text-muted"><?= date('Y-m-d H:i', $o['created_at']) ?></td>
    <td>
        <?php if ($o['status'] == 1): ?>
            <?php if (isset($refundMap[$o['id']])): ?>
                <?php $rf = $refundMap[$o['id']]; ?>
                <?php if ($rf['status'] == 0): ?>
                <span class="badge" style="background:#fef3c7;color:#92400e;">退款审核中</span>
                <?php elseif ($rf['status'] == 1): ?>
                <span class="badge badge-success">已退款¥<?= $rf['actual_refund'] ?></span>
                <?php else: ?>
                <span class="badge" style="background:#fee2e2;color:#991b1b;">退款被拒</span>
                <?php endif; ?>
            <?php else: ?>
            <button onclick="openRefundModal(<?= $o['id'] ?>, '<?= ($o['order_type'] ?? 'group') == 'balance' ? '余额充值' : htmlspecialchars($o['group_name'] ?? '会员') ?>', <?= $o['money'] ?>)" class="btn-secondary btn-sm"><i class="ri-refund-line"></i> 申请退款</button>
            <?php endif; ?>
        <?php else: ?>
        <span class="text-muted" style="font-size:12px;">-</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination" style="margin-top:20px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
    <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>" class="btn-secondary btn-sm">上一页</a><?php endif; ?>
    <span style="padding:8px 14px;font-size:13px;color:#6b7280;"><?= $page ?> / <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?>" class="btn-secondary btn-sm">下一页</a><?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
</main>

<!-- 退款申请弹窗 -->
<div id="refundModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:440px;margin:16px;max-height:90vh;overflow-y:auto;">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:4px;">申请退款</h3>
        <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">商品：<span id="rfProduct"></span> · 金额：<span id="rfAmount" style="font-weight:700;color:#2563eb;"></span></p>
        <form method="POST" id="refundForm">
            <input type="hidden" name="action" value="apply_refund">
            <input type="hidden" name="order_id" id="rfOrderId">
            <div class="form-group">
                <label>收款方式</label>
                <div style="display:flex;gap:10px;margin-top:6px;">
                    <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;" onclick="selectMethod('wechat',this)">
                        <input type="radio" name="refund_method" value="wechat" checked style="width:16px;height:16px;">
                        <i class="ri-wechat-pay-line" style="font-size:20px;color:#07c160;"></i> 微信
                    </label>
                    <label style="flex:1;display:flex;align-items:center;gap:8px;padding:10px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;" onclick="selectMethod('alipay',this)">
                        <input type="radio" name="refund_method" value="alipay" style="width:16px;height:16px;">
                        <i class="ri-alipay-line" style="font-size:20px;color:#1677ff;"></i> 支付宝
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>收款账号</label>
                <input type="text" name="refund_account" id="rfAccount" placeholder="请输入微信/支付宝账号" required>
            </div>
            <div class="form-group">
                <label>真实姓名</label>
                <input type="text" name="real_name" id="rfRealName" placeholder="请输入实名（与收款账号一致）" required>
            </div>
            <div class="form-group">
                <label>退款原因（选填）</label>
                <textarea name="refund_reason" rows="2" placeholder="请简要说明退款原因" style="resize:vertical;"></textarea>
            </div>
            <div style="background:#fef3c7;padding:10px 14px;border-radius:8px;font-size:12px;color:#92400e;margin-bottom:16px;">
                <i class="ri-information-line"></i> 会员退款按使用时长阶梯扣费；余额退款扣除3%手续费。具体金额以审核为准。
            </div>
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="closeRefundModal()" class="btn-secondary" style="flex:1;">取消</button>
                <button type="submit" class="btn-primary" style="flex:1;">提交申请</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRefundModal(orderId, product, amount){
    document.getElementById('rfOrderId').value = orderId;
    document.getElementById('rfProduct').textContent = product;
    document.getElementById('rfAmount').textContent = '¥' + amount.toFixed(2);
    document.getElementById('rfAccount').value = '';
    document.getElementById('rfRealName').value = '';
    document.getElementById('refundModal').style.display = 'flex';
}
function closeRefundModal(){ document.getElementById('refundModal').style.display = 'none'; }
function selectMethod(m, el){
    document.querySelectorAll('#refundModal label[onclick^="selectMethod"]').forEach(l=>{l.style.borderColor='#e5e7eb';l.style.background='#fff';});
    el.style.borderColor = '#2563eb'; el.style.background = '#eff6ff';
}
document.getElementById('refundModal').addEventListener('click', function(e){ if(e.target===this) closeRefundModal(); });
function toggleSidebar(){
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}
</script>
</body>
</html>

