<?php
if (!isset($config)) { $config = getConfig(); }
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$menuItems = [
    ['index.php', 'ri-dashboard-line', '仪表盘'],
    ['users.php', 'ri-user-line', '用户管理'],
    ['groups.php', 'ri-group-line', '用户组管理'],
    ['projects.php', 'ri-folder-line', '项目管理'],
    ['login_logs.php', 'ri-history-line', '登录日志'],
    ['system.php', 'ri-server-line', '系统信息'],
    ['config.php', 'ri-settings-line', '网站配置'],
    ['upgrade.php', 'ri-refresh-line', '在线升级'],
];
?>
<aside class="sidebar">
    <div>
        <div class="brand">
            <div class="logo"><i class="ri-cloud-line"></i></div>
            <div>
                <div class="name"><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></div>
                <div class="sub">管理后台</div>
            </div>
        </div>
        <nav>
<?php foreach ($menuItems as $item): ?>
            <a href="<?= $item[0] ?>"<?= $currentPage === $item[0] ? ' class="active"' : '' ?>><i class="<?= $item[1] ?>"></i> <?= $item[2] ?></a>
<?php endforeach; ?>
        </nav>
    </div>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="ri-logout-box-line"></i> 退出登录</a>
    </div>
</aside>
