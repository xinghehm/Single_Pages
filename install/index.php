<?php
session_start();
if (file_exists('../config.php') && file_exists('install.lock')) {
    header('Location: ../index.php');
    exit;
}
$error = '';
$success = '';

function execute_sql_file($pdo, $file) {
    $sql = file_get_contents($file);
    // 移除注释
    $sql = preg_replace('/--.*$/m', '', $sql);
    // 按分号分割语句（FROM_BASE64内容中无分号，安全）
    $statements = array_filter(array_map('trim', explode(";\n", $sql)));
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            // 忽略已存在等非致命错误
            if (strpos($e->getMessage(), 'already exists') === false &&
                strpos($e->getMessage(), 'Duplicate') === false &&
                strpos($e->getMessage(), '1062') === false) {
                throw $e;
            }
        }
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    $admin_user = trim($_POST['admin_user'] ?? 'admin');
    $admin_pass = $_POST['admin_pass'] ?? '';
    $site_name = trim($_POST['site_name'] ?? '单页工坊');
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = trim($_POST['smtp_port'] ?? '465');
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    $smtp_secure = $_POST['smtp_secure'] ?? 'ssl';
    $smtp_from_email = trim($_POST['smtp_from_email'] ?? '');
    $smtp_from_name = trim($_POST['smtp_from_name'] ?? '');

    if (empty($db_name) || empty($db_user)) $error = '请填写数据库名称和用户名';
    elseif (strlen($admin_pass) < 6) $error = '管理员密码至少6位';
    else {
        try {
            $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARSET utf8mb4");
            $pdo->exec("USE `$db_name`");
            execute_sql_file($pdo, __DIR__ . '/install.sql');
            $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
            $stmt->execute([$hash, $admin_user]);
            $stmt = $pdo->prepare("UPDATE site_config SET value = ? WHERE `key` = 'site_name'");
            $stmt->execute([$site_name]);
            $smtpConfigs = ['smtp_host' => $smtp_host, 'smtp_port' => $smtp_port, 'smtp_user' => $smtp_user, 'smtp_pass' => $smtp_pass, 'smtp_secure' => $smtp_secure, 'smtp_from_email' => $smtp_from_email, 'smtp_from_name' => $smtp_from_name];
            foreach ($smtpConfigs as $key => $value) { $stmt = $pdo->prepare("INSERT INTO site_config (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?"); $stmt->execute([$key, $value, $value]); }
            $config_content = "<?php\nreturn [\n    'db_host' => '$db_host',\n    'db_name' => '$db_name',\n    'db_user' => '$db_user',\n    'db_pass' => '$db_pass',\n];";
            file_put_contents('../config.php', $config_content);
            file_put_contents('install.lock', date('Y-m-d H:i:s'));
            $success = '安装成功！3秒后跳转首页...';
            header('refresh:3;url=../index.php');
        } catch (PDOException $e) { $error = '安装失败：' . $e->getMessage(); }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>系统安装 - 单页工坊</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f5f6f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;color:#1a1a2e}
.container{max-width:560px;width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:32px}
h2{font-size:22px;margin-bottom:6px;color:#1a1a2e}
.subtitle{color:#6b7280;font-size:14px;margin-bottom:24px}
h3{font-size:15px;font-weight:600;margin:20px 0 12px;color:#374151;padding-bottom:8px;border-bottom:1px solid #e5e7eb}
label{display:block;font-size:13px;color:#4b5563;margin-bottom:4px;font-weight:500}
input,select{width:100%;padding:10px 12px;margin-bottom:12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;background:#fff;transition:border-color .15s}
input:focus,select:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
button{background:#2563eb;color:#fff;border:none;padding:12px;border-radius:6px;cursor:pointer;width:100%;font-size:15px;font-weight:500;margin-top:8px;transition:background .15s}
button:hover{background:#1d4ed8}
.error{background:#fef2f2;border:1px solid #fecaca;border-left:3px solid #dc2626;padding:10px 14px;margin-bottom:16px;color:#991b1b;font-size:14px;border-radius:4px}
.success{background:#f0fdf4;border:1px solid #bbf7d0;border-left:3px solid #16a34a;padding:10px 14px;margin-bottom:16px;color:#166534;font-size:14px;border-radius:4px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.version{text-align:center;margin-top:20px;font-size:12px;color:#9ca3af}
</style>
</head>
<body>
<div class="container">
<h2>系统安装向导</h2>
<p class="subtitle">单页工坊 v2.4 — 填写以下信息完成安装</p>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<form method="post">
<h3>数据库配置</h3>
<div class="row">
<div><label>数据库主机</label><input type="text" name="db_host" value="localhost"></div>
<div><label>数据库名称</label><input type="text" name="db_name" placeholder="page"></div>
</div>
<div class="row">
<div><label>数据库用户名</label><input type="text" name="db_user" placeholder="root"></div>
<div><label>数据库密码</label><input type="password" name="db_pass"></div>
</div>
<h3>管理员账号</h3>
<div class="row">
<div><label>管理员用户名</label><input type="text" name="admin_user" value="admin"></div>
<div><label>管理员密码（至少6位）</label><input type="password" name="admin_pass"></div>
</div>
<h3>网站信息</h3>
<label>网站名称</label><input type="text" name="site_name" value="单页工坊">
<h3>SMTP 邮件配置（可选）</h3>
<div class="row">
<div><label>SMTP 服务器</label><input type="text" name="smtp_host" placeholder="smtp.qq.com"></div>
<div><label>端口</label><input type="text" name="smtp_port" value="465"></div>
</div>
<div class="row">
<div><label>SMTP 用户名</label><input type="text" name="smtp_user"></div>
<div><label>SMTP 密码/授权码</label><input type="password" name="smtp_pass"></div>
</div>
<div class="row">
<div><label>加密方式</label><select name="smtp_secure"><option value="ssl">SSL</option><option value="tls">TLS</option></select></div>
<div><label>发件人名称</label><input type="text" name="smtp_from_name" value="单页工坊"></div>
</div>
<label>发件人邮箱</label><input type="text" name="smtp_from_email" placeholder="noreply@example.com">
<button type="submit">开始安装</button>
</form>
<div class="version">单页工坊 v2.4.0</div>
</div>
</body>
</html>
