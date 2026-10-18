<?php
require_once 'functions.php';
if (isLoggedIn()) header('Location: users/dashboard.php');

$error = '';
$success = '';
$smsVerifyEnabled = isSmsVerifyEnabled();
$emailVerifyEnabled = ($config['email_verify_enabled'] ?? '1') === '1';
$passwordLoginEnabled = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginType = $_POST['login_type'] ?? 'password';

    // 邮箱验证码登录
    if ($loginType === 'email') {
        $email = trim($_POST['email'] ?? '');
        $code = trim($_POST['email_code'] ?? '');
        if (function_exists('verifyEmailCode') && verifyEmailCode($email, $code)) {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                checkUserGroupExpire($user['id']);
                addLoginLog($user['id'], $email, true);
                header('Location: users/dashboard.php');
                exit;
            } else {
                $error = '该邮箱未注册';
            }
        } else {
            // 如果没有verifyEmailCode函数，降级为提示
            $error = '邮箱验证码功能暂不可用，请使用账号密码登录';
        }
    }
    // 手机号验证码登录
    elseif ($loginType === 'phone' && $smsVerifyEnabled) {
        $phone = $_POST['phone'] ?? '';
        $sms_code = $_POST['sms_code'] ?? '';
        if (verifySmsCode($phone, $sms_code)) {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                checkUserGroupExpire($user['id']);
                addLoginLog($user['id'], $phone, true);
                clearLoginAttempts($phone);
                header('Location: users/dashboard.php');
                exit;
            } else {
                $error = '该手机号未注册';
            }
        } else {
            $error = '手机号或验证码错误';
        }
    }
    // 账号密码登录
    else {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        if (isLoginLocked($login)) {
            $remaining = getLoginLockRemaining($login);
            $error = '登录失败次数过多，请 ' . ceil($remaining / 60) . ' 分钟后再试';
        } elseif (login($login, $password)) {
            $u = getCurrentUser();
            checkUserGroupExpire($u['id']);
            addLoginLog($u['id'], $login, true);
            clearLoginAttempts($login);
            header('Location: users/dashboard.php');
            exit;
        } else {
            $error = '用户名/邮箱或密码错误';
            recordLoginAttempt($login);
            addLoginLog(null, $login, false, '密码错误');
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
<title>登录 - <?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(180deg,#dbeafe 0%,#e8f2fe 40%,#f0f6ff 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.login-wrap{width:100%;max-width:420px}
.back-btn{width:44px;height:44px;background:rgba(255,255,255,.7);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#374151;margin-bottom:16px;cursor:pointer;border:none;backdrop-filter:blur(10px)}
.login-card{background:#fff;border-radius:24px;padding:36px 28px;box-shadow:0 20px 60px rgba(59,130,246,.12)}
.login-card h1{font-size:28px;font-weight:800;text-align:center;margin-bottom:6px;color:#1a1a2e}
.login-card .subtitle{text-align:center;font-size:13px;color:#9ca3af;margin-bottom:28px}
.error-msg{background:#fef2f2;color:#dc2626;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;text-align:center}
.success-msg{background:#f0fdf4;color:#16a34a;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;text-align:center}
.tabs{display:flex;background:#f3f4f6;border-radius:12px;padding:4px;margin-bottom:24px}
.tab{flex:1;padding:10px;text-align:center;font-size:14px;font-weight:600;color:#6b7280;border-radius:9px;cursor:pointer;transition:all .2s;border:none;background:transparent}
.tab.active{background:#fff;color:#3b82f6;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
.input-wrap{position:relative}
.input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:16px}
.input-wrap input{width:100%;padding:13px 14px 13px 40px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;outline:none;transition:all .2s;background:#fafafa}
.input-wrap input:focus{border-color:#3b82f6;background:#fff;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.code-row{display:flex;gap:10px}
.code-row .input-wrap{flex:1}
.code-btn{padding:0 16px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:13px;font-weight:600;color:#3b82f6;background:#fff;cursor:pointer;white-space:nowrap;transition:all .2s;min-width:110px}
.code-btn:hover{border-color:#3b82f6;background:#eff6ff}
.code-btn:disabled{color:#9ca3af;border-color:#e5e7eb;cursor:not-allowed;background:#f9fafb}
.login-btn{width:100%;padding:14px;background:#3b82f6;color:#fff;border:none;border-radius:12px;font-size:16px;font-weight:700;cursor:pointer;transition:all .2s;margin-top:8px}
.login-btn:hover{background:#2563eb}
.login-btn:active{transform:scale(.98)}
.agreement{display:flex;align-items:flex-start;gap:8px;margin-top:16px;font-size:12px;color:#6b7280;line-height:1.5}
.agreement input{margin-top:2px;accent-color:#3b82f6}
.agreement a{color:#3b82f6;text-decoration:none}
.links{display:flex;justify-content:space-between;margin-top:20px;font-size:13px}
.links a{color:#6b7280;text-decoration:none}
.links a:last-child{color:#3b82f6;font-weight:600}
.tab-panel{display:none}
.tab-panel.active{display:block}
</style>
</head>
<body>
<div class="login-wrap">
<a href="index.php" class="back-btn"><i class="ri-arrow-left-line"></i></a>
<div class="login-card">
<h1>欢迎回来</h1>
<p class="subtitle"><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?> 控制台</p>
<?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="success-msg"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="tabs">
<?php $ft=true; ?>
<?php if($emailVerifyEnabled): ?><button type="button" class="tab <?=$ft?'active':''?>" data-tab="email"><?php $ft=false; ?>邮箱验证码</button><?php endif; ?>
<?php if($smsVerifyEnabled): ?><button type="button" class="tab <?=$ft?'active':''?>" data-tab="phone"><?php $ft=false; ?>手机验证码</button><?php endif; ?>
<button type="button" class="tab <?=$ft?'active':''?>" data-tab="password"><?php $ft=false; ?>账号密码</button>
</div>

<!-- 邮箱验证码登录 -->
<?php if($emailVerifyEnabled): ?>
<div class="tab-panel" id="panel-email">
<form method="post" id="form-email">
<input type="hidden" name="login_type" value="email">
<div class="form-group">
<div class="input-wrap"><i class="ri-mail-line"></i><input type="email" name="email" placeholder="邮箱（QQ/126/163）" required></div>
</div>
<div class="form-group">
<div class="code-row">
<div class="input-wrap"><i class="ri-shield-keyhole-line"></i><input type="text" name="email_code" placeholder="邮箱验证码（6位）" maxlength="6" required></div>
<button type="button" class="code-btn" id="sendEmailCode">获取验证码</button>
</div>
</div>
<button type="submit" class="login-btn">登 录</button>
</form>
</div>
<?php endif; ?>

<!-- 手机验证码登录 -->
<?php if($smsVerifyEnabled): ?>
<div class="tab-panel" id="panel-phone">
<form method="post">
<input type="hidden" name="login_type" value="phone">
<div class="form-group"><div class="input-wrap"><i class="ri-phone-line"></i><input type="tel" name="phone" placeholder="手机号" maxlength="11" required></div></div>
<div class="form-group"><div class="code-row"><div class="input-wrap"><i class="ri-shield-keyhole-line"></i><input type="text" name="sms_code" placeholder="短信验证码（6位）" maxlength="6" required></div><button type="button" class="code-btn" id="sendSmsCode">获取验证码</button></div></div>
<button type="submit" class="login-btn">登 录</button>
</form>
</div>
<?php endif; ?>

<!-- 账号密码登录 -->
<div class="tab-panel" id="panel-password">
<form method="post" id="form-password">
<input type="hidden" name="login_type" value="password">
<div class="form-group">
<div class="input-wrap"><i class="ri-user-line"></i><input type="text" name="login" placeholder="用户名 / 邮箱" required></div>
</div>
<div class="form-group">
<div class="input-wrap"><i class="ri-lock-line"></i><input type="password" name="password" placeholder="密码" required></div>
</div>
<button type="submit" class="login-btn">登 录</button>
</form>
</div>

<div class="agreement">
<input type="checkbox" id="agree" checked>
<label for="agree">我已阅读并同意 <a href="#">《用户条款》</a><a href="#">《隐私政策》</a></label>
</div>
<div class="links">
<a href="forgot_password.php">忘记密码？</a>
<a href="register.php">立即注册</a>
</div>
</div>
</div>

<script>
// Tab切换
document.querySelectorAll('.tab').forEach(function(tab){
    tab.addEventListener('click', function(){
        document.querySelectorAll('.tab').forEach(function(t){t.classList.remove('active')});
        document.querySelectorAll('.tab-panel').forEach(function(p){p.classList.remove('active')});
        this.classList.add('active');
        document.getElementById('panel-' + this.dataset.tab).classList.add('active');
    });
});

// 手机验证码
var smsBtn=document.getElementById('sendSmsCode');
if(smsBtn){smsBtn.onclick=function(){var p=document.querySelector('input[name=phone]').value;if(!/^1[3-9]\d{9}$/.test(p)){alert('请输入正确手机号');return;}var b=this;b.disabled=true;fetch('send_sms.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'phone='+encodeURIComponent(p)+'&type=login'}).then(r=>r.json()).then(d=>{if(d.success){var c=60;b.textContent=c+'s';var t=setInterval(()=>{c--;b.textContent=c+'s';if(c<=0){clearInterval(t);b.disabled=false;b.textContent='获取验证码';}},1000);}else{alert(d.msg||'失败');b.disabled=false;}}).catch(()=>{alert('失败');b.disabled=false;});};}

// 发送邮箱验证码
var emailCodeBtn = document.getElementById('sendEmailCode');
if (emailCodeBtn) {
    emailCodeBtn.addEventListener('click', function(){
        var emailInput = document.querySelector('#panel-email input[name=email]');
        var email = emailInput.value.trim();
        if (!email) { alert('请先输入邮箱'); emailInput.focus(); return; }
        var btn = this;
        btn.disabled = true;
        btn.textContent = '发送中...';
        fetch('send_email_code.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'email=' + encodeURIComponent(email)
        }).then(function(r){return r.json()}).then(function(data){
            if (data.success) {
                var count = 60;
                btn.textContent = count + 's后重发';
                var timer = setInterval(function(){
                    count--;
                    if (count <= 0) { clearInterval(timer); btn.disabled = false; btn.textContent = '获取验证码'; }
                    else { btn.textContent = count + 's后重发'; }
                }, 1000);
            } else {
                alert(data.message || '发送失败');
                btn.disabled = false;
                btn.textContent = '获取验证码';
            }
        }).catch(function(){
            alert('发送失败，请稍后重试');
            btn.disabled = false;
            btn.textContent = '获取验证码';
        });
    });
}

// 表单提交前检查协议
document.querySelectorAll('form').forEach(function(f){
    f.addEventListener('submit', function(e){
        if (!document.getElementById('agree').checked) {
            e.preventDefault();
            alert('请先阅读并同意用户条款和隐私政策');
        }
    });
});
</script>
</body>
</html>

