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
        
        // 易支付配置
        updateConfig('yipay_url', $_POST['yipay_url'] ?? '');
        updateConfig('yipay_pid', $_POST['yipay_pid'] ?? '');
        updateConfig('yipay_key', $_POST['yipay_key'] ?? '');
        updateConfig('yipay_pay_methods', implode(',', $_POST['pay_methods'] ?? []));
        
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
<style>
/* 移动端顶部导航 - 内联防止CSS丢失 */
.mobile-topbar {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(0,0,0,0.06);
    z-index: 999;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
}
.mobile-topbar .mobile-logo {
    display: flex; align-items: center; gap: 8px;
    font-weight: 600; font-size: 15px;
}
.mobile-topbar .logo-icon {
    width: 32px; height: 32px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 18px;
}
.hamburger-btn {
    width: 40px; height: 40px;
    background: rgba(0,0,0,0.05);
    border: none; border-radius: 10px;
    cursor: pointer;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 5px;
    transition: all 0.3s ease;
}
.hamburger-btn span {
    display: block;
    width: 20px; height: 2px;
    background: #333;
    border-radius: 2px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hamburger-btn.active span:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}
.hamburger-btn.active span:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
}
.hamburger-btn.active span:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(2px);
    z-index: 998;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.sidebar-overlay.show {
    display: block;
    opacity: 1;
}
@media (max-width: 768px) {
    .mobile-topbar { display: flex; }
    .sidebar {
        position: fixed !important;
        left: -280px !important;
        top: 0; bottom: 0;
        z-index: 1000 !important;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        width: 280px !important;
        box-shadow: 4px 0 24px rgba(0,0,0,0.12);
        padding-top: 70px !important;
    }
    .sidebar.show { left: 0 !important; }
    main {
        padding: 16px !important;
        padding-top: 72px !important;
        min-width: 0 !important;
        overflow-x: hidden !important;
    }
    body { overflow-x: hidden !important; }
    .overflow-x-auto {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        width: 100% !important;
    }
    .overflow-x-auto table {
        min-width: 600px !important;
        width: auto !important;
    }
}
</style>
</head>
<body class="flex min-h-screen">
<div class="mobile-topbar">
    <div class="mobile-logo">
        <div class="logo-icon"><i class="ri-cloud-line"></i></div>
        <span><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></span>
    </div>
    <button class="hamburger-btn" onclick="toggleSidebar(this)" aria-label="菜单">
        <span></span><span></span><span></span>
    </button>
</div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>
function toggleSidebar(btn) {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
    if (btn) btn.classList.toggle('active');
    else document.querySelector('.hamburger-btn').classList.toggle('active');
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sidebar a').forEach(function(a) {
        a.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('show');
            document.querySelector('.sidebar-overlay').classList.remove('show');
            document.querySelector('.hamburger-btn').classList.remove('active');
        });
    });
});
</script>

<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div>
        <div class="flex items-center gap-2 mb-8">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div>
            <div><div class="font-bold"><?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></div><div class="text-xs text-gray-500">Admin</div></div>
        </div>
        <nav class="space-y-2">
            <a href="index.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 仪表盘</a>
            <a href="users.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-user-line"></i> 用户管理</a>
            <a href="groups.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-group-line"></i> 用户组管理</a>
            <a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 项目管理</a>
            <a href="config.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-settings-line"></i> 网站配置</a>
        </nav>
    </div>
    <div><a href="logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>
<main class="flex-1 p-6 overflow-auto">
    <div class="glass-card max-w-3xl mx-auto p-6" style="overflow: hidden;">
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
            <h3 class="font-medium mt-6 mb-2 text-lg border-l-4 border-blue-500 pl-3">💳 易支付配置</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">易支付网站URL</label>
                    <input type="text" name="yipay_url" value="<?= htmlspecialchars($config['yipay_url'] ?? '') ?>" placeholder="https://pay.liohg.top/" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">商户号 (PID)</label>
                    <input type="text" name="yipay_pid" value="<?= htmlspecialchars($config['yipay_pid'] ?? '') ?>" placeholder="1001" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">商户密钥 (Key)</label>
                    <input type="text" name="yipay_key" value="<?= htmlspecialchars($config['yipay_key'] ?? '') ?>" placeholder="商户密钥" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">启用支付方式</label>
                    <?php $enabledMethods = explode(',', $config['yipay_pay_methods'] ?? 'alipay,wxpay,qqpay'); ?>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pay_methods[]" value="alipay" <?= in_array('alipay', $enabledMethods) ? 'checked' : '' ?> class="w-4 h-4">
                            <i class="ri-alipay-line text-blue-500"></i> 支付宝
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pay_methods[]" value="wxpay" <?= in_array('wxpay', $enabledMethods) ? 'checked' : '' ?> class="w-4 h-4">
                            <i class="ri-wechat-pay-line text-green-500"></i> 微信支付
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pay_methods[]" value="qqpay" <?= in_array('qqpay', $enabledMethods) ? 'checked' : '' ?> class="w-4 h-4">
                            <i class="ri-qq-line text-blue-400"></i> QQ钱包
                        </label>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">取消勾选后，用户端将不显示该支付方式</p>
                </div>
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