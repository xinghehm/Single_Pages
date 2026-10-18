<?php
require_once __DIR__ . '/../functions.php';
if (!isAdminLoggedIn()) { header('Location: login.php'); exit; }
$config = getConfig();

$msg = '';
$msgType = '';
$result = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $testEmail = trim($_POST['test_email'] ?? '');

    if (!$title || !$content) {
        $msg = '请填写标题和内容'; $msgType = 'error';
    } else {
        // 测试模式：只发给指定邮箱
        if ($testEmail) {
            $sent = sendMail($testEmail, $title, $content);
            $result[] = ['email' => $testEmail, 'status' => $sent ? 'success' : 'fail'];
            $msg = $sent ? '测试邮件已发送到 ' . htmlspecialchars($testEmail) : '测试邮件发送失败，请检查SMTP配置';
            $msgType = $sent ? 'success' : 'error';
        } else {
            // 群发所有用户
            $users = getAllUserEmails();
            $successCount = 0;
            $failCount = 0;
            foreach ($users as $u) {
                $sent = sendMail($u['email'], $title, $content);
                if ($sent) { $successCount++; $result[] = ['email' => $u['email'], 'status' => 'success']; }
                else { $failCount++; $result[] = ['email' => $u['email'], 'status' => 'fail']; }
            }
            $msg = "发送完成：成功 {$successCount} 封，失败 {$failCount} 封";
            $msgType = 'success';
        }
    }
}

$userCount = 0;
$pdo = getDB();
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE email IS NOT NULL AND email != ''");
$row = $stmt->fetch();
$userCount = intval($row['cnt'] ?? 0);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>邮箱通知 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=245">
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-mail-send-line"></i></div><span>邮箱通知</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('show');document.querySelector('.sidebar-overlay').classList.toggle('show');}</script>
<?php include 'sidebar.php'; ?>

<main>
<h1 class="page-title">邮件通知</h1>
<p class="page-desc">一键向所有注册用户发送邮件通知</p>
<?php if ($msg): ?><div class="msg <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="info-bar">
<div class="info-item"><i class="ri-user-line"></i><div><div class="num"><?= $userCount ?></div><div class="label">可发送用户</div></div></div>
<div class="info-item"><i class="ri-mail-line"></i><div><div class="num" style="font-size:14px;padding-top:4px">SMTP</div><div class="label">邮件通道</div></div></div>
</div>

<div class="tip"><i class="ri-information-line"></i> 提示：群发到所有用户可能需要较长时间，请确保SMTP配置正确。建议先使用测试邮箱验证。</div>

<div class="card">
<h3>撰写邮件</h3>
<form method="post">
<input type="hidden" name="action" value="send">
<div class="form-group"><label>邮件标题</label><input type="text" name="title" placeholder="请输入邮件标题" required></div>
<div class="form-group"><label>邮件内容</label><textarea name="content" placeholder="请输入邮件内容（支持HTML）" required></textarea></div>
<div class="test-row">
<div class="form-group"><label>测试邮箱（填写则只发给此邮箱，不群发）</label><input type="email" name="test_email" placeholder="如：test@example.com"></div>
</div>
<div class="btn-row">
<button type="submit" class="btn btn-primary"><i class="ri-mail-send-line"></i> 群发给所有用户（<?= $userCount ?>人）</button>
</div>
</form>
</div>

<?php if (!empty($result)): ?>
<div class="card">
<h3>发送结果（<?= count($result) ?>条）</h3>
<div class="result-list">
<?php foreach ($result as $r): ?>
<div class="result-item">
<span class="email"><?= htmlspecialchars($r['email']) ?></span>
<span class="<?= $r['status'] == 'success' ? 'status-ok' : 'status-fail' ?>"><?= $r['status'] == 'success' ? '✓ 成功' : '✗ 失败' ?></span>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>
</div>
</main>
</body>
</html>

