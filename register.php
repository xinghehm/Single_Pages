<?php
require_once 'functions.php';
if (isLoggedIn()) header('Location: users/dashboard.php');

$error = '';
$success = '';

// 检查是否启用验证码
$emailVerifyEnabled = isEmailVerifyEnabled();
$smsVerifyEnabled = isSmsVerifyEnabled();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $email_code = trim($_POST['email_code'] ?? '');
    $sms_code = trim($_POST['sms_code'] ?? '');
    
    // 验证用户名
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = '用户名只能包含字母数字下划线';
    }
    // 验证邮箱
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    }
    // 验证密码
    elseif (strlen($password) < 6) {
        $error = '密码至少6位';
    }
    elseif ($password !== $confirm) {
        $error = '两次密码不一致';
    }
    // 验证手机号（如果填写）
    elseif (!empty($phone) && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
        $error = '手机号格式不正确';
    }
    // 验证邮箱验证码（如果启用）
    elseif ($emailVerifyEnabled && !verifySmsCode($email, $email_code)) {
        $error = '邮箱验证码错误或已过期';
    }
    // 验证短信验证码（如果启用且填写了手机号）
    elseif ($smsVerifyEnabled && !empty($phone) && !verifySmsCode($phone, $sms_code)) {
        $error = '短信验证码错误或已过期';
    }
    else {
        if (register($username, $email, $password, $phone ?: null)) {
            $success = '注册成功！正在跳转到登录页...';
            header('refresh:2;url=login.php?registered=1');
        } else {
            $error = '用户名或邮箱已被注册';
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
    <title>注册 - <?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full p-8">
        <h2 class="text-2xl font-bold mb-6 text-center">注册新账号</h2>
        <?php if ($error): ?>
            <div class="error mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="post" id="registerForm">
            <!-- 用户名 -->
            <input type="text" name="username" placeholder="用户名 (字母数字下划线)" required class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-3">
            
            <!-- 邮箱 -->
            <div class="flex gap-2 mb-3">
                <input type="email" name="email" id="email" placeholder="邮箱" required class="flex-1 px-4 py-3 rounded-full bg-white/40 border border-gray-200">
                <?php if ($emailVerifyEnabled): ?>
                <button type="button" id="sendEmailCodeBtn" class="btn-secondary whitespace-nowrap px-4">获取验证码</button>
                <?php endif; ?>
            </div>
            <?php if ($emailVerifyEnabled): ?>
            <input type="text" name="email_code" placeholder="邮箱验证码" class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-3">
            <?php endif; ?>
            
            <!-- 密码 -->
            <input type="password" name="password" placeholder="密码 (至少6位)" required class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-3">
            <input type="password" name="confirm_password" placeholder="确认密码" required class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-3">
            
            <!-- 手机号 -->
            <div class="flex gap-2 mb-3">
                <input type="text" name="phone" id="phone" placeholder="手机号 (选填)" class="flex-1 px-4 py-3 rounded-full bg-white/40 border border-gray-200">
                <?php if ($smsVerifyEnabled): ?>
                <button type="button" id="sendSmsCodeBtn" class="btn-secondary whitespace-nowrap px-4" style="display: none;">获取验证码</button>
                <?php endif; ?>
            </div>
            <?php if ($smsVerifyEnabled): ?>
            <input type="text" name="sms_code" id="sms_code" placeholder="短信验证码" class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-4" style="display: none;">
            <?php endif; ?>
            
            <button type="submit" class="btn-primary w-full py-3">注册</button>
            <p class="mt-4 text-center text-sm">已有账号？<a href="login.php" class="text-blue-600">立即登录</a></p>
        </form>
    </div>
    
    <script>
        // 手机号输入时显示短信验证码
        const phoneInput = document.getElementById('phone');
        const smsCodeInput = document.getElementById('sms_code');
        const sendSmsBtn = document.getElementById('sendSmsCodeBtn');
        
        <?php if ($smsVerifyEnabled): ?>
        phoneInput.addEventListener('input', function() {
            const phone = this.value.trim();
            if (/^1[3-9]\d{9}$/.test(phone)) {
                sendSmsBtn.style.display = 'block';
                smsCodeInput.style.display = 'block';
            } else {
                sendSmsBtn.style.display = 'none';
                smsCodeInput.style.display = 'none';
            }
        });
        
        // 发送短信验证码
        let smsCountdown = 0;
        sendSmsBtn.addEventListener('click', function() {
            if (smsCountdown > 0) return;
            const phone = phoneInput.value.trim();
            if (!/^1[3-9]\d{9}$/.test(phone)) {
                alert('请输入有效的手机号');
                return;
            }
            fetch('send_sms.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'phone=' + encodeURIComponent(phone) + '&type=register'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('验证码已发送');
                    smsCountdown = 60;
                    sendSmsBtn.textContent = smsCountdown + '秒后重试';
                    const timer = setInterval(() => {
                        smsCountdown--;
                        if (smsCountdown <= 0) {
                            clearInterval(timer);
                            sendSmsBtn.textContent = '获取验证码';
                        } else {
                            sendSmsBtn.textContent = smsCountdown + '秒后重试';
                        }
                    }, 1000);
                } else {
                    alert(data.message);
                }
            })
            .catch(err => alert('发送失败，请重试'));
        });
        <?php endif; ?>
        
        <?php if ($emailVerifyEnabled): ?>
        // 发送邮箱验证码
        let emailCountdown = 0;
        const sendEmailBtn = document.getElementById('sendEmailCodeBtn');
        const emailInput = document.getElementById('email');
        
        sendEmailBtn.addEventListener('click', function() {
            if (emailCountdown > 0) return;
            const email = emailInput.value.trim();
            if (!email) {
                alert('请先输入邮箱');
                return;
            }
            fetch('send_email_code.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'email=' + encodeURIComponent(email) + '&type=register'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('验证码已发送到邮箱');
                    emailCountdown = 60;
                    sendEmailBtn.textContent = emailCountdown + '秒后重试';
                    const timer = setInterval(() => {
                        emailCountdown--;
                        if (emailCountdown <= 0) {
                            clearInterval(timer);
                            sendEmailBtn.textContent = '获取验证码';
                        } else {
                            sendEmailBtn.textContent = emailCountdown + '秒后重试';
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