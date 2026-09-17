<?php
// functions.php - 核心功能
session_start();

// 手动引入 PHPMailer（仅当文件存在时）
if (file_exists(__DIR__ . '/phpmailer/Exception.php')) {
    require_once __DIR__ . '/phpmailer/Exception.php';
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ========== 安装检测 ==========
$isInstallPage = (strpos($_SERVER['PHP_SELF'], '/install/') !== false) || 
                 (basename($_SERVER['PHP_SELF']) === 'index.php' && !file_exists(__DIR__ . '/config.php'));

if (!file_exists(__DIR__ . '/config.php') && !$isInstallPage) {
    header('Location: /install/');
    exit;
}

// 加载数据库配置
$db_config = null;
if (file_exists(__DIR__ . '/config.php')) {
    $db_config = require __DIR__ . '/config.php';
}

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        global $db_config;
        if ($db_config === null) return null;
        try {
            $pdo = new PDO(
                "mysql:host={$db_config['db_host']};dbname={$db_config['db_name']};charset=utf8mb4",
                $db_config['db_user'],
                $db_config['db_pass']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            if (!file_exists(__DIR__ . '/install/install.lock')) return null;
            die('数据库连接失败：' . $e->getMessage());
        }
    }
    return $pdo;
}

// 获取网站配置
function getConfig($key = null) {
    $pdo = getDB();
    
    $defaultConfig = [
        'site_name' => '单页工坊Pages',
        'site_logo' => 'https://www.xhehm.com/assets/img/LOGO-Pages.png',
        'site_footer' => '© 2026 单页工坊Pages - 静态页面托管平台',
        'stats_projects' => '100+',
        'stats_features' => '10+',
        'tagline' => '轻松托管，全程赋能',
        'tags' => '企业级托管服务 上线更快|无需服务器 上传即用|新用户永久免费福利',
        'smtp_host' => '',
        'smtp_port' => '465',
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_secure' => 'ssl',
        'smtp_from_email' => '',
        'smtp_from_name' => '单页工坊Pages',
        'sms_api_url' => '',
        'sms_api_key' => '',
        'sms_enabled' => '0',
        'email_verify_enabled' => '1',
        'sms_verify_enabled' => '0',
        'force_bind_phone' => '0',
        'default_quota' => '500'
    ];
    
    if ($pdo === null) {
        if ($key === null) return $defaultConfig;
        return $defaultConfig[$key] ?? '';
    }
    
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM site_config");
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['key']] = $row['value'];
        }
        $config = array_merge($defaultConfig, $config);
        if ($key === null) return $config;
        return $config[$key] ?? '';
    } catch (PDOException $e) {
        if ($key === null) return $defaultConfig;
        return $defaultConfig[$key] ?? '';
    }
}

// 更新配置
function updateConfig($key, $value) {
    $pdo = getDB();
    if ($pdo === null) return false;
    $stmt = $pdo->prepare("REPLACE INTO site_config (`key`, `value`) VALUES (?, ?)");
    return $stmt->execute([$key, $value]);
}

// ========== 用户相关 ==========
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $pdo = getDB();
    if ($pdo === null) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function login($login, $password) {
    $pdo = getDB();
    if ($pdo === null) return false;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    return false;
}

function register($username, $email, $password, $phone = null) {
    $pdo = getDB();
    if ($pdo === null) return false;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) return false;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, phone, registered_at) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$username, $email, $hash, $phone, time()]);
}

function changePassword($userId, $newPassword) {
    $pdo = getDB();
    if ($pdo === null) return false;
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([$hash, $userId]);
}

function generateResetToken($email) {
    $pdo = getDB();
    if ($pdo === null) return false;
    $token = bin2hex(random_bytes(32));
    $expire = time() + 3600;
    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expire = ? WHERE email = ?");
    if ($stmt->execute([$token, $expire, $email]) && $stmt->rowCount() > 0) {
        return $token;
    }
    return false;
}

function verifyResetToken($token) {
    $pdo = getDB();
    if ($pdo === null) return false;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expire > ?");
    $stmt->execute([$token, time()]);
    return $stmt->fetch();
}

function clearResetToken($userId) {
    $pdo = getDB();
    if ($pdo === null) return false;
    $stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, reset_expire = NULL WHERE id = ?");
    return $stmt->execute([$userId]);
}

// ========== 邮件发送 ==========
function sendMail($to, $subject, $body) {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer 未安装");
        return false;
    }
    $config = getConfig();
    if (empty($config['smtp_host']) || empty($config['smtp_user']) || empty($config['smtp_pass'])) {
        error_log("SMTP 配置不完整");
        return false;
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user'];
        $mail->Password   = $config['smtp_pass'];
        $mail->SMTPSecure = $config['smtp_secure'] ?? 'ssl';
        $mail->Port       = $config['smtp_port'] ?? 465;
        
        // 设置字符集
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        $fromEmail = $config['smtp_from_email'] ?? $config['smtp_user'];
        $fromName  = $config['smtp_from_name'] ?? $config['site_name'] ?? '单页工坊Pages';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        // 设置纯文本备用内容
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>', '</h2>'], "\n", $body));
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("邮件发送失败: " . ($mail->ErrorInfo ?? $e->getMessage()));
        return false;
    }
}

// ========== 短信验证码 ==========
function getSmsConfig() {
    $config = getConfig();
    return [
        'api_url' => $config['sms_api_url'] ?? '',
        'api_key' => $config['sms_api_key'] ?? '',
        'enabled' => $config['sms_enabled'] ?? '0'
    ];
}

function sendSmsCode($phone, $code) {
    $config = getSmsConfig();
    if ($config['enabled'] != '1') {
        return ['success' => false, 'message' => '短信功能未启用'];
    }
    if (empty($config['api_url']) || empty($config['api_key'])) {
        return ['success' => false, 'message' => '短信接口未配置'];
    }
    $params = ['api_key' => $config['api_key'], 'phone' => $phone, 'code' => $code];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['api_url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    if ($result === false) {
        return ['success' => false, 'message' => '网络请求失败'];
    }
    $response = json_decode($result, true);
    if ($response && isset($response['success'])) {
        return $response;
    }
    return ['success' => false, 'message' => '接口响应异常'];
}

function generateSmsCode() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function storeSmsCode($key, $code) {
    $_SESSION['sms'][$key] = ['code' => $code, 'expire' => time() + 300];
}

function verifySmsCode($key, $code) {
    if (!isset($_SESSION['sms'][$key])) return false;
    $data = $_SESSION['sms'][$key];
    if ($data['expire'] < time()) {
        unset($_SESSION['sms'][$key]);
        return false;
    }
    if ($data['code'] !== $code) return false;
    unset($_SESSION['sms'][$key]);
    return true;
}

function canSendSms($phone) {
    if (!isset($_SESSION['sms_last_send'][$phone])) return true;
    return $_SESSION['sms_last_send'][$phone] < time() - 60;
}

function recordSmsSend($phone) {
    $_SESSION['sms_last_send'][$phone] = time();
}

// ========== 验证码配置检查 ==========

// 检查是否启用邮箱验证码
function isEmailVerifyEnabled() {
    $config = getConfig();
    return ($config['email_verify_enabled'] ?? '1') == '1';
}

// 检查是否启用短信验证码
function isSmsVerifyEnabled() {
    $config = getConfig();
    $smsEnabled = $config['sms_enabled'] ?? '0';
    $smsVerifyEnabled = $config['sms_verify_enabled'] ?? '0';
    return ($smsEnabled == '1' && $smsVerifyEnabled == '1');
}

// 检查是否强制绑定手机号
function isForceBindPhone() {
    $config = getConfig();
    return ($config['force_bind_phone'] ?? '0') == '1';
}

// 检查用户是否已绑定手机号
function isPhoneBound($userId = null) {
    if ($userId === null) {
        $user = getCurrentUser();
        if (!$user) return false;
        $userId = $user['id'];
    }
    $pdo = getDB();
    if ($pdo === null) return false;
    $stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return !empty($user['phone']);
}

// 绑定手机号
function bindPhone($userId, $phone) {
    $pdo = getDB();
    if ($pdo === null) return false;
    $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
    return $stmt->execute([$phone, $userId]);
}

// 检查是否需要强制绑定手机号
function checkForceBindPhone() {
    if (!isLoggedIn()) return true;
    if (isForceBindPhone() && !isPhoneBound()) {
        header('Location: /users/profile.php?require_bind=1');
        exit;
    }
    return true;
}

// 获取用户配额
function getUserQuota($userId = null) {
    if ($userId === null) {
        $user = getCurrentUser();
        if (!$user) return 500;
        $userId = $user['id'];
    }
    $pdo = getDB();
    if ($pdo === null) return 500;
    $stmt = $pdo->prepare("SELECT quota FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user['quota'] ?? 500;
}

// 检查用户空间是否足够
function checkUserQuota($userId, $fileSize) {
    $quota = getUserQuota($userId) * 1048576;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT SUM(LENGTH(content)) as total_size FROM project_files WHERE pro_id IN (SELECT pro_id FROM projects WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $used = $stmt->fetch()['total_size'] ?? 0;
    return ($used + $fileSize) <= $quota;
}

// ========== 项目相关 ==========
function getUserProjects($userId) {
    $pdo = getDB();
    if ($pdo === null) return [];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createProject($userId, $name, $description = '') {
    $pdo = getDB();
    if ($pdo === null) {
        error_log("createProject: 数据库连接失败");
        return false;
    }
    
    // 获取用户名
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        error_log("createProject: 找不到用户 ID: $userId");
        return false;
    }
    
    $username = $user['username'];
    
    // 生成唯一 pro_id
    do {
        $proId = substr(bin2hex(random_bytes(8)), 0, 8);
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE pro_id = ?");
        $stmt->execute([$proId]);
    } while ($stmt->fetch());
    
    // 插入数据库
    $stmt = $pdo->prepare("INSERT INTO projects (pro_id, user_id, name, description, created_at) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt->execute([$proId, $userId, $name, $description, time()])) {
        error_log("createProject: 插入项目失败");
        return false;
    }
    
    // 创建文件目录
    $usersDir = __DIR__ . "/users";
    if (!is_dir($usersDir)) {
        mkdir($usersDir, 0755, true);
    }
    
    $userDir = $usersDir . "/$username";
    if (!is_dir($userDir)) {
        mkdir($userDir, 0755, true);
    }
    
    $projectsDir = $userDir . "/projects";
    if (!is_dir($projectsDir)) {
        mkdir($projectsDir, 0755, true);
    }
    
    $projectDir = $projectsDir . "/$proId";
    if (!is_dir($projectDir)) {
        mkdir($projectDir, 0755, true);
    }
    
    // 创建默认 index.html
    $defaultContent = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($name) . '</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <h1>' . htmlspecialchars($name) . '</h1>
    <p>欢迎来到你的新项目。</p>
    <p>访问链接：/'.$proId.'</p>
    <p>由<a href="https://net.xhhe.cn">单页工坊Pages</a>提供服务</p>
</body>
</html>';
    
    $filePath = $projectDir . "/index.html";
    file_put_contents($filePath, $defaultContent);
    
    // 存入数据库
    addOrUpdateFile($proId, 'index.html', $defaultContent, 1);
    
    return $proId;
}

function deleteProject($proId, $userId) {
    $pdo = getDB();
    if ($pdo === null) return false;
    
    $stmt = $pdo->prepare("SELECT p.*, u.username FROM projects p JOIN users u ON p.user_id = u.id WHERE p.pro_id = ? AND p.user_id = ?");
    $stmt->execute([$proId, $userId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        $projectDir = __DIR__ . "/users/{$project['username']}/projects/$proId";
        if (is_dir($projectDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($projectDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($projectDir);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM project_files WHERE pro_id = ?");
    $stmt->execute([$proId]);
    $stmt = $pdo->prepare("DELETE FROM projects WHERE pro_id = ? AND user_id = ?");
    return $stmt->execute([$proId, $userId]);
}

function getProjectByProId($proId) {
    $pdo = getDB();
    if ($pdo === null) return null;
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE pro_id = ?");
    $stmt->execute([$proId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getProjectFiles($proId) {
    $pdo = getDB();
    if ($pdo === null) return [];
    $stmt = $pdo->prepare("SELECT * FROM project_files WHERE pro_id = ? ORDER BY is_index DESC, filename");
    $stmt->execute([$proId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addOrUpdateFile($proId, $filename, $content, $isIndex = 0) {
    $pdo = getDB();
    if ($pdo === null) return false;
    
    $stmt = $pdo->prepare("SELECT p.*, u.username FROM projects p JOIN users u ON p.user_id = u.id WHERE p.pro_id = ?");
    $stmt->execute([$proId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        $projectDir = __DIR__ . "/users/{$project['username']}/projects/$proId";
        if (!is_dir($projectDir)) {
            mkdir($projectDir, 0755, true);
        }
        $filePath = $projectDir . "/" . $filename;
        file_put_contents($filePath, $content);
    }
    
    $stmt = $pdo->prepare("SELECT id FROM project_files WHERE pro_id = ? AND filename = ?");
    $stmt->execute([$proId, $filename]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE project_files SET content = ?, updated_at = ?, is_index = ? WHERE pro_id = ? AND filename = ?");
        return $stmt->execute([$content, time(), $isIndex, $proId, $filename]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO project_files (pro_id, filename, content, is_index, updated_at) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$proId, $filename, $content, $isIndex, time()]);
    }
}

function deleteFile($proId, $filename) {
    $pdo = getDB();
    if ($pdo === null) return false;
    
    $stmt = $pdo->prepare("SELECT p.*, u.username FROM projects p JOIN users u ON p.user_id = u.id WHERE p.pro_id = ?");
    $stmt->execute([$proId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        $filePath = __DIR__ . "/users/{$project['username']}/projects/$proId/" . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM project_files WHERE pro_id = ? AND filename = ?");
    return $stmt->execute([$proId, $filename]);
}

function getFileContent($proId, $filename) {
    $pdo = getDB();
    if ($pdo === null) return null;
    
    $stmt = $pdo->prepare("SELECT p.*, u.username FROM projects p JOIN users u ON p.user_id = u.id WHERE p.pro_id = ?");
    $stmt->execute([$proId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        $filePath = __DIR__ . "/users/{$project['username']}/projects/$proId/" . $filename;
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }
    }
    
    $stmt = $pdo->prepare("SELECT content FROM project_files WHERE pro_id = ? AND filename = ?");
    $stmt->execute([$proId, $filename]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['content'] : null;
}

function isProjectOwner($proId, $userId) {
    $pdo = getDB();
    if ($pdo === null) return false;
    $stmt = $pdo->prepare("SELECT id FROM projects WHERE pro_id = ? AND user_id = ?");
    $stmt->execute([$proId, $userId]);
    return $stmt->fetch() !== false;
}

function getUserStats($userId) {
    $pdo = getDB();
    if ($pdo === null) return ['project_count' => 0, 'total_size_mb' => 0];
    $stmt = $pdo->prepare("SELECT COUNT(*) as project_count FROM projects WHERE user_id = ?");
    $stmt->execute([$userId]);
    $projectCount = $stmt->fetch(PDO::FETCH_ASSOC)['project_count'];
    $stmt = $pdo->prepare("SELECT SUM(LENGTH(content)) as total_size FROM project_files WHERE pro_id IN (SELECT pro_id FROM projects WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $totalSize = $stmt->fetch(PDO::FETCH_ASSOC)['total_size'] ?? 0;
    return ['project_count' => $projectCount, 'total_size_mb' => round($totalSize / 1048576, 2)];
}

function writeLog($message, $type = 'error') {
    $logDir = __DIR__ . '/log';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . '/' . date('Y-m-d') . '.log';
    $logMessage = "[" . date('Y-m-d H:i:s') . "][$type] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// ========== 游客项目相关 ==========

/**
 * 生成游客项目及管理密钥
 */
function createGuestProject() {
    $pdo = getDB();
    if ($pdo === null) return false;

    do {
        $proId = 'g' . substr(bin2hex(random_bytes(6)), 0, 7);
        $stmt = $pdo->prepare("SELECT id FROM guest_projects WHERE pro_id = ?");
        $stmt->execute([$proId]);
    } while ($stmt->fetch());

    do {
        $accessKey = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("SELECT id FROM guest_projects WHERE access_key = ?");
        $stmt->execute([$accessKey]);
    } while ($stmt->fetch());

    $stmt = $pdo->prepare("INSERT INTO guest_projects (pro_id, access_key, name, created_at, updated_at) VALUES (?, ?, 'guest_project', ?, ?)");
    $ok = $stmt->execute([$proId, $accessKey, time(), time()]);

    if (!$ok) return false;

    $dir = __DIR__ . "/users/guests/projects/$proId";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    return ['pro_id' => $proId, 'access_key' => $accessKey];
}

/**
 * 通过 access_key 获取游客项目
 */
function getGuestProjectByKey($accessKey) {
    $pdo = getDB();
    if ($pdo === null) return null;
    $stmt = $pdo->prepare("SELECT * FROM guest_projects WHERE access_key = ?");
    $stmt->execute([$accessKey]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 通过 pro_id 获取游客项目
 */
function getGuestProjectByProId($proId) {
    $pdo = getDB();
    if ($pdo === null) return null;
    $stmt = $pdo->prepare("SELECT * FROM guest_projects WHERE pro_id = ?");
    $stmt->execute([$proId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 更新游客项目时间戳和统计
 */
function touchGuestProject($proId) {
    $pdo = getDB();
    if ($pdo === null) return false;
    $dir = __DIR__ . "/users/guests/projects/$proId";
    $count = 0; $size = 0;
    if (is_dir($dir)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($files as $f) {
            if ($f->isFile()) { $count++; $size += $f->getSize(); }
        }
    }
    $stmt = $pdo->prepare("UPDATE guest_projects SET updated_at = ?, file_count = ?, total_size = ? WHERE pro_id = ?");
    return $stmt->execute([time(), $count, $size, $proId]);
}

// ========== ZIP 安全解压 ==========

/**
 * 安全解压 zip 到指定目录
 */
function safeExtractZip($zipPath, $destDir, $maxFiles = 200) {
    if (!class_exists('ZipArchive')) {
        return ['success' => false, 'message' => '服务器未启用 ZipArchive 扩展', 'files' => 0];
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['success' => false, 'message' => '无法打开 zip 文件', 'files' => 0];
    }

    if ($zip->numFiles > $maxFiles) {
        $zip->close();
        return ['success' => false, 'message' => "zip 内文件数超过限制（最多 {$maxFiles} 个）", 'files' => 0];
    }

    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $extracted = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);

        if (strpos($name, '..') !== false
            || strpos($name, "\0") !== false
            || strpos($name, '/') === 0
            || strpos($name, '\\') === 0
            || preg_match('#^[a-zA-Z]:#', $name)
        ) {
            continue;
        }

        if (strpos($name, '__MACOSX') !== false || strpos($name, '.DS_Store') !== false) {
            continue;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext && !in_array($ext, getAllowedFileExtensions())) {
            continue;
        }

        if (substr($name, -1) === '/') continue;

        if ($zip->extractTo($destDir, $name)) {
            $extracted++;
        }
    }
    $zip->close();

    if ($extracted === 0) {
        return ['success' => false, 'message' => 'zip 内没有可用的 HTML/静态文件', 'files' => 0];
    }

    return ['success' => true, 'message' => "成功解压 {$extracted} 个文件", 'files' => $extracted];
}

/**
 * 允许上传的文件扩展名白名单
 */
function getAllowedFileExtensions() {
    return [
        'html', 'htm', 'css', 'js', 'json', 'txt', 'xml',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico',
        'woff', 'woff2', 'ttf', 'eot', 'mp3', 'mp4', 'webm',
        'pdf'
    ];
}

/**
 * 禁止上传的扩展名黑名单
 */
function getDeniedFileExtensions() {
    return [
        'php', 'phtml', 'php3', 'php4', 'php5', 'phar',
        'exe', 'bat', 'cmd', 'sh', 'bash', 'ps1', 'vbs',
        'jsp', 'asp', 'aspx', 'cgi', 'pl', 'py', 'rb',
        'com', 'scr', 'msi', 'dll', 'so', 'apk', 'ipa'
    ];
}

/**
 * 校验上传文件合法性
 */
function validateUploadFile($file) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'message' => '上传无效', 'ext' => ''];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = match($file['error']) {
            UPLOAD_ERR_INI_SIZE => '文件超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件上传不完整',
            default => '上传失败（错误码 ' . $file['error'] . '）'
        };
        return ['valid' => false, 'message' => $msg, 'ext' => ''];
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return ['valid' => false, 'message' => '文件大小超过 2MB 限制', 'ext' => ''];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (in_array($ext, getDeniedFileExtensions())) {
        return ['valid' => false, 'message' => "禁止上传 .{$ext} 类型文件", 'ext' => $ext];
    }

    if (!in_array($ext, getAllowedFileExtensions())) {
        return ['valid' => false, 'message' => "不支持 .{$ext} 类型", 'ext' => $ext];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'text/html', 'text/css', 'text/javascript', 'application/javascript',
        'application/json', 'text/plain', 'text/xml', 'application/xml',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'image/x-icon', 'image/vnd.microsoft.icon',
        'font/woff', 'font/woff2', 'font/ttf', 'font/otf',
        'audio/mpeg', 'video/mp4', 'video/webm', 'application/pdf',
    ];

    $mimeOk = false;
    foreach ($allowedMimes as $am) {
        if (strpos($mime, $am) === 0 || strpos($am, $mime) === 0) { $mimeOk = true; break; }
    }
    if (!$mimeOk && in_array($ext, ['svg', 'css', 'js', 'json', 'txt', 'xml', 'html', 'htm'])) {
        $mimeOk = true;
    }

    if (!$mimeOk) {
        return ['valid' => false, 'message' => "文件内容类型不匹配（检测到 {$mime}）", 'ext' => $ext];
    }

    return ['valid' => true, 'message' => 'ok', 'ext' => $ext];
}

/**
 * 获取游客项目文件内容
 */
function getGuestFileContent($proId, $filename) {
    $dir = __DIR__ . "/users/guests/projects/$proId";
    $filePath = $dir . '/' . $filename;
    if (file_exists($filePath) && is_file($filePath)) {
        return file_get_contents($filePath);
    }
    return null;
}

/**
 * 列出游客项目所有文件
 */
function getGuestProjectFiles($proId) {
    $dir = __DIR__ . "/users/guests/projects/$proId";
    $files = [];
    if (!is_dir($dir)) return $files;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relative = str_replace($dir . '/', '', $file->getPathname());
            $files[] = [
                'filename' => $relative,
                'size' => $file->getSize(),
                'updated_at' => $file->getMTime(),
                'is_index' => ($relative === 'index.html') ? 1 : 0,
            ];
        }
    }
    usort($files, fn($a, $b) => $b['is_index'] - $a['is_index']);
    return $files;
}

/**
 * 清空游客项目所有文件
 */
function clearGuestProjectFiles($proId) {
    $dir = __DIR__ . "/users/guests/projects/$proId";
    if (!is_dir($dir)) return true;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isDir()) rmdir($file->getRealPath());
        else unlink($file->getRealPath());
    }
    return true;
}

// ========== 背景颜色功能 ==========
function getUserBgColor($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT bg_color FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result ? $result['bg_color'] : null;
}

function setUserBgColor($userId, $color) {
    $pdo = getDB();
    // 验证颜色格式（支持#hex、rgb、rgba、渐变）
    if (empty($color)) {
        $stmt = $pdo->prepare("UPDATE users SET bg_color = NULL WHERE id = ?");
        return $stmt->execute([$userId]);
    }
    // 简单验证：只允许安全的颜色值
    if (!preg_match('/^[#a-zA-Z0-9(),\s.%\-rgba]+$/i', $color)) {
        return false;
    }
    $stmt = $pdo->prepare("UPDATE users SET bg_color = ? WHERE id = ?");
    return $stmt->execute([$color, $userId]);
}

// 输出用户背景颜色样式（在用户页面的</head>前调用）
function outputUserBgStyle() {
    if (!isLoggedIn()) return '';
    $user = getCurrentUser();
    if (empty($user['bg_color'])) return '';
    $color = htmlspecialchars($user['bg_color'], ENT_QUOTES);
    return "<style>body { background: {$color} !important; background-attachment: fixed !important; }</style>";
}


// ========== 用户组功能 ==========
function getUserGroup($groupId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM user_groups WHERE id = ?");
    $stmt->execute([$groupId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAllUserGroups() {
    $pdo = getDB();
    return $pdo->query("SELECT * FROM user_groups ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
}

function getDefaultUserGroup() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM user_groups WHERE is_default = 1 LIMIT 1");
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$group) {
        // 如果没有默认组，返回第一个组
        $stmt = $pdo->query("SELECT * FROM user_groups ORDER BY id ASC LIMIT 1");
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return $group;
}

function getUserProjectLimit($userId) {
    $user = getCurrentUser();
    if (!$user || empty($user['group_id'])) {
        $defaultGroup = getDefaultUserGroup();
        return $defaultGroup ? $defaultGroup['project_limit'] : 5;
    }
    $group = getUserGroup($user['group_id']);
    return $group ? $group['project_limit'] : 5;
}

function getUserProjectCount($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function canCreateProject($userId) {
    $limit = getUserProjectLimit($userId);
    $count = getUserProjectCount($userId);
    return $count < $limit;
}

function createUserGroup($name, $projectLimit, $description = '') {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO user_groups (name, project_limit, description, is_default, created_at) VALUES (?, ?, ?, 0, ?)");
    return $stmt->execute([$name, $projectLimit, $description, time()]);
}

function updateUserGroup($groupId, $name, $projectLimit, $description = '') {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE user_groups SET name = ?, project_limit = ?, description = ? WHERE id = ?");
    return $stmt->execute([$name, $projectLimit, $description, $groupId]);
}

function deleteUserGroup($groupId) {
    $pdo = getDB();
    // 检查是否是默认组
    $group = getUserGroup($groupId);
    if ($group && $group['is_default']) {
        return false; // 不能删除默认组
    }
    // 把该组的用户移到默认组
    $defaultGroup = getDefaultUserGroup();
    if ($defaultGroup) {
        $pdo->prepare("UPDATE users SET group_id = ? WHERE group_id = ?")->execute([$defaultGroup['id'], $groupId]);
    }
    $stmt = $pdo->prepare("DELETE FROM user_groups WHERE id = ?");
    return $stmt->execute([$groupId]);
}

function setUserGroup($userId, $groupId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET group_id = ? WHERE id = ?");
    return $stmt->execute([$groupId, $userId]);
}


// ========== 易支付功能 ==========
function getYiPayConfig() {
    $pdo = getDB();
    $config = [];
    $keys = ['yipay_url', 'yipay_pid', 'yipay_key'];
    foreach ($keys as $key) {
        $stmt = $pdo->prepare("SELECT value FROM site_config WHERE `key` = ?");
        $stmt->execute([$key]);
        $config[$key] = $stmt->fetchColumn();
    }
    return $config;
}

function generateOrderNo() {
    return date('YmdHis') . rand(1000, 9999);
}

function createOrder($userId, $groupId, $money, $type = null) {
    $pdo = getDB();
    $outTradeNo = generateOrderNo();
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, group_id, out_trade_no, money, type, status, created_at) VALUES (?, ?, ?, ?, ?, 0, ?)");
    $stmt->execute([$userId, $groupId, $outTradeNo, $money, $type, time()]);
    return $outTradeNo;
}

function getOrderByOutTradeNo($outTradeNo) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE out_trade_no = ?");
    $stmt->execute([$outTradeNo]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getYiPaySign($params, $key) {
    ksort($params);
    $signStr = '';
    foreach ($params as $k => $v) {
        if ($k != 'sign' && $k != 'sign_type' && $v !== '') {
            $signStr .= $k . '=' . $v . '&';
        }
    }
    $signStr = rtrim($signStr, '&');
    $signStr .= $key;
    return md5($signStr);
}

function verifyYiPaySign($params) {
    $config = getYiPayConfig();
    if (empty($config['yipay_key'])) return false;
    $sign = getYiPaySign($params, $config['yipay_key']);
    return $sign === ($params['sign'] ?? '');
}

function processPaidOrder($outTradeNo, $tradeNo) {
    $pdo = getDB();
    $order = getOrderByOutTradeNo($outTradeNo);
    if (!$order || $order['status'] == 1) return false;
    
    // 更新订单状态
    $stmt = $pdo->prepare("UPDATE orders SET status = 1, trade_no = ?, paid_at = ? WHERE out_trade_no = ?");
    $stmt->execute([$tradeNo, time(), $outTradeNo]);
    
    // 更新用户组
    $group = getUserGroup($order['group_id']);
    if ($group) {
        $expireAt = null;
        if ($group['duration'] > 0) {
            $expireAt = time() + ($group['duration'] * 86400);
        }
        $stmt = $pdo->prepare("UPDATE users SET group_id = ?, group_expire_at = ? WHERE id = ?");
        $stmt->execute([$order['group_id'], $expireAt, $order['user_id']]);
        
        // 更新订单的到期时间
        $stmt = $pdo->prepare("UPDATE orders SET expire_at = ? WHERE out_trade_no = ?");
        $stmt->execute([$expireAt, $outTradeNo]);
    }
    
    return true;
}

function checkUserGroupExpire($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT group_id, group_expire_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['group_expire_at'] && $user['group_expire_at'] < time()) {
        // 到期了，降为免费组
        $defaultGroup = getDefaultUserGroup();
        if ($defaultGroup) {
            $stmt = $pdo->prepare("UPDATE users SET group_id = ?, group_expire_at = NULL WHERE id = ?");
            $stmt->execute([$defaultGroup['id'], $userId]);
        }
    }
}

function getPublicGroups() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM user_groups WHERE is_public = 1 ORDER BY price ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserOrders($userId, $limit = 20) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT o.*, g.name as group_name FROM orders o LEFT JOIN user_groups g ON o.group_id = g.id WHERE o.user_id = ? ORDER BY o.id DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
