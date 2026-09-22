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
<html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>用户组管理 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><link rel="stylesheet" href="../style.css?v=241"><style>
/* 移动端顶部导航 - 内联防止CSS丢失 */
.mobile-topbar {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    background: #fff;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    z-index: 999;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
}
.mobile-topbar .mobile-logo {
    display: flex; align-items: center; gap: 8px;
    font-weight: 600; font-size: 15px;
}
.mobile-topbar .logo-icon {
    width: 32px; height: 32px;
    background: #2563eb;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 18px;
}
.hamburger-btn {
    width: 40px; height: 40px;
    background: #f3f4f6;
    border: none; border-radius: 10px;
    cursor: pointer;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 5px;
    transition: all 0.3s ease;
}
.hamburger-btn span {
    display: block;
    width: 20px; height: 2px;
    background: #333;
    border-radius: 2px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hamburger-btn.active span:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}
.hamburger-btn.active span:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
}
.hamburger-btn.active span:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.4);
    z-index: 998;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.sidebar-overlay.show {
    display: block;
    opacity: 1;
}
@media (max-width: 768px) {
    .mobile-topbar { display: flex; }
    .sidebar {
        position: fixed !important;
        left: -280px !important;
        top: 0; bottom: 0;
        z-index: 1000 !important;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        width: 280px !important;
        box-shadow: 4px 0 24px rgba(0,0,0,0.12);
        padding-top: 70px !important;
    }
    .sidebar.show { left: 0 !important; }
    main {
        padding: 16px !important;
        padding-top: 72px !important;
        min-width: 0 !important;
        overflow-x: hidden !important;
    }
    body { overflow-x: hidden !important; }
    .overflow-x-auto {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        width: 100% !important;
    }
    .overflow-x-auto table {
        min-width: 600px !important;
        width: auto !important;
    }
}
</style>
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><div class="mobile-logo"><div class="logo-icon"><i class="ri-cloud-line"></i></div><span><?= htmlspecialchars($config['site_name']) ?></span></div><button class="hamburger-btn" onclick="toggleSidebar(this)" aria-label="菜单"><span></span><span></span><span></span></button></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
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
            <a href="index.php"><i class="ri-dashboard-line"></i> 仪表盘</a>
            <a href="users.php"><i class="ri-user-line"></i> 用户管理</a>
            <a href="groups.php" class="active"><i class="ri-group-line"></i> 用户组管理</a>
            <a href="projects.php"><i class="ri-folder-line"></i> 项目管理</a>
            <a href="login_logs.php"><i class="ri-history-line"></i> 登录日志</a>
            <a href="system.php"><i class="ri-server-line"></i> 系统信息</a>
            <a href="config.php"><i class="ri-settings-line"></i> 网站配置</a>
            <a href="upgrade.php"><i class="ri-refresh-line"></i> 在线升级</a>
        </nav>
    </div>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="ri-logout-box-line"></i> 退出登录</a>
    </div>
</aside>
<main class="flex-1 p-6" style="min-width: 0; overflow-x: hidden;">
    <?php if ($message): ?><div class="success mb-4"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error mb-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">用户组管理</h1>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="btn-primary"><i class="ri-add-line"></i> 新增用户组</button>
    </div>

    <div class="glass-card p-4 overflow-x-auto">
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
                    <button onclick="editGroup(<?= $g['id'] ?>, '<?= htmlspecialchars($g['name'], ENT_QUOTES) ?>', <?= $g['project_limit'] ?>, '<?= htmlspecialchars($g['description'] ?? '', ENT_QUOTES) ?>', <?= $g['is_public'] ?>, <?= $g['price'] ?>, <?= $g['duration'] ?>)" class="text-blue-500 hover:underline">编辑</button>
                    <?php if (!$g['is_default']): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('确定删除？该组用户将移至默认组')">
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <button type="submit" class="text-red-500 hover:underline">删除</button>
                    </form>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="set_default"><input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <button type="submit" class="text-green-600 hover:underline">设为默认</button>
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
<div id="addModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
        <h2 class="text-lg font-bold mb-4" id="modalTitle">新增用户组</h2>
        <form method="POST">
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="id" id="modalId" value="">
            <div class="space-y-4">
                <div><label class="block text-sm font-medium mb-1">用户组名称</label><input type="text" name="name" id="modalName" required class="w-full px-3 py-2 border rounded-lg"></div>
                <div><label class="block text-sm font-medium mb-1">项目数限制</label><input type="number" name="project_limit" id="modalLimit" value="5" min="1" required class="w-full px-3 py-2 border rounded-lg"></div>
                <div class="flex items-center gap-3"><label class="block text-sm font-medium">公开售卖</label><input type="checkbox" name="is_public" id="modalPublic" class="w-4 h-4"></div>
                <div><label class="block text-sm font-medium mb-1">价格 (元)</label><input type="number" name="price" id="modalPrice" value="0" min="0" step="0.01" class="w-full px-3 py-2 border rounded-lg"></div>
                <div><label class="block text-sm font-medium mb-1">有效期 (天，0=永久)</label><input type="number" name="duration" id="modalDuration" value="0" min="0" class="w-full px-3 py-2 border rounded-lg"></div>
                <div><label class="block text-sm font-medium mb-1">描述</label><textarea name="description" id="modalDesc" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea></div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="btn-secondary flex-1">取消</button>
                <button type="submit" class="btn-primary flex-1">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
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
    document.getElementById('addModal').classList.remove('hidden');
}
function toggleSidebar(btn) {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
    if (btn) btn.classList.toggle('active');
}
</script>
</body></html>