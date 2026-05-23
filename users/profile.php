<?php
require_once '../functions.php';
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser();
$pdo = getDB();
$message = '';
$error = '';

$smsVerifyEnabled = isSmsVerifyEnabled();
$emailVerifyEnabled = isEmailVerifyEnabled();

// 处理修改密码
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_password') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($old, $user['password'])) {
            $error = '原密码错误';
        } elseif (strlen($new) < 6) {
            $error = '新密码至少6位';
        } elseif ($new !== $confirm) {
            $error = '两次密码不一致';
        } else {
            if (changePassword($user['id'], $new)) {
                $message = '密码修改成功，请重新登录';
                session_destroy();
                header('refresh:2;url=../login.php');
                exit;
            } else {
                $error = '修改失败，请重试';
            }
        }
    }
    
    // 处理修改绑定邮箱
    elseif ($_POST['action'] === 'change_email') {
        $newEmail = trim($_POST['new_email'] ?? '');
        $emailCode = trim($_POST['email_code'] ?? '');
        $smsCode = trim($_POST['sms_code'] ?? '');
        
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = '请输入有效的邮箱地址';
        } elseif ($emailVerifyEnabled && !verifySmsCode($newEmail, $emailCode)) {
            $error = '邮箱验证码错误或已过期';
        } elseif ($smsVerifyEnabled && !empty($user['phone']) && !verifySmsCode($user['phone'], $smsCode)) {
            $error = '短信验证码错误或已过期';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $user['id']]);
            if ($stmt->fetch()) {
                $error = '该邮箱已被其他账号使用';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                if ($stmt->execute([$newEmail, $user['id']])) {
                    $message = '邮箱修改成功';
                    $user['email'] = $newEmail;
                } else {
                    $error = '修改失败，请重试';
                }
            }
        }
    }
    
    // 处理修改绑定手机号
    elseif ($_POST['action'] === 'change_phone') {
        $newPhone = trim($_POST['new_phone'] ?? '');
        $smsCode = trim($_POST['sms_code'] ?? '');
        $emailCode = trim($_POST['email_code'] ?? '');
        
        if (!preg_match('/^1[3-9]\d{9}$/', $newPhone)) {
            $error = '请输入有效的手机号';
        } elseif ($smsVerifyEnabled && !verifySmsCode($newPhone, $smsCode)) {
            $error = '短信验证码错误或已过期';
        } elseif ($emailVerifyEnabled && !verifySmsCode($user['email'], $emailCode)) {
            $error = '邮箱验证码错误或已过期';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $stmt->execute([$newPhone, $user['id']]);
            if ($stmt->fetch()) {
                $error = '该手机号已被其他账号绑定';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
                if ($stmt->execute([$newPhone, $user['id']])) {
                    $message = '手机号修改成功';
                    $user['phone'] = $newPhone;
                } else {
                    $error = '修改失败，请重试';
                }
            }
        }
    }
    
    // 解绑手机号
    elseif ($_POST['action'] === 'unbind_phone') {
        if (isForceBindPhone()) {
            $error = '系统要求必须绑定手机号，无法解绑';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET phone = NULL WHERE id = ?");
            if ($stmt->execute([$user['id']])) {
                $message = '手机号已解绑';
                $user['phone'] = null;
            } else {
                $error = '解绑失败，请重试';
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
    <title>个人中心 - <?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .info-row { margin-bottom: 12px; }
        .info-label { font-weight: 600; width: 90px; display: inline-block; color: #4a5568; }
        .bind-status { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 12px; margin-left: 10px; }
        .bind-status.yes { background: #e6f9ed; color: #1e6f3f; }
        .bind-status.no { background: #ffeaea; color: #b22222; }
        .card-tab {
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .card-tab.active {
            background: linear-gradient(135deg, #1677ff, #0958d9);
            color: white;
            border-color: #1677ff;
        }
        .card-tab.active .card-desc { color: rgba(255,255,255,0.8); }
        .card-desc { font-size: 12px; margin-top: 4px; color: #6b7280; }
        .setting-panel { display: none; }
        .setting-panel.active { display: block; }
        @media (max-width: 768px) { .info-label { width: 75px; } }
    </style>
</head>
<body class="flex min-h-screen">
<aside class="sidebar w-64 min-h-screen p-5 flex flex-col justify-between">
    <div>
        <div class="flex items-center gap-2 mb-8">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white"><i class="ri-cloud-line"></i></div>
            <div><div class="font-bold"><?= htmlspecialchars($config['site_name'] ?? '云上云诺') ?></div><div class="text-xs text-gray-500">Pages</div></div>
        </div>
        <nav class="space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-dashboard-line"></i> 控制台</a>
            <a href="projects.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 text-gray-600"><i class="ri-folder-line"></i> 我的项目</a>
            <a href="profile.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600"><i class="ri-user-line"></i> 个人中心</a>
        </nav>
    </div>
    <div><a href="../logout.php" class="text-red-500"><i class="ri-logout-box-line"></i> 退出</a></div>
</aside>

<main class="flex-1 p-6">
    <div class="glass-card max-w-2xl mx-auto p-6">
        <h2 class="text-xl font-bold mb-6">个人中心</h2>
        
        <?php if ($message): ?>
            <div class="success mb-4"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- 三卡片切换区 -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- 卡片1：邮箱管理 -->
            <div class="card-tab glass rounded-xl p-4 text-center <?= !isset($_GET['tab']) || $_GET['tab'] == 'email' ? 'active' : '' ?>" data-tab="email">
                <i class="ri-mail-line text-2xl"></i>
                <div class="font-medium mt-1">邮箱管理</div>
                <div class="card-desc"><?= htmlspecialchars($user['email']) ?></div>
            </div>
            
            <!-- 卡片2：手机管理 -->
            <div class="card-tab glass rounded-xl p-4 text-center <?= isset($_GET['tab']) && $_GET['tab'] == 'phone' ? 'active' : '' ?>" data-tab="phone">
                <i class="ri-smartphone-line text-2xl"></i>
                <div class="font-medium mt-1">手机管理</div>
                <div class="card-desc"><?= !empty($user['phone']) ? htmlspecialchars(substr_replace($user['phone'], '****', 3, 4)) : '未绑定' ?></div>
            </div>
            
            <!-- 卡片3：密码管理 -->
            <div class="card-tab glass rounded-xl p-4 text-center <?= isset($_GET['tab']) && $_GET['tab'] == 'password' ? 'active' : '' ?>" data-tab="password">
                <i class="ri-lock-line text-2xl"></i>
                <div class="font-medium mt-1">密码管理</div>
                <div class="card-desc">修改登录密码</div>
            </div>
        </div>
        
        <!-- 面板1：邮箱管理 -->
        <div id="panel-email" class="setting-panel <?= !isset($_GET['tab']) || $_GET['tab'] == 'email' ? 'active' : '' ?>">
            <div class="glass p-4 rounded-xl">
                <div class="mb-4 pb-2 border-b border-gray-200">
                    <span class="text-gray-600">当前邮箱：</span><?= htmlspecialchars($user['email']) ?>
                    <span class="bind-status yes">已绑定</span>
                </div>
                <form method="post" class="space-y-3">
                    <input type="hidden" name="action" value="change_email">
                    <input type="email" name="new_email" placeholder="新邮箱地址" required class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
                    
                    <?php if ($emailVerifyEnabled): ?>
                    <div class="flex gap-2">
                        <input type="text" name="email_code" placeholder="邮箱验证码" required class="flex-1 px-4 py-2 rounded-full bg-white/40 border border-gray-200">
                        <button type="button" id="sendEmailCodeBtn" class="btn-secondary whitespace-nowrap px-4">获取验证码</button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($smsVerifyEnabled && !empty($user['phone'])): ?>
                    <div class="flex gap-2">
                        <input type="text" name="sms_code" placeholder="短信验证码" required class="flex-1 px-4 py-2 rounded-full bg-white/40 border border-gray-200">
                        <button type="button" id="sendSmsCodeBtn" class="btn-secondary whitespace-nowrap px-4">获取验证码</button>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn-primary w-full py-2">修改邮箱</button>
                </form>
            </div>
        </div>
        
        <!-- 面板2：手机管理 -->
        <div id="panel-phone" class="setting-panel <?= isset($_GET['tab']) && $_GET['tab'] == 'phone' ? 'active' : '' ?>">
            <div class="glass p-4 rounded-xl">
                <div class="mb-4 pb-2 border-b border-gray-200">
                    <span class="text-gray-600">当前手机：</span>
                    <?php if (!empty($user['phone'])): ?>
                        <?= htmlspecialchars(substr_replace($user['phone'], '****', 3, 4)) ?>
                        <span class="bind-status yes">已绑定</span>
                        <?php if (!isForceBindPhone()): ?>
                        <form method="post" class="inline ml-3">
                            <input type="hidden" name="action" value="unbind_phone">
                            <button type="submit" class="text-red-500 text-sm hover:underline" onclick="return confirm('确定要解绑手机号吗？')">解绑</button>
                        </form>
                        <?php endif; ?>
                    <?php else: ?>
                        未绑定
                        <span class="bind-status no">未绑定</span>
                    <?php endif; ?>
                </div>
                <form method="post" class="space-y-3">
                    <input type="hidden" name="action" value="change_phone">
                    <input type="text" name="new_phone" id="newPhone" placeholder="新手机号" required class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
                    
                    <?php if ($smsVerifyEnabled): ?>
                    <div class="flex gap-2">
                        <input type="text" name="sms_code" placeholder="短信验证码" required class="flex-1 px-4 py-2 rounded-full bg-white/40 border border-gray-200">
                        <button type="button" id="bindSmsBtn" class="btn-secondary whitespace-nowrap px-4">获取验证码</button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($emailVerifyEnabled): ?>
                    <div class="flex gap-2">
                        <input type="text" name="email_code" placeholder="邮箱验证码" required class="flex-1 px-4 py-2 rounded-full bg-white/40 border border-gray-200">
                        <button type="button" id="bindEmailCodeBtn" class="btn-secondary whitespace-nowrap px-4">获取验证码</button>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn-primary w-full py-2">修改手机号</button>
                </form>
            </div>
        </div>
        
        <!-- 面板3：密码管理 -->
        <div id="panel-password" class="setting-panel <?= isset($_GET['tab']) && $_GET['tab'] == 'password' ? 'active' : '' ?>">
            <div class="glass p-4 rounded-xl">
                <form method="post" class="space-y-3">
                    <input type="hidden" name="action" value="change_password">
                    <input type="password" name="old_password" placeholder="原密码" required class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
                    <input type="password" name="new_password" placeholder="新密码（至少6位）" required class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
                    <input type="password" name="confirm_password" placeholder="确认新密码" required class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200">
                    <button type="submit" class="btn-primary w-full py-2">修改密码</button>
                </form>
            </div>
        </div>
        
        <!-- 基本信息 -->
        <hr class="my-5">
        <div class="info-row"><span class="info-label">用户名：</span><?= htmlspecialchars($user['username']) ?></div>
        <div class="info-row"><span class="info-label">注册时间：</span><?= date('Y-m-d H:i', $user['registered_at']) ?></div>
        <div class="info-row"><span class="info-label">存储配额：</span><?= getUserQuota($user['id']) ?> MB</div>
    </div>
</main>

<script>
// 卡片切换
const cards = document.querySelectorAll('.card-tab');
const panels = {
    email: document.getElementById('panel-email'),
    phone: document.getElementById('panel-phone'),
    password: document.getElementById('panel-password')
};

cards.forEach(card => {
    card.addEventListener('click', function() {
        const tab = this.dataset.tab;
        // 更新卡片激活状态
        cards.forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        // 更新面板显示
        Object.keys(panels).forEach(key => {
            panels[key].classList.remove('active');
        });
        panels[tab].classList.add('active');
        // 更新URL参数（可选）
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.pushState({}, '', url);
    });
});

// 发送邮箱验证码
let emailCountdown = 0;
const sendEmailBtn = document.getElementById('sendEmailCodeBtn');
if (sendEmailBtn) {
    sendEmailBtn.addEventListener('click', function() {
        if (emailCountdown > 0) return;
        const newEmail = document.querySelector('input[name="new_email"]').value;
        if (!newEmail) {
            alert('请先输入新邮箱地址');
            return;
        }
        fetch('../send_email_code.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'email=' + encodeURIComponent(newEmail) + '&type=bind'
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
}

// 发送邮箱验证码（用于手机管理）
let bindEmailCountdown = 0;
const bindEmailBtn = document.getElementById('bindEmailCodeBtn');
if (bindEmailBtn) {
    bindEmailBtn.addEventListener('click', function() {
        if (bindEmailCountdown > 0) return;
        const currentEmail = '<?= $user['email'] ?>';
        fetch('../send_email_code.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'email=' + encodeURIComponent(currentEmail) + '&type=bind'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('验证码已发送到您的绑定邮箱');
                bindEmailCountdown = 60;
                bindEmailBtn.textContent = bindEmailCountdown + '秒后重试';
                const timer = setInterval(() => {
                    bindEmailCountdown--;
                    if (bindEmailCountdown <= 0) {
                        clearInterval(timer);
                        bindEmailBtn.textContent = '获取验证码';
                    } else {
                        bindEmailBtn.textContent = bindEmailCountdown + '秒后重试';
                    }
                }, 1000);
            } else {
                alert(data.message);
            }
        })
        .catch(err => alert('发送失败，请重试'));
    });
}

// 发送短信验证码（用于手机管理）
let smsCountdown = 0;
const bindSmsBtn = document.getElementById('bindSmsBtn');
const newPhoneInput = document.getElementById('newPhone');
if (bindSmsBtn) {
    bindSmsBtn.addEventListener('click', function() {
        if (smsCountdown > 0) return;
        const phone = newPhoneInput.value;
        if (!/^1[3-9]\d{9}$/.test(phone)) {
            alert('请输入有效的手机号');
            return;
        }
        fetch('../send_sms.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'phone=' + encodeURIComponent(phone) + '&type=bind'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('验证码已发送');
                smsCountdown = 60;
                bindSmsBtn.textContent = smsCountdown + '秒后重试';
                const timer = setInterval(() => {
                    smsCountdown--;
                    if (smsCountdown <= 0) {
                        clearInterval(timer);
                        bindSmsBtn.textContent = '获取验证码';
                    } else {
                        bindSmsBtn.textContent = smsCountdown + '秒后重试';
                    }
                }, 1000);
            } else {
                alert(data.message);
            }
        })
        .catch(err => alert('发送失败，请重试'));
    });
}

// 发送短信验证码（用于邮箱管理）
let smsCodeCountdown = 0;
const sendSmsBtn = document.getElementById('sendSmsCodeBtn');
if (sendSmsBtn) {
    sendSmsBtn.addEventListener('click', function() {
        if (smsCodeCountdown > 0) return;
        const phone = '<?= $user['phone'] ?>';
        if (!phone) {
            alert('您尚未绑定手机号，无法发送短信验证码');
            return;
        }
        fetch('../send_sms.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'phone=' + encodeURIComponent(phone) + '&type=bind'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('验证码已发送');
                smsCodeCountdown = 60;
                sendSmsBtn.textContent = smsCodeCountdown + '秒后重试';
                const timer = setInterval(() => {
                    smsCodeCountdown--;
                    if (smsCodeCountdown <= 0) {
                        clearInterval(timer);
                        sendSmsBtn.textContent = '获取验证码';
                    } else {
                        sendSmsBtn.textContent = smsCodeCountdown + '秒后重试';
                    }
                }, 1000);
            } else {
                alert(data.message);
            }
        })
        .catch(err => alert('发送失败，请重试'));
    });
}
</script>
</body>
</html>