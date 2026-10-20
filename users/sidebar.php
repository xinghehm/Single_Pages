<?php
if (!isset($config)) { $config = getConfig(); }
if (!isset($user)) { $user = getCurrentUser(); }
$currentUserPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$userMenuItems = [
    ['dashboard.php', 'ri-dashboard-line', '控制台'],
    ['recharge.php', 'ri-wallet-3-line', '余额充值'],
    ['templates.php', 'ri-apps-line', '模板市场'],
    ['projects.php', 'ri-folder-line', '我的项目'],
    ['orders.php', 'ri-file-list-3-line', '我的订单'],
    ['login_logs.php', 'ri-history-line', '登录日志'],
    ['buy_group.php', 'ri-vip-crown-line', '购买用户组'],
    ['profile.php', 'ri-user-line', '个人中心'],
];
?>
<aside class="sidebar">
    <div class="brand"><?php if (!empty($config['site_logo'])): ?><div class="logo" style="background:transparent;"><img src="<?= htmlspecialchars($config['site_logo']) ?>" style="width:36px;height:36px;border-radius:8px;object-fit:cover;"></div><?php else: ?><div class="logo"><i class="ri-cloud-line"></i></div><?php endif; ?><div><div class="name"><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></div><div class="sub">Pages</div></div></div>
    <nav>
<?php foreach ($userMenuItems as $item): ?>
        <a href="<?= $item[0] ?>"<?= $currentUserPage === $item[0] ? ' class="active"' : '' ?>><i class="<?= $item[1] ?>"></i> <?= $item[2] ?></a>
<?php endforeach; ?>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php" style="color:#dc2626;"><i class="ri-logout-box-line"></i> 退出登录</a></div>
</aside>

