<?php
require_once __DIR__ . '/../functions.php';
if (!isAdminLoggedIn()) { header('Location: login.php'); exit; }
$config = getConfig();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_faq') {
        $items = [];
        $qs = $_POST['faq_q'] ?? [];
        $as = $_POST['faq_a'] ?? [];
        for ($i = 0; $i < count($qs); $i++) {
            if (!empty(trim($qs[$i]))) {
                $items[] = ['q' => trim($qs[$i]), 'a' => trim($as[$i] ?? '')];
            }
        }
        setConfig('faq_list', json_encode($items, JSON_UNESCAPED_UNICODE));
        $msg = 'FAQ已保存';
    } elseif ($action === 'save_quick') {
        $items = [];
        $ts = $_POST['qs_title'] ?? [];
        $ds = $_POST['qs_desc'] ?? [];
        for ($i = 0; $i < count($ts); $i++) {
            if (!empty(trim($ts[$i]))) {
                $items[] = ['title' => trim($ts[$i]), 'desc' => trim($ds[$i] ?? '')];
            }
        }
        setConfig('help_quick_start', json_encode($items, JSON_UNESCAPED_UNICODE));
        $msg = '快速开始已保存';
    } elseif ($action === 'save_features') {
        $items = [];
        $icons = $_POST['f_icon'] ?? [];
        $titles = $_POST['f_title'] ?? [];
        $descs = $_POST['f_desc'] ?? [];
        for ($i = 0; $i < count($titles); $i++) {
            if (!empty(trim($titles[$i]))) {
                $items[] = ['icon' => trim($icons[$i] ?? 'ri-checkbox-circle-line'), 'title' => trim($titles[$i]), 'desc' => trim($descs[$i] ?? '')];
            }
        }
        setConfig('help_features', json_encode($items, JSON_UNESCAPED_UNICODE));
        $msg = '功能说明已保存';
    }
}

$faqList = json_decode($config['faq_list'] ?? '[]', true);
$quickStart = json_decode($config['help_quick_start'] ?? '[]', true);
$features = json_decode($config['help_features'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>内容配置 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
<style>
.form-row{display:flex;gap:10px;margin-bottom:10px}
.form-row input{padding:8px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px}
.add-btn{margin-bottom:12px;padding:8px 16px;border:1px dashed #3b82f6;background:none;color:#3b82f6;border-radius:8px;cursor:pointer;font-size:13px}
.del-btn{padding:8px 12px;border:none;background:#ef4444;color:#fff;border-radius:8px;cursor:pointer;font-size:13px}
</style>
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-file-text-line"></i></div><span>内容配置</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('show');document.querySelector('.sidebar-overlay').classList.toggle('show');}</script>
<?php include 'sidebar.php'; ?>

<main>
<div class="page-header">
<div><h1>内容配置</h1><p class="subtitle">配置首页FAQ、帮助中心快速开始和功能说明</p></div>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- FAQ -->
<div class="card">
<div class="card-header"><h3>常见问题（FAQ）</h3><span class="muted">显示在首页和帮助中心</span></div>
<div class="card-body">
<form method="post">
<input type="hidden" name="action" value="save_faq">
<div id="faqItems">
<?php if (empty($faqList)): ?>
<div class="form-row"><input type="text" name="faq_q[]" placeholder="问题" style="flex:1"><input type="text" name="faq_a[]" placeholder="答案" style="flex:2"><button type="button" class="del-btn" onclick="this.parentElement.remove()">删</button></div>
<?php else: foreach ($faqList as $f): ?>
<div class="form-row"><input type="text" name="faq_q[]" value="<?= htmlspecialchars($f['q']) ?>" style="flex:1"><input type="text" name="faq_a[]" value="<?= htmlspecialchars($f['a']) ?>" style="flex:2"><button type="button" class="del-btn" onclick="this.parentElement.remove()">删</button></div>
<?php endforeach; endif; ?>
</div>
<button type="button" class="add-btn" onclick="addRow('faqItems','faq_q[]','问题','faq_a[]','答案')">+ 添加问题</button>
<br><button type="submit" class="btn-primary">保存FAQ</button>
</form>
</div>
</div>

<!-- 快速开始 -->
<div class="card">
<div class="card-header"><h3>帮助中心 - 快速开始</h3></div>
<div class="card-body">
<form method="post">
<input type="hidden" name="action" value="save_quick">
<div id="quickItems">
<?php if (empty($quickStart)): ?>
<div class="form-row"><input type="text" name="qs_title[]" placeholder="步骤标题" style="flex:1"><input type="text" name="qs_desc[]" placeholder="步骤说明" style="flex:2"><button type="button" class="del-btn" onclick="this.parentElement.remove()">删</button></div>
<?php else: foreach ($quickStart as $q): ?>
<div class="form-row"><input type="text" name="qs_title[]" value="<?= htmlspecialchars($q['title']) ?>" style="flex:1"><input type="text" name="qs_desc[]" value="<?= htmlspecialchars($q['desc']) ?>" style="flex:2"><button type="button" class="del-btn" onclick="this.parentElement.remove()">删</button></div>
<?php endforeach; endif; ?>
</div>
<button type="button" class="add-btn" onclick="addRow('quickItems','qs_title[]','步骤标题','qs_desc[]','步骤说明')">+ 添加步骤</button>
<br><button type="submit" class="btn-primary">保存快速开始</button>
</form>
</div>
</div>

<!-- 功能说明 -->
<div class="card">
<div class="card-header"><h3>帮助中心 - 功能说明</h3><span class="muted">图标用Remix Icon类名，如 ri-folder-line</span></div>
<div class="card-body">
<form method="post">
<input type="hidden" name="action" value="save_features">
<div id="featureItems">
<?php if (empty($features)): ?>
<div class="form-row"><input type="text" name="f_icon[]" placeholder="图标类名" style="width:140px"><input type="text" name="f_title[]" placeholder="功能名称" style="flex:1"><input type="text" name="f_desc[]" placeholder="功能说明" style="flex:2"><button type="button" class="del-btn" onclick="this.parentElement.remove()">删</button></div>
<?php else: foreach ($features as $f): ?>
<div class="form-row"><input type="text" name="f_icon[]" value="<?= htmlspecialchars($f['icon']) ?>" style="width:140px"><input type="text" name="f_title[]" value="<?= htmlspecialchars($f['title']) ?>" style="flex:1"><input type="text" name="f_desc[]" value="<?= htmlspecialchars($f['desc']) ?>" style="flex:2"><button type="button" class="del-btn" onclick="this.parentElement.remove()">删</button></div>
<?php endforeach; endif; ?>
</div>
<button type="button" class="add-btn" onclick="addFeatureRow()">+ 添加功能</button>
<br><button type="submit" class="btn-primary">保存功能说明</button>
</form>
</div>
</div>
</main>

<script>
function addRow(containerId, name1, ph1, name2, ph2){
  var d=document.createElement('div');d.className='form-row';
  d.innerHTML='<input type="text" name="'+name1+'" placeholder="'+ph1+'" style="flex:1"><input type="text" name="'+name2+'" placeholder="'+ph2+'" style="flex:2"><button type="button" class="del-btn" onclick="this.parentElement.remove()">删</button>';
  document.getElementById(containerId).appendChild(d);
}
function addFeatureRow(){
  var d=document.createElement('div');d.className='form-row';
  d.innerHTML='<input type="text" name="f_icon[]" placeholder="图标类名" style="width:140px"><input type="text" name="f_title[]" placeholder="功能名称" style="flex:1"><input type="text" name="f_desc[]" placeholder="功能说明" style="flex:2"><button type="button" class="del-btn" onclick="this.parentElement.remove()">删</button>';
  document.getElementById('featureItems').appendChild(d);
}
</script>
</body>
</html>
