<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
$config = getConfig();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tpl = ['id' => 0, 'name' => '', 'description' => '', 'icon' => 'ri-file-code-line', 'category' => '通用', 'demo_url' => '', 'price' => 0, 'image' => '', 'sort' => 0, 'is_active' => 1];

if ($id > 0) {
    $stmt = getDB()->prepare("SELECT * FROM project_templates WHERE id = ?");
    $stmt->execute([$id]);
    $tpl = $stmt->fetch();
    if (!$tpl) { header('Location: templates.php'); exit; }
}

// 处理保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'ri-file-code-line');
    $category = trim($_POST['category'] ?? '通用');
    $demo_url = trim($_POST['demo_url'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $image = trim($_POST['image'] ?? '');
    $sort = intval($_POST['sort'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id > 0) {
        $stmt = getDB()->prepare("UPDATE project_templates SET name=?, description=?, icon=?, category=?, demo_url=?, price=?, image=?, sort=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $description, $icon, $category, $demo_url, $price, $image, $sort, $is_active, $id]);
    } else {
        $stmt = getDB()->prepare("INSERT INTO project_templates (name, description, icon, category, demo_url, price, image, sort, is_active, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $description, $icon, $category, $demo_url, $price, $image, $sort, $is_active, time()]);
        $id = getDB()->lastInsertId();
    }
    header('Location: templates.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $id > 0 ? '编辑模板' : '新建模板' ?> - <?= htmlspecialchars($config['site_name']) ?></title>
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
        <h1><?= $id > 0 ? '编辑模板' : '新建模板' ?></h1>
        <p class="subtitle">配置模板基本信息</p>
    </div>
    <a href="templates.php" class="btn-secondary"><i class="ri-arrow-left-line"></i> 返回</a>
</div>

<div class="card" style="max-width:700px;">
<form method="post">
    <div class="grid grid-cols-2">
        <div class="form-group">
            <label>模板名称 *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($tpl['name']) ?>" required placeholder="如：个人主页">
        </div>
        <div class="form-group">
            <label>图标 (Remix Icon 类名)</label>
            <input type="text" name="icon" value="<?= htmlspecialchars($tpl['icon']) ?>" placeholder="ri-user-smile-line">
            <div class="form-text">参考 <a href="https://remixicon.com/" target="_blank">remixicon.com</a></div>
        </div>
    </div>
    <div class="form-group">
        <label>模板描述</label>
        <textarea name="description" rows="2" placeholder="简短描述模板用途"><?= htmlspecialchars($tpl['description']) ?></textarea>
    </div>
    <div class="grid grid-cols-2">
        <div class="form-group">
            <label>分类</label>
            <input type="text" name="category" value="<?= htmlspecialchars($tpl['category']) ?>" placeholder="通用/个人/营销...">
        </div>
        <div class="form-group">
            <label>价格 (元，0=免费)</label>
            <input type="number" name="price" step="0.01" min="0" value="<?= $tpl['price'] ?>" placeholder="0">
        </div>
    </div>
    <div class="form-group">
        <label>示例站链接 (选填)</label>
        <input type="url" name="demo_url" value="<?= htmlspecialchars($tpl['demo_url']) ?>" placeholder="https://demo.example.com">
        <div class="form-text">填了之后用户端会出现"访问实例站"按钮</div>
    </div>
    <div class="form-group">
        <label>模板封面图 URL (选填)</label>
        <input type="url" name="image" value="<?= htmlspecialchars($tpl['image']) ?>" placeholder="https://.../cover.png">
        <div class="form-text">不填则使用图标+渐变色作为封面</div>
    </div>
    <div class="grid grid-cols-2">
        <div class="form-group">
            <label>排序 (数字越小越靠前)</label>
            <input type="number" name="sort" value="<?= $tpl['sort'] ?>">
        </div>
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;margin-top:24px;">
                <input type="checkbox" name="is_active" <?= $tpl['is_active'] ? 'checked' : '' ?> style="width:auto;">
                启用此模板
            </label>
        </div>
    </div>
    <div class="flex gap-3 mt-4">
        <button type="submit" class="btn-primary"><i class="ri-save-line"></i> 保存</button>
        <?php if ($id > 0): ?>
        <a href="template_files.php?id=<?= $id ?>" class="btn-secondary"><i class="ri-file-code-line"></i> 管理模板文件</a>
        <?php endif; ?>
    </div>
</form>
</div>
</main>
</body>
</html>
