<?php require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$projects = getUserProjects($user['id']);
$templates = getAllTemplates();
$config = getConfig();

// 处理克隆
if (isset($_GET['clone']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $clonePro = $_GET['clone'];
    $newProId = cloneProject($clonePro, $user['id']);
    if ($newProId) {
        header("Location: project_detail.php?pro=$newProId");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>我的项目 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><link rel="stylesheet" href="../style.css?v=241"><?= outputUserBgStyle() ?>
<style>
.mobile-topbar { display: none; position: fixed; top: 0; left: 0; right: 0; height: 56px; background: #ffffff; border-bottom: 1px solid rgba(0,0,0,0.06); z-index: 999; align-items: center; justify-content: space-between; padding: 0 16px; }
.mobile-topbar .mobile-logo { display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 15px; }
.mobile-topbar .logo-icon { width: 32px; height: 32px; background: #2563eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; }
.hamburger-btn { width: 40px; height: 40px; background: #f3f4f6; border: none; border-radius: 10px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; }
.hamburger-btn span { display: block; width: 20px; height: 2px; background: #333; border-radius: 2px; }
.sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 998; }
.sidebar-overlay.show { display: block; }
.modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; padding: 20px; }
.modal-overlay.show { display: flex; }
.modal-box { background: #fff; border-radius: 8px; padding: 32px; max-width: 560px; width: 100%; max-height: 90vh; overflow-y: auto; }
.template-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin: 16px 0; }
.template-card { border: 2px solid #e5e7eb; border-radius: 12px; padding: 16px; cursor: pointer; transition: all 0.2s; text-align: center; }
.template-card:hover { border-color: #3b82f6; transform: translateY(-2px); }
.template-card.selected { border-color: #3b82f6; background: #eff6ff; }
.template-card .tpl-icon { font-size: 32px; color: #3b82f6; margin-bottom: 8px; }
.template-card .tpl-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
.template-card .tpl-desc { font-size: 11px; color: #999; }
@media (max-width: 768px) {
    .mobile-topbar { display: flex; }
    .sidebar { position: fixed !important; left: -280px !important; top: 0; bottom: 0; z-index: 1000 !important; transition: left 0.3s !important; width: 280px !important; padding-top: 70px !important; }
    .sidebar.show { left: 0 !important; }
    main { padding: 16px !important; padding-top: 72px !important; }
}
</style>
</head>
<body class="has-sidebar">
<div class="mobile-topbar">
    <div class="mobile-logo"><div class="logo-icon"><i class="ri-cloud-line"></i></div><span><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></span></div>
    <button class="hamburger-btn" onclick="toggleSidebar(this)"><span></span><span></span><span></span></button>
</div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>
function toggleSidebar(btn) {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}
</script>

<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div><div class="flex items-center gap-2 mb-8"><div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div><div><div class="font-bold"><?= htmlspecialchars($config['site_name']) ?></div><div class="text-xs text-gray-500">Pages</div></div></div>
    <nav class="space-y-2"><a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 控制台</a><a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-folder-line"></i> 我的项目</a><a href="login_logs.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-history-line"></i> 登录日志</a><a href="buy_group.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-vip-crown-line"></i> 购买用户组</a><a href="profile.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-user-line"></i> 个人中心</a></nav></div>
    <div><a href="../logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>

<main class="flex-1 p-6">
<div class="flex justify-between items-center mb-6 flex-wrap gap-3">
    <div><h1 class="text-2xl font-bold">我的项目</h1><p class="text-gray-500">共 <?= count($projects) ?> 个项目</p></div>
    <button onclick="openNewModal()" class="btn-primary"><i class="ri-add-line"></i> 新建项目</button>
</div>

<div class="glass-card p-4">
<?php if (empty($projects)): ?>
<div class="text-center py-12"><i class="ri-folder-line text-6xl text-gray-300 mb-4"></i><p>暂无项目，点击右上角创建</p></div>
<?php else: ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
<?php foreach ($projects as $pro): $totalVisits = getProjectTotalVisits($pro['pro_id']); ?>
<div class="bg-white/60 rounded-xl p-4 hover:shadow-lg transition">
    <div class="font-bold text-lg"><?= htmlspecialchars($pro['name']) ?></div>
    <div class="text-gray-500 text-xs mb-2"><?= date('Y-m-d', $pro['created_at']) ?></div>
    <div class="text-xs text-gray-400 mb-3"><i class="ri-eye-line"></i> 访问 <?= $totalVisits ?> 次</div>
    <div class="flex justify-between items-center">
        <span class="text-xs text-green-600 flex items-center gap-1"><span class="w-2 h-2 bg-green-500 rounded-full"></span> 在线</span>
        <div class="space-x-2">
            <a href="project_detail.php?pro=<?= $pro['pro_id'] ?>" class="text-blue-600 text-sm">管理</a>
            <a href="projects.php?clone=<?= $pro['pro_id'] ?>" onclick="return confirm('克隆此项目？')" class="text-purple-600 text-sm">克隆</a>
            <a href="project_delete.php?pro=<?= $pro['pro_id'] ?>" onclick="return confirm('删除项目不可恢复')" class="text-red-500 text-sm">删除</a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</main>

<!-- 新建项目模态框 -->
<div class="modal-overlay" id="newModal">
    <div class="modal-box">
        <h2 class="text-xl font-bold mb-4">新建项目</h2>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">项目名称</label>
            <input type="text" id="projectName" placeholder="输入项目名称" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">选择模板</label>
            <div class="template-grid" id="templateGrid">
                <div class="template-card selected" data-id="0">
                    <div class="tpl-icon"><i class="ri-file-code-line"></i></div>
                    <div class="tpl-name">空白项目</div>
                    <div class="tpl-desc">从零开始</div>
                </div>
                <?php foreach ($templates as $tpl): ?>
                <div class="template-card" data-id="<?= $tpl['id'] ?>">
                    <div class="tpl-icon"><i class="<?= htmlspecialchars($tpl['icon']) ?>"></i></div>
                    <div class="tpl-name"><?= htmlspecialchars($tpl['name']) ?></div>
                    <div class="tpl-desc"><?= htmlspecialchars($tpl['description']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex gap-3 justify-end">
            <button onclick="closeNewModal()" class="btn-secondary">取消</button>
            <button onclick="createProject()" class="btn-primary">创建</button>
        </div>
    </div>
</div>

<script>
let selectedTemplate = 0;
function openNewModal() { document.getElementById('newModal').classList.add('show'); document.getElementById('projectName').value = ''; selectedTemplate = 0; updateTemplateSelection(); }
function closeNewModal() { document.getElementById('newModal').classList.remove('show'); }
document.getElementById('templateGrid').addEventListener('click', function(e) {
    const card = e.target.closest('.template-card');
    if (!card) return;
    selectedTemplate = parseInt(card.dataset.id);
    updateTemplateSelection();
});
function updateTemplateSelection() {
    document.querySelectorAll('.template-card').forEach(function(c) {
        c.classList.toggle('selected', parseInt(c.dataset.id) === selectedTemplate);
    });
}
function createProject() {
    const name = document.getElementById('projectName').value.trim();
    if (!name) { alert('请输入项目名称'); return; }
    if (!/^[a-zA-Z0-9\x{4e00}-\x{9fa5}_\-]+$/u.test(name)) { alert('项目名只能包含中英文、数字、下划线、短横线'); return; }
    let url = 'project_new.php?name=' + encodeURIComponent(name);
    if (selectedTemplate > 0) url += '&template=' + selectedTemplate;
    location.href = url;
}
document.getElementById('newModal').addEventListener('click', function(e) { if (e.target === this) closeNewModal(); });
</script>
</body>
</html>
