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

// 处理新增/编辑
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
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
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-apps-line"></i></div><span>模板管理</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<?php include 'sidebar.php'; ?>

<main>
<div class="page-header">
    <div><h1>模板管理</h1><p class="subtitle">共 <?= count($templates) ?> 个模板</p></div>
    <button onclick="openTplModal()" class="btn-primary"><i class="ri-add-line"></i> 新建模板</button>
</div>

<div class="table-wrap">
<table>
<thead>
<tr>
    <th>ID</th><th>图标</th><th>模板名称</th><th>分类</th><th>价格</th><th>示例站</th><th>文件数</th><th>状态</th><th>排序</th><th>操作</th>
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
    <td><a href="templates.php?toggle=<?= $tpl['id'] ?>"><?= $tpl['is_active'] ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-gray">禁用</span>' ?></a></td>
    <td><?= $tpl['sort'] ?></td>
    <td>
        <div class="flex gap-2" style="flex-wrap:wrap;">
            <button onclick="openTplModal(<?= $tpl['id'] ?>, '<?= htmlspecialchars($tpl['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($tpl['description'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($tpl['icon']) ?>', '<?= htmlspecialchars($tpl['category']) ?>', '<?= htmlspecialchars($tpl['demo_url'] ?? '', ENT_QUOTES) ?>', <?= $tpl['price'] ?>, '<?= htmlspecialchars($tpl['image'] ?? '', ENT_QUOTES) ?>', <?= $tpl['sort'] ?>, <?= $tpl['is_active'] ?>)" class="btn-secondary btn-sm"><i class="ri-edit-line"></i> 编辑</button>
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

<!-- 新增/编辑模板弹窗 -->
<div id="tplModal" class="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:560px;margin:16px;max-height:90vh;overflow-y:auto;">
        <h3 id="tplModalTitle" style="font-size:18px;font-weight:700;margin-bottom:20px;">新建模板</h3>
        <form method="post">
            <input type="hidden" name="id" id="tpl_id" value="0">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group"><label>模板名称 *</label><input type="text" name="name" id="tpl_name" required placeholder="如：个人主页"></div>
                <div class="form-group"><label>图标 (Remix Icon)</label><input type="text" name="icon" id="tpl_icon" value="ri-file-code-line" placeholder="ri-user-smile-line"></div>
            </div>
            <div class="form-group"><label>模板描述</label><textarea name="description" id="tpl_desc" rows="2" placeholder="简短描述模板用途"></textarea></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group"><label>分类</label><input type="text" name="category" id="tpl_category" value="通用" placeholder="通用/个人/营销"></div>
                <div class="form-group"><label>价格 (元，0=免费)</label><input type="number" name="price" id="tpl_price" step="0.01" min="0" value="0"></div>
            </div>
            <div class="form-group"><label>示例站链接 (选填)</label><input type="url" name="demo_url" id="tpl_demo" placeholder="https://demo.example.com"></div>
            <div class="form-group"><label>封面图 URL (选填)</label><input type="url" name="image" id="tpl_image" placeholder="https://.../cover.png"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group"><label>排序 (小的靠前)</label><input type="number" name="sort" id="tpl_sort" value="0"></div>
                <div class="form-group"><label style="display:flex;align-items:center;gap:8px;margin-top:24px;"><input type="checkbox" name="is_active" id="tpl_active" checked style="width:auto;"> 启用此模板</label></div>
            </div>
            <div style="display:flex;gap:10px;margin-top:20px;">
                <button type="button" onclick="closeTplModal()" class="btn-secondary flex-1">取消</button>
                <button type="submit" class="btn-primary flex-1"><i class="ri-save-line"></i> 保存</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSidebar(){
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}
function openTplModal(id, name, desc, icon, category, demo, price, image, sort, active) {
    document.getElementById('tplModalTitle').textContent = id ? '编辑模板' : '新建模板';
    document.getElementById('tpl_id').value = id || 0;
    document.getElementById('tpl_name').value = name || '';
    document.getElementById('tpl_desc').value = desc || '';
    document.getElementById('tpl_icon').value = icon || 'ri-file-code-line';
    document.getElementById('tpl_category').value = category || '通用';
    document.getElementById('tpl_demo').value = demo || '';
    document.getElementById('tpl_price').value = price || 0;
    document.getElementById('tpl_image').value = image || '';
    document.getElementById('tpl_sort').value = sort || 0;
    document.getElementById('tpl_active').checked = active ? true : false;
    document.getElementById('tplModal').style.display = 'flex';
}
function closeTplModal() {
    document.getElementById('tplModal').style.display = 'none';
}
document.getElementById('tplModal').addEventListener('click', function(e) {
    if (e.target === this) closeTplModal();
});
</script>
</body>
</html>
