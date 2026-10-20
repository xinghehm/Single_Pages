<?php
session_start();
require_once 'functions.php';
$config = getConfig();

$step = 'check';
$error = '';
$project = null;

// 支持GET参数直接带入项目ID
if (!empty($_GET['pro_id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $proId = trim($_GET['pro_id']);
    $project = getProjectByProId($proId);
    if (!$project) $project = getProjectBySlug($proId);
    if ($project && !empty($project['banned'])) {
        $step = 'form';
    } elseif ($project) {
        $error = '该项目状态正常，无需申诉';
    } else {
        $error = '项目不存在，请确认项目ID';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'check') {
        $input = trim($_POST['project_input'] ?? '');
        // 支持输入pro_id或完整链接
        if (preg_match('#/([a-zA-Z0-9]{6,16})$#', $input, $m)) {
            $proId = $m[1];
        } else {
            $proId = $input;
        }
        $project = getProjectByProId($proId);
        if (!$project) $project = getProjectBySlug($proId);
        if (!$project) {
            $error = '项目不存在，请确认项目ID或链接';
        } elseif (empty($project['banned'])) {
            $error = '该项目状态正常，无需申诉';
        } else {
            $step = 'form';
        }
    } elseif ($action === 'submit') {
        $proId = $_POST['pro_id'] ?? '';
        $project = getProjectByProId($proId);
        if (!$project) $project = getProjectBySlug($proId);
        if (!$project || empty($project['banned'])) {
            $error = '项目信息无效';
            $step = 'check';
        } else {
            $email = trim($_POST['email'] ?? '');
            $contact = trim($_POST['contact'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            if (!$email || !$contact || !$reason) {
                $error = '请填写所有必填项';
                $step = 'form';
            } else {
                $proofImage = null;
                if (!empty($_FILES['proof']['name']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/uploads/appeals';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
                    if (in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp'])) {
                        $fileName = 'project_appeal_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                        if (move_uploaded_file($_FILES['proof']['tmp_name'], $uploadDir . '/' . $fileName)) {
                            $proofImage = 'uploads/appeals/' . $fileName;
                        }
                    }
                }
                // 项目申诉存入user_appeals表，user_id为项目所有者
                $pdo = getDB();
                $stmt = $pdo->prepare("INSERT INTO user_appeals (user_id, username, email, contact, reason, proof_image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
                $stmt->execute([$project['user_id'], '项目:' . $project['pro_id'], $email, $contact, $reason, $proofImage, time()]);
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
<title>项目申诉 - <?= htmlspecialchars($config['site_name']) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f5f5f5;min-height:100vh;padding:20px 16px}
.wrap{max-width:480px;margin:40px auto}
.card{background:#fff;border-radius:12px;padding:28px 24px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
h1{font-size:20px;font-weight:600;margin-bottom:4px;text-align:center}
.sub{text-align:center;color:#999;font-size:13px;margin-bottom:24px}
label{display:block;font-size:13px;font-weight:500;color:#333;margin-bottom:6px}
label .req{color:#e53935}
input,textarea{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;outline:none;margin-bottom:16px;font-family:inherit;transition:border-color .2s}
input:focus,textarea:focus{border-color:#4a90d9}
textarea{resize:vertical;min-height:90px}
.btn{width:100%;padding:11px;border:none;border-radius:8px;font-size:15px;font-weight:500;cursor:pointer;background:#4a90d9;color:#fff;transition:background .2s}
.btn:hover{background:#3a7bc8}
.btn-gray{background:#f0f0f0;color:#333}
.btn-gray:hover{background:#e0e0e0}
.error{background:#fdecea;color:#c62828;padding:10px 12px;border-radius:8px;font-size:13px;margin-bottom:16px;text-align:center}
.info{background:#f5f5f5;padding:12px;border-radius:8px;font-size:13px;color:#666;margin-bottom:16px;line-height:1.6}
.info strong{color:#333}
.success{text-align:center;padding:20px 0}
.success .icon{width:56px;height:56px;background:#e8f5e9;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#43a047;font-size:28px;margin-bottom:16px}
.success h2{font-size:18px;margin-bottom:8px}
.success p{color:#666;font-size:13px;line-height:1.6;margin-bottom:20px}
.hint{font-size:11px;color:#999;margin-top:-10px;margin-bottom:16px}
.back{text-align:center;margin-top:16px}
.back a{color:#4a90d9;font-size:13px;text-decoration:none}
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>项目申诉</h1>
        <p class="sub"><?= htmlspecialchars($config['site_name']) ?></p>

        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <?php if ($step === 'check'): ?>
        <form method="POST">
            <input type="hidden" name="action" value="check">
            <label>项目ID或访问链接 <span class="req">*</span></label>
            <input type="text" name="project_input" placeholder="如：abc123 或 https://域名/abc123" required>
            <p class="hint">在项目被封禁页面或我的项目中可找到项目ID</p>
            <button type="submit" class="btn">下一步</button>
        </form>

        <?php elseif ($step === 'form' && $project): ?>
        <div class="info">
            <div><strong>项目名：</strong><?= htmlspecialchars($project['name']) ?></div>
            <div style="margin-top:4px"><strong>项目ID：</strong><?= htmlspecialchars($project['pro_id']) ?></div>
            <div style="margin-top:4px"><strong>封禁原因：</strong><?= htmlspecialchars($project['ban_reason'] ?? '违反平台规定') ?></div>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit">
            <input type="hidden" name="pro_id" value="<?= htmlspecialchars($project['pro_id']) ?>">
            <label>联系邮箱 <span class="req">*</span></label>
            <input type="email" name="email" placeholder="用于接收申诉结果" required>
            <label>联系方式 <span class="req">*</span></label>
            <input type="text" name="contact" placeholder="手机号/QQ/微信" required>
            <label>申诉理由 <span class="req">*</span></label>
            <textarea name="reason" placeholder="请详细说明申诉理由..." required></textarea>
            <label>证明图片（可选）</label>
            <input type="file" name="proof" accept="image/*">
            <p class="hint">支持 JPG/PNG/GIF/WebP</p>
            <button type="submit" class="btn">提交申诉</button>
        </form>

        <?php elseif ($step === 'success'): ?>
        <div class="success">
            <div class="icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <h2>申诉提交成功</h2>
            <p>我们将在1-3个工作日内审核，结果通过邮箱通知。</p>
            <a href="index.php" class="btn btn-gray" style="text-decoration:none;display:block;">返回首页</a>
        </div>
        <?php endif; ?>

        <div class="back">
            <a href="index.php">返回首页</a>
        </div>
    </div>
</div>
</body>
</html>

