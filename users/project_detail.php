<?php
require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$proId = $_GET['pro'] ?? '';
if (!isProjectOwner($proId, $user['id'])) die('无权访问');
$project = getProjectByProId($proId);
$files = getProjectFiles($proId);
$config = getConfig();

// 处理自定义后缀
$slugMsg = ''; $slugMsgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_slug') {
    $slug = trim($_POST['custom_slug'] ?? '');
    if ($slug && !preg_match('/^[a-zA-Z0-9_-]{2,30}$/', $slug)) {
        $slugMsg = '后缀只能包含字母、数字、下划线、短横线，长度2-30位'; $slugMsgType = 'error';
    } elseif ($slug && !checkSlugAvailable($slug, $project['id'])) {
        $slugMsg = '该后缀已被占用'; $slugMsgType = 'error';
    } else {
        setProjectSlug($project['id'], $slug);
        $project = getProjectByProId($proId);
        $slugMsg = $slug ? '自定义后缀已保存' : '已恢复默认访问链接'; $slugMsgType = 'success';
    }
}

$visitStats = getProjectVisitStats($proId, 14);
$totalVisits = getProjectTotalVisits($proId);
$todayVisits = 0;
foreach ($visitStats as $v) { if ($v['visit_date'] === date('Y-m-d')) $todayVisits = intval($v['visit_count']); }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['name']) ?> - 项目详情</title>
    
    <link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=245">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?= outputUserBgStyle() ?>
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
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-cloud-line"></i></div><span><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<?php include 'sidebar.php'; ?>

<main class="flex-1 p-6">
    <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
        <div>
            <a href="projects.php" class="text-gray-500 hover:text-blue-600"><i class="ri-arrow-left-line"></i> 返回项目列表</a>
            <h1 class="text-2xl font-bold mt-2"><?= htmlspecialchars($project['name']) ?></h1>
        </div>
        <div class="flex gap-3 flex-wrap">
           <a href="/<?= $proId ?>" target="_blank" class="btn-secondary">预览站点</a>
            <a href="projects.php?clone=<?= $proId ?>" class="btn-secondary"><i class="ri-file-copy-line"></i> 克隆项目</a>
            <a href="file_new.php?pro=<?= $proId ?>" class="btn-primary"><i class="ri-add-line"></i> 新建文件</a>
        </div>
    </div>

    <!-- 项目信息卡片 -->
    <div class="card" style="margin-bottom:20px;padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:10px;">
            <h3 style="font-size:16px;font-weight:700;margin:0;"><i class="ri-link"></i> 访问设置</h3>
            <?php if ($slugMsg): ?><span style="font-size:13px;color:<?= $slugMsgType=='success'?'#16a34a':'#dc2626'; ?>;"><?= htmlspecialchars($slugMsg) ?></span><?php endif; ?>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">默认访问链接</label>
                <div style="display:flex;align-items:center;gap:8px;background:#f9fafb;padding:10px 14px;border-radius:10px;border:1.5px solid #e5e7eb;">
                    <span style="color:#6b7280;font-size:13px;white-space:nowrap;"><?= htmlspecialchars($config['site_name'] ?? '') ?>/</span>
                    <code style="font-size:13px;color:#2563eb;font-weight:600;"><?= htmlspecialchars($proId) ?></code>
                    <a href="/<?= $proId ?>" target="_blank" style="margin-left:auto;color:#2563eb;font-size:13px;text-decoration:none;"><i class="ri-external-link-line"></i></a>
                </div>
            </div>
            <div>
                <form method="POST">
                    <input type="hidden" name="action" value="save_slug">
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">自定义后缀（可选）</label>
                    <div style="display:flex;gap:8px;">
                        <div style="flex:1;display:flex;align-items:center;background:#f9fafb;padding:0 14px;border-radius:10px;border:1.5px solid #e5e7eb;">
                            <span style="color:#6b7280;font-size:13px;white-space:nowrap;"><?= htmlspecialchars($config['site_name'] ?? '') ?>/</span>
                            <input type="text" name="custom_slug" value="<?= htmlspecialchars($project['custom_slug'] ?? '') ?>" placeholder="留空使用默认" style="flex:1;border:none;background:transparent;padding:10px 0;font-size:13px;outline:none;color:#2563eb;font-weight:600;" maxlength="30">
                        </div>
                        <button type="submit" class="btn-primary btn-sm" style="white-space:nowrap;"><i class="ri-save-line"></i> 保存</button>
                    </div>
                </form>
            </div>
        </div>
        <?php if (!empty($project['custom_slug'])): ?>
        <div style="margin-top:12px;padding:10px 14px;background:#eff6ff;border-radius:8px;font-size:13px;color:#1e40af;">
            <i class="ri-checkbox-circle-line"></i> 自定义链接已生效：<strong><?= htmlspecialchars($config['site_name'] ?? '') ?>/<?= htmlspecialchars($project['custom_slug']) ?></strong>
        </div>
        <?php endif; ?>
    </div>

    <!-- 访问统计 -->
    <div class="card mb-4">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div class="flex items-center gap-2 text-gray-600"><i class="ri-bar-chart-line"></i> 访问统计（近14天）</div>
            <div class="flex gap-4 text-sm">
                <span class="text-gray-500">今日 <strong class="text-blue-600"><?= $todayVisits ?></strong></span>
                <span class="text-gray-500">累计 <strong class="text-blue-600"><?= $totalVisits ?></strong></span>
            </div>
        </div>
        <div style="height: 200px;"><canvas id="visitChart"></canvas></div>
    </div>

    <div class="card">
        <div class="flex items-center gap-2 text-gray-600 mb-4"><i class="ri-file-list-line"></i> 文件列表</div>
        <?php if (empty($files)): ?>
            <div class="text-center py-12"><i class="ri-file-line text-6xl text-gray-300 mb-4"></i><p>暂无文件，点击新建文件</p></div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-gray-500 border-b"><tr><th class="text-left p-3">文件名</th><th class="text-left p-3">首页</th><th class="text-left p-3">更新时间</th><th class="text-left p-3">操作</th></tr></thead>
                    <tbody>
                    <?php foreach ($files as $file): ?>
                        <tr class="border-t">
                            <td class="p-3"><?= htmlspecialchars($file['filename']) ?></td>
                            <td class="p-3"><?= $file['is_index'] ? '<span class="bg-green-100 text-green-600 px-2 py-0.5 rounded text-xs">首页</span>' : '' ?></td>
                            <td class="p-3"><?= date('Y-m-d H:i', $file['updated_at']) ?></td>
                            <td class="p-3 space-x-2">
                                <a href="file_edit.php?pro=<?= $proId ?>&file=<?= urlencode($file['filename']) ?>" class="btn-secondary btn-sm"><i class="ri-edit-line"></i> 编辑</a>
                                <?php if (!$file['is_index']): ?>
                                    <a href="file_delete.php?pro=<?= $proId ?>&file=<?= urlencode($file['filename']) ?>" onclick="return confirm('确定删除？')" class="text-red-500">删除</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
const ctx = document.getElementById('visitChart').getContext('2d');
const labels = <?= json_encode(array_column($visitStats, 'visit_date')) ?>;
const data = <?= json_encode(array_map('intval', array_column($visitStats, 'visit_count'))) ?>;
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: '访问量',
            data: data,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointBackgroundColor: '#3b82f6'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } }
        }
    }
});
</script>
<script>
function toggleSidebar(){
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}
</script>
</body>
</html>










