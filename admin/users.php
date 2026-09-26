<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../functions.php';

// 处理修改用户组
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'set_group') {
        $userId = intval($_POST['user_id'] ?? 0);
        $groupId = intval($_POST['group_id'] ?? 0);
        if ($userId > 0 && $groupId > 0) {
            setUserGroup($userId, $groupId);
        }
    }
}
$groups = getAllUserGroups();


$pdo = getDB();
$error = '';
$success = '';

// 处理编辑用户
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = (int)$_POST['user_id'];
    
    if ($_POST['action'] === 'edit_user') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $quota = (int)$_POST['quota'];
        $new_password = $_POST['new_password'] ?? '';
        
        // 验证
        if (empty($username) || empty($email)) {
            $error = '用户名和邮箱不能为空';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '邮箱格式不正确';
        } elseif (!empty($phone) && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            $error = '手机号格式不正确';
        } else {
            try {
                // 检查用户名/邮箱是否已被其他用户使用
                $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $userId]);
                if ($stmt->fetch()) {
                    $error = '用户名或邮箱已被其他用户使用';
                } else {
                    // 更新用户信息
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, quota = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $phone, $quota, $userId]);
                    
                    // 如果填写了新密码，更新密码
                    if (!empty($new_password)) {
                        if (strlen($new_password) < 6) {
                            $error = '新密码至少6位';
                        } else {
                            $hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt->execute([$hash, $userId]);
                        }
                    }
                    
                    if (empty($error)) {
                        $success = '用户信息已更新';
                        // 如果修改的是当前登录用户，更新 session 中的用户名
                        if ($userId == $_SESSION['admin_id']) {
                            $_SESSION['admin_username'] = $username;
                        }
                    }
                }
            } catch (PDOException $e) {
                $error = '更新失败：' . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'delete_user') {
        $userId = (int)$_POST['user_id'];
        // 不能删除管理员自己
        if ($userId == $_SESSION['admin_id']) {
            $error = '不能删除当前登录的管理员账号';
        } else {
            // 删除用户的所有项目和文件
            $stmt = $pdo->prepare("SELECT pro_id FROM projects WHERE user_id = ?");
            $stmt->execute([$userId]);
            while ($project = $stmt->fetch()) {
                $pdo->prepare("DELETE FROM project_files WHERE pro_id = ?")->execute([$project['pro_id']]);
            }
            $pdo->prepare("DELETE FROM projects WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            $success = '用户已删除';
        }
    }
}

// 获取所有用户（分页）
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$total = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPages = ceil($total / $perPage);

$users = $pdo->prepare("SELECT * FROM users ORDER BY id DESC LIMIT ? OFFSET ?");
$users->bindValue(1, $perPage, PDO::PARAM_INT);
$users->bindValue(2, $offset, PDO::PARAM_INT);
$users->execute();
$users = $users->fetchAll();

$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - <?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=243">
<style>
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
<div class="mobile-topbar">
    <div class="mobile-logo">
        <div class="logo-icon"><i class="ri-cloud-line"></i></div>
        <span><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></span>
    </div>
    <button class="hamburger-btn" onclick="toggleSidebar(this)" aria-label="菜单">
        <span></span><span></span><span></span>
    </button>
</div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>
function toggleSidebar(btn) {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
    if (btn) btn.classList.toggle('active');
    else document.querySelector('.hamburger-btn').classList.toggle('active');
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sidebar a').forEach(function(a) {
        a.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('show');
            document.querySelector('.sidebar-overlay').classList.remove('show');
            document.querySelector('.hamburger-btn').classList.remove('active');
        });
    });
});
</script>

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
            <a href="users.php" class="active"><i class="ri-user-line"></i> 用户管理</a>
            <a href="groups.php"><i class="ri-group-line"></i> 用户组管理</a>
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
    <div class="card" style="overflow: hidden;">
        <h1 class="text-lg font-bold mb-4">用户管理</h1>
        
        <?php if ($error): ?>
            <div class="error mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <!-- 统计 -->
        <div class="text-sm text-gray-500 mb-4">共 <?= $total ?> 位用户</div>
        
        <!-- 用户列表 -->
        <div class="overflow-x-auto" style="width: 100%; max-width: 100%; -webkit-overflow-scrolling: touch;">
            <table class="w-full text-sm">
                <thead class="text-gray-500 border-b">
                    <tr>
                        <th class="text-left p-3">ID</th>
                        <th class="text-left p-3">用户名</th>
                        <th class="text-left p-3">邮箱</th>
                        <th class="text-left p-3">手机号</th>
                        <th class="text-left p-3">配额(MB)</th>
                        <th class="text-left p-3">用户组</th>
                        <th class="text-left p-3">注册时间</th>
                        <th class="text-left p-3">操作</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr class="border-t hover:bg-blue-50/30">
                        <td class="p-3"><?= $u['id'] ?></td>
                        <td class="p-3"><?= htmlspecialchars($u['username']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                        <td class="p-3">
                            <span class="font-medium"><?= $u['quota'] ?? 500 ?></span>
                            <button onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', '<?= htmlspecialchars($u['email']) ?>', '<?= htmlspecialchars($u['phone'] ?? '') ?>', <?= $u['quota'] ?? 500 ?>)" 
                                    class="text-blue-500 text-xs ml-2">编辑</button>
                        </td>
                        <td class="p-3">
                            <form method="POST" class="flex items-center gap-1">
                                <input type="hidden" name="action" value="set_group">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="group_id" onchange="this.form.submit()" class="text-xs px-2 py-1 border rounded max-w-[100px]">
                                    <?php foreach ($groups as $g): ?>
                                    <option value="<?= $g['id'] ?>" <?= ($u['group_id'] ?? 1) == $g['id'] ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?>(<?= $g['project_limit'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td class="p-3"><?= date('Y-m-d', $u['registered_at']) ?></td>
                        <td class="p-3">
                            <button onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', '<?= htmlspecialchars($u['email']) ?>', '<?= htmlspecialchars($u['phone'] ?? '') ?>', <?= $u['quota'] ?? 500 ?>)" 
                                    class="text-blue-600 mr-2">编辑</button>
                            <?php if ($u['id'] != $_SESSION['admin_id']): ?>
                            <button onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')" 
                                    class="text-red-500">删除</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center gap-2 mt-4">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="px-3 py-1 rounded-lg <?= $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- 编辑用户弹窗 -->
<div id="editModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden" style="">
    <div class="glass-card max-w-md w-full p-6 m-4" style="overflow: hidden;">
        <h3 class="text-xl font-bold mb-4">编辑用户</h3>
        <form method="post" id="editForm">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">用户名</label>
                <input type="text" name="username" id="edit_username" required class="w-full px-4 py-2 rounded-lg">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">邮箱</label>
                <input type="email" name="email" id="edit_email" required class="w-full px-4 py-2 rounded-lg">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">手机号</label>
                <input type="text" name="phone" id="edit_phone" class="w-full px-4 py-2 rounded-lg">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">存储配额 (MB)</label>
                <input type="number" name="quota" id="edit_quota" required class="w-full px-4 py-2 rounded-lg">
                <p class="text-xs text-gray-500 mt-1">默认500MB，用户空间使用限制</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">新密码（留空则不修改）</label>
                <input type="password" name="new_password" id="edit_password" class="w-full px-4 py-2 rounded-lg">
                <p class="text-xs text-gray-500 mt-1">至少6位</p>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1">保存</button>
                <button type="button" onclick="closeModal()" class="btn-secondary">取消</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(id, username, email, phone, quota) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone || '';
    document.getElementById('edit_quota').value = quota;
    document.getElementById('edit_password').value = '';
    document.getElementById('editModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function deleteUser(id, username) {
    if (confirm('确定要删除用户 "' + username + '" 吗？此操作将删除该用户的所有项目文件，不可恢复！')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

// 点击模态框背景关闭
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>