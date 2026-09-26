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
                $user = getUserByPhone($phone);
                if ($user) {
                    $token = generateResetToken($user['email']);
                    if ($token) {
                        $resetLink = "http://{$_SERVER['HTTP_HOST']}/forgot_password.php?step=reset&token=$token";
                        $smsResult = sendSmsCode($phone, 0, '您的密码重置链接：' . $resetLink);
                        $success = '重置链接已发送至手机，请查收。';
                    } else {
                        $error = '生成重置链接失败';
                    }
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
    <title>找回密码 — <?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></title>
    <link rel="stylesheet" href="style.css?v=245">
    <style>
        .tab-btn { padding: 6px 16px; border-radius: 6px; cursor: pointer; background: #f3f4f6; border: 1px solid #d1d5db; font-size: 13px; color: #4b5563; }
        .tab-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; }
    </style>
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <h2>找回密码</h2>
            <p class="auth-sub">验证身份后重置你的密码</p>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($step === 'request' && !$success): ?>
                <?php if ($emailVerifyEnabled && $smsVerifyEnabled): ?>
                <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                    <button type="button" id="tabEmail" class="tab-btn active">邮箱找回</button>
                    <button type="button" id="tabPhone" class="tab-btn">手机找回</button>
                </div>
                <?php endif; ?>

                <div id="emailPanel">
                    <form method="post">
                        <input type="hidden" name="method" value="email">
                        <div class="form-group">
                            <label>注册邮箱</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="email" name="email" id="resetEmail" required style="flex: 1;">
                                <button type="button" id="sendEmailCodeBtn" class="btn-secondary btn-sm" style="white-space: nowrap;">获取验证码</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>邮箱验证码</label>
                            <input type="text" name="code" required>
                        </div>
                        <button type="submit" class="btn-primary btn-block" style="padding: 10px;">验证身份</button>
                    </form>
                </div>

                <?php if ($smsVerifyEnabled): ?>
                <div id="phonePanel" style="display: none;">
                    <form method="post">
                        <input type="hidden" name="method" value="phone">
                        <div class="form-group">
                            <label>绑定手机号</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" name="phone" id="resetPhone" required style="flex: 1;">
                                <button type="button" id="sendSmsCodeBtn" class="btn-secondary btn-sm" style="white-space: nowrap;">获取验证码</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>短信验证码</label>
                            <input type="text" name="code" required>
                        </div>
                        <button type="submit" class="btn-primary btn-block" style="padding: 10px;">验证身份</button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="auth-footer"><a href="login.php">返回登录</a></div>

            <?php elseif ($step === 'reset' && isset($_GET['token']) && !$success): ?>
                <form method="post">
                    <div class="form-group">
                        <label>新密码</label>
                        <input type="password" name="password" placeholder="至少6位" required>
                    </div>
                    <div class="form-group">
                        <label>确认新密码</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn-primary btn-block" style="padding: 10px;">重置密码</button>
                </form>
                <div class="auth-footer"><a href="login.php">返回登录</a></div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    var tabEmail = document.getElementById('tabEmail');
    var tabPhone = document.getElementById('tabPhone');
    if (tabEmail && tabPhone) {
        tabEmail.addEventListener('click', function(){
            tabEmail.classList.add('active'); tabPhone.classList.remove('active');
            document.getElementById('emailPanel').style.display = 'block';
            document.getElementById('phonePanel').style.display = 'none';
        });
        tabPhone.addEventListener('click', function(){
            tabPhone.classList.add('active'); tabEmail.classList.remove('active');
            document.getElementById('phonePanel').style.display = 'block';
            document.getElementById('emailPanel').style.display = 'none';
        });
    }

    function startCountdown(btn, seconds) {
        var n = seconds;
        btn.textContent = n + 's';
        btn.disabled = true;
        var t = setInterval(function(){
            n--;
            if (n <= 0) { clearInterval(t); btn.textContent = '获取验证码'; btn.disabled = false; }
            else btn.textContent = n + 's';
        }, 1000);
    }

    var emailBtn = document.getElementById('sendEmailCodeBtn');
    if (emailBtn) {
        emailBtn.addEventListener('click', function(){
            var email = document.getElementById('resetEmail').value.trim();
            if (!email) { alert('请先输入邮箱'); return; }
            fetch('send_email_code.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'email='+encodeURIComponent(email)+'&type=reset'})
            .then(function(r){return r.json();}).then(function(data){
                if (data.success) { alert('验证码已发送到邮箱'); startCountdown(emailBtn, 60); }
                else alert(data.message);
            });
        });
    }

    var smsBtn = document.getElementById('sendSmsCodeBtn');
    if (smsBtn) {
        smsBtn.addEventListener('click', function(){
            var phone = document.getElementById('resetPhone').value.trim();
            if (!/^1[3-9]\d{9}$/.test(phone)) { alert('请输入有效的手机号'); return; }
            fetch('send_sms.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'phone='+encodeURIComponent(phone)+'&type=reset'})
            .then(function(r){return r.json();}).then(function(data){
                if (data.success) { alert('验证码已发送'); startCountdown(smsBtn, 60); }
                else alert(data.message);
            });
        });
    }
    </script>
</div></body>
</html>
