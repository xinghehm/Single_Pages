<?php session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
require_once '../functions.php';
$user = getCurrentUser();
$config = getConfig();
checkUserGroupExpire($user['id']);

$groups = getPublicGroups();
$currentGroup = getUserGroup($user['group_id'] ?? 1);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupId = intval($_POST['group_id'] ?? 0);
    $payType = $_POST['pay_type'] ?? '';
    
    $group = getUserGroup($groupId);
    if (!$group || !$group['is_public']) {
        $error = '该用户组不可购买';
    } elseif ($group['price'] <= 0) {
        $error = '该用户组价格异常';
    } else {
        // 创建订单
        $outTradeNo = createOrder($user['id'], $groupId, $group['price'], $payType);
        
        // 构建支付参数
        $yipayConfig = getYiPayConfig();
        if (empty($yipayConfig['yipay_url']) || empty($yipayConfig['yipay_pid']) || empty($yipayConfig['yipay_key'])) {
            $error = '支付功能未配置，请联系管理员';
        } else {
            $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $notifyUrl = $siteUrl . '/pay_notify.php';
            $returnUrl = $siteUrl . '/pay_return.php';
            
            $params = [
                'pid' => $yipayConfig['yipay_pid'],
                'type' => $payType ?: 'alipay',
                'out_trade_no' => $outTradeNo,
                'notify_url' => $notifyUrl,
                'return_url' => $returnUrl,
                'name' => $group['name'] . '用户组',
                'money' => number_format($group['price'], 2, '.', ''),
                'param' => $user['id'],
            ];
            
            $sign = getYiPaySign($params, $yipayConfig['yipay_key']);
            $params['sign'] = $sign;
            $params['sign_type'] = 'MD5';
            
            $payUrl = rtrim($yipayConfig['yipay_url'], '/') . '/submit.php?' . http_build_query($params);
            header('Location: ' . $payUrl);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>购买用户组 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><link rel="stylesheet" href="../style.css"></head>
<body class="flex min-h-screen">
<div class="mobile-topbar"><div class="mobile-logo"><div class="logo-icon"><i class="ri-vip-crown-line"></i></div><span>购买用户组</span></div><button class="hamburger-btn" onclick="toggleSidebar(this)" aria-label="菜单"><span></span><span></span><span></span></button></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div><div class="flex items-center gap-2 mb-8"><div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div><div><div class="font-bold"><?= htmlspecialchars($config['site_name']) ?></div><div class="text-xs text-gray-500"><?= htmlspecialchars($user['username']) ?></div></div></div>
    <nav class="space-y-2"><a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 控制台</a><a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 我的项目</a><a href="buy_group.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-vip-crown-line"></i> 购买用户组</a><a href="profile.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-user-line"></i> 个人设置</a></nav></div>
    <div><a href="../logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>
<main class="flex-1 p-6" style="min-width: 0; overflow-x: hidden;">
    <?php if ($message): ?><div class="success mb-4"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error mb-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    
    <h1 class="text-2xl font-bold mb-2">购买用户组</h1>
    <p class="text-gray-500 mb-6">当前用户组：<span class="font-medium text-blue-600"><?= htmlspecialchars($currentGroup['name'] ?? '免费用户') ?></span>（<?= $currentGroup['project_limit'] ?? 5 ?>个项目）</p>
    
    <?php if (empty($groups)): ?>
    <div class="glass-card p-8 text-center text-gray-500">
        <i class="ri-inbox-line text-4xl mb-3"></i>
        <p>暂无可购买的用户组</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <?php foreach ($groups as $g): ?>
        <div class="glass-card p-5 relative <?= $g['id'] == ($user['group_id'] ?? 1) ? 'ring-2 ring-blue-500' : '' ?>">
            <?php if ($g['id'] == ($user['group_id'] ?? 1)): ?>
            <span class="absolute top-3 right-3 bg-blue-500 text-white text-xs px-2 py-1 rounded">当前</span>
            <?php endif; ?>
            <h3 class="text-lg font-bold mb-2"><?= htmlspecialchars($g['name']) ?></h3>
            <div class="text-3xl font-bold text-blue-600 mb-1">¥<?= $g['price'] ?></div>
            <div class="text-sm text-gray-500 mb-4"><?= $g['duration'] > 0 ? $g['duration'] . '天有效期' : '永久有效' ?></div>
            <ul class="text-sm text-gray-600 space-y-2 mb-4">
                <li><i class="ri-check-line text-green-500"></i> 最多 <?= $g['project_limit'] ?> 个项目</li>
                <?php if ($g['description']): ?>
                <li class="text-gray-400"><?= htmlspecialchars($g['description']) ?></li>
                <?php endif; ?>
            </ul>
            <button onclick="showPayModal(<?= $g['id'] ?>, '<?= htmlspecialchars($g['name'], ENT_QUOTES) ?>', <?= $g['price'] ?>)" class="btn-primary w-full">
                <i class="ri-shopping-cart-line"></i> 立即购买
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<!-- 支付方式选择模态框 -->
<div id="payModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
        <h2 class="text-lg font-bold mb-4">选择支付方式</h2>
        <p class="text-gray-500 mb-4">购买 <span id="payGroupName" class="font-medium"></span>，金额 <span id="payGroupPrice" class="font-medium text-blue-600"></span> 元</p>
        <form method="POST" id="payForm">
            <input type="hidden" name="group_id" id="payGroupId">
            <div class="space-y-3 mb-6">
                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-blue-50">
                    <input type="radio" name="pay_type" value="alipay" checked class="w-4 h-4">
                    <i class="ri-alipay-line text-blue-500 text-xl"></i>
                    <span>支付宝</span>
                </label>
                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-green-50">
                    <input type="radio" name="pay_type" value="wxpay" class="w-4 h-4">
                    <i class="ri-wechat-pay-line text-green-500 text-xl"></i>
                    <span>微信支付</span>
                </label>
                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-blue-50">
                    <input type="radio" name="pay_type" value="qqpay" class="w-4 h-4">
                    <i class="ri-qq-line text-blue-400 text-xl"></i>
                    <span>QQ钱包</span>
                </label>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('payModal').classList.add('hidden')" class="btn-secondary flex-1">取消</button>
                <button type="submit" class="btn-primary flex-1">确认支付</button>
            </div>
        </form>
    </div>
</div>

<script>
function showPayModal(id, name, price) {
    document.getElementById('payGroupId').value = id;
    document.getElementById('payGroupName').textContent = name;
    document.getElementById('payGroupPrice').textContent = price;
    document.getElementById('payModal').classList.remove('hidden');
}
function toggleSidebar(btn) {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
    if (btn) btn.classList.toggle('active');
}
</script>
</body></html>