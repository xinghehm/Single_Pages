<?php
session_start();
if (file_exists('../config.php') && file_exists('install.lock')) {
    header('Location: ../index.php');
    exit;
}
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    $admin_user = trim($_POST['admin_user'] ?? 'admin');
    $admin_pass = $_POST['admin_pass'] ?? '';
    $site_name = trim($_POST['site_name'] ?? '云上云诺');
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
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
            $pdo->exec("USE `$db_name`");
            $sql = file_get_contents(__DIR__ . '/install.sql');
            $pdo->exec($sql);
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
            $success = '安装成功！3秒后跳转...';
            header('refresh:3;url=../index.php');
        } catch (PDOException $e) { $error = '安装失败：' . $e->getMessage(); }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>系统安装</title><style>*{margin:0;padding:0;box-sizing:border-box}body{background:linear-gradient(135deg,#f0f5ff 0%,#e0e9f7 100%);font-family:'Segoe UI',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}.container{max-width:650px;width:100%;background:rgba(255,255,255,0.85);backdrop-filter:blur(30px);border-radius:24px;padding:40px;border:1px solid rgba(255,255,255,0.3)}h2{color:#0b3b5c;margin-bottom:20px}h3{color:#1e4a7a;margin:20px 0 15px;border-left:4px solid #1677ff;padding-left:12px}input,select{width:100%;padding:12px 16px;margin-bottom:12px;border-radius:40px;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.6)}button{background:linear-gradient(90deg,#1677ff,#0958d9);color:white;border:none;padding:12px 28px;border-radius:40px;cursor:pointer;width:100%}.error{background:#ffeaea;border-left:4px solid #b22222;padding:12px;margin-bottom:20px;color:#b22222}.success{background:#e6f9ed;border-left:4px solid #1e6f3f;padding:12px;margin-bottom:20px;color:#1e6f3f}</style></head>
<body><div class="container"><h2>🚀 系统安装向导</h2><p style="margin-bottom:20px">填写以下信息完成安装。</p>
<?php if ($error): ?><div class="error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?><?php if ($success): ?><div class="success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<form method="post"><h3>📊 数据库配置</h3><input type="text" name="db_host" placeholder="数据库主机" value="localhost"><input type="text" name="db_name" placeholder="数据库名称"><input type="text" name="db_user" placeholder="数据库用户名"><input type="password" name="db_pass" placeholder="数据库密码">
<h3>👨‍💼 管理员账号</h3><input type="text" name="admin_user" placeholder="管理员用户名" value="admin"><input type="password" name="admin_pass" placeholder="管理员密码（至少6位）">
<h3>🌐 网站信息</h3><input type="text" name="site_name" placeholder="网站名称" value="云上云诺">
<h3>📧 SMTP 邮件配置（可选）</h3><input type="text" name="smtp_host" placeholder="SMTP 服务器" value="smtp.qq.com"><input type="text" name="smtp_port" placeholder="端口" value="465"><input type="text" name="smtp_user" placeholder="SMTP 用户名"><input type="password" name="smtp_pass" placeholder="SMTP 密码/授权码"><select name="smtp_secure"><option value="ssl">SSL</option><option value="tls">TLS</option></select><input type="text" name="smtp_from_email" placeholder="发件人邮箱"><input type="text" name="smtp_from_name" placeholder="发件人名称" value="云上云诺">
<button type="submit">🔧 开始安装</button></form></div></body></html>