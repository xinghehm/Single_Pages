<?php
require_once 'functions.php';

$step = $_GET['step'] ?? 'request';
$error = '';
$success = '';

$emailVerifyEnabled = isEmailVerifyEnabled();
$smsVerifyEnabled = isSmsVerifyEnabled();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'request') {
        $method = $_POST['method'] ?? 'email';
        
        if ($method === 'email' && $emailVerifyEnabled) {
            $email = trim($_POST['email'] ?? '');
            $code = trim($_POST['code'] ?? '');
            
            if (verifySmsCode($email, $code)) {
                $token = generateResetToken($email);
                if ($token) {
                    $resetLink = "http://{$_SERVER['HTTP_HOST']}/forgot_password.php?step=reset&token=$token";
                    $body = "点击链接重置密码：<a href='$resetLink'>$resetLink</a>";
                    sendMail($email, '重置密码 - ' . getConfig('site_name'), $body);
                    $success = '重置链接已发送至邮箱，请查收。';
                } else {
                    $error = '邮箱未注册';
                }
            } else {
                $error = '验证码错误或已过期';
            }
        } elseif ($method === 'phone' && $smsVerifyEnabled) {
            $phone = trim($_POST['phone'] ?? '');
            $code = trim($_POST['code'] ?? '');
            
            if (verifySmsCode($phone, $code)) {
                $pdo = getDB();
                $stmt = $pdo->prepare("SELECT id, email FROM users WHERE phone = ?");
                $stmt->execute([$phone]);
                $user = $stmt->fetch();
                if ($user) {
                    $token = generateResetToken($user['email']);
                    $resetLink = "http://{$_SERVER['HTTP_HOST']}/forgot_password.php?step=reset&token=$token";
                    $body = "点击链接重置密码：<a href='$resetLink'>$resetLink</a>";
                    sendMail($user['email'], '重置密码 - ' . getConfig('site_name'), $body);
                    $success = '重置链接已发送至注册邮箱，请查收。';
                } else {
                    $error = '该手机号未注册';
                }
            } else {
                $error = '验证码错误或已过期';
            }
        }
    } elseif ($step === 'reset') {
        $token = $_GET['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (strlen($password) < 6) {
            $error = '密码至少6位';
        } elseif ($password !== $confirm) {
            $error = '两次密码不一致';
        } else {
            $user = verifyResetToken($token);
            if ($user) {
                changePassword($user['id'], $password);
                clearResetToken($user['id']);
                $success = '密码已重置，请登录。';
                header('refresh:2;url=login.php');
            } else {
                $error = '链接无效或已过期';
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
    <title>找回密码 - <?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .tab-btn { padding: 8px 20px; border-radius: 30px; cursor: pointer; background: #e5e7eb; border: none; transition: all 0.3s; }
        .tab-btn.active { background: #1677ff; color: white; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full p-8">
        <h2 class="text-2xl font-bold mb-6">找回密码</h2>
        <?php if ($error): ?>
            <div class="error mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($step === 'request' && !$success): ?>
            <div class="flex gap-2 mb-4">
                <?php if ($emailVerifyEnabled): ?>
                <button type="button" id="tabEmail" class="tab-btn active">邮箱找回</button>
                <?php endif; ?>
                <?php if ($smsVerifyEnabled): ?>
                <button type="button" id="tabPhone" class="tab-btn">手机找回</button>
                <?php endif; ?>
            </div>
            
            <!-- 邮箱找回 -->
            <div id="emailPanel">
                <form method="post">
                    <input type="hidden" name="method" value="email">
                    <div class="flex gap-2 mb-3">
                        <input type="email" name="email" id="resetEmail" placeholder="注册邮箱" required class="flex-1 px-4 py-3 rounded-full bg-white/40 border border-gray-200 focus:outline-none focus:border-blue-500">
                        <button type="button" id="sendEmailCodeBtn" class="btn-secondary whitespace-nowrap px-4">获取验证码</button>
                    </div>
                    <input type="text" name="code" placeholder="邮箱验证码" required class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-4 focus:outline-none focus:border-blue-500">
                    <button type="submit" class="btn-primary w-full py-3">验证身份</button>
                </form>
            </div>
            
            <!-- 手机找回 -->
            <?php if ($smsVerifyEnabled): ?>
            <div id="phonePanel" style="display: none;">
                <form method="post">
                    <input type="hidden" name="method" value="phone">
                    <div class="flex gap-2 mb-3">
                        <input type="text" name="phone" id="resetPhone" placeholder="绑定手机号" required class="flex-1 px-4 py-3 rounded-full bg-white/40 border border-gray-200 focus:outline-none focus:border-blue-500">
                        <button type="button" id="sendSmsCodeBtn" class="btn-secondary whitespace-nowrap px-4">获取验证码</button>
                    </div>
                    <input type="text" name="code" placeholder="短信验证码" required class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-4 focus:outline-none focus:border-blue-500">
                    <button type="submit" class="btn-primary w-full py-3">验证身份</button>
                </form>
            </div>
            <?php endif; ?>
            
            <p class="mt-4 text-center text-sm"><a href="login.php" class="text-blue-600">返回登录</a></p>
        <?php elseif ($step === 'reset' && isset($_GET['token']) && !$success): ?>
            <form method="post">
                <input type="password" name="password" placeholder="新密码（至少6位）" required class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-3 focus:outline-none focus:border-blue-500">
                <input type="password" name="confirm_password" placeholder="确认新密码" required class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-4 focus:outline-none focus:border-blue-500">
                <button type="submit" class="btn-primary w-full py-3">重置密码</button>
                <p class="mt-4 text-center text-sm"><a href="login.php" class="text-blue-600">返回登录</a></p>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        // Tab切换
        const tabEmail = document.getElementById('tabEmail');
        const tabPhone = document.getElementById('tabPhone');
        const emailPanel = document.getElementById('emailPanel');
        const phonePanel = document.getElementById('phonePanel');
        
        <?php if ($emailVerifyEnabled && $smsVerifyEnabled): ?>
        tabEmail.addEventListener('click', function() {
            tabEmail.classList.add('active');
            tabPhone.classList.remove('active');
            emailPanel.style.display = 'block';
            phonePanel.style.display = 'none';
        });
        tabPhone.addEventListener('click', function() {
            tabPhone.classList.add('active');
            tabEmail.classList.remove('active');
            phonePanel.style.display = 'block';
            emailPanel.style.display = 'none';
        });
        <?php endif; ?>
        
        <?php if ($emailVerifyEnabled): ?>
        // 发送邮箱验证码
        let emailCountdown = 0;
        const sendEmailBtn = document.getElementById('sendEmailCodeBtn');
        const resetEmail = document.getElementById('resetEmail');
        
        sendEmailBtn.addEventListener('click', function() {
            if (emailCountdown > 0) return;
            const email = resetEmail.value.trim();
            if (!email) {
                alert('请先输入邮箱');
                return;
            }
            fetch('send_email_code.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'email=' + encodeURIComponent(email) + '&type=reset'
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
        
        <?php if ($smsVerifyEnabled): ?>
        // 发送短信验证码
        let smsCountdown = 0;
        const sendSmsBtn = document.getElementById('sendSmsCodeBtn');
        const resetPhone = document.getElementById('resetPhone');
        
        if (sendSmsBtn) {
            sendSmsBtn.addEventListener('click', function() {
                if (smsCountdown > 0) return;
                const phone = resetPhone.value.trim();
                if (!/^1[3-9]\d{9}$/.test(phone)) {
                    alert('请输入有效的手机号');
                    return;
                }
                fetch('send_sms.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'phone=' + encodeURIComponent(phone) + '&type=reset'
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
        }
        <?php endif; ?>
    </script>
</body>
</html>