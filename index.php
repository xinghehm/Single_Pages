<?php
require_once 'functions.php';

// 获取项目ID和文件名
$proId = $_GET['pro'] ?? '';
$filename = $_GET['file'] ?? '';

// 如果没有pro参数，尝试从URL路径获取（支持 Nginx 重写）
if (empty($proId)) {
    $requestUri = $_SERVER['REQUEST_URI'];
    // 匹配 /项目ID/文件名.html 格式
    if (preg_match('/^\/([a-zA-Z0-9]+)\/([a-zA-Z0-9_\-]+)\.html$/', $requestUri, $matches)) {
        $proId = $matches[1];
        $filename = $matches[2] . '.html';
    }
    // 匹配 /项目ID 格式
    elseif (preg_match('/^\/([a-zA-Z0-9]+)$/', $requestUri, $matches)) {
        $proId = $matches[1];
        $filename = 'index.html';
    }
}

// 如果有项目ID但没有文件名，默认使用 index.html
if (!empty($proId) && empty($filename)) {
    $filename = 'index.html';
}

// 如果有项目ID，显示项目内容
if (!empty($proId)) {
    // 检查项目是否存在
    $project = getProjectByProId($proId);
    if (!$project) {
        http_response_code(404);
        die('项目不存在');
    }
    
    // 获取文件内容
    $content = getFileContent($proId, $filename);
    
    // 如果文件不存在，尝试查找 index.html
    if ($content === null && $filename !== 'index.html') {
        $content = getFileContent($proId, 'index.html');
    }
    
    if ($content === null) {
        http_response_code(404);
        die('文件不存在：' . $filename);
    }
    
    // 输出HTML
    header('Content-Type: text/html; charset=utf-8');
    echo $content;
    exit;
}

// 没有pro参数时显示首页
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['site_name'] ?? '单页工坊Pages') ?> - 静态页面托管平台</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .feature-card {
            background: rgba(255,255,255,0.5);
            border-radius: 20px;
            padding: 24px;
            transition: all 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            background: rgba(255,255,255,0.7);
        }
        .step-number {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #1677ff, #0958d9);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin: 0 auto 16px;
        }
        @media (prefers-color-scheme: dark) {
            .feature-card { background: rgba(30,35,48,0.6); }
            .feature-card:hover { background: rgba(30,35,48,0.8); }
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- 导航栏 -->
    <nav class="glass-card mx-4 mt-4 px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-file-code-line"></i></div>
            <span class="font-bold text-lg"><?= htmlspecialchars($config['site_name'] ?? '单页工坊Pages') ?></span>
        </div>
        <div class="flex gap-4">
            <a href="index.php" class="text-gray-600 hover:text-blue-600">首页</a>
            <a href="tutorial.php" class="text-gray-600 hover:text-blue-600">使用教程</a>
            <a href="login.php" class="btn-primary text-sm px-4 py-1">登录</a>
            <a href="register.php" class="btn-secondary text-sm px-4 py-1">注册</a>
        </div>
    </nav>

    <!-- Hero区域 -->
    <div class="max-w-6xl mx-auto px-4 py-16 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">静态页面托管，<span class="text-blue-600">就这么简单</span></h1>
        <p class="text-gray-600 text-lg mb-8">写代码 → 保存 → 生成链接 → 发给别人</p>
        <div class="flex gap-4 justify-center">
            <a href="register.php" class="btn-primary px-8 py-3 text-lg">开始使用</a>
            <a href="tutorial.php" class="btn-secondary px-8 py-3 text-lg">查看教程</a>
        </div>
    </div>

    <!-- 特色功能 -->
    <div class="max-w-6xl mx-auto px-4 py-12">
        <h2 class="text-2xl font-bold text-center mb-8">为什么选择我们？</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="feature-card text-center">
                <i class="ri-flashlight-line text-4xl text-blue-500 mb-3"></i>
                <h3 class="font-bold text-lg mb-2">零配置</h3>
                <p class="text-gray-500 text-sm">注册即可使用，无需任何环境配置，不用买服务器</p>
            </div>
            <div class="feature-card text-center">
                <i class="ri-file-copy-line text-4xl text-blue-500 mb-3"></i>
                <h3 class="font-bold text-lg mb-2">多文件支持</h3>
                <p class="text-gray-500 text-sm">一个项目支持多个HTML文件，像真正的网站一样</p>
            </div>
            <div class="feature-card text-center">
                <i class="ri-share-line text-4xl text-blue-500 mb-3"></i>
                <h3 class="font-bold text-lg mb-2">独立链接</h3>
                <p class="text-gray-500 text-sm">每个项目都有专属访问链接，方便分享给任何人</p>
            </div>
        </div>
    </div>

    <!-- 三步上手 -->
    <div class="max-w-6xl mx-auto px-4 py-12">
        <h2 class="text-2xl font-bold text-center mb-8">三步上手</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="step-number">1</div>
                <h3 class="font-bold mb-2">注册账号</h3>
                <p class="text-gray-500 text-sm">填写用户名、邮箱、密码，一分钟完成注册</p>
            </div>
            <div class="text-center">
                <div class="step-number">2</div>
                <h3 class="font-bold mb-2">新建项目</h3>
                <p class="text-gray-500 text-sm">点击新建项目，输入项目名称，开始写代码</p>
            </div>
            <div class="text-center">
                <div class="step-number">3</div>
                <h3 class="font-bold mb-2">分享链接</h3>
                <p class="text-gray-500 text-sm">保存代码，复制链接，发给别人就能看</p>
            </div>
        </div>
    </div>

    <!-- 数据统计 -->
    <div class="max-w-4xl mx-auto px-4 py-12">
        <div class="glass-card p-8 text-center">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <div class="text-3xl font-bold text-blue-600"><?= htmlspecialchars($config['stats_projects'] ?? '100+') ?></div>
                    <div class="text-gray-500 text-sm">托管项目</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-blue-600"><?= htmlspecialchars($config['stats_features'] ?? '10+') ?></div>
                    <div class="text-gray-500 text-sm">功能更新</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-blue-600">免费</div>
                    <div class="text-gray-500 text-sm">永久免费</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-blue-600">在线</div>
                    <div class="text-gray-500 text-sm">即时生效</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 底部 -->
    <footer class="text-center py-8 text-gray-400 text-sm">
        <?= htmlspecialchars($config['site_footer'] ?? '© 2026 单页工坊Pages - 静态页面托管平台') ?>
    </footer>
</body>
</html>