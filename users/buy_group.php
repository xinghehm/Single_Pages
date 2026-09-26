<?php session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
require_once '../functions.php';
$user = getCurrentUser();
$config = getConfig();
checkUserGroupExpire($user['id']);

$groups = getPublicGroups();
$enabledPayMethods = explode(',', $config['yipay_pay_methods'] ?? 'alipay,wxpay,qqpay');
$allPayMethods = [
    'alipay' => ['name' => '支付宝', 'icon' => 'ri-alipay-line', 'color' => 'text-blue-500'],
    'wxpay' => ['name' => '微信支付', 'icon' => 'ri-wechat-pay-line', 'color' => 'text-green-500'],
    'qqpay' => ['name' => 'QQ钱包', 'icon' => 'ri-qq-line', 'color' => 'text-blue-400'],
];
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
    } elseif (!in_array($payType, $enabledPayMethods)) {
        $error = '该支付方式未启用';
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
<html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>购买用户组 - <?= htmlspecialchars($config['site_name']) ?></title><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><link rel="stylesheet" href="../style.css?v=245"><style>
/* 移动端顶部导航 - 内联防止CSS丢失 */
.mobile-topbar {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    background: #fff;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    z-index: 999;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
}
.mobile-topbar .mobile-logo {
    display: flex; align-items: center; gap: 8px;
    font-weight: 600; font-size: 15px;
}
.mobile-topbar .logo-icon {
    width: 32px; height: 32px;
    background: #2563eb;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 18px;
}
.hamburger-btn {
    width: 40px; height: 40px;
    background: #f3f4f6;
    border: none; border-radius: 10px;
    cursor: pointer;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 5px;
    transition: all 0.3s ease;
}
.hamburger-btn span {
    display: block;
    width: 20px; height: 2px;
    background: #333;
    border-radius: 2px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hamburger-btn.active span:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}
.hamburger-btn.active span:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
}
.hamburger-btn.active span:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.4);
    z-index: 998;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.sidebar-overlay.show {
    display: block;
    opacity: 1;
}
@media (max-width: 768px) {
    .mobile-topbar { display: flex; }
    .sidebar {
        position: fixed !important;
        left: -280px !important;
        top: 0; bottom: 0;
        z-index: 1000 !important;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        width: 280px !important;
        box-shadow: 4px 0 24px rgba(0,0,0,0.12);
        padding-top: 70px !important;
    }
    .sidebar.show { left: 0 !important; }
    main {
        padding: 16px !important;
        padding-top: 72px !important;
        min-width: 0 !important;
        overflow-x: hidden !important;
    }
    body { overflow-x: hidden !important; }
    .overflow-x-auto {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        width: 100% !important;
    }
    .overflow-x-auto table {
        min-width: 600px !important;
        width: auto !important;
    }
}
</style>
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><div class="mobile-logo"><div class="logo-icon"><i class="ri-vip-crown-line"></i></div><span>购买用户组</span></div><button class="hamburger-btn" onclick="toggleSidebar(this)" aria-label="菜单"><span></span><span></span><span></span></button></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<aside class="sidebar">
    <div>
        <div class="brand">
            <div class="logo"><i class="ri-cloud-line"></i></div>
            <div><div class="name"><?= htmlspecialchars($config['site_name']) ?></div><div class="sub">Pages</div></div>
        </div>
        <nav>
            <a href="dashboard.php"><i class="ri-dashboard-line"></i> 控制台</a>
            <a href="templates.php"><i class="ri-apps-line"></i> 模板市场</a>
            <a href="projects.php"><i class="ri-folder-line"></i> 我的项目</a>
            <a href="login_logs.php"><i class="ri-history-line"></i> 登录日志</a>
            <a href="buy_group.php" class="active"><i class="ri-vip-crown-line"></i> 购买用户组</a>
            <a href="profile.php"><i class="ri-user-line"></i> 个人中心</a>
            </nav>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php" style="color:#dc2626;"><i class="ri-logout-box-line"></i> 退出登录</a>
    </div>
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
                <?php if (empty($enabledPayMethods) || count($enabledPayMethods) == 0): ?>
                <div class="text-center text-gray-500 py-4">
                    <i class="ri-information-line text-2xl mb-2"></i>
                    <p>暂未启用任何支付方式，请联系管理员</p>
                </div>
                <?php else: ?>
                <?php $first = true; foreach ($enabledPayMethods as $method): ?>
                <?php if (isset($allPayMethods[$method])): ?>
                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="pay_type" value="<?= $method ?>" <?= $first ? 'checked' : '' ?> class="w-4 h-4">
                    <i class="<?= $allPayMethods[$method]['icon'] ?> <?= $allPayMethods[$method]['color'] ?> text-xl"></i>
                    <span><?= $allPayMethods[$method]['name'] ?></span>
                </label>
                <?php $first = false; endif; ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
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