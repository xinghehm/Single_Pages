<?php session_start();
require_once 'functions.php';

$config = getConfig();
$success = false;
$message = '';

// 验证签名
if (verifyYiPaySign($_GET)) {
    $tradeStatus = $_GET['trade_status'] ?? '';
    if ($tradeStatus === 'TRADE_SUCCESS') {
        $outTradeNo = $_GET['out_trade_no'] ?? '';
        $tradeNo = $_GET['trade_no'] ?? '';
        processPaidOrder($outTradeNo, $tradeNo);
        $success = true;
        $message = '支付成功！用户组已开通';
    } else {
        $message = '支付未完成';
    }
} else {
    $message = '签名验证失败';
}
?>
<!DOCTYPE html>
<html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>支付结果 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><link rel="stylesheet" href="style.css?v=241"></head>
<body class="min-h-screen flex items-center justify-center bg-gray-50">
<div class="glass-card p-8 text-center max-w-md w-full mx-4">
    <?php if ($success): ?>
    <div class="w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
        <i class="ri-check-line text-green-500 text-3xl"></i>
    </div>
    <h1 class="text-2xl font-bold mb-2">支付成功</h1>
    <p class="text-gray-500 mb-6"><?= htmlspecialchars($message) ?></p>
    <?php else: ?>
    <div class="w-16 h-16 bg-red-100 rounded-lg flex items-center justify-center mx-auto mb-4">
        <i class="ri-close-line text-red-500 text-3xl"></i>
    </div>
    <h1 class="text-2xl font-bold mb-2">支付失败</h1>
    <p class="text-gray-500 mb-6"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <a href="users/dashboard.php" class="btn-primary inline-block">
        <i class="ri-home-line"></i> 返回控制台
    </a>
</div>
</body></html>