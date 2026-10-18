<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
require_once '../functions.php';
$user = getCurrentUser();
$config = getConfig();
$refundCfg = getRefundConfig();
$minRecharge = $refundCfg['min_recharge'];

$allPayMethods = ['alipay' => ['name' => '支付宝', 'icon' => 'ri-alipay-line', 'color' => 'text-blue-500'], 'wxpay' => ['name' => '微信支付', 'icon' => 'ri-wechat-pay-line', 'color' => 'text-green-500'], 'qqpay' => ['name' => 'QQ钱包', 'icon' => 'ri-qq-line', 'color' => 'text-purple-500']];
$enabledPayMethods = getEnabledPayMethods();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $payType = $_POST['pay_type'] ?? '';
    if ($amount < $minRecharge) {
        $error = "最低充值金额为¥{$minRecharge}";
    } elseif (!in_array($payType, $enabledPayMethods)) {
        $error = '请选择有效的支付方式';
    } else {
        $outTradeNo = createBalanceOrder($user['id'], $amount, $payType);
        $yipayConfig = getYiPayConfig();
        if (empty($yipayConfig['yipay_url']) || empty($yipayConfig['yipay_pid']) || empty($yipayConfig['yipay_key'])) {
            $error = '支付功能未配置，请联系管理员';
        } else {
            $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $params = ['pid' => $yipayConfig['yipay_pid'], 'type' => $payType ?: 'alipay', 'out_trade_no' => $outTradeNo, 'notify_url' => $siteUrl . '/pay_notify.php', 'return_url' => $siteUrl . '/pay_return.php', 'name' => '余额充值', 'money' => number_format($amount, 2, '.', ''), 'param' => $user['id']];
            $sign = getYiPaySign($params, $yipayConfig['yipay_key']);
            $params['sign'] = $sign;
            $params['sign_type'] = 'MD5';
            $payUrl = rtrim($yipayConfig['yipay_url'], '/') . '/submit.php?' . http_build_query($params);
            header('Location: ' . $payUrl);
            exit;
        }
    }
}
$balance = getUserBalance($user['id']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>余额充值 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=246">
<style>
.recharge-card{max-width:480px;margin:0 auto;}
.amount-input{width:100%;padding:16px;font-size:24px;font-weight:700;text-align:center;border:2px solid #e5e7eb;border-radius:12px;outline:none;transition:border .2s;}
.amount-input:focus{border-color:#2563eb;}
.quick-amounts{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:16px 0;}
.quick-amt{padding:10px;text-align:center;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600;transition:all .15s;}
.quick-amt:hover,.quick-amt.active{border-color:#2563eb;color:#2563eb;background:#eff6ff;}
.balance-display{text-align:center;padding:24px;background:linear-gradient(135deg,#2563eb,#1d4ed8);border-radius:16px;color:#fff;margin-bottom:24px;}
.balance-display .label{font-size:13px;opacity:.8;}
.balance-display .value{font-size:36px;font-weight:800;margin-top:4px;}
</style>
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-wallet-3-line"></i></div><span>余额充值</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<?php include 'sidebar.php'; ?>

<main>
<div class="recharge-card">
    <div class="balance-display">
        <div class="label">当前余额</div>
        <div class="value">¥<?= number_format($balance, 2) ?></div>
    </div>

    <?php if ($error): ?><div class="msg error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
        <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;">充值金额</h3>
        <form method="POST" id="rechargeForm">
            <input type="text" class="amount-input" id="amountInput" name="amount" placeholder="0.00" inputmode="decimal">
            <p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:8px;">最低充值¥<?= $minRecharge ?></p>

            <div class="quick-amounts">
                <div class="quick-amt" onclick="setAmount(10)">¥10</div>
                <div class="quick-amt" onclick="setAmount(50)">¥50</div>
                <div class="quick-amt" onclick="setAmount(100)">¥100</div>
                <div class="quick-amt" onclick="setAmount(500)">¥500</div>
            </div>

            <h3 style="font-size:16px;font-weight:700;margin:20px 0 12px;">选择支付方式</h3>
            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
                <?php $first = true; foreach ($enabledPayMethods as $method): ?>
                <?php if (isset($allPayMethods[$method])): ?>
                <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;" onclick="this.querySelector('input').checked=true;this.style.borderColor='#2563eb';this.style.background='#eff6ff';document.querySelectorAll('.pay-label').forEach(l=>{if(l!==this){l.style.borderColor='#e5e7eb';l.style.background='#fff';}})">
                    <input type="radio" name="pay_type" value="<?= $method ?>" <?= $first ? 'checked' : '' ?> style="width:16px;height:16px;">
                    <i class="<?= $allPayMethods[$method]['icon'] ?>" style="font-size:22px;"></i>
                    <span style="font-weight:600;"><?= $allPayMethods[$method]['name'] ?></span>
                </label>
                <?php $first = false; endif; ?>
                <?php endforeach; ?>
                <?php if (empty($enabledPayMethods)): ?>
                <p style="text-align:center;color:#9ca3af;padding:16px;">暂未启用任何支付方式</p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-primary" style="width:100%;padding:14px;font-size:16px;" onclick="return validateRecharge()">
                <i class="ri-shield-check-line"></i> 确认充值
            </button>
        </form>
    </div>

    <div class="card" style="margin-top:16px;">
        <h4 style="font-size:14px;font-weight:700;margin-bottom:8px;color:#6b7280;"><i class="ri-information-line"></i> 充值说明</h4>
        <ul style="font-size:13px;color:#6b7280;line-height:1.8;padding-left:16px;">
            <li>充值金额即时到账，可用于购买会员组等服务</li>
            <li>余额退款将扣除<?= $refundCfg['balance_fee'] ?>%手续费</li>
            <li>如有问题请联系管理员</li>
        </ul>
    </div>
</div>
</main>
<script>
function setAmount(v){
    document.getElementById('amountInput').value = v;
    document.querySelectorAll('.quick-amt').forEach(el=>el.classList.remove('active'));
    event.target.classList.add('active');
}
function validateRecharge(){
    var v = parseFloat(document.getElementById('amountInput').value);
    if(isNaN(v) || v < <?= $minRecharge ?>){
        alert('最低充值金额为¥<?= $minRecharge ?>');
        return false;
    }
    return true;
}
function toggleSidebar(){
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}
</script>
</body>
</html>

