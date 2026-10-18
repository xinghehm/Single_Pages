<?php
if (!defined('IN_SITE')) { require_once __DIR__ . '/../../functions.php'; $config = getConfig(); }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(['site_name'] ?? '单页工坊') ?> - <?= htmlspecialchars(['site_description'] ?? '静态网站托管平台') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=245">
<style>
.feature-card{background:#fff;border-radius:8px;padding:24px;transition:all .3s}
.feature-card:hover{transform:translateY(-4px)}
.step-number{width:48px;height:48px;background:#2563eb;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:bold;margin:0 auto 16px}
</style>
</head>
<body class="min-h-screen">
<nav class="glass-card mx-4 mt-4 px-6 py-3 flex justify-between items-center">
<div class="flex items-center gap-2">
<div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-file-code-line"></i></div>
<span class="font-bold text-lg"><?= htmlspecialchars($config['site_name'] ?? '单页工坊Pages') ?></span>
</div>
<div class="flex gap-4 items-center">
<a href="index.php" class="text-gray-600 hover:text-blue-600">首页</a>
<a href="help.php" class="text-gray-600 hover:text-blue-600">帮助</a>
<a href="tutorial.php" class="text-gray-600 hover:text-blue-600">教程</a>
<a href="login.php" class="btn-primary text-sm px-4 py-1">登录</a>
<a href="register.php" class="btn-secondary text-sm px-4 py-1">注册</a>
</div>
</nav>
<div class="max-w-6xl mx-auto px-4 py-16 text-center">
<h1 class="text-4xl md:text-5xl font-bold mb-4">静态页面托管，<span class="text-blue-600">就这么简单</span></h1>
<p class="text-gray-600 text-lg mb-8">写代码 → 保存 → 生成链接 → 发给别人</p>
<div class="flex gap-4 justify-center flex-wrap">
<a href="register.php" class="btn-primary px-8 py-3 text-lg">开始使用</a>
<a href="login.php" class="btn-secondary px-8 py-3 text-lg">登录控制台</a>
</div>
</div>
<div class="max-w-6xl mx-auto px-4 py-12">
<h2 class="text-2xl font-bold text-center mb-8">核心功能</h2>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
<div class="feature-card text-center"><i class="ri-flashlight-line text-4xl text-blue-500 mb-3"></i><h3 class="font-bold text-lg mb-2">零配置</h3><p class="text-gray-500 text-sm">注册即可使用</p></div>
<div class="feature-card text-center"><i class="ri-file-copy-line text-4xl text-blue-500 mb-3"></i><h3 class="font-bold text-lg mb-2">多文件</h3><p class="text-gray-500 text-sm">支持多HTML文件</p></div>
<div class="feature-card text-center"><i class="ri-share-line text-4xl text-blue-500 mb-3"></i><h3 class="font-bold text-lg mb-2">独立链接</h3><p class="text-gray-500 text-sm">专属访问链接</p></div>
</div>
</div>
<footer class="text-center py-8 text-gray-400 text-sm"><?= htmlspecialchars($config['site_footer'] ?? '© 2026 单页工坊Pages') ?></footer>
</body>
</html>