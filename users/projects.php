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

// 生成项目封面渐变色（基于项目名hash）
function projectCoverGradient($name) {
    $gradients = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
        'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
        'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
    ];
    $hash = crc32($name);
    return $gradients[$hash % count($gradients)];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>我的项目 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
<?= outputUserBgStyle() ?>
<style>
.template-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin: 16px 0; }
.template-card { border: 2px solid var(--border); border-radius: var(--radius-md); padding: 16px; cursor: pointer; transition: var(--transition); text-align: center; }
.template-card:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow-md); }
.template-card.selected { border-color: var(--primary); background: var(--primary-light); }
.template-card .tpl-icon { font-size: 28px; color: var(--primary); margin-bottom: 8px; }
.template-card .tpl-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; color: var(--text-1); }
.template-card .tpl-desc { font-size: 11px; color: var(--text-4); }

.delete-confirm-btn {
    position: relative;
    overflow: hidden;
    background: #dc2626;
    color: #fff;
    padding: 12px 28px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    -webkit-user-select: none;
    user-select: none;
    -webkit-touch-callout: none;
}
.delete-confirm-btn .dcp-progress {
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 0%;
    background: rgba(0,0,0,0.2);
    pointer-events: none;
}
.delete-confirm-btn.pressing {
    transform: scale(0.96);
}
.delete-confirm-btn.pressing .dcp-progress {
    width: 100%;
    transition: width 1s linear;
}
.delete-confirm-btn .dcp-text { position: relative; z-index: 1; }
</style>
</head>
<body class="has-sidebar">

<!-- 移动端顶部栏 -->
<div class="mobile-topbar">
    <div class="mobile-logo"><div class="logo-icon"><i class="ri-cloud-line"></i></div><span><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></span></div>
    <button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
</div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- 侧边栏 -->
<aside class="sidebar">
    <div>
        <div class="brand">
            <div class="logo"><i class="ri-cloud-line"></i></div>
            <div><div class="name"><?= htmlspecialchars($config['site_name']) ?></div><div class="sub">Pages</div></div>
        </div>
        <nav>
            <a href="dashboard.php"><i class="ri-dashboard-line"></i> 控制台</a>
            <a href="templates.php"><i class="ri-apps-line"></i> 模板市场</a>
            <a href="projects.php" class="active"><i class="ri-folder-line"></i> 我的项目</a>
            <a href="login_logs.php"><i class="ri-history-line"></i> 登录日志</a>
            <a href="buy_group.php"><i class="ri-vip-crown-line"></i> 购买用户组</a>
            <a href="profile.php"><i class="ri-user-line"></i> 个人中心</a>
        </nav>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php" style="color:#dc2626;"><i class="ri-logout-box-line"></i> 退出登录</a>
    </div>
</aside>

<main>
    <!-- 页头 -->
    <div class="page-header">
        <div>
            <h1>我的项目</h1>
            <p class="subtitle">共 <?= count($projects) ?> 个项目</p>
        </div>
        <button onclick="openNewModal()" class="btn-primary"><i class="ri-add-line"></i> 新建项目</button>
    </div>

    <!-- 骨架屏（加载时显示） -->
    <div id="skeletonArea" class="masonry" style="display:none;">
        <?php for ($i = 0; $i < 8; $i++): ?>
        <div class="masonry-item">
            <div class="project-card">
                <div class="skeleton skeleton-cover"></div>
                <div class="body">
                    <div class="skeleton skeleton-title"></div>
                    <div class="skeleton skeleton-text" style="width:40%;"></div>
                    <div class="skeleton skeleton-text" style="width:60%;margin-top:12px;"></div>
                </div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- 实际内容 -->
    <div id="contentArea">
    <?php if (empty($projects)): ?>
        <!-- 空状态 -->
        <div class="empty-state">
            <div class="icon-wrap"><i class="ri-folder-open-line"></i></div>
            <h3>还没有项目</h3>
            <p>创建你的第一个项目，开始搭建静态页面</p>
            <button onclick="openNewModal()" class="btn-primary"><i class="ri-add-line"></i> 新建项目</button>
        </div>
    <?php else: ?>
        <!-- 瀑布流 -->
        <div class="masonry">
        <?php foreach ($projects as $pro): $totalVisits = getProjectTotalVisits($pro['pro_id']); ?>
            <div class="masonry-item">
                <div class="project-card">
                    <div class="cover" style="background:<?= projectCoverGradient($pro['name']) ?>;">
                        <span style="font-size:28px;font-weight:700;position:relative;z-index:1;"><?= mb_substr($pro['name'], 0, 1) ?></span>
                    </div>
                    <div class="body">
                        <div class="title" title="<?= htmlspecialchars($pro['name']) ?>"><?= htmlspecialchars($pro['name']) ?></div>
                        <div class="meta">
                            <span><i class="ri-calendar-line"></i> <?= date('Y-m-d', $pro['created_at']) ?></span>
                            <span><i class="ri-eye-line"></i> <?= $totalVisits ?></span>
                        </div>
                        <div class="actions">
                            <a href="project_detail.php?pro=<?= $pro['pro_id'] ?>" class="act-manage"><i class="ri-settings-3-line"></i> 管理</a>
                            <a href="projects.php?clone=<?= $pro['pro_id'] ?>" onclick="return confirm('克隆此项目？')" class="act-clone"><i class="ri-file-copy-line"></i> 克隆</a>
                            <button class="act-delete" onclick="openDeleteModal('<?= $pro['pro_id'] ?>', '<?= htmlspecialchars($pro['name'], ENT_QUOTES) ?>')"><i class="ri-delete-bin-line"></i> 删除</button>
                        </div>
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
    <div class="modal">
        <h3>新建项目</h3>
        <div class="form-group">
            <label>项目名称</label>
            <input type="text" id="projectName" placeholder="输入项目名称">
        </div>
        <div class="form-group">
            <label>选择模板</label>
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

<!-- 删除确认弹窗 -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:400px;text-align:center;">
        <div style="width:64px;height:64px;margin:0 auto 16px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;color:#dc2626;">
            <i class="ri-error-warning-line"></i>
        </div>
        <h3 style="margin-bottom:8px;">确认删除项目</h3>
        <p style="color:var(--text-3);margin-bottom:24px;">项目 <strong id="deleteProName" style="color:var(--text-1);"></strong> 将被永久删除，不可恢复</p>
        <div class="flex gap-3" style="justify-content:center;">
            <button onclick="closeDeleteModal()" class="btn-secondary">取消</button>
            <button id="deleteConfirmBtn" class="delete-confirm-btn">
                <div class="dcp-progress"></div>
                <span class="dcp-text">按住确认删除</span>
            </button>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}

// 骨架屏：页面加载时短暂显示
(function() {
    var skeleton = document.getElementById('skeletonArea');
    var content = document.getElementById('contentArea');
    if (skeleton && content) {
        content.style.display = 'none';
        skeleton.style.display = 'block';
        setTimeout(function() {
            skeleton.style.display = 'none';
            content.style.display = 'block';
        }, 600);
    }
})();

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
    if (!/^[a-zA-Z0-9\u4e00-\u9fa5_\-]+$/.test(name)) { alert('项目名只能包含中英文、数字、下划线、短横线'); return; }
    let url = 'project_new.php?name=' + encodeURIComponent(name);
    if (selectedTemplate > 0) url += '&template=' + selectedTemplate;
    location.href = url;
}
document.getElementById('newModal').addEventListener('click', function(e) { if (e.target === this) closeNewModal(); });

// 删除确认弹窗
var deleteProId = null;
function openDeleteModal(proId, proName) {
    deleteProId = proId;
    document.getElementById('deleteProName').textContent = proName;
    document.getElementById('deleteModal').classList.add('show');
    resetDeleteBtn();
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    resetDeleteBtn();
}
function resetDeleteBtn() {
    var btn = document.getElementById('deleteConfirmBtn');
    btn.classList.remove('pressing');
    btn.querySelector('.dcp-text').textContent = '按住确认删除';
}
// 长按确认删除
(function() {
    var btn = document.getElementById('deleteConfirmBtn');
    var timer = null;
    function startPress(e) {
        e.preventDefault();
        btn.classList.add('pressing');
        btn.querySelector('.dcp-text').textContent = '正在删除...';
        timer = setTimeout(function() {
            location.href = 'project_delete.php?pro=' + deleteProId;
        }, 1000);
    }
    function cancelPress() {
        if (timer) { clearTimeout(timer); timer = null; }
        resetDeleteBtn();
    }
    btn.addEventListener('mousedown', startPress);
    btn.addEventListener('touchstart', startPress, {passive: false});
    btn.addEventListener('mouseup', cancelPress);
    btn.addEventListener('mouseleave', cancelPress);
    btn.addEventListener('touchend', cancelPress);
    btn.addEventListener('touchcancel', cancelPress);
})();
document.getElementById('deleteModal').addEventListener('click', function(e) { if (e.target === this) closeDeleteModal(); });
</script>
</body>
</html>
