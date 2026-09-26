<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
$config = getConfig();

$id = intval($_GET['id'] ?? 0);
$stmt = getDB()->prepare("SELECT * FROM project_templates WHERE id = ?");
$stmt->execute([$id]);
$tpl = $stmt->fetch();
if (!$tpl) { header('Location: templates.php'); exit; }

// 处理添加文件
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $filename = trim($_POST['filename'] ?? '');
    $content = $_POST['content'] ?? '';
    $is_index = isset($_POST['is_index']) ? 1 : 0;
    if ($filename) {
        if ($is_index) {
            getDB()->prepare("UPDATE template_files SET is_index = 0 WHERE template_id = ?")->execute([$id]);
        }
        getDB()->prepare("INSERT INTO template_files (template_id, filename, content, is_index) VALUES (?,?,?,?)")->execute([$id, $filename, $content, $is_index]);
    }
    header("Location: template_files.php?id=$id");
    exit;
}

// 处理删除文件
if (isset($_GET['delfile'])) {
    $fid = intval($_GET['delfile']);
    getDB()->prepare("DELETE FROM template_files WHERE id = ?")->execute([$fid]);
    header("Location: template_files.php?id=$id");
    exit;
}

// 处理设为首页
if (isset($_GET['setindex'])) {
    $fid = intval($_GET['setindex']);
    getDB()->prepare("UPDATE template_files SET is_index = 0 WHERE template_id = ?")->execute([$id]);
    getDB()->prepare("UPDATE template_files SET is_index = 1 WHERE id = ?")->execute([$fid]);
    header("Location: template_files.php?id=$id");
    exit;
}

$files = getTemplateFiles($id);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>模板文件 - <?= htmlspecialchars($tpl['name']) ?> - <?= htmlspecialchars($config['site_name']) ?></title>
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
        <h1>模板文件</h1>
        <p class="subtitle"><?= htmlspecialchars($tpl['name']) ?> · 共 <?= count($files) ?> 个文件</p>
    </div>
    <a href="template_edit.php?id=<?= $id ?>" class="btn-primary"><i class="ri-edit-line"></i> 编辑模板信息</a>
    <a href="templates.php" class="btn-secondary"><i class="ri-arrow-left-line"></i> 返回模板列表</a>
</div>

<!-- 添加文件 -->
<div class="card mb-6">
<h3 class="section-title"><i class="ri-add-line"></i> 添加文件</h3>
<form method="post">
<input type="hidden" name="action" value="add">
<div class="form-group">
    <label>文件名</label>
    <input type="text" name="filename" placeholder="index.html / css/style.css / js/app.js" required>
</div>
<div class="form-group">
    <label>文件内容</label>
    <textarea name="content" rows="8" style="font-family:monospace;font-size:13px;" placeholder="<!DOCTYPE html>..."></textarea>
</div>
<div class="form-group">
    <label style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" name="is_index" style="width:auto;">
        设为首页 (index.html，用户创建项目时默认打开)
    </label>
</div>
<button type="submit" class="btn-primary"><i class="ri-add-line"></i> 添加文件</button>
</form>
</div>

<!-- 文件列表 -->
<?php if (empty($files)): ?>
<div class="empty-state">
    <div class="icon-wrap"><i class="ri-file-code-line"></i></div>
    <h3>暂无文件</h3>
    <p>在上方添加模板文件</p>
</div>
<?php else: ?>
<div class="table-wrap">
<table>
<thead>
<tr><th>ID</th><th>文件名</th><th>大小</th><th>首页</th><th>操作</th></tr>
</thead>
<tbody>
<?php foreach ($files as $f): ?>
<tr>
    <td><?= $f['id'] ?></td>
    <td><i class="ri-file-code-line"></i> <?= htmlspecialchars($f['filename']) ?></td>
    <td><?= strlen($f['content']) ?> 字节</td>
    <td><?= $f['is_index'] ? '<span class="badge badge-success">首页</span>' : '-' ?></td>
    <td>
        <div class="flex gap-2">
            <a href="template_file_edit.php?template=<?= $id ?>&file=<?= $f['id'] ?>" class="btn-primary btn-sm"><i class="ri-edit-line"></i> 编辑</a>
            <?php if (!$f['is_index']): ?>
            <a href="template_files.php?id=<?= $id ?>&setindex=<?= $f['id'] ?>" class="btn-secondary btn-sm">设为首页</a>
            <?php endif; ?>
            <a href="template_files.php?id=<?= $id ?>&delfile=<?= $f['id'] ?>" onclick="return confirm('删除此文件？')" class="btn-danger btn-sm"><i class="ri-delete-bin-line"></i></a>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</main>
</body>
</html>
