<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../functions.php';

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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="flex min-h-screen">
<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div>
        <div class="flex items-center gap-2 mb-8">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div>
            <div><div class="font-bold"><?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></div><div class="text-xs text-gray-500">Admin</div></div>
        </div>
        <nav class="space-y-2">
            <a href="index.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 仪表盘</a>
            <a href="users.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-user-line"></i> 用户管理</a>
            <a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 项目管理</a>
            <a href="config.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-settings-line"></i> 网站配置</a>
            <a href="change_password.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-lock-line"></i> 修改密码</a>
        </nav>
    </div>
    <div><a href="logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>
<main class="flex-1 p-6">
    <div class="glass-card p-4">
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
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-gray-500 border-b">
                    <tr>
                        <th class="text-left p-3">ID</th>
                        <th class="text-left p-3">用户名</th>
                        <th class="text-left p-3">邮箱</th>
                        <th class="text-left p-3">手机号</th>
                        <th class="text-left p-3">配额(MB)</th>
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
                <a href="?page=<?= $i ?>" class="px-3 py-1 rounded-full <?= $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- 编辑用户弹窗 -->
<div id="editModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden" style="backdrop-filter: blur(4px);">
    <div class="glass-card max-w-md w-full p-6 m-4">
        <h3 class="text-xl font-bold mb-4">编辑用户</h3>
        <form method="post" id="editForm">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">用户名</label>
                <input type="text" name="username" id="edit_username" required class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">邮箱</label>
                <input type="email" name="email" id="edit_email" required class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">手机号</label>
                <input type="text" name="phone" id="edit_phone" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">存储配额 (MB)</label>
                <input type="number" name="quota" id="edit_quota" required class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
                <p class="text-xs text-gray-500 mt-1">默认500MB，用户空间使用限制</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">新密码（留空则不修改）</label>
                <input type="password" name="new_password" id="edit_password" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
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