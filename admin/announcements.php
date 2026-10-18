<?php
require_once __DIR__ . '/../functions.php';
if (!isAdminLoggedIn()) { header('Location: login.php'); exit; }
$config = getConfig();

$msg = '';
$msgType = '';

// 处理操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
        if ($title) {
            $pdo = getDB();
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, is_pinned, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $content, $isPinned, time()]);
            $msg = '公告已发布'; $msgType = 'success';
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
        if ($id && $title) {
            $pdo = getDB();
            $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ?, is_pinned = ? WHERE id = ?");
            $stmt->execute([$title, $content, $isPinned, $id]);
            $msg = '公告已更新'; $msgType = 'success';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $pdo = getDB();
            $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->execute([$id]);
            $msg = '公告已删除'; $msgType = 'success';
        }
    }
}

$announcements = getAnnouncements();
$editId = intval($_GET['edit'] ?? 0);
$editItem = $editId ? getAnnouncement($editId) : null;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>公告管理 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-notification-3-line"></i></div><span>公告管理</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('show');document.querySelector('.sidebar-overlay').classList.toggle('show');}</script>
<?php include 'sidebar.php'; ?>

<main>
<h1 class="page-title">公告管理</h1>
<p class="page-desc">发布和管理系统公告，展示在用户端控制台</p>
<?php if ($msg): ?><div class="msg <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card">
<h3><?= $editItem ? '编辑公告' : '发布新公告' ?></h3>
<form method="post">
<input type="hidden" name="action" value="<?= $editItem ? 'edit' : 'add' ?>">
<?php if ($editItem): ?><input type="hidden" name="id" value="<?= intval($editItem['id']) ?>"><?php endif; ?>
<div class="form-group"><label>公告标题</label><input type="text" name="title" value="<?= htmlspecialchars($editItem['title'] ?? '') ?>" placeholder="请输入公告标题" required></div>
<div class="form-group"><label>公告内容</label><textarea name="content" placeholder="请输入公告内容（支持换行）"><?= htmlspecialchars($editItem['content'] ?? '') ?></textarea></div>
<div class="form-group"><div class="checkbox-row"><input type="checkbox" name="is_pinned" id="pinned" <?= ($editItem['is_pinned'] ?? 0) ? 'checked' : '' ?>><label for="pinned" style="margin:0">置顶此公告</label></div></div>
<div style="display:flex;gap:10px">
<button type="submit" class="btn btn-primary"><i class="ri-send-plane-line"></i> <?= $editItem ? '保存修改' : '发布公告' ?></button>
<?php if ($editItem): ?><a href="announcements.php" class="btn btn-secondary">取消编辑</a><?php endif; ?>
</div>
</form>
</div>

<div class="card">
<h3>公告列表（<?= count($announcements) ?>条）</h3>
<?php if (empty($announcements)): ?>
<p style="color:#9ca3af;font-size:14px;text-align:center;padding:20px 0">暂无公告</p>
<?php else: ?>
<div class="ann-list">
<?php foreach ($announcements as $ann): ?>
<div class="ann-item">
<div style="flex:1">
<h4><?php if ($ann['is_pinned']): ?><span class="pinned-tag">置顶</span><?php endif; ?><?= htmlspecialchars($ann['title']) ?></h4>
<?php if ($ann['content']): ?><p><?= nl2br(htmlspecialchars($ann['content'])) ?></p><?php endif; ?>
<div class="date"><?= date('Y-m-d H:i', $ann['created_at']) ?></div>
</div>
<div class="ann-actions">
<a href="announcements.php?edit=<?= intval($ann['id']) ?>" style="background:#eff6ff;color:#3b82f6">编辑</a>
<form method="post" style="display:inline" onsubmit="return confirm('确定删除此公告？')">
<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= intval($ann['id']) ?>">
<button type="submit" class="btn-danger">删除</button>
</form>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
</main>
</body>
</html>

