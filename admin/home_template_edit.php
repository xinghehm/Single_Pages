<?php
require_once __DIR__ . '/../functions.php';
if (!isAdminLoggedIn()) { header('Location: login.php'); exit; }
$config = getConfig();

$tpl = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['tpl'] ?? '');
$tplDir = __DIR__ . '/../templates/' . $tpl;
$tplIndex = $tplDir . '/index.php';
$tplInfo = $tplDir . '/info.json';

if (!is_dir($tplDir) || !file_exists($tplIndex)) {
    die('模板不存在');
}

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_index') {
        $content = $_POST['content'] ?? '';
        file_put_contents($tplIndex, $content);
        $msg = '模板文件已保存';
        $msgType = 'success';
    } elseif ($action === 'save_info') {
        $content = $_POST['info_content'] ?? '';
        if (json_decode($content) !== null) {
            file_put_contents($tplInfo, $content);
            $msg = '模板信息已保存';
            $msgType = 'success';
        } else {
            $msg = 'JSON格式错误，请检查';
            $msgType = 'error';
        }
    }
}

$indexContent = file_get_contents($tplIndex);
$infoContent = file_exists($tplInfo) ? file_get_contents($tplInfo) : '{}';
$info = json_decode($infoContent, true) ?: [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>模板编辑 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-edit-line"></i></div><span>模板编辑</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('show');document.querySelector('.sidebar-overlay').classList.toggle('show');}</script>
<?php include 'sidebar.php'; ?>

<main>
<a href="home_templates.php" class="back-link"><i class="ri-arrow-left-line"></i> 返回模板列表</a>
<h1 class="page-title">编辑模板：<?= htmlspecialchars($info['name'] ?? $tpl) ?></h1>
<p class="page-desc">模板目录：/templates/<?= htmlspecialchars($tpl) ?>/</p>
<?php if ($msg): ?><div class="msg <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="tabs">
<button type="button" class="tab active" data-tab="index">index.php</button>
<button type="button" class="tab" data-tab="info">info.json</button>
</div>

<div class="panel active" id="panel-index">
<label class="editor-label">模板首页文件（PHP/HTML）</label>
<form method="post">
<input type="hidden" name="action" value="save_index">
<textarea name="content" class="code-editor" spellcheck="false"><?= htmlspecialchars($indexContent) ?></textarea>
<button type="submit" class="save-btn"><i class="ri-save-line"></i> 保存模板文件</button>
</form>
</div>

<div class="panel" id="panel-info">
<label class="editor-label">模板信息（JSON格式）</label>
<form method="post">
<input type="hidden" name="action" value="save_info">
<textarea name="info_content" class="code-editor" spellcheck="false" style="min-height:300px"><?= htmlspecialchars($infoContent) ?></textarea>
<button type="submit" class="save-btn"><i class="ri-save-line"></i> 保存模板信息</button>
</form>
</div>
</div>
<script>
document.querySelectorAll('.tab').forEach(function(tab){
    tab.addEventListener('click', function(){
        document.querySelectorAll('.tab').forEach(function(t){t.classList.remove('active')});
        document.querySelectorAll('.panel').forEach(function(p){p.classList.remove('active')});
        this.classList.add('active');
        document.getElementById('panel-' + this.dataset.tab).classList.add('active');
    });
});
</script>
</body>
</html>


