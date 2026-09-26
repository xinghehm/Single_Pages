<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
$config = getConfig();

$templateId = intval($_GET['template'] ?? 0);
$fileId = intval($_GET['file'] ?? 0);

// 获取模板信息
$stmt = getDB()->prepare("SELECT * FROM project_templates WHERE id = ?");
$stmt->execute([$templateId]);
$tpl = $stmt->fetch();
if (!$tpl) { header('Location: templates.php'); exit; }

// 获取文件信息
$stmt = getDB()->prepare("SELECT * FROM template_files WHERE id = ? AND template_id = ?");
$stmt->execute([$fileId, $templateId]);
$file = $stmt->fetch();
if (!$file) { header("Location: template_files.php?id=$templateId"); exit; }

// 处理保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = trim($_POST['filename'] ?? '');
    $content = $_POST['content'] ?? '';
    if ($filename) {
        getDB()->prepare("UPDATE template_files SET filename = ?, content = ? WHERE id = ? AND template_id = ?")
            ->execute([$filename, $content, $fileId, $templateId]);
    }
    header("Location: template_files.php?id=$templateId");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>编辑文件 - <?= htmlspecialchars($tpl['name']) ?> - <?= htmlspecialchars($config['site_name']) ?></title>
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
        <h1>编辑文件</h1>
        <p class="subtitle"><?= htmlspecialchars($tpl['name']) ?> / <?= htmlspecialchars($file['filename']) ?></p>
    </div>
    <a href="template_files.php?id=<?= $templateId ?>" class="btn-secondary"><i class="ri-arrow-left-line"></i> 返回文件列表</a>
</div>

<div class="card">
<form method="post">
    <div class="form-group">
        <label>文件名</label>
        <input type="text" name="filename" value="<?= htmlspecialchars($file['filename']) ?>" required>
    </div>
    <div class="form-group">
        <label>文件内容</label>
        <textarea name="content" rows="24" style="font-family:'Courier New',monospace;font-size:13px;line-height:1.6;"><?= htmlspecialchars($file['content']) ?></textarea>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="btn-primary"><i class="ri-save-line"></i> 保存文件</button>
        <a href="template_files.php?id=<?= $templateId ?>" class="btn-secondary">取消</a>
    </div>
</form>
</div>
</main>
</body>
</html>
