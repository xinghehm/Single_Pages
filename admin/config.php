<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    
    if ($action === 'save') {
        // 基本配置
        updateConfig('site_name', $_POST['site_name']);
        updateConfig('site_logo', $_POST['site_logo']);
        updateConfig('site_footer', $_POST['site_footer']);
        updateConfig('stats_projects', $_POST['stats_projects']);
        updateConfig('stats_features', $_POST['stats_features']);
        updateConfig('tagline', $_POST['tagline']);
        updateConfig('tags', $_POST['tags']);
        
        // SMTP 配置
        updateConfig('smtp_host', $_POST['smtp_host']);
        updateConfig('smtp_port', $_POST['smtp_port']);
        updateConfig('smtp_user', $_POST['smtp_user']);
        if (!empty($_POST['smtp_pass'])) {
            updateConfig('smtp_pass', $_POST['smtp_pass']);
        }
        updateConfig('smtp_secure', $_POST['smtp_secure']);
        updateConfig('smtp_from_email', $_POST['smtp_from_email']);
        updateConfig('smtp_from_name', $_POST['smtp_from_name']);
        
        // 短信 API 配置
        updateConfig('sms_api_url', $_POST['sms_api_url']);
        updateConfig('sms_api_key', $_POST['sms_api_key']);
        updateConfig('sms_enabled', isset($_POST['sms_enabled']) ? '1' : '0');
        
        // 验证码开关配置
        updateConfig('email_verify_enabled', isset($_POST['email_verify_enabled']) ? '1' : '0');
        updateConfig('sms_verify_enabled', isset($_POST['sms_verify_enabled']) ? '1' : '0');
        updateConfig('force_bind_phone', isset($_POST['force_bind_phone']) ? '1' : '0');
        
        $success = '配置已保存';
    } elseif ($action === 'test_sms') {
        $testPhone = trim($_POST['test_phone'] ?? '');
        if (!preg_match('/^1[3-9]\d{9}$/', $testPhone)) {
            $sms_test_error = '请输入有效的手机号';
        } else {
            $testCode = generateSmsCode();
            $result = sendSmsCode($testPhone, $testCode);
            if ($result['success']) {
                $sms_test_success = "测试短信已发送！验证码：{$testCode}（5分钟内有效）";
            } else {
                $sms_test_error = "发送失败：" . ($result['message'] ?? '未知错误');
            }
        }
    }
}

$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站配置 - <?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="flex min-h-screen">
<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div>
        <div class="flex items-center gap-2 mb-8">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div>
            <div><div class="font-bold"><?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></div><div class="text-xs text-gray-500">Admin</div></div>
        </div>
        <nav class="space-y-2">
            <a href="index.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 仪表盘</a>
            <a href="users.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-user-line"></i> 用户管理</a>
            <a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 项目管理</a>
            <a href="config.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-settings-line"></i> 网站配置</a>
        </nav>
    </div>
    <div><a href="logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>
<main class="flex-1 p-6 overflow-auto">
    <div class="glass-card max-w-3xl mx-auto p-6">
        <h2 class="text-xl font-bold mb-4">网站配置</h2>
        <?php if (isset($success)): ?>
            <div class="success mb-4">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="action" value="save">
            
            <h3 class="font-medium mt-4 mb-2 text-lg border-l-4 border-blue-500 pl-3">🌐 基本设置</h3>
            <div class="space-y-3">
                <div><label class="block text-sm font-medium mb-1">网站名称</label><input type="text" name="site_name" value="<?= htmlspecialchars($config['site_name'] ?? '') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
                <div><label class="block text-sm font-medium mb-1">Logo 地址</label><input type="text" name="site_logo" value="<?= htmlspecialchars($config['site_logo'] ?? '') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
                <div><label class="block text-sm font-medium mb-1">页脚版权</label><input type="text" name="site_footer" value="<?= htmlspecialchars($config['site_footer'] ?? '') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
                <div><label class="block text-sm font-medium mb-1">成功托管统计</label><input type="text" name="stats_projects" value="<?= htmlspecialchars($config['stats_projects'] ?? '') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
                <div><label class="block text-sm font-medium mb-1">自研功能统计</label><input type="text" name="stats_features" value="<?= htmlspecialchars($config['stats_features'] ?? '') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
                <div><label class="block text-sm font-medium mb-1">标语</label><input type="text" name="tagline" value="<?= htmlspecialchars($config['tagline'] ?? '') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
                <div><label class="block text-sm font-medium mb-1">标签（竖线分隔）</label><input type="text" name="tags" value="<?= htmlspecialchars($config['tags'] ?? '') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
            </div>
            
            <h3 class="font-medium mt-6 mb-2 text-lg border-l-4 border-blue-500 pl-3">📧 SMTP 邮件配置</h3>
            <div class="space-y-3">
                <div><label class="block text-sm font-medium mb-1">SMTP 服务器</label><input type="text" name="smtp_host" value="<?= htmlspecialchars($config['smtp_host'] ?? '') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
                <div><label class="block text-sm font-medium mb-1">端口</label><input type="text" name="smtp_port" value="<?= htmlspecialchars($config['smtp_port'] ?? '465') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
                <div><label class="block text-sm font-medium mb-1">用户名</label><input type="text" name="smtp_user" value="<?= htmlspecialchars($config['smtp_user'] ?? '') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
                <div><label class="block text-sm font-medium mb-1">密码/授权码</label><input type="password" name="smtp_pass" placeholder="留空则不修改" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
                <div><label class="block text-sm font-medium mb-1">加密方式</label>
                    <select name="smtp_secure" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
                        <option value="ssl" <?= (($config['smtp_secure'] ?? '') == 'ssl') ? 'selected' : '' ?>>SSL</option>
                        <option value="tls" <?= (($config['smtp_secure'] ?? '') == 'tls') ? 'selected' : '' ?>>TLS</option>
                    </select>
                </div>
                <div><label class="block text-sm font-medium mb-1">发件人邮箱</label><input type="text" name="smtp_from_email" value="<?= htmlspecialchars($config['smtp_from_email'] ?? '') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
                <div><label class="block text-sm font-medium mb-1">发件人名称</label><input type="text" name="smtp_from_name" value="<?= htmlspecialchars($config['smtp_from_name'] ?? '') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
            </div>
            
            <h3 class="font-medium mt-6 mb-2 text-lg border-l-4 border-blue-500 pl-3">📱 短信验证码配置</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <label class="block text-sm font-medium">启用短信功能</label>
                    <input type="checkbox" name="sms_enabled" value="1" <?= (($config['sms_enabled'] ?? '1') == '1') ? 'checked' : '' ?> class="w-5 h-5">
                </div>
                <div><label class="block text-sm font-medium mb-1">短信 API 地址</label><input type="text" name="sms_api_url" value="<?= htmlspecialchars($config['sms_api_url'] ?? 'https://sms.losels.eu.org/api/send.php') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
                <div><label class="block text-sm font-medium mb-1">API Key</label><input type="text" name="sms_api_key" value="<?= htmlspecialchars($config['sms_api_key'] ?? '') ?>" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
            </div>
            <h3 class="font-medium mt-6 mb-2 text-lg border-l-4 border-blue-500 pl-3">📱 短信验证码配置</h3>
<div class="space-y-3">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm">
        <p class="text-blue-800 mb-1">短信平台</p>
        <p class="text-gray-600 text-xs">推荐使用简信 sms.losels.eu.org<a href="<?= htmlspecialchars($config['recommended_sms_platform'] ?? 'https://sms.losels.eu.org/') ?>" target="_blank" class="text-blue-600 hover:underline">本系统仅适配此短信平台</p>
        <p class="text-gray-500 text-xs mt-1">注册后获取 API Key，填写到下方即可使用短信验证码功能。</p>
    </div>
    <div class="flex items-center gap-3">
        <label class="block text-sm font-medium">启用短信功能</label>
        <input type="checkbox" name="sms_enabled" value="1" <?= (($config['sms_enabled'] ?? '0') == '1') ? 'checked' : '' ?> class="w-5 h-5">
    </div>
    <div><label class="block text-sm font-medium mb-1">短信 API 地址</label><input type="text" name="sms_api_url" value="<?= htmlspecialchars($config['sms_api_url'] ?? '') ?>" placeholder="https://sms.losels.eu.org/api/send.php" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
    <div><label class="block text-sm font-medium mb-1">API Key</label><input type="text" name="sms_api_key" value="<?= htmlspecialchars($config['sms_api_key'] ?? '') ?>" placeholder="注册后获取" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200"></div>
</div>
            <h3 class="font-medium mt-6 mb-2 text-lg border-l-4 border-blue-500 pl-3">⚙️ 验证码与安全设置</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <label class="block text-sm font-medium">启用邮箱验证码（注册/找回密码）</label>
                    <input type="checkbox" name="email_verify_enabled" value="1" <?= (($config['email_verify_enabled'] ?? '1') == '1') ? 'checked' : '' ?> class="w-5 h-5">
                </div>
                <div class="flex items-center gap-3">
                    <label class="block text-sm font-medium">启用短信验证码（注册/登录/找回密码）</label>
                    <input type="checkbox" name="sms_verify_enabled" value="1" <?= (($config['sms_verify_enabled'] ?? '1') == '1') ? 'checked' : '' ?> class="w-5 h-5">
                </div>
                <div class="flex items-center gap-3">
                    <label class="block text-sm font-medium text-red-600">强制绑定手机号（用户必须绑定手机号才能使用）</label>
                    <input type="checkbox" name="force_bind_phone" value="1" <?= (($config['force_bind_phone'] ?? '0') == '1') ? 'checked' : '' ?> class="w-5 h-5">
                </div>
            </div>
            
            <button type="submit" class="btn-primary w-full mt-6 py-3">💾 保存所有配置</button>
        </form>
        
        <hr class="my-6">
        <h3 class="font-medium mb-2 text-lg border-l-4 border-blue-500 pl-3">📱 测试短信发送</h3>
        <?php if (isset($sms_test_success)): ?>
            <div class="success mb-4">✅ <?= htmlspecialchars($sms_test_success) ?></div>
        <?php endif; ?>
        <?php if (isset($sms_test_error)): ?>
            <div class="error mb-4">❌ <?= htmlspecialchars($sms_test_error) ?></div>
        <?php endif; ?>
        <form method="post" class="flex flex-col sm:flex-row gap-3 items-end">
            <input type="hidden" name="action" value="test_sms">
            <div class="flex-1 w-full">
                <label class="block text-sm font-medium mb-1">测试手机号</label>
                <input type="text" name="test_phone" placeholder="请输入手机号" class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
            </div>
            <button type="submit" class="btn-primary px-6 py-2">发送测试验证码</button>
        </form>
    </div>
</main>
</body>
</html>