<?php session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
$pdo = getDB();
$config = getConfig();

$message = '';
$error = '';

// 处理新增/编辑/删除
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $limit = intval($_POST['project_limit'] ?? 5);
        $desc = trim($_POST['description'] ?? '');
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        $price = floatval($_POST['price'] ?? 0);
        $duration = intval($_POST['duration'] ?? 0);
        if (empty($name)) {
            $error = '请输入用户组名称';
        } elseif ($limit < 1) {
            $error = '项目数限制至少为1';
        } else {
            $pdo = getDB();
            $stmt = $pdo->prepare("INSERT INTO user_groups (name, project_limit, description, is_default, is_public, price, duration, created_at) VALUES (?, ?, ?, 0, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $limit, $desc, $isPublic, $price, $duration, time()])) {
                $message = '用户组创建成功';
            } else {
                $error = '创建失败，名称可能已存在';
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $limit = intval($_POST['project_limit'] ?? 5);
        $desc = trim($_POST['description'] ?? '');
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        $price = floatval($_POST['price'] ?? 0);
        $duration = intval($_POST['duration'] ?? 0);
        if (empty($name) || $id < 1) {
            $error = '参数错误';
        } else {
            $pdo = getDB();
            $stmt = $pdo->prepare("UPDATE user_groups SET name = ?, project_limit = ?, description = ?, is_public = ?, price = ?, duration = ? WHERE id = ?");
            if ($stmt->execute([$name, $limit, $desc, $isPublic, $price, $duration, $id])) {
                $message = '用户组更新成功';
            } else {
                $error = '更新失败';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id < 1) {
            $error = '参数错误';
        } else {
            if (deleteUserGroup($id)) {
                $message = '用户组已删除，该组用户已移至默认组';
            } else {
                $error = '删除失败（默认组不可删除）';
            }
        }
    } elseif ($action === 'set_default') {
        $id = intval($_POST['id'] ?? 0);
        if ($id < 1) {
            $error = '参数错误';
        } else {
            $pdo->query("UPDATE user_groups SET is_default = 0");
            $stmt = $pdo->prepare("UPDATE user_groups SET is_default = 1 WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = '已设为默认用户组';
            } else {
                $error = '设置失败';
            }
        }
    }
}

$groups = getAllUserGroups();
// 统计每个组的用户数
$groupStats = [];
foreach ($groups as $g) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE group_id = ?");
    $stmt->execute([$g['id']]);
    $groupStats[$g['id']] = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>用户组管理 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-group-line"></i></div><span>用户组管理</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('show');document.querySelector('.sidebar-overlay').classList.toggle('show');}</script>
<?php include 'sidebar.php'; ?>

<main>
    <?php if ($message): ?><div class="success mb-4"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error mb-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">用户组管理</h1>
        <button onclick="document.getElementById('addModal').style.display='flex'" class="btn-primary"><i class="ri-add-line"></i> 新增用户组</button>
    </div>

    <div class="table-wrap">
        <table class="w-full text-sm">
            <thead class="text-gray-500 border-b">
                <tr><th class="text-left p-3">ID</th><th class="text-left p-3">用户组名称</th><th class="text-left p-3">项目数限制</th><th class="text-left p-3">售卖</th><th class="text-left p-3">价格</th><th class="text-left p-3">有效期</th><th class="text-left p-3">用户数</th><th class="text-left p-3">描述</th><th class="text-left p-3">操作</th></tr>
            </thead>
            <tbody>
            <?php foreach ($groups as $g): ?>
            <tr class="border-t">
                <td class="p-3"><?= $g['id'] ?></td>
                <td class="p-3 font-medium"><?= htmlspecialchars($g['name']) ?> <?php if ($g['is_default']): ?><span class="bg-green-100 text-green-600 px-2 py-0.5 rounded text-xs ml-1">默认</span><?php endif; ?></td>
                <td class="p-3"><?= $g['project_limit'] ?> 个</td>
                <td class="p-3"><?= $g['is_public'] ? '<span class="bg-green-100 text-green-600 px-2 py-0.5 rounded text-xs">公开</span>' : '<span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded text-xs">隐藏</span>' ?></td>
                <td class="p-3">¥<?= $g['price'] ?></td>
                <td class="p-3"><?= $g['duration'] > 0 ? $g['duration'] . '天' : '永久' ?></td>
                <td class="p-3"><?= $groupStats[$g['id']] ?? 0 ?> 人</td>
                <td class="p-3 text-gray-500 text-xs max-w-xs truncate"><?= htmlspecialchars($g['description'] ?? '') ?></td>
                <td class="p-3 space-x-2 whitespace-nowrap">
                    <button onclick="editGroup(<?= $g['id'] ?>, '<?= htmlspecialchars($g['name'], ENT_QUOTES) ?>', <?= $g['project_limit'] ?>, '<?= htmlspecialchars($g['description'] ?? '', ENT_QUOTES) ?>', <?= $g['is_public'] ?>, <?= $g['price'] ?>, <?= $g['duration'] ?>)" class="btn-secondary btn-sm"><i class="ri-edit-line"></i> 编辑</button>
                    <?php if (!$g['is_default']): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('确定删除？该组用户将移至默认组')">
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <button type="submit" class="btn-danger btn-sm"><i class="ri-delete-bin-line"></i> 删除</button>
                    </form>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="set_default"><input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <button type="submit" class="btn-secondary btn-sm"><i class="ri-star-line"></i> 设为默认</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- 新增/编辑模态框 -->
<div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:480px;margin:16px;max-height:90vh;overflow-y:auto;">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:20px;" id="modalTitle">新增用户组</h2>
        <form method="POST">
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="id" id="modalId" value="">
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div class="form-group"><label>用户组名称</label><input type="text" name="name" id="modalName" required class=""></div>
                <div class="form-group"><label>项目数限制</label><input type="number" name="project_limit" id="modalLimit" value="5" min="1" required class=""></div>
                <div class="form-group"><label style="display:flex;align-items:center;gap:8px;">公开售卖</label><input type="checkbox" name="is_public" id="modalPublic" style="width:auto;"></div>
                <div class="form-group"><label>价格 (元)</label><input type="number" name="price" id="modalPrice" value="0" min="0" step="0.01" class=""></div>
                <div class="form-group"><label>有效期 (天，0=永久)</label><input type="number" name="duration" id="modalDuration" value="0" min="0" class=""></div>
                <div class="form-group"><label>描述</label><textarea name="description" id="modalDesc" rows="2" class=""></textarea></div>
            </div>
            <div style="display:flex;gap:10px;margin-top:20px;">
                <button type="button" onclick="document.getElementById('addModal').style.display='none'" class="btn-secondary flex-1">取消</button>
                <button type="submit" class="btn-primary flex-1">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
function openGroupModal() {
    document.getElementById('modalTitle').textContent = '新增用户组';
    document.getElementById('modalAction').value = 'add';
    document.getElementById('modalId').value = '';
    document.getElementById('modalName').value = '';
    document.getElementById('modalLimit').value = 5;
    document.getElementById('modalDesc').value = '';
    document.getElementById('modalPublic').checked = false;
    document.getElementById('modalPrice').value = 0;
    document.getElementById('modalDuration').value = 0;
    document.getElementById('addModal').style.display = 'flex';
}
document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target === this) document.getElementById('addModal').style.display = 'none';
});
function editGroup(id, name, limit, desc, isPublic, price, duration) {
    document.getElementById('modalTitle').textContent = '编辑用户组';
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('modalId').value = id;
    document.getElementById('modalName').value = name;
    document.getElementById('modalLimit').value = limit;
    document.getElementById('modalDesc').value = desc;
    document.getElementById('modalPublic').checked = isPublic == 1;
    document.getElementById('modalPrice').value = price;
    document.getElementById('modalDuration').value = duration;
    document.getElementById('addModal').style.display='flex';
}
function toggleSidebar(btn) {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
    if (btn) btn.classList.toggle('active');
}
</script>
</body></html>



