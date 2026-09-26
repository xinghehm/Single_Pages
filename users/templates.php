<?php
require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$config = getConfig();
$templates = getAllTemplates(true);

// 渐变色封面
function tplCover($name) {
    $gradients = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
    ];
    return $gradients[crc32($name) % count($gradients)];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>模板市场 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
<style>
.tpl-market-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-direction: column;
}
.tpl-market-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: #c7d2fe;
}
.tpl-market-card .cover {
    width: 100%;
    aspect-ratio: 16/9;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}
.tpl-market-card .cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.tpl-market-card .cover .icon-big {
    font-size: 48px;
    color: #fff;
    position: relative;
    z-index: 1;
}
.tpl-market-card .cover::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 50%, rgba(0,0,0,0.25));
}
.tpl-market-card .price-tag {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 2;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    backdrop-filter: blur(10px);
}
.tpl-market-card .price-tag.free {
    background: rgba(34,197,94,0.9);
    color: #fff;
}
.tpl-market-card .price-tag.paid {
    background: rgba(255,255,255,0.9);
    color: #dc2626;
}
.tpl-market-card .body {
    padding: 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.tpl-market-card .title {
    font-weight: 600;
    font-size: 16px;
    color: var(--text-1);
    margin-bottom: 6px;
}
.tpl-market-card .desc {
    font-size: 13px;
    color: var(--text-3);
    margin-bottom: 12px;
    flex: 1;
    line-height: 1.5;
}
.tpl-market-card .actions {
    display: flex;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid var(--border-light);
}
.tpl-market-card .actions .btn {
    flex: 1;
    text-align: center;
    padding: 8px 12px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    text-decoration: none;
    transition: var(--transition);
    border: none;
    cursor: pointer;
}
.tpl-market-card .actions .btn-use {
    background: var(--primary);
    color: #fff;
}
.tpl-market-card .actions .btn-use:hover {
    background: var(--primary-hover);
}
.tpl-market-card .actions .btn-demo {
    background: var(--border-light);
    color: var(--text-2);
}
.tpl-market-card .actions .btn-demo:hover {
    background: var(--border);
}
</style>
</head>
<body class="has-sidebar">
<div class="mobile-topbar">
    <div class="mobile-logo"><div class="logo-icon"><i class="ri-cloud-line"></i></div><span><?= htmlspecialchars($config['site_name']) ?></span></div>
    <button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
</div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('show');document.querySelector('.sidebar-overlay').classList.toggle('show');}</script>

<aside class="sidebar">
    <div>
        <div class="brand">
            <div class="logo"><i class="ri-cloud-line"></i></div>
            <div><div class="name"><?= htmlspecialchars($config['site_name']) ?></div><div class="sub">Pages</div></div>
        </div>
        <nav>
            <a href="dashboard.php"><i class="ri-dashboard-line"></i> 控制台</a>
            <a href="templates.php" class="active"><i class="ri-apps-line"></i> 模板市场</a>
            <a href="projects.php"><i class="ri-folder-line"></i> 我的项目</a>
            <a href="login_logs.php"><i class="ri-history-line"></i> 登录日志</a>
            <a href="buy_group.php"><i class="ri-vip-crown-line"></i> 购买用户组</a>
            <a href="profile.php"><i class="ri-user-line"></i> 个人中心</a>
        </nav>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php" style="color:#dc2626;"><i class="ri-logout-box-line"></i> 退出登录</a>
    </div>
</aside>

<main>
<div class="page-header">
    <div>
        <h1>模板市场</h1>
        <p class="subtitle">精选模板，一键创建，共 <?= count($templates) ?> 个模板</p>
    </div>
</div>

<?php if (empty($templates)): ?>
<div class="empty-state">
    <div class="icon-wrap"><i class="ri-apps-line"></i></div>
    <h3>暂无模板</h3>
    <p>管理员还未上架任何模板</p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
<?php foreach ($templates as $tpl): ?>
<div class="tpl-market-card">
    <?php if (!empty($tpl['image'])): ?>
    <div class="cover" style="background:url(<?= htmlspecialchars($tpl['image']) ?>) center/cover;">
        <span class="price-tag <?= $tpl['price'] > 0 ? 'paid' : 'free' ?>">
            <?= $tpl['price'] > 0 ? '¥' . $tpl['price'] : '免费' ?>
        </span>
    </div>
    <?php else: ?>
    <div style="padding:16px 16px 0;">
        <span class="price-tag <?= $tpl['price'] > 0 ? 'paid' : 'free' ?>" style="position:static;display:inline-block;margin-bottom:8px;">
            <?= $tpl['price'] > 0 ? '¥' . $tpl['price'] : '免费' ?>
        </span>
    </div>
    <?php endif; ?>
    <div class="body">
        <div class="title"><?= htmlspecialchars($tpl['name']) ?></div>
        <div class="desc"><?= htmlspecialchars($tpl['description']) ?></div>
        <div class="actions">
            <a href="project_new.php?name=<?= urlencode($tpl['name'] . '_副本') ?>&template=<?= $tpl['id'] ?>" class="btn btn-use">
                <i class="ri-add-line"></i> 使用模板
            </a>
            <?php if (!empty($tpl['demo_url'])): ?>
            <a href="<?= htmlspecialchars($tpl['demo_url']) ?>" target="_blank" class="btn btn-demo">
                <i class="ri-external-link-line"></i> 实例站
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</main>
</body>
</html>
