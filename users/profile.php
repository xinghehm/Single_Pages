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
        // 处理修改背景颜色
    elseif ($_POST['action'] === 'change_bg_color') {
        $bgColor = trim($_POST['bg_color'] ?? '');
        if (setUserBgColor($user['id'], $bgColor)) {
            $message = '背景颜色已保存';
            $user['bg_color'] = $bgColor;
        } else {
            $error = '保存失败，请重试';
        }
    }
    
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
    
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=243">
    <style>
        .info-row { margin-bottom: 12px; }
        .info-label { font-weight: 600; width: 90px; display: inline-block; color: #4a5568; }
        .bind-status { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 12px; margin-left: 10px; }
        .bind-status.yes { background: #e6f9ed; color: #1e6f3f; }
        .bind-status.no { background: #ffeaea; color: #b22222; }
        .card-tab {
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .card-tab.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        .card-tab.active .card-desc { color: #ffffff; }
        .card-desc { font-size: 12px; margin-top: 4px; color: #6b7280; }
        .setting-panel { display: none; }
        .setting-panel.active { display: block; }
        @media (max-width: 768px) { .info-label { width: 75px; } }
    </style>

<style>
/* 移动端顶部导航 - 内联防止CSS丢失 */
.mobile-topbar {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    background: #fff;
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
    background: #2563eb;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 18px;
}
.hamburger-btn {
    width: 40px; height: 40px;
    background: #f3f4f6;
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
<body class="has-sidebar">
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

<aside class="sidebar">
    <div>
        <div class="brand">
            <div class="logo"><i class="ri-cloud-line"></i></div>
            <div><div class="name"><?= htmlspecialchars($config['site_name']) ?></div><div class="sub">Pages</div></div>
        </div>
        <nav>
            <a href="dashboard.php"><i class="ri-dashboard-line"></i> 控制台</a>
            <a href="projects.php"><i class="ri-folder-line"></i> 我的项目</a>
            <a href="login_logs.php"><i class="ri-history-line"></i> 登录日志</a>
            <a href="buy_group.php"><i class="ri-vip-crown-line"></i> 购买用户组</a>
            <a href="profile.php" class="active"><i class="ri-user-line"></i> 个人中心</a>
            </nav>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php" style="color:#dc2626;"><i class="ri-logout-box-line"></i> 退出登录</a>
    </div>
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
                <div class="mb-4 pb-2 border-b border-gray-300">
                    <span class="text-gray-600">当前邮箱：</span><?= htmlspecialchars($user['email']) ?>
                    <span class="bind-status yes">已绑定</span>
                </div>
                <form method="post" class="space-y-3">
                    <input type="hidden" name="action" value="change_email">
                    <input type="email" name="new_email" placeholder="新邮箱地址" required class="w-full px-4 py-2 rounded-lg">
                    
                    <?php if ($emailVerifyEnabled): ?>
                    <div class="flex gap-2">
                        <input type="text" name="email_code" placeholder="邮箱验证码" required class="flex-1 px-4 py-2 rounded-lg">
                        <button type="button" id="sendEmailCodeBtn" class="btn-secondary whitespace-nowrap px-4">获取验证码</button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($smsVerifyEnabled && !empty($user['phone'])): ?>
                    <div class="flex gap-2">
                        <input type="text" name="sms_code" placeholder="短信验证码" required class="flex-1 px-4 py-2 rounded-lg">
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
                <div class="mb-4 pb-2 border-b border-gray-300">
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
                    <input type="text" name="new_phone" id="newPhone" placeholder="新手机号" required class="w-full px-4 py-2 rounded-lg">
                    
                    <?php if ($smsVerifyEnabled): ?>
                    <div class="flex gap-2">
                        <input type="text" name="sms_code" placeholder="短信验证码" required class="flex-1 px-4 py-2 rounded-lg">
                        <button type="button" id="bindSmsBtn" class="btn-secondary whitespace-nowrap px-4">获取验证码</button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($emailVerifyEnabled): ?>
                    <div class="flex gap-2">
                        <input type="text" name="email_code" placeholder="邮箱验证码" required class="flex-1 px-4 py-2 rounded-lg">
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
                    <input type="password" name="old_password" placeholder="原密码" required class="w-full px-4 py-2 rounded-lg">
                    <input type="password" name="new_password" placeholder="新密码（至少6位）" required class="w-full px-4 py-2 rounded-lg">
                    <input type="password" name="confirm_password" placeholder="确认新密码" required class="w-full px-4 py-2 rounded-lg">
                    <button type="submit" class="btn-primary w-full py-2">修改密码</button>
                </form>
            </div>
        </div>
        
        <!-- 基本信息 -->
        <hr class="my-5">
        <div class="info-row"><span class="info-label">用户名：</span><?= htmlspecialchars($user['username']) ?></div>
        <div class="info-row"><span class="info-label">注册时间：</span><?= date('Y-m-d H:i', $user['registered_at']) ?></div>
        <div class="info-row"><span class="info-label">存储配额：</span><?= getUserQuota($user['id']) ?> MB</div>
        
        <!-- 背景颜色设置 -->
        <hr class="my-5">
        <div class="mb-4">
            <div class="info-label mb-2">专属背景颜色：</div>
            <form method="POST" class="flex items-center gap-3 flex-wrap">
                <input type="hidden" name="action" value="change_bg_color">
                <input type="color" name="bg_color_picker" id="bgColorPicker" 
                       value="<?= htmlspecialchars($user['bg_color'] ?? '#f0f5ff') ?>"
                       class="w-12 h-10 rounded cursor-pointer border-0"
                       oninput="document.getElementById('bgColorInput').value=this.value">
                <input type="text" name="bg_color" id="bgColorInput" 
                       value="<?= htmlspecialchars($user['bg_color'] ?? '') ?>"
                       placeholder="留空恢复默认"
                       class="flex-1 px-3 py-2 rounded-lg border border-gray-300 text-sm font-mono"
                       oninput="document.getElementById('bgColorPicker').value=this.value">
                <button type="submit" class="btn-primary text-sm px-4 py-2">保存</button>
                <button type="button" onclick="document.getElementById('bgColorInput').value='';this.form.submit()" class="btn-secondary text-sm px-4 py-2">恢复默认</button>
            </form>
            <p class="text-xs text-gray-400 mt-2">支持纯色（#ff0000）或渐变（linear-gradient(135deg, #667eea 0%, #764ba2 100%)）</p>
        </div>

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