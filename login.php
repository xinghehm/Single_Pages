<?php
require_once 'functions.php';
if (isLoggedIn()) header('Location: users/dashboard.php');

$error = '';
$smsVerifyEnabled = isSmsVerifyEnabled();

// 处理登录
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginType = $_POST['login_type'] ?? 'password';
    
    if ($loginType === 'phone' && $smsVerifyEnabled) {
        $phone = $_POST['phone'] ?? '';
        $sms_code = $_POST['sms_code'] ?? '';
        if (verifySmsCode($phone, $sms_code)) {
            // 通过手机号查找用户
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                header('Location: users/dashboard.php');
                exit;
            } else {
                $error = '该手机号未注册';
            }
        } else {
            $error = '手机号或验证码错误';
        }
    } else {
        $login = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';
        if (login($login, $password)) {
            header('Location: users/dashboard.php');
            exit;
        } else {
            $error = '用户名/邮箱或密码错误';
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
    <title>登录 - <?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0f5ff 0%, #e0e9f7 100%);
            min-height: 100vh;
            display: flex;
        }
        .left-side { flex: 1; padding: 60px 80px; display: flex; flex-direction: column; justify-content: center; color: #112244; }
        .right-side { width: 480px; display: flex; align-items: center; padding: 40px; }
        .login-card { width: 100%; background: rgba(255,255,255,0.85); backdrop-filter: blur(30px); border-radius: 24px; padding: 48px 40px; border: 1px solid rgba(255,255,255,0.3); }
        .tab-btn { padding: 8px 16px; border-radius: 30px; cursor: pointer; background: transparent; border: none; }
        .tab-btn.active { background: #1677ff; color: white; }
        @media (max-width: 900px) { body { flex-direction: column; } .left-side { padding: 40px 25px; text-align: center; } .right-side { width: 100%; padding: 20px; } .login-card { padding: 32px 24px; } }
        @media (prefers-color-scheme: dark) { body { background: linear-gradient(135deg, #1a1f2e 0%, #0f1419 100%); } .login-card { background: rgba(30,35,48,0.85); border-color: rgba(255,255,255,0.1); color: #e6f0ff; } .left-side { color: #e6f0ff; } }
    </style>
</head>
<body>
<div class="left-side">
    <img src="<?= htmlspecialchars($config['site_logo'] ?? 'https://www.xhehm.com/assets/img/LOGO-Pages.png') ?>" style="height: 180px; margin-bottom: 33px;">
    <h1 class="text-3xl md:text-4xl font-bold mb-4">轻松托管,<?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?><span class="text-blue-600">全程赋能</span></h1>
    <div class="flex gap-8 mt-6"><div><div class="text-3xl font-bold text-blue-600"><?= htmlspecialchars($config['stats_projects'] ?? '100+') ?></div><div class="text-sm">成功托管网站</div></div><div><div class="text-3xl font-bold text-blue-600"><?= htmlspecialchars($config['stats_features'] ?? '10+') ?></div><div class="text-sm">自研优化功能</div></div></div>
    <div class="flex flex-wrap gap-3 mt-6"><?php foreach (explode('|', $config['tags'] ?? '') as $tag): ?><span class="bg-white/20 px-4 py-1 rounded-full text-sm"><?= htmlspecialchars($tag) ?></span><?php endforeach; ?></div>
</div>
<div class="right-side">
    <div class="login-card">
        <h2 class="text-2xl font-bold mb-6">账号登录</h2>
        <?php if ($error): ?><div class="error mb-4"><?= $error ?></div><?php endif; ?>
        
        <div class="flex gap-2 mb-4">
            <button type="button" id="tabPassword" class="tab-btn active">密码登录</button>
            <?php if ($smsVerifyEnabled): ?>
            <button type="button" id="tabPhone" class="tab-btn">验证码登录</button>
            <?php endif; ?>
        </div>
        
        <!-- 密码登录 -->
        <div id="passwordPanel">
            <form method="post">
                <input type="hidden" name="login_type" value="password">
                <input type="text" name="login" placeholder="用户名 / 邮箱" required class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-4">
                <input type="password" name="password" placeholder="密码" required class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-6">
                <button type="submit" class="btn-primary w-full py-3">登录</button>
            </form>
        </div>
        
        <!-- 验证码登录 -->
        <?php if ($smsVerifyEnabled): ?>
        <div id="phonePanel" style="display: none;">
            <form method="post">
                <input type="hidden" name="login_type" value="phone">
                <input type="text" name="phone" id="loginPhone" placeholder="手机号" required class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-3">
                <div class="flex gap-2 mb-6">
                    <input type="text" name="sms_code" placeholder="短信验证码" required class="flex-1 px-4 py-3 rounded-full bg-white/40 border border-gray-200">
                    <button type="button" id="loginSendSmsBtn" class="btn-secondary whitespace-nowrap px-4">获取验证码</button>
                </div>
                <button type="submit" class="btn-primary w-full py-3">登录</button>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="mt-6 text-center space-y-2">
            <a href="forgot_password.php" class="text-blue-600 block text-sm">忘记密码？</a>
            <a href="register.php" class="text-gray-600 dark:text-gray-400 text-sm">注册新账号</a>
        </div>
        <div class="text-center text-xs text-gray-500 mt-8"><?= htmlspecialchars($config['site_footer'] ?? '') ?></div>
    </div>
</div>

<script>
    // Tab切换
    const tabPassword = document.getElementById('tabPassword');
    const tabPhone = document.getElementById('tabPhone');
    const passwordPanel = document.getElementById('passwordPanel');
    const phonePanel = document.getElementById('phonePanel');
    
    if (tabPassword && tabPhone) {
        tabPassword.addEventListener('click', function() {
            tabPassword.classList.add('active');
            tabPhone.classList.remove('active');
            passwordPanel.style.display = 'block';
            phonePanel.style.display = 'none';
        });
        tabPhone.addEventListener('click', function() {
            tabPhone.classList.add('active');
            tabPassword.classList.remove('active');
            phonePanel.style.display = 'block';
            passwordPanel.style.display = 'none';
        });
    }
    
    <?php if ($smsVerifyEnabled): ?>
    // 登录页短信验证码发送
    let loginCountdown = 0;
    const loginSendBtn = document.getElementById('loginSendSmsBtn');
    const loginPhoneInput = document.getElementById('loginPhone');
    
    loginSendBtn.addEventListener('click', function() {
        if (loginCountdown > 0) return;
        const phone = loginPhoneInput.value.trim();
        if (!/^1[3-9]\d{9}$/.test(phone)) {
            alert('请输入有效的手机号');
            return;
        }
        fetch('send_sms.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'phone=' + encodeURIComponent(phone) + '&type=login'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('验证码已发送');
                loginCountdown = 60;
                loginSendBtn.textContent = loginCountdown + '秒后重试';
                const timer = setInterval(() => {
                    loginCountdown--;
                    if (loginCountdown <= 0) {
                        clearInterval(timer);
                        loginSendBtn.textContent = '获取验证码';
                    } else {
                        loginSendBtn.textContent = loginCountdown + '秒后重试';
                    }
                }, 1000);
            } else {
                alert(data.message);
            }
        })
        .catch(err => alert('发送失败，请重试'));
    });
    <?php endif; ?>
</script>
</body>
</html>