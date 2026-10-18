<?php
require_once __DIR__ . '/functions.php';
$siteName = $config['site_name'] ?? '单页工坊';
$siteDesc = $config['site_description'] ?? '静态网站一站式托管平台';
$siteIcp = $config['site_icp'] ?? '';
$siteFooter = $config['site_footer'] ?? '';

// 动态读取帮助内容
$quickStart = json_decode($config['help_quick_start'] ?? '[]', true);
if (empty($quickStart)) {
    $quickStart = [
        ['title' => '注册账号', 'desc' => '填写邮箱完成注册，立即获得 200MB 初始空间'],
        ['title' => '创建项目', 'desc' => '点击"新建项目"，输入项目名称和简介'],
        ['title' => '上传文件', 'desc' => '进入文件管理，上传你的 HTML/CSS/JS/图片等文件'],
        ['title' => '设置域名', 'desc' => '系统自动分配访问地址，也可绑定自定义域名'],
        ['title' => '发布上线', 'desc' => '内容审核通过后，网站即刻对外可访问'],
    ];
}
$features = json_decode($config['help_features'] ?? '[]', true);
if (empty($features)) {
    $features = [
        ['icon' => 'ri-folder-line', 'title' => '文件管理', 'desc' => '在线上传、编辑、删除文件，支持批量操作'],
        ['icon' => 'ri-database-2-line', 'title' => '数据库管理', 'desc' => '每个项目独立数据库，在线管理表和数据'],
        ['icon' => 'ri-link', 'title' => '我的站点', 'desc' => '查看所有项目访问地址，管理站点状态'],
        ['icon' => 'ri-user-settings-line', 'title' => '账号管理', 'desc' => '修改密码、绑定邮箱、查看登录记录'],
        ['icon' => 'ri-shopping-bag-line', 'title' => '购买套餐', 'desc' => '升级用户组获得更多空间和功能权限'],
        ['icon' => 'ri-wallet-3-line', 'title' => '账户余额', 'desc' => '在线充值，余额可用于购买套餐和增值服务'],
        ['icon' => 'ri-notification-3-line', 'title' => '系统公告', 'desc' => '及时了解平台更新、维护通知和重要公告'],
        ['icon' => 'ri-shield-check-line', 'title' => '内容审核', 'desc' => '上传内容自动审核，保障网站合规运行'],
        ['icon' => 'ri-mail-line', 'title' => '邮件通知', 'desc' => '登录、充值、封禁等重要操作全程邮件提醒'],
    ];
}
$faqList = json_decode($config['faq_list'] ?? '[]', true);
if (empty($faqList)) {
    $faqList = [
        ['q' => '什么是' . $siteName . '？', 'a' => $siteName . '是一个' . $siteDesc . '，无需服务器运维，上传文件即可上线。'],
        ['q' => '初始空间是多少？', 'a' => '注册即赠送 200MB 初始空间，可随时充值升级。'],
        ['q' => '网站需要审核吗？', 'a' => '是的，上传文件后系统会进行内容审核，通过后即可开放访问。'],
        ['q' => '账号安全如何保障？', 'a' => '登录、异地登录、充值、封禁全程邮件通知，异常站点自动拦截。'],
        ['q' => '支持哪些支付方式？', 'a' => '支持易支付安全充值，实时到账，多种支付方式可选。'],
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>帮助中心 - <?= htmlspecialchars($siteName) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f7fa;color:#333;line-height:1.6}
.container{max-width:900px;margin:0 auto;padding:20px}
.hero{background:linear-gradient(135deg,#4a90d9,#357abd);color:#fff;border-radius:16px;padding:40px 30px;text-align:center;margin-bottom:24px}
.hero h1{font-size:28px;margin-bottom:8px}
.hero p{opacity:.9;font-size:15px}
.section{background:#fff;border-radius:12px;padding:28px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.section h2{font-size:20px;margin-bottom:20px;color:#1a1a2e;display:flex;align-items:center;gap:8px}
.steps{display:flex;flex-direction:column;gap:0;position:relative;padding-left:24px}
.steps::before{content:'';position:absolute;left:8px;top:12px;bottom:12px;width:2px;background:#e0e8f0}
.step{position:relative;padding:12px 0}
.step::before{content:'';position:absolute;left:-24px;top:18px;width:14px;height:14px;border-radius:50%;background:#4a90d9;border:3px solid #fff;box-shadow:0 0 0 2px #4a90d9}
.step h4{font-size:15px;color:#1a1a2e;margin-bottom:2px}
.step p{font-size:13px;color:#666}
.features-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px}
.feature-item{display:flex;gap:12px;padding:14px;background:#f8fafc;border-radius:10px}
.feature-item .icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#4a90d9,#357abd);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.feature-item h4{font-size:14px;color:#1a1a2e;margin-bottom:2px}
.feature-item p{font-size:12px;color:#666}
.faq-item{border-bottom:1px solid #f0f0f0;cursor:pointer}
.faq-item:last-child{border-bottom:none}
.faq-q{padding:14px 0;display:flex;justify-content:space-between;align-items:center;font-size:14px;font-weight:500;color:#1a1a2e}
.faq-q i{transition:transform .3s;color:#999}
.faq-item.open .faq-q i{transform:rotate(90deg)}
.faq-a{max-height:0;overflow:hidden;transition:max-height .3s ease;font-size:13px;color:#666;padding:0}
.faq-item.open .faq-a{max-height:200px;padding-bottom:14px}
.cta{text-align:center;padding:30px}
.cta .btn{display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#4a90d9,#357abd);color:#fff;border-radius:25px;text-decoration:none;font-size:15px;font-weight:500;transition:transform .2s,box-shadow .2s}
.cta .btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(74,144,217,.3)}
footer{text-align:center;padding:24px;color:#999;font-size:12px}
footer a{color:#4a90d9;text-decoration:none}
@media(max-width:600px){.hero{padding:30px 20px}.hero h1{font-size:22px}.section{padding:20px}.features-grid{grid-template-columns:1fr}}
</style>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
<div class="container">
<div class="hero">
<h1><i class="ri-question-line"></i> 帮助中心</h1>
<p><?= htmlspecialchars($siteName) ?> 使用指南，快速上手搭建你的网站</p>
</div>

<div class="section">
<h2><i class="ri-rocket-line" style="color:#4a90d9"></i> 快速开始</h2>
<div class="steps">
<?php foreach ($quickStart as $s): ?>
<div class="step"><h4><?= htmlspecialchars($s['title']) ?></h4><p><?= htmlspecialchars($s['desc']) ?></p></div>
<?php endforeach; ?>
</div>
</div>

<div class="section">
<h2><i class="ri-apps-line" style="color:#4a90d9"></i> 功能说明</h2>
<div class="features-grid">
<?php foreach ($features as $f): ?>
<div class="feature-item"><div class="icon"><i class="<?= htmlspecialchars($f['icon']) ?>"></i></div><div><h4><?= htmlspecialchars($f['title']) ?></h4><p><?= htmlspecialchars($f['desc']) ?></p></div></div>
<?php endforeach; ?>
</div>
</div>

<div class="section">
<h2><i class="ri-chat-question-line" style="color:#4a90d9"></i> 常见问题</h2>
<?php foreach ($faqList as $faq): ?>
<div class="faq-item" onclick="this.classList.toggle('open')">
<div class="faq-q"><?= htmlspecialchars($faq['q']) ?><i class="ri-arrow-right-s-line"></i></div>
<div class="faq-a"><?= htmlspecialchars($faq['a']) ?></div>
</div>
<?php endforeach; ?>
</div>

<div class="cta">
<a href="/register.php" class="btn"><i class="ri-user-add-line"></i> 立即注册，免费开始</a>
</div>

<footer>
<?php if ($siteIcp): ?><a href="https://beian.miit.gov.cn" target="_blank"><?= htmlspecialchars($siteIcp) ?></a> | <?php endif; ?>
<?= htmlspecialchars($siteFooter ?: '© ' . date('Y') . ' ' . $siteName) ?>
</footer>
</div>
</body>
</html>
