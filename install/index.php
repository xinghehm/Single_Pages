<?php
session_start();

if (file_exists('../config.php') && file_exists('install.lock')) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';
$installCount = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    $admin_user = trim($_POST['admin_user'] ?? 'admin');
    $admin_pass = $_POST['admin_pass'] ?? '';
    $site_name = trim($_POST['site_name'] ?? '单页工坊Pages');
    
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = trim($_POST['smtp_port'] ?? '465');
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    $smtp_secure = $_POST['smtp_secure'] ?? 'ssl';
    $smtp_from_email = trim($_POST['smtp_from_email'] ?? '');
    $smtp_from_name = trim($_POST['smtp_from_name'] ?? '单页工坊Pages');
    
    $sms_api_url = trim($_POST['sms_api_url'] ?? 'https://sms.losels.eu.org/api/send.php');
    $sms_api_key = trim($_POST['sms_api_key'] ?? '');
    $sms_enabled = isset($_POST['sms_enabled']) ? '1' : '0';
    
    if (empty($db_name)) {
        $error = '请填写数据库名称';
    } elseif (empty($db_user)) {
        $error = '请填写数据库用户名';
    } elseif (strlen($admin_pass) < 6) {
        $error = '管理员密码至少6位';
    } else {
        try {
            $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
            $pdo->exec("USE `$db_name`");
            
            $sql = file_get_contents(__DIR__ . '/install.sql');
            $pdo->exec($sql);
            
            // 更新管理员密码
            $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
            $stmt->execute([$hash, $admin_user]);
            
            // 更新网站名称
            $stmt = $pdo->prepare("UPDATE site_config SET value = ? WHERE `key` = 'site_name'");
            $stmt->execute([$site_name]);
            
            // 保存 SMTP 配置
            $smtpConfigs = [
                'smtp_host' => $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_user' => $smtp_user,
                'smtp_pass' => $smtp_pass,
                'smtp_secure' => $smtp_secure,
                'smtp_from_email' => $smtp_from_email,
                'smtp_from_name' => $smtp_from_name
            ];
            foreach ($smtpConfigs as $key => $value) {
                $stmt = $pdo->prepare("REPLACE INTO site_config (`key`, `value`) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
            
            // 保存短信配置
            $smsConfigs = [
                'sms_api_url' => $sms_api_url,
                'sms_api_key' => $sms_api_key,
                'sms_enabled' => $sms_enabled,
                'email_verify_enabled' => '1',
                'sms_verify_enabled' => $sms_enabled,
                'force_bind_phone' => '0'
            ];
            foreach ($smsConfigs as $key => $value) {
                $stmt = $pdo->prepare("REPLACE INTO site_config (`key`, `value`) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
            
            // 生成配置文件
            $config_content = "<?php\nreturn [\n    'db_host' => '" . addslashes($db_host) . "',\n    'db_name' => '" . addslashes($db_name) . "',\n    'db_user' => '" . addslashes($db_user) . "',\n    'db_pass' => '" . addslashes($db_pass) . "',\n];";
            file_put_contents('../config.php', $config_content);
            
            // 创建 install.lock
            file_put_contents('install.lock', date('Y-m-d H:i:s'));
            
            // 发送安装计数（不影响安装成功）
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://www.xhehm.com/api/projects/Pages/icountx.php');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError === '' && preg_match('/^OK\s+(\d+)$/', trim($response), $matches)) {
                    $installCount = $matches[1];
                }
            }
            
            $success = '安装成功！';
            if ($installCount) {
                $success .= ' 您是第 ' . $installCount . ' 个安装本系统的。';
            }
            $success .= ' 3秒后跳转到前台首页...';
            header('refresh:3;url=../index.php');
        } catch (PDOException $e) {
            $error = '安装失败：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - 单页工坊Pages</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #f0f5ff 0%, #e0e9f7 100%);
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 650px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(30px);
            border-radius: 24px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
        }
        h2 { color: #0b3b5c; margin-bottom: 10px; font-size: 28px; }
        h3 { color: #1e4a7a; margin: 20px 0 15px; font-size: 18px; border-left: 4px solid #1677ff; padding-left: 12px; }
        input, select {
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 12px;
            border-radius: 40px;
            border: 1px solid rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            outline: none;
        }
        input:focus, select:focus { border-color: #1677ff; background: white; }
        button {
            background: linear-gradient(90deg, #1677ff 0%, #0958d9 100%);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 40px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            margin-top: 10px;
        }
        .error { background: #ffeaea; border-left: 4px solid #b22222; padding: 12px; margin-bottom: 20px; color: #b22222; border-radius: 12px; }
        .success { background: #e6f9ed; border-left: 4px solid #1e6f3f; padding: 12px; margin-bottom: 20px; color: #1e6f3f; border-radius: 12px; }
        hr { margin: 20px 0; border: none; border-top: 1px solid rgba(0,0,0,0.1); }
        .info { background: #e6f4ff; padding: 10px 15px; border-radius: 12px; font-size: 13px; color: #0958d9; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h2>单页工坊 Pages 安装向导</h2>
    <p class="sub-desc" style="color:#666; margin-bottom:20px;">填写以下信息完成安装。</p>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <h3>数据库配置</h3>
        <input type="text" name="db_host" placeholder="数据库主机" value="localhost" required>
        <input type="text" name="db_name" placeholder="数据库名称" required>
        <input type="text" name="db_user" placeholder="数据库用户名" required>
        <input type="password" name="db_pass" placeholder="数据库密码">
        
        <h3>管理员账号</h3>
        <input type="text" name="admin_user" placeholder="管理员用户名" value="admin" required>
        <input type="password" name="admin_pass" placeholder="管理员密码（至少6位）" required>
        
        <h3>网站信息</h3>
        <input type="text" name="site_name" placeholder="网站名称" value="单页工坊Pages" required>
        
        <h3>邮件 SMTP 配置（可选）</h3>
        <input type="text" name="smtp_host" placeholder="SMTP 服务器" value="">
        <input type="text" name="smtp_port" placeholder="端口" value="465">
        <input type="text" name="smtp_user" placeholder="用户名">
        <input type="password" name="smtp_pass" placeholder="密码/授权码">
        <select name="smtp_secure">
            <option value="ssl">SSL</option>
            <option value="tls">TLS</option>
        </select>
        <input type="text" name="smtp_from_email" placeholder="发件人邮箱">
        <input type="text" name="smtp_from_name" placeholder="发件人名称" value="单页工坊Pages">
        
        <h3>短信配置（可选）</h3>
        <div class="info">短信接口地址固定为 https://sms.losels.eu.org/api/send.php，API Key 请到该平台注册获取。</div>
        <input type="text" name="sms_api_url" placeholder="短信 API 地址" value="https://sms.losels.eu.org/api/send.php">
        <input type="text" name="sms_api_key" placeholder="API Key">
        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
            <input type="checkbox" name="sms_enabled" value="1"> 启用短信验证码功能
        </label>
        
        <button type="submit">开始安装</button>
    </form>
</div>
</body>
</html>