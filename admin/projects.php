<?php session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
$pdo = getDB();
$config = getConfig();

// 处理删除
if (isset($_GET['delete'])) {
    $proId = $_GET['delete'];
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
    $proId = $_GET['view'];
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
<html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>项目管理 - <?= htmlspecialchars($config['site_name']) ?></title><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><link rel="stylesheet" href="../style.css?v=243"><style>
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
            <a href="users.php"><i class="ri-user-line"></i> 用户管理</a>
            <a href="groups.php"><i class="ri-group-line"></i> 用户组管理</a>
            <a href="projects.php" class="active"><i class="ri-folder-line"></i> 项目管理</a>
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
    <h1 class="text-lg font-bold mb-4">项目管理</h1>
    
    <!-- Tab切换 -->
    <div class="flex gap-2 mb-4">
        <a href="?tab=user" class="px-4 py-2 rounded-lg text-sm font-medium <?= $tab === 'user' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">用户项目 (<?= count($projects) ?>)</a>
        <a href="?tab=guest" class="px-4 py-2 rounded-lg text-sm font-medium <?= $tab === 'guest' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">访客项目 (<?= count($guestProjects) ?>)</a>
    </div>

    <!-- 用户项目列表 -->
    <?php if ($tab === 'user'): ?>
    <div class="overflow-x-auto" style="width: 100%; max-width: 100%; -webkit-overflow-scrolling: touch;"><table class="w-full text-sm"><thead class="text-gray-500 border-b"><tr><th class="text-left p-3">项目ID</th><th class="text-left p-3">项目名</th><th class="text-left p-3">所属用户</th><th class="text-left p-3">文件数</th><th class="text-left p-3">创建时间</th><th class="text-left p-3">操作</th></tr></thead><tbody>
    <?php foreach ($projects as $pro): ?>
    <tr class="border-t"><td class="p-3 font-mono text-xs"><?= htmlspecialchars($pro['pro_id']) ?></td><td class="p-3"><?= htmlspecialchars($pro['name']) ?></td><td class="p-3"><?= htmlspecialchars($pro['username']) ?></td><td class="p-3"><?= $pro['file_count'] ?></td><td class="p-3"><?= date('Y-m-d', $pro['created_at']) ?></td>
    <td class="p-3 space-x-2">
        <a href="?view=<?= urlencode($pro['pro_id']) ?>&vtype=user&tab=user" class="text-blue-500 hover:underline"><i class="ri-file-list-line"></i> 文件</a>
        <a href="/<?= urlencode($pro['pro_id']) ?>" target="_blank" class="text-green-600 hover:underline"><i class="ri-external-link-line"></i> 访问</a>
        <a href="?delete=<?= urlencode($pro['pro_id']) ?>&type=user" onclick="return confirm('删除项目及所有文件？')" class="text-red-500 hover:underline"><i class="ri-delete-bin-line"></i> 删除</a>
    </td></tr>
    <?php endforeach; ?>
    <?php if (empty($projects)): ?><tr><td colspan="6" class="p-8 text-center text-gray-400">暂无用户项目</td></tr><?php endif; ?>
    </tbody></table></div>
    <?php endif; ?>

    <!-- 访客项目列表 -->
    <?php if ($tab === 'guest'): ?>
    <div class="overflow-x-auto" style="width: 100%; max-width: 100%; -webkit-overflow-scrolling: touch;"><table class="w-full text-sm"><thead class="text-gray-500 border-b"><tr><th class="text-left p-3">项目ID</th><th class="text-left p-3">项目名</th><th class="text-left p-3">访问密钥</th><th class="text-left p-3">文件数</th><th class="text-left p-3">大小</th><th class="text-left p-3">创建时间</th><th class="text-left p-3">操作</th></tr></thead><tbody>
    <?php foreach ($guestProjects as $pro): ?>
    <tr class="border-t"><td class="p-3 font-mono text-xs"><?= htmlspecialchars($pro['pro_id']) ?></td><td class="p-3"><?= htmlspecialchars($pro['name']) ?></td><td class="p-3 font-mono text-xs text-gray-400"><?= htmlspecialchars(substr($pro['access_key'], 0, 16)) ?>...</td><td class="p-3"><?= $pro['file_count'] ?></td><td class="p-3"><?= round($pro['total_size']/1024, 1) ?> KB</td><td class="p-3"><?= date('Y-m-d', $pro['created_at']) ?></td>
    <td class="p-3 space-x-2">
        <a href="?view=<?= urlencode($pro['pro_id']) ?>&vtype=guest&tab=guest" class="text-blue-500 hover:underline"><i class="ri-file-list-line"></i> 文件</a>
        <a href="/<?= urlencode($pro['pro_id']) ?>" target="_blank" class="text-green-600 hover:underline"><i class="ri-external-link-line"></i> 访问</a>
        <a href="../guest_manage.php?key=<?= urlencode($pro['access_key']) ?>" target="_blank" class="text-purple-500 hover:underline"><i class="ri-edit-line"></i> 管理</a>
        <a href="?delete=<?= urlencode($pro['pro_id']) ?>&type=guest" onclick="return confirm('删除访客项目及所有文件？')" class="text-red-500 hover:underline"><i class="ri-delete-bin-line"></i> 删除</a>
    </td></tr>
    <?php endforeach; ?>
    <?php if (empty($guestProjects)): ?><tr><td colspan="7" class="p-8 text-center text-gray-400">暂无访客项目</td></tr><?php endif; ?>
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
</body></html>