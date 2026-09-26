<?php session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
$config = getConfig();
$message = '';
$messageType = '';

// 处理操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'backup') {
        $result = backupDatabase();
        if ($result) {
            $message = "备份成功：{$result['filename']}（" . round($result['size']/1024, 1) . " KB）";
            $messageType = 'success';
        } else {
            $message = '备份失败';
            $messageType = 'error';
        }
    } elseif ($action === 'cleanup_guests') {
        $result = cleanupExpiredGuestProjects();
        $message = "清理完成：删除 {$result['deleted']} 个过期访客项目";
        $messageType = 'success';
    } elseif ($action === 'delete_backup') {
        $filename = basename($_POST['filename'] ?? '');
        $filepath = __DIR__ . '/../backups/' . $filename;
        if (file_exists($filepath) && strpos($filename, 'db_backup_') === 0) {
            unlink($filepath);
            $message = "已删除备份：$filename";
            $messageType = 'success';
        }
    }
}

$sys = getSystemInfo();
$backups = listBackups();
$expiredGuests = getExpiredGuestProjects();
?>
<!DOCTYPE html>
<html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>系统信息 - <?= htmlspecialchars($config['site_name']) ?></title><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><link rel="stylesheet" href="../style.css?v=245">
<style>
.mobile-topbar { display: none; position: fixed; top: 0; left: 0; right: 0; height: 56px; background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.06); z-index: 999; align-items: center; justify-content: space-between; padding: 0 16px; }
.mobile-topbar .mobile-logo { display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 15px; }
.mobile-topbar .logo-icon { width: 32px; height: 32px; background: #2563eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; }
.hamburger-btn { width: 40px; height: 40px; background: #f3f4f6; border: none; border-radius: 10px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; }
.hamburger-btn span { display: block; width: 20px; height: 2px; background: #333; border-radius: 2px; }
.sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 998; }
.sidebar-overlay.show { display: block; }
@media (max-width: 768px) {
    .mobile-topbar { display: flex; }
    .sidebar { position: fixed !important; left: -280px !important; top: 0; bottom: 0; z-index: 1000 !important; transition: left 0.3s !important; width: 280px !important; padding-top: 70px !important; }
    .sidebar.show { left: 0 !important; }
    main { padding: 16px !important; padding-top: 72px !important; }
}
</style>
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><div class="mobile-logo"><div class="logo-icon"><i class="ri-cloud-line"></i></div><span><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></span></div><button class="hamburger-btn" onclick="toggleSidebar(this)"><span></span><span></span><span></span></button></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>function toggleSidebar(btn) { document.querySelector('.sidebar').classList.toggle('show'); document.querySelector('.sidebar-overlay').classList.toggle('show'); }</script>

<?php include 'sidebar.php'; ?>

<main class="flex-1 p-6" style="min-width:0;">
<h1 class="text-2xl font-bold mb-6">系统信息</h1>

<?php if ($message): ?>
<div class="mb-4 p-3 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- 服务器信息 -->
<div class="card mb-4">
    <h3 class="font-medium mb-4"><i class="ri-server-line"></i> 服务器信息</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div><div class="text-gray-500">操作系统</div><div class="font-medium"><?= htmlspecialchars($sys['os']) ?></div></div>
        <div><div class="text-gray-500">PHP 版本</div><div class="font-medium"><?= htmlspecialchars($sys['php_version']) ?></div></div>
        <div><div class="text-gray-500">PHP SAPI</div><div class="font-medium"><?= htmlspecialchars($sys['php_sapi']) ?></div></div>
        <div><div class="text-gray-500">Web 服务器</div><div class="font-medium"><?= htmlspecialchars($sys['server_software']) ?></div></div>
        <div><div class="text-gray-500">MySQL 版本</div><div class="font-medium"><?= htmlspecialchars($sys['mysql_version']) ?></div></div>
        <div><div class="text-gray-500">时区</div><div class="font-medium"><?= htmlspecialchars($sys['timezone']) ?></div></div>
        <div><div class="text-gray-500">内存限制</div><div class="font-medium"><?= htmlspecialchars($sys['memory_limit']) ?></div></div>
        <div><div class="text-gray-500">上传限制</div><div class="font-medium"><?= htmlspecialchars($sys['upload_max_filesize']) ?></div></div>
        <div><div class="text-gray-500">执行时间</div><div class="font-medium"><?= htmlspecialchars($sys['max_execution_time']) ?></div></div>
        <div><div class="text-gray-500">数据库大小</div><div class="font-medium"><?= $sys['db_size_mb'] ?> MB</div></div>
        <div><div class="text-gray-500">用户文件</div><div class="font-medium"><?= $sys['users_dir_mb'] ?> MB</div></div>
        <div><div class="text-gray-500">磁盘</div><div class="font-medium"><?= $sys['disk_used_percent'] ?>% 已用</div></div>
    </div>
    <div class="mt-4">
        <div class="flex justify-between text-xs text-gray-500 mb-1"><span>磁盘使用</span><span>剩余 <?= $sys['disk_free'] ?> / 共 <?= $sys['disk_total'] ?></span></div>
        <div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-blue-500 h-2 rounded-full" style="width: <?= $sys['disk_used_percent'] ?>%"></div></div>
    </div>
</div>

<!-- PHP扩展 -->
<div class="card mb-4">
    <h3 class="font-medium mb-3"><i class="ri-puzzle-line"></i> PHP 扩展检查</h3>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($sys['extensions'] as $ext => $ok): ?>
        <span class="px-3 py-1 rounded-full text-xs <?= $ok ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><i class="ri-<?= $ok ? 'checkbox-circle' : 'close-circle' ?>-line"></i> <?= $ext ?></span>
        <?php endforeach; ?>
    </div>
</div>

<!-- 数据库备份 -->
<div class="card mb-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-medium"><i class="ri-database-line"></i> 数据库备份</h3>
        <form method="post"><input type="hidden" name="action" value="backup"><button type="submit" class="btn-primary text-sm"><i class="ri-download-line"></i> 立即备份</button></form>
    </div>
    <?php if (empty($backups)): ?>
    <p class="text-gray-500 text-sm">暂无备份文件</p>
    <?php else: ?>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
    <thead class="text-gray-500 border-b"><tr><th class="text-left p-2">文件名</th><th class="text-left p-2">大小</th><th class="text-left p-2">创建时间</th><th class="text-left p-2">操作</th></tr></thead>
    <tbody>
    <?php foreach ($backups as $bk): ?>
    <tr class="border-t">
        <td class="p-2"><code class="text-xs"><?= htmlspecialchars($bk['filename']) ?></code></td>
        <td class="p-2"><?= $bk['size_mb'] > 1 ? $bk['size_mb'] . ' MB' : round($bk['size']/1024, 1) . ' KB' ?></td>
        <td class="p-2"><?= date('Y-m-d H:i:s', $bk['created_at']) ?></td>
        <td class="p-2 space-x-2">
            <a href="../backups/<?= urlencode($bk['filename']) ?>" download class="text-blue-600 text-xs">下载</a>
            <form method="post" class="inline" onsubmit="return confirm('确定删除此备份？')"><input type="hidden" name="action" value="delete_backup"><input type="hidden" name="filename" value="<?= htmlspecialchars($bk['filename']) ?>"><button type="submit" class="text-red-500 text-xs">删除</button></form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- 访客项目清理 -->
<div class="card">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-medium"><i class="ri-delete-bin-line"></i> 过期访客项目清理</h3>
        <form method="post" onsubmit="return confirm('确定清理所有过期访客项目？此操作不可恢复')"><input type="hidden" name="action" value="cleanup_guests"><button type="submit" class="btn-secondary text-sm"><i class="ri-broom-line"></i> 清理过期项目</button></form>
    </div>
    <p class="text-gray-500 text-sm mb-3">超过 <?= htmlspecialchars(getConfig('guest_expire_days') ?: '30') ?> 天未更新的访客项目将被清理。当前有 <strong class="text-orange-500"><?= count($expiredGuests) ?></strong> 个过期项目。</p>
    <?php if (!empty($expiredGuests)): ?>
    <div class="overflow-x-auto max-h-48 overflow-y-auto">
    <table class="w-full text-xs">
    <thead class="text-gray-500 border-b sticky top-0 bg-white"><tr><th class="text-left p-2">项目ID</th><th class="text-left p-2">名称</th><th class="text-left p-2">最后更新</th><th class="text-left p-2">文件数</th></tr></thead>
    <tbody>
    <?php foreach (array_slice($expiredGuests, 0, 20) as $gp): ?>
    <tr class="border-t"><td class="p-2"><code><?= htmlspecialchars($gp['pro_id']) ?></code></td><td class="p-2"><?= htmlspecialchars($gp['name']) ?></td><td class="p-2"><?= date('Y-m-d', $gp['updated_at']) ?></td><td class="p-2"><?= $gp['file_count'] ?? 0 ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

</main>
</body>
</html>
