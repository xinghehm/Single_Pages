<?php require_once 'functions.php';
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>使用教程 - <?= htmlspecialchars($config['site_name'] ?? '单页工坊Pages') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .toc-card {
            background: rgba(255,255,255,0.5);
            border-radius: 16px;
            padding: 20px;
            position: sticky;
            top: 20px;
        }
        .toc-link {
            display: block;
            padding: 8px 12px;
            color: #4a5568;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .toc-link:hover {
            border-left-color: #1677ff;
            background: rgba(22,119,255,0.1);
        }
        .section-card {
            background: rgba(255,255,255,0.5);
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 24px;
        }
        .code-block {
            background: #1e1e2e;
            color: #e0e0e0;
            padding: 16px;
            border-radius: 12px;
            font-family: monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 16px 0;
        }
        @media (prefers-color-scheme: dark) {
            .toc-card, .section-card { background: rgba(30,35,48,0.6); }
            .toc-link { color: #cbd5e0; }
        }
        @media (max-width: 768px) {
            .sidebar-toc { display: none; }
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

    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- 侧边目录 -->
            <div class="md:w-64 sidebar-toc">
                <div class="toc-card">
                    <h3 class="font-bold mb-3">目录</h3>
                    <a href="#step1" class="toc-link">1. 注册账号</a>
                    <a href="#step2" class="toc-link">2. 新建项目</a>
                    <a href="#step3" class="toc-link">3. 编写代码</a>
                    <a href="#step4" class="toc-link">4. 管理文件</a>
                    <a href="#step5" class="toc-link">5. 分享链接</a>
                    <a href="#faq" class="toc-link">6. 常见问题</a>
                </div>
            </div>

            <!-- 主要内容 -->
            <div class="flex-1">
                <h1 class="text-3xl font-bold mb-2">使用教程</h1>
                <p class="text-gray-500 mb-8">从零开始，学会使用单页工坊Pages托管你的静态页面</p>

                <!-- 步骤1 -->
                <div id="step1" class="section-card">
                    <h2 class="text-xl font-bold mb-4">1. 注册账号</h2>
                    <p class="mb-3">访问 <a href="register.php" class="text-blue-600">注册页面</a>，填写以下信息：</p>
                    <ul class="list-disc list-inside space-y-2 mb-4 text-gray-700">
                        <li>用户名（字母、数字、下划线）</li>
                        <li>邮箱地址（用于找回密码）</li>
                        <li>密码（至少6位）</li>
                        <li>手机号（可选，用于找回密码）</li>
                    </ul>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm">
                        <span class="font-medium">💡 提示：</span>注册后会自动登录，跳转到控制台页面。
                    </div>
                </div>

                <!-- 步骤2 -->
                <div id="step2" class="section-card">
                    <h2 class="text-xl font-bold mb-4">2. 新建项目</h2>
                    <p class="mb-3">登录后，在控制台点击「新建项目」按钮：</p>
                    <ul class="list-disc list-inside space-y-2 mb-4 text-gray-700">
                        <li>输入项目名称（只能使用英文和数字）</li>
                        <li>点击确定，系统会自动创建项目</li>
                        <li>创建成功后自动进入项目详情页</li>
                    </ul>
                    <div class="code-block">
                        # 项目名称示例<br>
                        myblog<br>
                        demo2024<br>
                        portfolio
                    </div>
                </div>

                <!-- 步骤3 -->
                <div id="step3" class="section-card">
                    <h2 class="text-xl font-bold mb-4">3. 编写代码</h2>
                    <p class="mb-3">进入项目详情页后：</p>
                    <ul class="list-disc list-inside space-y-2 mb-4 text-gray-700">
                        <li>点击「编辑」按钮，进入代码编辑器</li>
                        <li>在编辑器中编写或粘贴你的HTML代码</li>
                        <li>点击「保存」，代码立即生效</li>
                    </ul>
                    <div class="code-block">
                        &lt;!DOCTYPE html&gt;<br>
                        &lt;html&gt;<br>
                        &nbsp;&nbsp;&lt;head&gt;<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&lt;title&gt;我的网站&lt;/title&gt;<br>
                        &nbsp;&nbsp;&lt;/head&gt;<br>
                        &nbsp;&nbsp;&lt;body&gt;<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&lt;h1&gt;Hello World&lt;/h1&gt;<br>
                        &nbsp;&nbsp;&lt;/body&gt;<br>
                        &lt;/html&gt;
                    </div>
                </div>

                <!-- 步骤4 -->
                <div id="step4" class="section-card">
                    <h2 class="text-xl font-bold mb-4">4. 管理文件</h2>
                    <p class="mb-3">一个项目可以包含多个HTML文件：</p>
                    <ul class="list-disc list-inside space-y-2 mb-4 text-gray-700">
                        <li><span class="font-medium">新建文件：</span>点击「新建文件」，输入文件名（如 about.html）</li>
                        <li><span class="font-medium">编辑文件：</span>点击文件旁的「编辑」按钮</li>
                        <li><span class="font-medium">设为首页：</span>点击文件旁的「设为首页」，该文件成为项目的默认页面</li>
                        <li><span class="font-medium">删除文件：</span>点击文件旁的「删除」按钮（首页不能删除）</li>
                    </ul>
                </div>

                <!-- 步骤5 -->
                <div id="step5" class="section-card">
                    <h2 class="text-xl font-bold mb-4">5. 分享链接</h2>
                    <p class="mb-3">项目创建后，系统会自动生成访问链接：</p>
                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-3 mb-4">
                        <p class="font-mono text-sm break-all">https://<?= $_SERVER['HTTP_HOST'] ?>/项目ID</p>
                    </div>
                    <p class="mb-2">多文件项目的访问方式：</p>
                    <ul class="list-disc list-inside space-y-2 text-gray-700">
                        <li>首页：<code class="bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded">https://域名/项目ID</code></li>
                        <li>其他页面：<code class="bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded">https://域名/项目ID/about.html</code></li>
                    </ul>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 mt-4 text-sm">
                        <span class="font-medium">✅ 示例：</span>假设项目ID是 a1b2c3d4，那么访问链接就是<br>
                        https://<?= $_SERVER['HTTP_HOST'] ?>/a1b2c3d4
                    </div>
                </div>

                <!-- 常见问题 -->
                <div id="faq" class="section-card">
                    <h2 class="text-xl font-bold mb-4">6. 常见问题</h2>
                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold mb-1">Q: 能放图片和CSS吗？</h3>
                            <p class="text-gray-600 text-sm">目前只支持HTML文件。图片和CSS建议使用CDN外链。</p>
                            <p class="text-gray-600 text-sm">这里推荐网盘/图床 :<a href="https://img.6qu.cc">K-Valut（img.6qu.cc）</a></p>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Q: 有文件大小限制吗？</h3>
                            <p class="text-gray-600 text-sm">单个文件不超过2MB，总空间500MB（可联系管理员扩容）。</p>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Q: 项目可以删除吗？</h3>
                            <p class="text-gray-600 text-sm">可以。在项目列表页点击「删除」即可，删除后不可恢复。</p>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Q: 忘记密码怎么办？</h3>
                            <p class="text-gray-600 text-sm">点击登录页的「忘记密码」，通过绑定邮箱或手机号重置。</p>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-1">Q: 能绑定自己的域名吗？</h3>
                            <p class="text-gray-600 text-sm">目前不支持，后续会考虑增加此功能。</p>
                        </div>
                    </div>
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