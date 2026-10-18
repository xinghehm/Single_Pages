<?php
if (!defined('IN_SITE')) { require_once __DIR__ . '/../../functions.php'; $config = getConfig(); }
$siteName = htmlspecialchars($config['site_name'] ?? '单页工坊');
$siteDesc = htmlspecialchars($config['site_description'] ?? '静态网站一站式托管平台');
$siteIcp = htmlspecialchars($config['site_icp'] ?? '');
$siteFooter = htmlspecialchars($config['site_footer'] ?? '');
$statsProjects = htmlspecialchars($config['stats_projects'] ?? '100+');
$statsFeatures = htmlspecialchars($config['stats_features'] ?? '10+');
$groups = getPublicGroups();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $siteName ?> - <?= $siteDesc ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(180deg,#e8f2fe 0%,#f0f6ff 30%,#ffffff 60%);color:#1a1a2e;min-height:100vh;line-height:1.6}
a{text-decoration:none;color:inherit}
.topbar{background:#3b82f6;color:#fff;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.topbar .logo{display:flex;align-items:center;gap:8px;font-weight:700;font-size:15px}
.topbar .logo i{font-size:20px}
.topbar .actions{display:flex;gap:12px;font-size:18px}
.topbar .actions a{color:#fff;opacity:.9}
.container{max-width:480px;margin:0 auto;padding:0 16px}
.hero{text-align:center;padding:40px 0 30px}
.hero .badge{display:inline-flex;align-items:center;gap:6px;background:#fff;border-radius:20px;padding:8px 18px;font-size:13px;color:#3b82f6;font-weight:500;box-shadow:0 2px 12px rgba(59,130,246,.1);margin-bottom:24px}
.hero h1{font-size:32px;font-weight:800;line-height:1.3;margin-bottom:16px}
.hero h1 span{color:#3b82f6}
.hero p{color:#6b7280;font-size:15px;margin-bottom:8px}
.hero .sub{color:#9ca3af;font-size:13px;margin-bottom:28px}
.hero .btns{display:flex;flex-direction:column;gap:12px;max-width:280px;margin:0 auto}
.btn{display:inline-flex;align-items:center;justify-content:center;padding:14px 24px;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;transition:all .2s;border:none}
.btn-primary{background:#3b82f6;color:#fff}
.btn-primary:hover{background:#2563eb}
.btn-outline{background:#fff;color:#374151;border:1.5px solid #e5e7eb}
.btn-outline:hover{border-color:#3b82f6;color:#3b82f6}
.stats{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:30px 0}
.stat-card{background:#fff;border-radius:16px;padding:20px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.stat-card .num{font-size:28px;font-weight:800;color:#1a1a2e}
.stat-card .label{font-size:12px;color:#9ca3af;margin-top:4px}
.stat-card.full{grid-column:1/-1}
.section{padding:30px 0}
.section .tag{display:flex;align-items:center;justify-content:center;gap:6px;color:#3b82f6;font-size:13px;font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:1px}
.section h2{text-align:center;font-size:26px;font-weight:800;margin-bottom:8px}
.section .desc{text-align:center;color:#9ca3af;font-size:14px;margin-bottom:24px}
.feature-list{display:flex;flex-direction:column;gap:14px}
.feature-card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.04);transition:transform .2s}
.feature-card:active{transform:scale(.98)}
.feature-card .icon{width:48px;height:48px;border-radius:12px;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-size:22px;color:#3b82f6;margin-bottom:12px}
.feature-card h3{font-size:17px;font-weight:700;margin-bottom:6px}
.feature-card p{font-size:13px;color:#6b7280;line-height:1.6}
.step-list{display:flex;flex-direction:column;gap:14px}
.step-card{background:#fff;border-radius:16px;padding:24px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.step-card .num{width:52px;height:52px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;margin:0 auto 12px;box-shadow:0 4px 16px rgba(59,130,246,.3)}
.step-card h3{font-size:17px;font-weight:700;margin-bottom:6px}
.step-card p{font-size:13px;color:#6b7280}
.price-list{display:flex;flex-direction:column;gap:14px}
.price-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.04);border:2px solid #e5e7eb;position:relative}
.price-card.hot{border-color:#3b82f6}
.price-card .hot-tag{position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#3b82f6;color:#fff;font-size:11px;padding:4px 14px;border-radius:10px;font-weight:600}
.price-card h3{text-align:center;font-size:18px;font-weight:700;margin-bottom:12px}
.price-card .price{text-align:center;margin-bottom:16px}
.price-card .price .amount{font-size:36px;font-weight:800;color:#3b82f6}
.price-card .price .unit{font-size:14px;color:#9ca3af}
.price-card ul{list-style:none;margin-bottom:18px}
.price-card li{display:flex;align-items:center;gap:8px;font-size:13px;color:#6b7280;padding:6px 0}
.price-card li i{color:#3b82f6;font-size:16px}
.price-card .btn{width:100%}
.faq-list{display:flex;flex-direction:column}
.faq-item{border-bottom:1px solid #f3f4f6}
.faq-item:last-child{border-bottom:none}
.faq-q{display:flex;align-items:center;justify-content:space-between;padding:16px 0;cursor:pointer;font-size:15px;font-weight:600;color:#1a1a2e}
.faq-q i{color:#9ca3af;transition:transform .2s;font-size:18px}
.faq-item.open .faq-q i{transform:rotate(90deg)}
.faq-a{max-height:0;overflow:hidden;transition:max-height .3s;font-size:13px;color:#6b7280;line-height:1.7}
.faq-item.open .faq-a{max-height:200px;padding-bottom:14px}
.help-cta{background:#fff;border-radius:16px;padding:24px;margin:20px 0;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.help-cta h3{font-size:18px;font-weight:700;margin-bottom:8px}
.help-cta p{font-size:13px;color:#6b7280;margin-bottom:16px}
.help-cta .btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
.footer{background:#1a1a2e;color:#9ca3af;text-align:center;padding:40px 20px;margin-top:30px}
.footer .flogo{display:flex;align-items:center;justify-content:center;gap:8px;font-size:18px;font-weight:700;color:#fff;margin-bottom:12px}
.footer .flogo i{color:#3b82f6;font-size:22px}
.footer p{font-size:13px;margin-bottom:6px}
.footer .icp{font-size:12px;color:#6b7280;margin-top:8px}
.footer .icp a{color:#6b7280}
.footer .copy{font-size:12px;color:#6b7280;margin-top:12px}
</style>
</head>
<body>
<div class="topbar">
<div class="logo"><i class="ri-links-line"></i><?= $siteName ?></div>
<div class="actions">
<a href="help.php"><i class="ri-question-line"></i></a>
<a href="login.php"><i class="ri-user-line"></i></a>
</div>
</div>

<div class="container">
<div class="hero">
<div class="badge"><i class="ri-send-plane-fill"></i><?= $siteDesc ?></div>
<h1>创建你的网站<br><span>从这里开始</span></h1>
<p>无需服务器运维，无需繁琐配置</p>
<p class="sub">上传文件、内容审核、一键上线</p>
<div class="btns">
<a href="register.php" class="btn btn-primary">立即免费注册</a>
<a href="login.php" class="btn btn-outline">登录控制台</a>
</div>
</div>

<div class="stats">
<div class="stat-card"><div class="num">200MB</div><div class="label">初始空间</div></div>
<div class="stat-card"><div class="num">秒级</div><div class="label">在线发布</div></div>
<div class="stat-card full"><div class="num">1:1</div><div class="label">邮件提醒服务</div></div>
</div>

<div class="section">
<div class="tag"><i class="ri-sparkling-line"></i>CORE FEATURES</div>
<h2>核心功能</h2>
<p class="desc">为你的建站之旅保驾护航</p>
<div class="feature-list">
<div class="feature-card"><div class="icon"><i class="ri-send-plane-fill"></i></div><h3>一键建站</h3><p>创建静态网站，审核通过即可上线，无需繁琐配置</p></div>
<div class="feature-card"><div class="icon"><i class="ri-folder-3-line"></i></div><h3>在线文件管理</h3><p>上传、重命名、移动、删除，浏览器里全搞定</p></div>
<div class="feature-card"><div class="icon"><i class="ri-shield-check-line"></i></div><h3>内容审核</h3><p>上传内容自动审核，保障网站合规运行</p></div>
<div class="feature-card"><div class="icon"><i class="ri-mail-line"></i></div><h3>邮箱安全提醒</h3><p>登录、异地登录、充值、封禁全程邮件通知</p></div>
<div class="feature-card"><div class="icon"><i class="ri-bank-card-line"></i></div><h3>易支付充值</h3><p>安全合规的支付通道，充值购买更放心</p></div>
<div class="feature-card"><div class="icon"><i class="ri-lock-line"></i></div><h3>封禁保护机制</h3><p>异常站点自动拦截，账号安全有保障</p></div>
</div>
</div>

<div class="section">
<div class="tag"><i class="ri-arrow-down-up-line"></i>HOW IT WORKS</div>
<h2>三步上线你的网站</h2>
<p class="desc">简单清晰，全程可视化</p>
<div class="step-list">
<div class="step-card"><div class="num">1</div><h3>注册账号</h3><p>国内邮箱一键注册，初始赠送 200MB 空间</p></div>
<div class="step-card"><div class="num">2</div><h3>上传文件</h3><p>在线文件管理，拖拽上传即建站</p></div>
<div class="step-card"><div class="num">3</div><h3>审核上线</h3><p>通过内容审核，网站即刻开放访问</p></div>
</div>
</div>

<div class="section">
<div class="tag"><i class="ri-shopping-bag-line"></i>PRICING</div>
<h2>选择你的套餐</h2>
<p class="desc">200MB 起步，空间可随时升级叠加</p>
<div class="price-list">
<?php foreach ($groups as $i => $g): ?>
<div class="price-card <?= $i == 1 ? 'hot' : '' ?>">
<?php if ($i == 1): ?><div class="hot-tag">推荐</div><?php endif; ?>
<h3><?= htmlspecialchars($g['name']) ?></h3>
<div class="price">
<span class="amount">¥<?= number_format($g['price'], 2) ?></span>
<span class="unit"><?= $g['duration'] > 0 ? '/ ' . $g['duration'] . '天' : '/ 永久' ?></span>
</div>
<ul>
<li><i class="ri-hard-drive-2-line"></i><?= htmlspecialchars($g['description'] ?: '标准存储空间') ?></li>
<li><i class="ri-global-line"></i><?= intval($g['project_limit']) ?> 个站点</li>
</ul>
<a href="register.php" class="btn btn-primary">立即购买</a>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="section">
<div class="tag"><i class="ri-question-line"></i>FAQ</div>
<h2>常见问题</h2>
<p class="desc">你可能想了解这些</p>
<div class="faq-list">
<div class="faq-item" onclick="this.classList.toggle('open')"><div class="faq-q">什么是<?= $siteName ?>？<i class="ri-arrow-right-s-line"></i></div><div class="faq-a"><?= $siteName ?>是一个<?= $siteDesc ?>，无需服务器运维，上传文件即可上线。</div></div>
<div class="faq-item" onclick="this.classList.toggle('open')"><div class="faq-q">初始空间是多少？<i class="ri-arrow-right-s-line"></i></div><div class="faq-a">注册即赠送 200MB 初始空间，可随时充值升级。</div></div>
<div class="faq-item" onclick="this.classList.toggle('open')"><div class="faq-q">网站需要审核吗？<i class="ri-arrow-right-s-line"></i></div><div class="faq-a">是的，上传文件后系统会进行内容审核，通过后即可开放访问。</div></div>
<div class="faq-item" onclick="this.classList.toggle('open')"><div class="faq-q">账号安全如何保障？<i class="ri-arrow-right-s-line"></i></div><div class="faq-a">登录、异地登录、充值、封禁全程邮件通知，异常站点自动拦截。</div></div>
<div class="faq-item" onclick="this.classList.toggle('open')"><div class="faq-q">支持哪些支付方式？<i class="ri-arrow-right-s-line"></i></div><div class="faq-a">支持易支付安全充值，实时到账，多种支付方式可选。</div></div>
</div>
</div>

<div class="help-cta">
<h3>需要更多帮助？</h3>
<p>仍无法解决问题时，可以查看帮助中心或联系客服</p>
<div class="btns">
<a href="help.php" class="btn btn-primary">帮助中心</a>
<a href="login.php" class="btn btn-outline">登录 / 注册</a>
</div>
</div>
</div>

<div class="footer">
<div class="flogo"><i class="ri-links-line"></i><?= $siteName ?></div>
<p>简单 · 安全 · 高效 —— <?= $siteDesc ?></p>
<?php if ($siteIcp): ?><div class="icp"><a href="https://beian.miit.gov.cn" target="_blank"><?= $siteIcp ?></a></div><?php endif; ?>
<div class="copy"><?= $siteFooter ?: '© 2026 ' . $siteName . ' · 保留所有权利' ?></div>
</div>
</body>
</html>
