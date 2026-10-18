<?php
require_once __DIR__ . '/../functions.php';
if (!isAdminLoggedIn()) { header('Location: login.php'); exit; }
$config = getConfig();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template'])) {
    $tpl = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['template']);
    $tplFile = __DIR__ . '/../templates/' . $tpl . '/index.php';
    if (file_exists($tplFile)) {
        updateConfig('home_template', $tpl);
        $msg = '模板已切换为：' . htmlspecialchars($tpl);
    }
}

$templates = listTemplates();
$current = getHomeTemplate();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>首页模板 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
<style>
.tpl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.tpl-card{background:#fff;border-radius:12px;padding:20px;border:2px solid #e5e7eb;transition:all .2s}
.tpl-card.active{border-color:#3b82f6;box-shadow:0 4px 16px rgba(59,130,246,.12)}
.tpl-card .header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px}
.tpl-card .tname{font-size:16px;font-weight:700}
.tpl-card .meta{font-size:12px;color:#6b7280;line-height:1.8;margin-bottom:14px}
.tpl-card .meta span{display:block}
.tpl-card .desc{font-size:12px;color:#9ca3af;margin-bottom:14px;min-height:32px}
.tpl-card button{width:100%;padding:10px;border-radius:8px;border:none;font-size:13px;font-weight:600;cursor:pointer}
.tpl-card button.use{background:#3b82f6;color:#fff}
.tpl-card button.disabled{background:#f3f4f6;color:#9ca3af;cursor:default}
.tpl-card .edit-btn{display:block;width:100%;padding:10px;border-radius:8px;border:1.5px solid #e5e7eb;font-size:13px;font-weight:600;color:#6b7280;text-align:center;text-decoration:none;margin-top:8px}
</style>
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-layout-line"></i></div><span>首页模板</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('show');document.querySelector('.sidebar-overlay').classList.toggle('show');}</script>
<?php include 'sidebar.php'; ?>

<main>
<div class="page-header">
<div><h1>首页模板</h1><p class="subtitle">选择网站首页使用的模板，模板文件位于 /templates/ 目录下</p></div>
</div>

<?php if (!empty($msg)): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

<div class="tpl-grid">
<?php foreach ($templates as $tpl): ?>
<div class="tpl-card <?= $tpl['active'] ? 'active' : '' ?>">
<div class="header">
<div class="tname"><?= htmlspecialchars($tpl['name']) ?></div>
<span class="badge <?= $tpl['active'] ? 'badge-success' : 'badge-gray' ?>"><?= $tpl['active'] ? '使用中' : '未启用' ?></span>
</div>
<div class="meta">
<span><i class="ri-user-line"></i> 作者：<?= htmlspecialchars($tpl['author'] ?: '未知') ?></span>
<span><i class="ri-global-line"></i> 官网：<?= !empty($tpl['website']) ? '<a href="'.htmlspecialchars($tpl['website']).'" target="_blank">访问</a>' : '无' ?></span>
<span><i class="ri-code-s-slash-line"></i> 版本：<?= htmlspecialchars($tpl['version']) ?></span>
</div>
<div class="desc"><?= htmlspecialchars($tpl['description']) ?></div>
<?php if ($tpl['active']): ?>
<button class="disabled" disabled>当前使用中</button>
<?php else: ?>
<form method="post"><input type="hidden" name="template" value="<?= htmlspecialchars($tpl['dir']) ?>"><button type="submit" class="use">启用此模板</button></form>
<a href="home_template_edit.php?tpl=<?= htmlspecialchars($tpl['dir']) ?>" class="edit-btn">编辑模板</a>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</main>
</body>
</html>
