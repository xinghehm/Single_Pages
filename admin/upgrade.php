<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';

$config = getConfig();
$currentVersion = upgradeGetCurrentVersion();
$updateInfo = null;
$upgradeResult = null;
$error = '';

// 检查更新
if (isset($_GET['action']) && $_GET['action'] === 'check') {
    $updateInfo = upgradeCheckUpdate(true);
}

// 执行升级
if (isset($_POST['action']) && $_POST['action'] === 'upgrade') {
    $targetVersion = $_POST['target_version'] ?? '';
    $downloadUrl = $_POST['download_url'] ?? '';
    $checksum = $_POST['checksum'] ?? '';

    if (!$targetVersion || !$downloadUrl) {
        $error = '升级参数缺失';
    } else {
        $upgradeResult = doUpgrade($targetVersion, $downloadUrl, $checksum);
    }
}

// 列出备份文件
$backups = [];
if (is_dir(UPGRADE_BACKUP_DIR)) {
    $files = glob(UPGRADE_BACKUP_DIR . '/*');
    foreach ($files as $f) {
        if (is_file($f)) {
            $backups[] = [
                'name' => basename($f),
                'size' => filesize($f),
                'time' => filemtime($f),
            ];
        }
    }
    usort($backups, function($a, $b) { return $b['time'] - $a['time']; });
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>在线升级 — 管理后台</title>
    <link rel="stylesheet" href="../style.css?v=243">
</head>
<body class="has-sidebar">
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
            <a href="projects.php"><i class="ri-folder-line"></i> 项目管理</a>
            <a href="login_logs.php"><i class="ri-history-line"></i> 登录日志</a>
            <a href="system.php"><i class="ri-server-line"></i> 系统信息</a>
            <a href="config.php"><i class="ri-settings-line"></i> 网站配置</a>
            <a href="upgrade.php" class="active"><i class="ri-refresh-line"></i> 在线升级</a>
        </nav>
    </div>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="ri-logout-box-line"></i> 退出登录</a>
    </div>
</aside>

<main class="flex-1">
    <h1 class="page-title">在线升级</h1>
    <p class="page-subtitle">检查并安装最新版本，升级前自动备份文件和数据库</p>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- 版本信息卡片 -->
    <div class="card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <div style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">当前版本</div>
                <div style="font-size: 28px; font-weight: 700; color: #111827;"><?= htmlspecialchars($currentVersion) ?></div>
            </div>
            <a href="?action=check" class="btn-primary">检查更新</a>
        </div>
    </div>

    <!-- 升级结果 -->
    <?php if ($upgradeResult): ?>
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; color: <?= $upgradeResult['success'] ? '#16a34a' : '#dc2626' ?>;">
                <?= $upgradeResult['success'] ? '升级成功' : '升级失败' ?>
            </h3>
            <?php if (!$upgradeResult['success']): ?>
                <div class="error"><?= htmlspecialchars($upgradeResult['error'] ?? '未知错误') ?></div>
            <?php endif; ?>
            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; font-size: 12px; font-family: monospace; max-height: 300px; overflow-y: auto;">
                <?php foreach ($upgradeResult['log'] as $line): ?>
                    <div style="padding: 2px 0; color: #374151;"><?= htmlspecialchars($line) ?></div>
                <?php endforeach; ?>
            </div>
            <?php if ($upgradeResult['success']): ?>
                <div style="margin-top: 12px; font-size: 13px; color: #6b7280;">
                    备份位置：<?= htmlspecialchars(UPGRADE_BACKUP_DIR) ?><br>
                    文件备份：<?= htmlspecialchars(basename($upgradeResult['backup_files'])) ?><br>
                    数据库备份：<?= htmlspecialchars(basename($upgradeResult['backup_db'])) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- 检查更新结果 -->
    <?php if ($updateInfo): ?>
        <div class="card" style="margin-bottom: 20px;">
            <?php if (isset($updateInfo['error'])): ?>
                <div class="error"><?= htmlspecialchars($updateInfo['error']) ?></div>
            <?php elseif ($updateInfo['has_update']): ?>
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 8px; color: #2563eb;">
                    发现新版本：<?= htmlspecialchars($updateInfo['latest']) ?>
                </h3>
                <p style="font-size: 13px; color: #6b7280; margin-bottom: 16px;">
                    发布日期：<?= htmlspecialchars($updateInfo['latest_date'] ?? '未知') ?>
                    <?php if (!empty($updateInfo['min_php'])): ?>
                        　|　最低 PHP 版本：<?= htmlspecialchars($updateInfo['min_php']) ?>
                    <?php endif; ?>
                </p>

                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #111827;">更新内容</h4>
                <ul style="margin-bottom: 20px; padding-left: 20px;">
                    <?php foreach ($updateInfo['notes'] as $note): ?>
                        <li style="font-size: 13px; color: #374151; padding: 2px 0;"><?= htmlspecialchars($note) ?></li>
                    <?php endforeach; ?>
                </ul>

                <form method="post" onsubmit="return confirm('确定要升级到 <?= htmlspecialchars($updateInfo['latest']) ?> 吗？\n升级前会自动备份文件和数据库。');">
                    <input type="hidden" name="action" value="upgrade">
                    <input type="hidden" name="target_version" value="<?= htmlspecialchars($updateInfo['latest']) ?>">
                    <input type="hidden" name="download_url" value="<?= htmlspecialchars($updateInfo['download']) ?>">
                    <input type="hidden" name="checksum" value="<?= htmlspecialchars($updateInfo['checksum'] ?? '') ?>">
                    <button type="submit" class="btn-primary" style="padding: 10px 24px;">
                        立即升级到 <?= htmlspecialchars($updateInfo['latest']) ?>
                    </button>
                </form>

                <?php if (count($updateInfo['upgradable']) > 1): ?>
                    <p style="margin-top: 12px; font-size: 12px; color: #9ca3af;">
                        共 <?= count($updateInfo['upgradable']) ?> 个版本可升级，将一次性升级到最新版本
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <div class="success" style="margin-bottom: 0;">
                    当前已是最新版本（<?= htmlspecialchars($updateInfo['latest']) ?>）
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- 备份列表 -->
    <div class="card">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; color: #111827;">升级备份</h3>
        <?php if (empty($backups)): ?>
            <p style="font-size: 13px; color: #6b7280;">暂无备份文件</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>文件名</th><th>大小</th><th>时间</th></tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($backups, 0, 20) as $b): ?>
                        <tr>
                            <td style="font-family: monospace; font-size: 12px;"><?= htmlspecialchars($b['name']) ?></td>
                            <td><?= formatBytes($b['size']) ?></td>
                            <td><?= date('Y-m-d H:i:s', $b['time']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($backups) > 20): ?>
                <p style="margin-top: 8px; font-size: 12px; color: #9ca3af;">仅显示最近 20 个，共 <?= count($backups) ?> 个</p>
            <?php endif; ?>
        <?php endif; ?>
        <p style="margin-top: 12px; font-size: 12px; color: #9ca3af;">
            备份目录：<?= htmlspecialchars(UPGRADE_BACKUP_DIR) ?>（如需回滚，请手动恢复）
        </p>
    </div>
</main>
</body>
</html>
