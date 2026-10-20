<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
requireAdminPermission('manage_refunds');
$config = getConfig();

$msg = ''; $msgType = '';
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    if ($action === 'delete') {
        $result = deleteRefund($id);
    } elseif ($action === 'reject') {
        $remark = $_GET['remark'] ?? '';
        $result = processRefund($id, 'reject', $remark);
    }
    if (isset($result)) {
        $msg = $result['msg'];
        $msgType = $result['success'] ? 'success' : 'error';
    }
}
// POST方式同意退款（带金额，调用易支付API）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_refund') {
    $id = intval($_POST['refund_id']);
    $refundAmount = floatval($_POST['refund_money'] ?? 0);
    $remark = trim($_POST['admin_remark'] ?? '');
    if ($refundAmount <= 0) {
        $msg = '退款金额必须大于0'; $msgType = 'error';
    } else {
        // 获取退款申请对应的订单
        $refund = getRefundById($id);
        if (!$refund) {
            $msg = '退款申请不存在'; $msgType = 'error';
        } else {
            $pdo = getDB();
            $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE out_trade_no = ? OR id = ?");
            $orderStmt->execute([$refund['order_no'] ?? '', $refund['order_id'] ?? 0]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                $msg = '关联订单不存在'; $msgType = 'error';
            } else {
                // 先更新实际退款金额
                $pdo->prepare("UPDATE refund_applications SET actual_refund = ? WHERE id = ?")->execute([$refundAmount, $id]);
                
                // 调用易支付退款API
                $apiUrl = rtrim($config['yipay_url'] ?? '', '/') . '/api.php?act=refund';
                $postData = [
                    'pid' => $config['yipay_pid'] ?? '',
                    'key' => $config['yipay_key'] ?? '',
                    'money' => number_format($refundAmount, 2, '.', ''),
                ];
                if (!empty($order['trade_no'])) {
                    $postData['trade_no'] = $order['trade_no'];
                } else {
                    $postData['out_trade_no'] = $order['out_trade_no'];
                }
                
                $apiSuccess = false;
                $apiMsg = '';
                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-Type: application/x-www-form-urlencoded',
                        'content' => http_build_query($postData),
                        'timeout' => 10,
                        'ignore_errors' => true,
                    ],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                ]);
                $response = @file_get_contents($apiUrl, false, $ctx);
                if ($response !== false && $response !== '') {
                    $apiResult = json_decode($response, true);
                    if ($apiResult && isset($apiResult['code']) && $apiResult['code'] == 0) {
                        $apiSuccess = true;
                        $apiMsg = $apiResult['msg'] ?? '退款成功';
                    } elseif ($apiResult) {
                        $apiMsg = $apiResult['msg'] ?? 'API返回错误';
                    } else {
                        $apiMsg = 'API返回空响应（可能未在商户后台开启退款API开关）';
                    }
                } else {
                    $apiMsg = 'API请求失败（网络超时或支付平台不可达），已执行本地退款';
                }
                
                // 无论API是否成功，都执行本地退款（扣余额/取消会员）
                $result = processRefund($id, 'approve', $remark, $refundAmount);
                
                if ($apiSuccess) {
                    $msg = '退款成功：' . $apiMsg . '，实际退款¥' . number_format($refundAmount, 2);
                } else {
                    $msg = '本地退款已完成（¥' . number_format($refundAmount, 2) . '），但易支付API退款失败：' . $apiMsg . '。请登录易支付商户后台手动退款。';
                }
                $msgType = $apiSuccess ? 'success' : 'warning';
            }
        }
    }
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
            <button onclick="openApproveModal(<?= $r['id'] ?>, <?= number_format($r['actual_refund'], 2, '.', '') ?>, '<?= htmlspecialchars($r['order_no'] ?? '', ENT_QUOTES) ?>')" class="btn-success btn-sm" style="background:#16a34a;color:#fff;border:none;padding:6px 10px;border-radius:8px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;"><i class="ri-check-line"></i> 同意</button>
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
<!-- 同意退款弹窗 -->
<div id="approveModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:420px;margin:16px;max-height:90vh;overflow-y:auto;">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:4px;"><i class="ri-refund-line" style="color:#16a34a;"></i> 同意退款</h3>
        <p style="font-size:12px;color:#9ca3af;margin-bottom:16px;">将调用易支付API发起退款，退款金额由管理员手动输入</p>
        <form method="POST">
            <input type="hidden" name="action" value="approve_refund">
            <input type="hidden" name="refund_id" id="approveRefundId">
            <div class="form-group"><label>订单号</label><input type="text" id="approveOrderNo" readonly style="background:#f9fafb;color:#6b7280;"></div>
            <div class="form-group"><label>申请退款金额</label><input type="text" id="approveApplyAmount" readonly style="background:#f9fafb;color:#6b7280;"></div>
            <div class="form-group"><label>实际退款金额（元）<span style="color:#ef4444;">*</span></label><input type="number" name="refund_money" id="approveMoney" step="0.01" min="0.01" required placeholder="请输入实际退款金额"></div>
            <div class="form-group"><label>处理备注（可选）</label><textarea name="admin_remark" rows="2" placeholder="填写退款说明..." style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;resize:vertical;"></textarea></div>
            <div style="background:#fffbeb;border-radius:8px;padding:10px 12px;font-size:12px;color:#92400e;margin-bottom:16px;line-height:1.5;">
                <i class="ri-information-line"></i> 点击确认后将先调用易支付退款API，API成功后自动扣除用户余额/取消会员。
            </div>
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="closeApproveModal()" class="btn-secondary" style="flex:1;">取消</button>
                <button type="submit" class="btn-primary" style="flex:1;background:#16a34a;border-color:#16a34a;">确认退款</button>
            </div>
        </form>
    </div>
</div>
<script>
function openApproveModal(id, amount, orderNo) {
    document.getElementById('approveRefundId').value = id;
    document.getElementById('approveOrderNo').value = orderNo || '-';
    document.getElementById('approveApplyAmount').value = '¥' + amount.toFixed(2);
    document.getElementById('approveMoney').value = amount.toFixed(2);
    document.getElementById('approveModal').style.display = 'flex';
}
function closeApproveModal() { document.getElementById('approveModal').style.display = 'none'; }
document.getElementById('approveModal').addEventListener('click', function(e) { if(e.target===this) closeApproveModal(); });
</script>
</body>
</html>






