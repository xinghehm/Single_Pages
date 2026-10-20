<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
requireAdminPermission('manage_users');
$config = getConfig();
$isSuper = isSuperAdmin();

$msg = ''; $msgType = '';
$tab = $_GET['tab'] ?? 'users';

// === 处理用户操作 ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'set_group') {
        $userId = intval($_POST['user_id'] ?? 0);
        $groupId = intval($_POST['group_id'] ?? 0);
        if ($userId > 0 && $groupId > 0) { setUserGroup($userId, $groupId); $msg='用户组已更新'; $msgType='success'; }
    } elseif ($action === 'edit_user') {
        $userId = intval($_POST['user_id']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $quota = intval($_POST['quota']);
        $newPassword = $_POST['new_password'] ?? '';
        $pdo = getDB();
        if ($newPassword) {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET username=?, email=?, phone=?, quota=?, password=? WHERE id=?")->execute([$username,$email,$phone,$quota,$hash,$userId]);
        } else {
            $pdo->prepare("UPDATE users SET username=?, email=?, phone=?, quota=? WHERE id=?")->execute([$username,$email,$phone,$quota,$userId]);
        }
        $msg='用户已更新'; $msgType='success';
    } elseif ($action === 'delete_user') {
        $userId = intval($_POST['user_id']);
        if ($userId == ($_SESSION['admin_id'] ?? 0)) { $msg='不能删除自己'; $msgType='error'; }
        else {
            $pdo = getDB();
            $pdo->prepare("DELETE FROM project_files WHERE project_id IN (SELECT id FROM projects WHERE user_id=?)")->execute([$userId]);
            $pdo->prepare("DELETE FROM projects WHERE user_id=?")->execute([$userId]);
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$userId]);
            $msg='用户已删除'; $msgType='success';
        }
    } elseif ($action === 'ban_user') {
        $userId = intval($_POST['user_id']);
        $reason = trim($_POST['ban_reason'] ?? '');
        if ($userId > 0 && $reason) { banUser($userId, $reason); $msg='用户已封禁'; $msgType='success'; }
    } elseif ($action === 'unban_user') {
        $userId = intval($_POST['user_id']);
        if ($userId > 0) { unbanUser($userId); $msg='用户已解封'; $msgType='success'; }
    } elseif ($action === 'login_as_user') {
        $userId = intval($_POST['user_id']);
        $_SESSION['user_id'] = $userId;
        header('Location: ../users/dashboard.php');
        exit;
    }
    // === 管理员操作（仅总管理员） ===
    elseif ($isSuper && $action === 'add_admin') {
        $username = trim($_POST['admin_username']);
        $password = $_POST['admin_password'] ?? '';
        $email = trim($_POST['admin_email'] ?? '');
        $role = $_POST['admin_role'] ?? 'sub';
        if (strlen($password) < 6) { $msg='密码至少6位'; $msgType='error'; }
        else {
            $result = addAdmin($username, $password, $email, $role);
            $msg = $result['msg']; $msgType = $result['success'] ? 'success' : 'error';
        }
    } elseif ($isSuper && $action === 'edit_admin') {
        $adminId = intval($_POST['admin_id']);
        $username = trim($_POST['admin_username']);
        $email = trim($_POST['admin_email'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $role = $_POST['admin_role'] ?? 'sub';
        $result = updateAdmin($adminId, $username, $email, $password, $role);
        $msg = $result['msg']; $msgType = $result['success'] ? 'success' : 'error';
    } elseif ($isSuper && $action === 'delete_admin') {
        $adminId = intval($_POST['admin_id']);
        $result = deleteAdmin($adminId);
        $msg = $result['msg']; $msgType = $result['success'] ? 'success' : 'error';
    }
}

$groups = getAllUserGroups();
$pdo = getDB();
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}
$admins = $isSuper ? getAdminUsers() : [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>用户管理 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=247">
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-user-line"></i></div><span>用户管理</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<?php include 'sidebar.php'; ?>

<main>
<div class="page-header">
    <div><h1>用户管理</h1><p class="subtitle">共 <?= count($users) ?> 个用户<?= $isSuper ? '，' . count($admins) . ' 个管理员' : '' ?></p></div>
</div>

<?php if ($msg): ?><div class="msg <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- Tab切换 -->
<div style="display:flex;gap:4px;background:#f3f4f6;border-radius:10px;padding:4px;margin-bottom:20px;flex-wrap:wrap;">
    <a href="?tab=users" class="config-tab <?= $tab=='users'?'active':'' ?>" style="padding:10px 18px;font-size:13px;font-weight:600;border-radius:8px;cursor:pointer;text-decoration:none;<?= $tab=='users'?'background:#fff;color:#3b82f6;box-shadow:0 2px 8px rgba(0,0,0,.06);':'color:#6b7280;' ?>"><i class="ri-user-line"></i> 用户列表</a>
    <?php if ($isSuper): ?>
    <a href="?tab=admins" class="config-tab <?= $tab=='admins'?'active':'' ?>" style="padding:10px 18px;font-size:13px;font-weight:600;border-radius:8px;cursor:pointer;text-decoration:none;<?= $tab=='admins'?'background:#fff;color:#3b82f6;box-shadow:0 2px 8px rgba(0,0,0,.06);':'color:#6b7280;' ?>"><i class="ri-shield-user-line"></i> 管理员管理</a>
    <?php endif; ?>
</div>

<?php if ($tab === 'users'): ?>
<div class="table-wrap">
<table>
<thead>
<tr><th>ID</th><th>用户名</th><th>邮箱</th><th>手机</th><th>用户组</th><th>状态</th><th>配额</th><th>余额</th><th>注册时间</th><th>操作</th></tr>
</thead>
<tbody>
<?php foreach ($users as $u): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['username']) ?></td>
    <td class="text-small"><?= htmlspecialchars($u['email'] ?? '-') ?></td>
    <td class="text-small"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
    <td>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="set_group">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <select name="group_id" onchange="this.form.submit()" style="padding:4px 8px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px;">
                <?php foreach ($groups as $g): ?>
                <option value="<?= $g['id'] ?>" <?= $u['group_id']==$g['id']?'selected':'' ?>><?= htmlspecialchars($g['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </td>
    <td><?php if (!empty($u['banned'])): ?><span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:4px;font-size:11px;">已封禁</span><?php else: ?><span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:4px;font-size:11px;">正常</span><?php endif; ?></td>
    <td><?= intval($u['quota']) ?>MB</td>
    <td>¥<?= number_format($u['balance'] ?? 0, 2) ?></td>
    <td class="text-small text-muted"><?= date('Y-m-d', $u['created_at'] ?? time()) ?></td>
    <td>
        <div style="display:flex;gap:4px;flex-wrap:wrap;">
            <button onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($u['phone'] ?? '', ENT_QUOTES) ?>', <?= intval($u['quota']) ?>)" class="btn-secondary btn-sm"><i class="ri-edit-line"></i> 编辑</button>
            <form method="POST" style="display:inline;" onsubmit="return confirm('确定登录该用户账号？将切换到用户视角。')">
                <input type="hidden" name="action" value="login_as_user">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn-secondary btn-sm" style="background:#0ea5e9;color:#fff;border-color:#0ea5e9;"><i class="ri-login-box-line"></i> 登录</button>
            </form>
            <?php if (!empty($u['banned'])): ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('确定解封该用户？')">
                <input type="hidden" name="action" value="unban_user">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn-secondary btn-sm" style="background:#16a34a;color:#fff;border-color:#16a34a;"><i class="ri-lock-unlock-line"></i> 解封</button>
            </form>
            <?php else: ?>
            <button onclick="openBanUserModal(<?= $u['id'] ?>)" class="btn-secondary btn-sm" style="background:#f59e0b;color:#fff;border-color:#f59e0b;"><i class="ri-ban-line"></i> 封禁</button>
            <?php endif; ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('确定删除用户？将删除所有项目文件，不可恢复！')">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn-danger btn-sm"><i class="ri-delete-bin-line"></i> 删除</button>
            </form>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php elseif ($tab === 'admins' && $isSuper): ?>
<div style="margin-bottom:16px;">
    <button onclick="openAdminModal()" class="btn-primary"><i class="ri-add-line"></i> 添加管理员</button>
</div>
<div class="table-wrap">
<table>
<thead>
<tr><th>ID</th><th>用户名</th><th>邮箱</th><th>角色</th><th>操作</th></tr>
</thead>
<tbody>
<?php foreach ($admins as $a): ?>
<tr>
    <td><?= $a['id'] ?></td>
    <td><?= htmlspecialchars($a['username']) ?></td>
    <td class="text-small"><?= htmlspecialchars($a['email'] ?? '-') ?></td>
    <td>
        <?php if (($a['role'] ?? 'super') === 'super'): ?>
        <span class="badge badge-success">总管理员</span>
        <?php else: ?>
        <span class="badge" style="background:#dbeafe;color:#1e40af;">分管理员</span>
        <?php endif; ?>
    </td>
    <td>
        <div style="display:flex;gap:4px;">
            <button onclick="editAdmin(<?= $a['id'] ?>, '<?= htmlspecialchars($a['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['email'] ?? '', ENT_QUOTES) ?>', '<?= $a['role'] ?? 'sub' ?>', <?= !empty($a['permissions']) ? $a['permissions'] : '[]' ?>)" class="btn-secondary btn-sm"><i class="ri-edit-line"></i> 编辑</button>
            <?php if ($a['id'] != 1 && $a['id'] != ($_SESSION['admin_id'] ?? 0)): ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('确定删除该管理员？')">
                <input type="hidden" name="action" value="delete_admin">
                <input type="hidden" name="admin_id" value="<?= $a['id'] ?>">
                <button type="submit" class="btn-danger btn-sm"><i class="ri-delete-bin-line"></i> 删除</button>
            </form>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</main>

<!-- 用户编辑弹窗 -->
<div id="userModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:440px;margin:16px;max-height:90vh;overflow-y:auto;">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:16px;">编辑用户</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="uId">
            <div class="form-group"><label>用户名</label><input type="text" name="username" id="uName" required></div>
            <div class="form-group"><label>邮箱</label><input type="email" name="email" id="uEmail"></div>
            <div class="form-group"><label>手机号</label><input type="text" name="phone" id="uPhone"></div>
            <div class="form-group"><label>配额(MB)</label><input type="number" name="quota" id="uQuota"></div>
            <div class="form-group"><label>新密码（留空不修改）</label><input type="password" name="new_password"></div>
            <div style="display:flex;gap:10px;margin-top:16px;">
                <button type="button" onclick="closeUserModal()" class="btn-secondary" style="flex:1;">取消</button>
                <button type="submit" class="btn-primary" style="flex:1;">保存</button>
            </div>
        </form>
    </div>
</div>

<!-- 管理员编辑弹窗 -->
<div id="adminModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:440px;margin:16px;max-height:90vh;overflow-y:auto;">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:16px;" id="adminModalTitle">添加管理员</h3>
        <form method="POST">
            <input type="hidden" name="action" id="adminAction" value="add_admin">
            <input type="hidden" name="admin_id" id="aId">
            <div class="form-group"><label>用户名</label><input type="text" name="admin_username" id="aName" required></div>
            <div class="form-group"><label>邮箱</label><input type="email" name="admin_email" id="aEmail"></div>
            <div class="form-group"><label>密码<?= '<span id="aPwdHint" style="color:#9ca3af;font-size:12px;">（至少6位）</span>' ?></label><input type="password" name="admin_password" id="aPwd"></div>
            <div class="form-group"><label>角色</label>
                <select name="admin_role" id="aRole" style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;">
                    <option value="sub">分管理员</option>
                    <option value="super">总管理员</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;margin-top:16px;">
                <button type="button" onclick="closeAdminModal()" class="btn-secondary" style="flex:1;">取消</button>
                <button type="submit" class="btn-primary" style="flex:1;">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(id, name, email, phone, quota) {
    document.getElementById('uId').value = id;
    document.getElementById('uName').value = name;
    document.getElementById('uEmail').value = email;
    document.getElementById('uPhone').value = phone;
    document.getElementById('uQuota').value = quota;
    document.getElementById('userModal').style.display = 'flex';
}
function closeUserModal() { document.getElementById('userModal').style.display = 'none'; }
document.getElementById('userModal').addEventListener('click', function(e) { if(e.target===this) closeUserModal(); });

// 封禁用户弹窗
function openBanUserModal(userId) {
    document.getElementById('banUserId').value = userId;
    document.getElementById('banUserModal').style.display = 'flex';
}
function closeBanUserModal() { document.getElementById('banUserModal').style.display = 'none'; }
document.addEventListener('DOMContentLoaded', function() {
    const m = document.getElementById('banUserModal');
    if (m) m.addEventListener('click', function(e) { if(e.target===this) closeBanUserModal(); });
});

function openAdminModal() {
    document.getElementById('adminModalTitle').textContent = '添加管理员';
    document.getElementById('adminAction').value = 'add_admin';
    document.getElementById('aId').value = '';
    document.getElementById('aName').value = '';
    document.getElementById('aEmail').value = '';
    document.getElementById('aPwd').value = '';
    document.getElementById('aPwd').required = true;
    document.getElementById('aRole').value = 'sub';
    document.getElementById('adminModal').style.display = 'flex';
}
function editAdmin(id, name, email, role) {
    document.getElementById('adminModalTitle').textContent = '编辑管理员';
    document.getElementById('adminAction').value = 'edit_admin';
    document.getElementById('aId').value = id;
    document.getElementById('aName').value = name;
    document.getElementById('aEmail').value = email;
    document.getElementById('aPwd').value = '';
    document.getElementById('aPwd').required = false;
    document.getElementById('aRole').value = role;
    document.getElementById('adminModal').style.display = 'flex';
}
function closeAdminModal() { document.getElementById('adminModal').style.display = 'none'; }
document.getElementById('adminModal').addEventListener('click', function(e) { if(e.target===this) closeAdminModal(); });

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}
</script>
<!-- 封禁用户弹窗 -->
<div id="banUserModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:400px;margin:16px;">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:12px;"><i class="ri-ban-line" style="color:#f59e0b;"></i> 封禁用户</h3>
        <form method="POST">
            <input type="hidden" name="action" value="ban_user">
            <input type="hidden" name="user_id" id="banUserId">
            <div class="form-group"><label>封禁原因（必填）</label><textarea name="ban_reason" rows="3" required placeholder="请填写封禁原因" style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;resize:vertical;"></textarea></div>
            <div style="display:flex;gap:10px;margin-top:16px;">
                <button type="button" onclick="closeBanUserModal()" class="btn-secondary" style="flex:1;">取消</button>
                <button type="submit" class="btn-primary" style="flex:1;background:#f59e0b;border-color:#f59e0b;">确认封禁</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>





