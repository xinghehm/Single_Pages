<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
requireAdminPermission('manage_projects');
$config = getConfig();

// 处理封禁
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ban_project') {
    $proId = $_POST['pro_id'] ?? '';
    $reason = trim($_POST['ban_reason'] ?? '');
    if ($proId && $reason) {
        $pdo->prepare("UPDATE projects SET banned=1, ban_reason=? WHERE pro_id=?")->execute([$reason, $proId]);
    }
}
if (isset($_GET['unban'])) {
    $pdo->prepare("UPDATE projects SET banned=0, ban_reason=NULL WHERE pro_id=?")->execute([trim($_GET["unban"] ?? "")]);
}
// 访客项目封禁
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ban_guest') {
    $proId = $_POST['pro_id'] ?? '';
    $reason = trim($_POST['ban_reason'] ?? '');
    if ($proId && $reason) { banGuestProject($proId, $reason); }
}
if (isset($_GET['unban_guest'])) {
    unbanGuestProject(trim($_GET['unban_guest'] ?? ''));
}

// 处理删除
if (isset($_GET['delete'])) {
    $proId = trim($_GET['delete'] ?? ''); if (empty($proId)) { header('Location: projects.php'); exit; }
    $type = $_GET['type'] ?? 'user';
    if ($type === 'guest') {
        $pdo->prepare("DELETE FROM guest_projects WHERE pro_id = ?")->execute([$proId]);
        $dir = __DIR__ . "/../users/guests/projects/$proId";
        if (is_dir($dir)) {
            $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir()) rmdir($file->getRealPath());
                else unlink($file->getRealPath());
            }
            rmdir($dir);
        }
    } else {
        $pdo->prepare("DELETE FROM project_files WHERE pro_id = ?")->execute([$proId]);
        $pdo->prepare("DELETE FROM projects WHERE pro_id = ?")->execute([$proId]);
    }
    header('Location: projects.php?tab=' . $type);
    exit;
}

// 查看文件
$viewFiles = null;
$viewType = null;
if (isset($_GET['view'])) {
    $proId = trim($_GET['view'] ?? ''); if (empty($proId)) { header('Location: projects.php'); exit; }
    $viewType = $_GET['vtype'] ?? 'user';
    if ($viewType === 'guest') {
        $viewFiles = getGuestProjectFiles($proId);
    } else {
        $stmt = $pdo->prepare("SELECT filename, is_index, updated_at, LENGTH(content) as size FROM project_files WHERE pro_id = ? ORDER BY is_index DESC");
        $stmt->execute([$proId]);
        $viewFiles = $stmt->fetchAll();
    }
}

$tab = $_GET['tab'] ?? 'user';

// 用户项目
$projects = $pdo->query("SELECT p.*, u.username FROM projects p JOIN users u ON p.user_id = u.id ORDER BY p.id DESC")->fetchAll();
foreach ($projects as &$p) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_files WHERE pro_id = ?");
    $stmt->execute([$p['pro_id']]);
    $p['file_count'] = $stmt->fetchColumn();
}

// 访客项目
$guestProjects = $pdo->query("SELECT * FROM guest_projects ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>项目管理 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-folder-line"></i></div><span>项目管理</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('show');document.querySelector('.sidebar-overlay').classList.toggle('show');}</script>
<?php include 'sidebar.php'; ?>

<main>
<div class="card" style="overflow: hidden;">
    <h1 class="text-lg font-bold mb-4">项目管理</h1>
    
    <!-- Tab切换 -->
    <div class="flex gap-2 mb-4">
        <a href="?tab=user" class="px-4 py-2 rounded-lg text-sm font-medium <?= $tab === 'user' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">用户项目 (<?= count($projects) ?>)</a>
        <a href="?tab=guest" class="px-4 py-2 rounded-lg text-sm font-medium <?= $tab === 'guest' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">访客项目 (<?= count($guestProjects) ?>)</a>
    </div>

    <!-- 用户项目列表 -->
    <?php if ($tab === 'user'): ?>
    <div class="table-wrap"><table class="w-full text-sm"><thead class="text-gray-500 border-b"><tr><th class="text-left p-3">项目ID</th><th class="text-left p-3">项目名</th><th class="text-left p-3">所属用户</th><th class="text-left p-3">文件数</th><th class="text-left p-3">状态</th><th class="text-left p-3">创建时间</th><th class="text-left p-3">操作</th></tr></thead><tbody>
    <?php foreach ($projects as $pro): ?>
    <tr class="border-t"><td class="p-3 font-mono text-xs"><?= htmlspecialchars($pro['pro_id']) ?></td><td class="p-3"><?= htmlspecialchars($pro['name']) ?></td><td class="p-3"><?= htmlspecialchars($pro['username']) ?></td><td class="p-3"><?= $pro['file_count'] ?></td><td class="p-3"><?php if (!empty($pro['banned'])): ?><span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:4px;font-size:11px;">已封禁</span><?php else: ?><span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:4px;font-size:11px;">正常</span><?php endif; ?></td>
    <td class="p-3"><?= date('Y-m-d', $pro['created_at']) ?></td>
    <td class="p-3 space-x-2">
        <a href="?view=<?= urlencode($pro['pro_id']) ?>&vtype=user&tab=user" class="text-blue-500 hover:underline"><i class="ri-file-list-line"></i> 文件</a>
        <a href="/<?= urlencode($pro['pro_id']) ?>" target="_blank" class="text-green-600 hover:underline"><i class="ri-external-link-line"></i> 访问</a>
        <?php if (!empty($pro['banned'])): ?>
        <a href="?unban=<?= urlencode($pro['pro_id']) ?>&tab=user" onclick="return confirm('确定解封该项目？')" style="color:#16a34a;text-decoration:none;font-size:13px;"><i class="ri-lock-unlock-line"></i> 解封</a>
        <?php else: ?>
        <a href="javascript:void(0)" onclick="openBanModal('<?= urlencode($pro['pro_id']) ?>')" style="color:#f59e0b;text-decoration:none;font-size:13px;"><i class="ri-ban-line"></i> 封禁</a>
        <?php endif; ?>
        <a href="?delete=<?= urlencode($pro['pro_id']) ?>&type=user" onclick="return confirm('删除项目及所有文件？')" class="text-red-500 hover:underline"><i class="ri-delete-bin-line"></i> 删除</a>
    </td></tr>
    <?php endforeach; ?>
    <?php if (empty($projects)): ?><tr><td colspan="7" class="p-8 text-center text-gray-400">暂无用户项目</td></tr><?php endif; ?>
    </tbody></table></div>
    <?php endif; ?>

    <!-- 访客项目列表 -->
    <?php if ($tab === 'guest'): ?>
    <div class="table-wrap"><table class="w-full text-sm"><thead class="text-gray-500 border-b"><tr><th class="text-left p-3">项目ID</th><th class="text-left p-3">项目名</th><th class="text-left p-3">访问密钥</th><th class="text-left p-3">文件数</th><th class="text-left p-3">大小</th><th class="text-left p-3">状态</th><th class="text-left p-3">创建时间</th><th class="text-left p-3">操作</th></tr></thead><tbody>
    <?php foreach ($guestProjects as $pro): ?>
    <tr class="border-t"><td class="p-3 font-mono text-xs"><?= htmlspecialchars($pro['pro_id']) ?></td><td class="p-3"><?= htmlspecialchars($pro['name']) ?></td><td class="p-3 font-mono text-xs text-gray-400"><?= htmlspecialchars(substr($pro['access_key'], 0, 16)) ?>...</td><td class="p-3"><?= $pro['file_count'] ?></td><td class="p-3"><?= round($pro['total_size']/1024, 1) ?> KB</td><td class="p-3"><?= date('Y-m-d', $pro['created_at']) ?></td>
        <td class="p-3"><?php if (!empty($pro['banned'])): ?><span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:4px;font-size:11px;">已封禁</span><?php else: ?><span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:4px;font-size:11px;">正常</span><?php endif; ?></td>
    <td class="p-3 space-x-2">
        <a href="?view=<?= urlencode($pro['pro_id']) ?>&vtype=guest&tab=guest" class="text-blue-500 hover:underline"><i class="ri-file-list-line"></i> 文件</a>
        <a href="/<?= urlencode($pro['pro_id']) ?>" target="_blank" class="text-green-600 hover:underline"><i class="ri-external-link-line"></i> 访问</a>
        <a href="../guest_manage.php?key=<?= urlencode($pro['access_key']) ?>" target="_blank" class="text-purple-500 hover:underline"><i class="ri-edit-line"></i> 管理</a>
        <a href="?delete=<?= urlencode($pro['pro_id']) ?>&type=guest" onclick="return confirm('删除访客项目及所有文件？')" class="text-red-500 hover:underline"><i class="ri-delete-bin-line"></i> 删除</a>
    </td></tr>
    <?php endforeach; ?>
    <?php if (empty($guestProjects)): ?><tr><td colspan="8" class="p-8 text-center text-gray-400">暂无访客项目</td></tr><?php endif; ?>
    </tbody></table></div>
    <?php endif; ?>
</div>

<!-- 文件查看模态框 -->
<?php if ($viewFiles !== null): ?>
<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" onclick="if(event.target===this)location.href='?tab=<?= $viewType ?>'">
    <div class="bg-white rounded-xl p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold">文件列表 (<?= count($viewFiles) ?> 个)</h2>
            <a href="?tab=<?= $viewType ?>" class="text-gray-400 hover:text-gray-600 text-xl"><i class="ri-close-line"></i></a>
        </div>
        <?php if (empty($viewFiles)): ?>
            <p class="text-gray-400 text-center py-8">暂无文件</p>
        <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($viewFiles as $f): ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-3">
                    <i class="ri-file-code-line text-blue-500 text-xl"></i>
                    <div>
                        <div class="font-medium text-sm"><?= htmlspecialchars($f['filename']) ?></div>
                        <div class="text-xs text-gray-400"><?= round(($f['size'] ?? 0)/1024, 1) ?> KB · <?= date('Y-m-d H:i', $f['updated_at'] ?? time()) ?></div>
                    </div>
                </div>
                <a href="/<?= urlencode($_GET['view']) ?>/<?= urlencode($f['filename']) ?>" target="_blank" class="text-green-600 text-sm hover:underline"><i class="ri-external-link-line"></i> 打开</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</main>
<!-- 封禁弹窗 -->
<div id="banModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:400px;margin:16px;">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:12px;"><i class="ri-ban-line" style="color:#f59e0b;"></i> 封禁项目</h3>
        <form method="POST">
            <input type="hidden" name="action" value="ban_project">
            <input type="hidden" name="pro_id" id="banProId">
            <div class="form-group"><label>封禁原因（必填）</label><textarea name="ban_reason" rows="3" required placeholder="请填写封禁原因，将展示给访问者" style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;resize:vertical;"></textarea></div>
            <div style="display:flex;gap:10px;margin-top:16px;">
                <button type="button" onclick="closeBanModal()" class="btn-secondary" style="flex:1;">取消</button>
                <button type="submit" class="btn-primary" style="flex:1;background:#f59e0b;border-color:#f59e0b;">确认封禁</button>
            </div>
        </form>
    </div>
</div>
<script>
function openBanModal(proId) { document.getElementById('banProId').value = proId; document.getElementById('banModal').style.display = 'flex'; }
function closeBanModal() { document.getElementById('banModal').style.display = 'none'; }
document.getElementById('banModal').addEventListener('click', function(e) { if(e.target===this) closeBanModal(); });

// 封禁访客项目弹窗
function openBanGuestModal(proId) {
    document.getElementById('banGuestProId').value = proId;
    document.getElementById('banGuestModal').style.display = 'flex';
}
function closeBanGuestModal() { document.getElementById('banGuestModal').style.display = 'none'; }
document.addEventListener('DOMContentLoaded', function() {
    const m = document.getElementById('banGuestModal');
    if (m) m.addEventListener('click', function(e) { if(e.target===this) closeBanGuestModal(); });
});
</script>
<!-- 封禁访客项目弹窗 -->
<div id="banGuestModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:400px;margin:16px;">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:12px;"><i class="ri-ban-line" style="color:#f59e0b;"></i> 封禁访客项目</h3>
        <form method="POST">
            <input type="hidden" name="action" value="ban_guest">
            <input type="hidden" name="pro_id" id="banGuestProId">
            <div class="form-group"><label>封禁原因（必填）</label><textarea name="ban_reason" rows="3" required placeholder="请填写封禁原因" style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;resize:vertical;"></textarea></div>
            <div style="display:flex;gap:10px;margin-top:16px;">
                <button type="button" onclick="closeBanGuestModal()" class="btn-secondary" style="flex:1;">取消</button>
                <button type="submit" class="btn-primary" style="flex:1;background:#f59e0b;border-color:#f59e0b;">确认封禁</button>
            </div>
        </form>
    </div>
</div>
</body></html>







