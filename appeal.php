<?php
session_start();
require_once 'functions.php';
$config = getConfig();

$step = 'check'; // check -> form -> success
$error = '';
$username = '';
$userInfo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'check_user') {
        $username = trim($_POST['username'] ?? '');
        $userInfo = getUserByUsername($username);
        if (!$userInfo) {
            $error = '该用户名不存在，请确认后重试';
        } elseif (empty($userInfo['banned'])) {
            $error = '该账号状态正常，无需申诉';
        } elseif (hasPendingAppeal($userInfo['id'])) {
            $step = 'pending';
        } else {
            $step = 'form';
        }
    } elseif ($action === 'submit_appeal') {
        $username = trim($_POST['username'] ?? '');
        $userInfo = getUserByUsername($username);
        if (!$userInfo || empty($userInfo['banned'])) {
            $error = '用户信息无效';
            $step = 'check';
        } elseif (hasPendingAppeal($userInfo['id'])) {
            $step = 'pending';
        } else {
            $email = trim($_POST['email'] ?? '');
            $contact = trim($_POST['contact'] ?? '');
            $reason = trim($_POST['reason'] ?? '');

            if (!$email || !$contact || !$reason) {
                $error = '请填写所有必填项';
                $step = 'form';
            } else {
                // 处理证明图片上传
                $proofImage = null;
                if (!empty($_FILES['proof']['name']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/uploads/appeals';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
                    if (in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp'])) {
                        $fileName = 'appeal_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                        if (move_uploaded_file($_FILES['proof']['tmp_name'], $uploadDir . '/' . $fileName)) {
                            $proofImage = 'uploads/appeals/' . $fileName;
                        }
                    }
                }
                createAppeal($userInfo['id'], $username, $email, $contact, $reason, $proofImage);
                $step = 'success';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>账号申诉 - <?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
.container{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.15);width:100%;max-width:460px;padding:36px 32px}
.logo{text-align:center;margin-bottom:24px}
.logo-icon{width:56px;height:56px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:28px;margin-bottom:10px}
h1{font-size:22px;font-weight:700;color:#1f2937;text-align:center;margin-bottom:4px}
.subtitle{text-align:center;color:#9ca3af;font-size:13px;margin-bottom:24px}
.banned-notice{background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 16px;margin-bottom:20px;font-size:13px;color:#991b1b;line-height:1.6}
.banned-notice strong{display:block;margin-bottom:4px;font-size:14px}
.form-group{margin-bottom:16px}
label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
label .req{color:#ef4444}
input,textarea{width:100%;padding:11px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;outline:none;transition:border-color .2s;font-family:inherit}
input:focus,textarea:focus{border-color:#667eea}
textarea{resize:vertical;min-height:90px}
.btn{width:100%;padding:12px;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;transition:all .2s;margin-top:8px}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff}
.btn-primary:hover{opacity:.92;transform:translateY(-1px)}
.btn-secondary{background:#f3f4f6;color:#374151}
.error{background:#fef2f2;color:#dc2626;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;text-align:center}
.success-box{text-align:center;padding:20px 0}
.success-icon{width:64px;height:64px;background:#dcfce7;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#16a34a;font-size:32px;margin-bottom:16px}
.success-box h2{font-size:18px;color:#1f2937;margin-bottom:8px}
.success-box p{color:#6b7280;font-size:13px;line-height:1.6;margin-bottom:20px}
.pending-box{text-align:center;padding:20px 0}
.pending-icon{width:64px;height:64px;background:#fef3c7;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#d97706;font-size:32px;margin-bottom:16px}
.user-info{background:#f9fafb;border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13px;color:#374151}
.user-info span{color:#6b7280}
.file-hint{font-size:11px;color:#9ca3af;margin-top:4px}
.back-link{text-align:center;margin-top:16px}
.back-link a{color:#667eea;font-size:13px;text-decoration:none}
</style>
</head>
<body>
<div class="container">
    <div class="logo">
        <div class="logo-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <h1>账号申诉</h1>
        <p class="subtitle"><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></p>
    </div>

    <?php if (isset($_GET['banned'])): ?>
    <div class="banned-notice">
        <strong>账号已被封禁</strong>
        您的账号因违反平台规定被封禁，无法登录。如认为封禁有误，可提交申诉。
    </div>
    <?php endif; ?>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if ($step === 'check'): ?>
    <form method="POST">
        <input type="hidden" name="action" value="check_user">
        <div class="form-group">
            <label>请输入被封禁的用户名 <span class="req">*</span></label>
            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" placeholder="请输入用户名" required>
        </div>
        <button type="submit" class="btn btn-primary">下一步</button>
    </form>

    <?php elseif ($step === 'form' && $userInfo): ?>
    <div class="user-info">
        <div>用户名：<strong><?= htmlspecialchars($userInfo['username']) ?></strong></div>
        <div style="margin-top:4px;">封禁原因：<span><?= htmlspecialchars($userInfo['ban_reason'] ?? '违反平台规定') ?></span></div>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="submit_appeal">
        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
        <div class="form-group">
            <label>联系邮箱 <span class="req">*</span></label>
            <input type="email" name="email" placeholder="用于接收申诉结果" required>
        </div>
        <div class="form-group">
            <label>联系方式 <span class="req">*</span></label>
            <input type="text" name="contact" placeholder="手机号/QQ/微信" required>
        </div>
        <div class="form-group">
            <label>申诉理由 <span class="req">*</span></label>
            <textarea name="reason" placeholder="请详细说明申诉理由..." required></textarea>
        </div>
        <div class="form-group">
            <label>证明图片（可选）</label>
            <input type="file" name="proof" accept="image/*">
            <p class="file-hint">支持 JPG/PNG/GIF/WebP，用于辅助证明</p>
        </div>
        <button type="submit" class="btn btn-primary">提交申诉</button>
    </form>

    <?php elseif ($step === 'success'): ?>
    <div class="success-box">
        <div class="success-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <h2>申诉提交成功</h2>
        <p>您的申诉已提交，我们将在1-3个工作日内审核。<br>审核结果将通过您填写的邮箱通知，请留意查收。</p>
        <a href="login.php" class="btn btn-secondary" style="text-decoration:none;display:block;">返回登录</a>
    </div>

    <?php elseif ($step === 'pending'): ?>
    <div class="pending-box">
        <div class="pending-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <h2 style="font-size:18px;color:#1f2937;margin-bottom:8px">申诉处理中</h2>
        <p style="color:#6b7280;font-size:13px;line-height:1.6;margin-bottom:20px;">您已有一条待处理的申诉，请耐心等待审核结果。<br>同一账号同时只能提交一条申诉。</p>
        <a href="login.php" class="btn btn-secondary" style="text-decoration:none;display:block;">返回登录</a>
    </div>
    <?php endif; ?>

    <div class="back-link">
        <a href="login.php">返回登录页</a>
    </div>
</div>
</body>
</html>
