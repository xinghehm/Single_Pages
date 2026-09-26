<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
$config = getConfig();

// 处理删除
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo = getDB();
    $pdo->prepare("DELETE FROM project_templates WHERE id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM template_files WHERE template_id = ?")->execute([$id]);
    header('Location: templates.php');
    exit;
}

// 处理切换状态
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT is_active FROM project_templates WHERE id = ?");
    $stmt->execute([$id]);
    $tpl = $stmt->fetch();
    if ($tpl) {
        $newStatus = $tpl['is_active'] ? 0 : 1;
        $pdo->prepare("UPDATE project_templates SET is_active = ? WHERE id = ?")->execute([$newStatus, $id]);
    }
    header('Location: templates.php');
    exit;
}

$templates = getAllTemplates(false);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>模板管理 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
</head>
<body class="has-sidebar">
<div class="mobile-topbar">
    <div class="mobile-logo"><div class="logo-icon"><i class="ri-cloud-line"></i></div><span><?= htmlspecialchars($config['site_name']) ?></span></div>
    <button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
</div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('show');document.querySelector('.sidebar-overlay').classList.toggle('show');}</script>
<?php include 'sidebar.php'; ?>

<main>
<div class="page-header">
    <div>
        <h1>模板管理</h1>
        <p class="subtitle">共 <?= count($templates) ?> 个模板</p>
    </div>
    <a href="template_edit.php" class="btn-primary"><i class="ri-add-line"></i> 新建模板</a>
</div>

<div class="table-wrap">
<table>
<thead>
<tr>
    <th>ID</th>
    <th>图标</th>
    <th>模板名称</th>
    <th>分类</th>
    <th>价格</th>
    <th>示例站</th>
    <th>文件数</th>
    <th>状态</th>
    <th>排序</th>
    <th>操作</th>
</tr>
</thead>
<tbody>
<?php foreach ($templates as $tpl): 
    $fileCount = getTemplateFiles($tpl['id']);
?>
<tr>
    <td><?= $tpl['id'] ?></td>
    <td><i class="<?= htmlspecialchars($tpl['icon']) ?>" style="font-size:20px;color:var(--primary);"></i></td>
    <td><strong><?= htmlspecialchars($tpl['name']) ?></strong><br><span class="text-small text-muted"><?= htmlspecialchars($tpl['description']) ?></span></td>
    <td><span class="badge badge-info"><?= htmlspecialchars($tpl['category']) ?></span></td>
    <td><?= $tpl['price'] > 0 ? '¥' . $tpl['price'] : '<span class="badge badge-success">免费</span>' ?></td>
    <td><?= !empty($tpl['demo_url']) ? '<a href="' . htmlspecialchars($tpl['demo_url']) . '" target="_blank" class="text-small">访问</a>' : '<span class="text-muted">-</span>' ?></td>
    <td><?= count($fileCount) ?></td>
    <td>
        <a href="templates.php?toggle=<?= $tpl['id'] ?>">
            <?= $tpl['is_active'] ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-gray">禁用</span>' ?>
        </a>
    </td>
    <td><?= $tpl['sort'] ?></td>
    <td>
        <div class="flex gap-2">
            <a href="template_edit.php?id=<?= $tpl['id'] ?>" class="btn-secondary btn-sm"><i class="ri-edit-line"></i> 编辑</a>
            <a href="template_files.php?id=<?= $tpl['id'] ?>" class="btn-secondary btn-sm"><i class="ri-file-code-line"></i> 文件</a>
            <a href="templates.php?delete=<?= $tpl['id'] ?>" onclick="return confirm('确定删除此模板？')" class="btn-danger btn-sm"><i class="ri-delete-bin-line"></i></a>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</main>
</body>
</html>
