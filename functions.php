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

// ============================================================
// v2.4 新增功能函数
// ============================================================

// ========== CSRF 防护 ==========
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}
function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
        return false;
    }
    return true;
}

// ========== 登录安全（失败次数限制） ==========
function getLoginAttempts($identifier) {
    $key = 'login_attempt_' . md5($identifier);
    return $_SESSION[$key] ?? 0;
}
function recordLoginAttempt($identifier) {
    $key = 'login_attempt_' . md5($identifier);
    if (!isset($_SESSION[$key])) $_SESSION[$key] = 0;
    $_SESSION[$key]++;
    $_SESSION[$key . '_time'] = time();
}
function clearLoginAttempts($identifier) {
    $key = 'login_attempt_' . md5($identifier);
    unset($_SESSION[$key], $_SESSION[$key . '_time']);
}
function isLoginLocked($identifier) {
    $max = intval(getConfig('login_max_attempts') ?: 5);
    $lockMin = intval(getConfig('login_lock_minutes') ?: 15);
    $key = 'login_attempt_' . md5($identifier);
    $attempts = $_SESSION[$key] ?? 0;
    $lastTime = $_SESSION[$key . '_time'] ?? 0;
    if ($attempts >= $max && (time() - $lastTime) < ($lockMin * 60)) {
        return true;
    }
    if ($attempts >= $max && (time() - $lastTime) >= ($lockMin * 60)) {
        unset($_SESSION[$key], $_SESSION[$key . '_time']);
    }
    return false;
}
function getLoginLockRemaining($identifier) {
    $lockMin = intval(getConfig('login_lock_minutes') ?: 15);
    $key = 'login_attempt_' . md5($identifier);
    $lastTime = $_SESSION[$key . '_time'] ?? 0;
    $remaining = ($lockMin * 60) - (time() - $lastTime);
    return max(0, $remaining);
}

// ========== 登录日志 ==========
function addLoginLog($userId, $username, $status, $failReason = '') {
    $pdo = getDB();
    if ($pdo === null) return false;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, username, ip, user_agent, status, fail_reason, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $username, $ip, $ua, $status ? 1 : 0, $failReason, time()]);
}
function getUserLoginLogs($userId, $limit = 20) {
    $pdo = getDB();
    if ($pdo === null) return [];
    $stmt = $pdo->prepare("SELECT * FROM login_logs WHERE user_id = ? ORDER BY id DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getAllLoginLogs($limit = 50, $offset = 0) {
    $pdo = getDB();
    if ($pdo === null) return [];
    $stmt = $pdo->prepare("SELECT l.*, u.username as db_username FROM login_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.id DESC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getLoginLogCount() {
    $pdo = getDB();
    if ($pdo === null) return 0;
    return intval($pdo->query("SELECT COUNT(*) FROM login_logs")->fetchColumn());
}

// ========== 项目访问统计 ==========
function recordProjectVisit($proId) {
    if (getConfig('enable_project_stats') !== '1') return;
    $pdo = getDB();
    if ($pdo === null) return;
    $date = date('Y-m-d');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // 简单的去重：同 IP 同一天只计一次 unique
    try {
        $stmt = $pdo->prepare("INSERT INTO project_visits (pro_id, visit_date, visit_count, unique_ips) VALUES (?, ?, 1, 1) ON DUPLICATE KEY UPDATE visit_count = visit_count + 1");
        $stmt->execute([$proId, $date]);
    } catch (Exception $e) {
        // 静默失败，不影响页面访问
    }
}
function getProjectVisitStats($proId, $days = 14) {
    $pdo = getDB();
    if ($pdo === null) return [];
    $stmt = $pdo->prepare("SELECT visit_date, visit_count FROM project_visits WHERE pro_id = ? AND visit_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) ORDER BY visit_date ASC");
    $stmt->execute([$proId, $days]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getProjectTotalVisits($proId) {
    $pdo = getDB();
    if ($pdo === null) return 0;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(visit_count), 0) FROM project_visits WHERE pro_id = ?");
    $stmt->execute([$proId]);
    return intval($stmt->fetchColumn());
}
function getAllProjectsTotalVisits() {
    $pdo = getDB();
    if ($pdo === null) return 0;
    return intval($pdo->query("SELECT COALESCE(SUM(visit_count), 0) FROM project_visits")->fetchColumn());
}

// ========== 项目模板 ==========
function getAllTemplates($activeOnly = true) {
    $pdo = getDB();
    if ($pdo === null) return [];
    $sql = "SELECT * FROM project_templates";
    if ($activeOnly) $sql .= " WHERE is_active = 1";
    $sql .= " ORDER BY sort ASC, id ASC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
function getTemplateById($id) {
    $pdo = getDB();
    if ($pdo === null) return null;
    $stmt = $pdo->prepare("SELECT * FROM project_templates WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function getTemplateFiles($templateId) {
    $pdo = getDB();
    if ($pdo === null) return [];
    $stmt = $pdo->prepare("SELECT * FROM template_files WHERE template_id = ? ORDER BY is_index DESC, filename ASC");
    $stmt->execute([$templateId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function createProjectFromTemplate($userId, $name, $templateId, $description = '') {
    $pdo = getDB();
    if ($pdo === null) return false;
    $template = getTemplateById($templateId);
    if (!$template) return false;

    // 获取用户名
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) return false;
    $username = $user['username'];

    // 生成 pro_id
    do {
        $proId = substr(bin2hex(random_bytes(8)), 0, 8);
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE pro_id = ?");
        $stmt->execute([$proId]);
    } while ($stmt->fetch());

    // 插入项目
    $stmt = $pdo->prepare("INSERT INTO projects (pro_id, user_id, name, description, created_at) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt->execute([$proId, $userId, $name, $description, time()])) return false;

    // 创建目录
    $projectDir = __DIR__ . "/users/$username/projects/$proId";
    if (!is_dir($projectDir)) mkdir($projectDir, 0755, true);

    // 复制模板文件
    $files = getTemplateFiles($templateId);
    foreach ($files as $f) {
        $content = $f['content'];
        // 替换模板中的项目名占位符
        $content = str_replace(['{{PROJECT_NAME}}', '{{项目名}}'], $name, $content);
        file_put_contents($projectDir . '/' . $f['filename'], $content);
        addOrUpdateFile($proId, $f['filename'], $content, $f['is_index']);
    }

    // 如果模板没有 index.html，创建一个默认的
    if (!file_exists($projectDir . '/index.html')) {
        $default = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($name) . '</title></head><body><h1>' . htmlspecialchars($name) . '</h1></body></html>';
        file_put_contents($projectDir . '/index.html', $default);
        addOrUpdateFile($proId, 'index.html', $default, 1);
    }

    return $proId;
}

// ========== 项目克隆 ==========
function cloneProject($proId, $userId, $newName = '') {
    $pdo = getDB();
    if ($pdo === null) return false;
    $project = getProjectByProId($proId);
    if (!$project || $project['user_id'] != $userId) return false;

    $name = $newName ?: ($project['name'] . ' 副本');
    $files = getProjectFiles($proId);

    // 获取用户名
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $user['username'];

    // 生成新 pro_id
    do {
        $newProId = substr(bin2hex(random_bytes(8)), 0, 8);
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE pro_id = ?");
        $stmt->execute([$newProId]);
    } while ($stmt->fetch());

    // 插入新项目
    $stmt = $pdo->prepare("INSERT INTO projects (pro_id, user_id, name, description, created_at) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt->execute([$newProId, $userId, $name, $project['description'], time()])) return false;

    // 创建目录并复制文件
    $newDir = __DIR__ . "/users/$username/projects/$newProId";
    if (!is_dir($newDir)) mkdir($newDir, 0755, true);

    foreach ($files as $f) {
        $content = $f['content'];
        file_put_contents($newDir . '/' . $f['filename'], $content);
        addOrUpdateFile($newProId, $f['filename'], $content, $f['is_index']);
    }

    return $newProId;
}

// ========== 系统信息 ==========
function getSystemInfo() {
    $pdo = getDB();
    $info = [];
    $info['php_version'] = PHP_VERSION;
    $info['php_sapi'] = PHP_SAPI;
    $info['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'unknown';
    $info['os'] = PHP_OS . ' ' . (php_uname('r') ?? '');
    $info['timezone'] = date_default_timezone_get();
    $info['memory_limit'] = ini_get('memory_limit');
    $info['upload_max_filesize'] = ini_get('upload_max_filesize');
    $info['max_execution_time'] = ini_get('max_execution_time') . 's';

    // 磁盘使用
    $diskTotal = disk_total_space(__DIR__);
    $diskFree = disk_free_space(__DIR__);
    $info['disk_total'] = round($diskTotal / 1073741824, 1) . ' GB';
    $info['disk_free'] = round($diskFree / 1073741824, 1) . ' GB';
    $info['disk_used_percent'] = $diskTotal > 0 ? round(($diskTotal - $diskFree) / $diskTotal * 100, 1) : 0;

    // MySQL 版本
    if ($pdo) {
        try {
            $info['mysql_version'] = $pdo->query("SELECT VERSION()")->fetchColumn();
            $dbSize = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1048576, 2) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
            $info['db_size_mb'] = $dbSize ?: 0;
        } catch (Exception $e) {
            $info['mysql_version'] = 'unknown';
            $info['db_size_mb'] = 0;
        }
    }

    // 扩展检查
    $info['extensions'] = [
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'zip' => extension_loaded('zip'),
        'gd' => extension_loaded('gd'),
        'curl' => extension_loaded('curl'),
        'mbstring' => extension_loaded('mbstring'),
        'openssl' => extension_loaded('openssl'),
        'fileinfo' => extension_loaded('fileinfo'),
    ];

    // 站点目录大小
    $siteSize = 0;
    if (is_dir(__DIR__ . '/users')) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/users', RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $f) {
            if ($f->isFile()) $siteSize += $f->getSize();
        }
    }
    $info['users_dir_mb'] = round($siteSize / 1048576, 2);

    return $info;
}

// ========== 访客项目过期清理 ==========
function getExpiredGuestProjects($days = null) {
    $pdo = getDB();
    if ($pdo === null) return [];
    if ($days === null) $days = intval(getConfig('guest_expire_days') ?: 30);
    $threshold = time() - ($days * 86400);
    $stmt = $pdo->prepare("SELECT * FROM guest_projects WHERE updated_at < ? ORDER BY updated_at ASC");
    $stmt->execute([$threshold]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function cleanupExpiredGuestProjects($days = null) {
    $pdo = getDB();
    if ($pdo === null) return ['deleted' => 0, 'error' => '数据库连接失败'];
    $expired = getExpiredGuestProjects($days);
    $deleted = 0;
    foreach ($expired as $gp) {
        $dir = __DIR__ . "/users/guests/projects/{$gp['pro_id']}";
        if (is_dir($dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $f) {
                if ($f->isDir()) rmdir($f->getRealPath());
                else unlink($f->getRealPath());
            }
            rmdir($dir);
        }
        $stmt = $pdo->prepare("DELETE FROM guest_projects WHERE id = ?");
        if ($stmt->execute([$gp['id']])) $deleted++;
    }
    return ['deleted' => $deleted, 'total' => count($expired)];
}
function deleteGuestProjectById($id) {
    $pdo = getDB();
    if ($pdo === null) return false;
    $stmt = $pdo->prepare("SELECT * FROM guest_projects WHERE id = ?");
    $stmt->execute([$id]);
    $gp = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$gp) return false;
    $dir = __DIR__ . "/users/guests/projects/{$gp['pro_id']}";
    if (is_dir($dir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $f) {
            if ($f->isDir()) rmdir($f->getRealPath());
            else unlink($f->getRealPath());
        }
        rmdir($dir);
    }
    $stmt = $pdo->prepare("DELETE FROM guest_projects WHERE id = ?");
    return $stmt->execute([$id]);
}

// ========== 数据库备份 ==========
function backupDatabase() {
    $pdo = getDB();
    if ($pdo === null) return false;
    global $db_config;
    $backupDir = __DIR__ . '/backups';
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

    $filename = 'db_backup_' . date('Ymd_His') . '.sql';
    $filepath = $backupDir . '/' . $filename;

    // 获取所有表
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sql = "-- 单页工坊数据库备份\n-- 时间: " . date('Y-m-d H:i:s') . "\n-- 数据库: {$db_config['db_name']}\n\n";
    $sql .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

    foreach ($tables as $table) {
        // 表结构
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $sql .= "-- 表结构: $table\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $create['Create Table'] . ";\n\n";

        // 表数据
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            $sql .= "-- 表数据: $table (" . count($rows) . " 行)\n";
            $columns = array_keys($rows[0]);
            $colList = implode('`, `', $columns);
            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $v) {
                    if ($v === null) $values[] = 'NULL';
                    else $values[] = "'" . addslashes($v) . "'";
                }
                $sql .= "INSERT INTO `$table` (`$colList`) VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    file_put_contents($filepath, $sql);
    return ['filename' => $filename, 'filepath' => $filepath, 'size' => filesize($filepath)];
}
function listBackups() {
    $backupDir = __DIR__ . '/backups';
    if (!is_dir($backupDir)) return [];
    $files = glob($backupDir . '/db_backup_*.sql');
    $result = [];
    foreach ($files as $f) {
        $result[] = [
            'filename' => basename($f),
            'size' => filesize($f),
            'size_mb' => round(filesize($f) / 1048576, 2),
            'created_at' => filemtime($f),
        ];
    }
    usort($result, fn($a, $b) => $b['created_at'] - $a['created_at']);
    return $result;
}

// ========== 工具函数 ==========
function formatTimeAgo($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60) return '刚刚';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    if ($diff < 2592000) return floor($diff / 86400) . '天前';
    return date('Y-m-d', $timestamp);
}
function formatBytes($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
    return round($bytes / 1073741824, 1) . ' GB';
}

// ============================================================
// 极验验证码（v3.0 / v4.0 双版本兼容）
// ============================================================
function geetest_enabled() {
    return getConfig('geetest_enabled') === '1';
}
function geetest_version() {
    return getConfig('geetest_version') === 'v3' ? 'v3' : 'v4';
}

// v4 服务端校验
function geetest_v4_validate($lot_number, $captcha_output, $pass_token, $gen_time) {
    $captcha_id = getConfig('geetest_v4_id');
    $captcha_key = getConfig('geetest_v4_key');
    if (!$captcha_id || !$captcha_key) return false;
    $sign_token = hash_hmac('sha256', $lot_number, $captcha_key);
    $data = http_build_query([
        'lot_number' => $lot_number,
        'captcha_output' => $captcha_output,
        'pass_token' => $pass_token,
        'gen_time' => $gen_time,
        'sign_token' => $sign_token,
    ]);
    $url = 'https://gcaptcha4.geetest.com/validate?captcha_id=' . urlencode($captcha_id);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($resp, true);
    return isset($result['result']) && $result['result'] === 'success';
}

// v3 服务端校验
function geetest_v3_validate($challenge, $validate, $seccode) {
    $id = getConfig('geetest_v3_id');
    $key = getConfig('geetest_v3_key');
    if (!$id || !$key) return false;
    $data = http_build_query([
        'gt' => $id,
        'challenge' => $challenge,
        'validate' => $validate,
        'seccode' => $seccode,
        'json_format' => '1',
    ]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.geetest.com/validate.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($resp, true);
    if (!$result || !isset($result['seccode'])) return false;
    return $result['seccode'] === md5($validate . $key);
}

// v3 register（获取 challenge）
function geetest_v3_register() {
    $id = getConfig('geetest_v3_id');
    if (!$id) return ['success' => 0, 'challenge' => '', 'gt' => ''];
    $url = 'https://api.geetest.com/register.php?gt=' . urlencode($id) . '&json_format=1';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp, true);
    if (!$data || empty($data['challenge'])) {
        return ['success' => 0, 'challenge' => md5(uniqid()), 'gt' => $id];
    }
    return ['success' => 1, 'challenge' => $data['challenge'], 'gt' => $id];
}

// 统一校验入口（从 POST 读取参数），未启用则返回 true
function geetest_verify() {
    if (!geetest_enabled()) return true;
    $version = geetest_version();
    if ($version === 'v4') {
        $lot = $_POST['geetest_lot_number'] ?? '';
        $out = $_POST['geetest_captcha_output'] ?? '';
        $pass = $_POST['geetest_pass_token'] ?? '';
        $gen = $_POST['geetest_gen_time'] ?? '';
        if (!$lot || !$out || !$pass || !$gen) return false;
        return geetest_v4_validate($lot, $out, $pass, $gen);
    } else {
        $challenge = $_POST['geetest_challenge'] ?? '';
        $validate = $_POST['geetest_validate'] ?? '';
        $seccode = $_POST['geetest_seccode'] ?? '';
        if (!$challenge || !$validate || !$seccode) return false;
        return geetest_v3_validate($challenge, $validate, $seccode);
    }
}

// 输出极验前端 HTML + JS（嵌入表单内）
function geetest_field() {
    if (!geetest_enabled()) return '';
    $html = '<input type="hidden" name="geetest_lot_number" id="geetest_lot_number">';
    $html .= '<input type="hidden" name="geetest_captcha_output" id="geetest_captcha_output">';
    $html .= '<input type="hidden" name="geetest_pass_token" id="geetest_pass_token">';
    $html .= '<input type="hidden" name="geetest_gen_time" id="geetest_gen_time">';
    $html .= '<input type="hidden" name="geetest_challenge" id="geetest_challenge">';
    $html .= '<input type="hidden" name="geetest_validate" id="geetest_validate">';
    $html .= '<input type="hidden" name="geetest_seccode" id="geetest_seccode">';
    $html .= '<div id="geetest-container" style="margin: 10px 0;"></div>';
    return $html;
}

// 输出极验初始化脚本（放在页面底部，formSelector 指定要拦截的表单）
function geetest_init_js($formSelector = 'form') {
    if (!geetest_enabled()) return '';
    $version = geetest_version();
    $id = $version === 'v4' ? getConfig('geetest_v4_id') : getConfig('geetest_v3_id');
    $js = '';
    if ($version === 'v4') {
        $js .= '<script src="https://static.geetest.com/v4/gt4.js"></script>';
        $js .= '<script>
        var _geetestObj = null, _geetestNeedSubmit = false;
        if (window.initGeetest4) {
            initGeetest4({captchaId:"' . $id . '",product:"bind",riskType:"slide"},function(captcha){
                _geetestObj = captcha;
                captcha.appendTo("#geetest-container");
                captcha.onSuccess(function(){
                    var r = captcha.getValidate();
                    document.getElementById("geetest_lot_number").value = r.lot_number;
                    document.getElementById("geetest_captcha_output").value = r.captcha_output;
                    document.getElementById("geetest_pass_token").value = r.pass_token;
                    document.getElementById("geetest_gen_time").value = r.gen_time;
                    if (_geetestNeedSubmit) { _geetestNeedSubmit = false; document.querySelector("' . $formSelector . '").submit(); }
                });
            });
        }
        document.querySelector("' . $formSelector . '").addEventListener("submit",function(e){
            if (!_geetestObj) { e.preventDefault(); alert("验证码加载中，请稍候"); return; }
            if (document.getElementById("geetest_lot_number").value) return;
            e.preventDefault();
            _geetestNeedSubmit = true;
            _geetestObj.verify();
        });
        </script>';
    } else {
        $js .= '<script src="https://static.geetest.com/static/tools/gt.js"></script>';
        $js .= '<script>
        var _geetestObj = null, _geetestNeedSubmit = false;
        fetch("geetest.php?action=register").then(function(r){return r.json();}).then(function(data){
            if (window.initGeetest) {
                initGeetest({gt:data.gt,challenge:data.challenge,offline:!data.success,new_captcha:true,product:"bind",width:"100%"},function(captcha){
                    _geetestObj = captcha;
                    captcha.appendTo("#geetest-container");
                    captcha.onSuccess(function(){
                        var r = captcha.getValidate();
                        document.getElementById("geetest_challenge").value = r.geetest_challenge;
                        document.getElementById("geetest_validate").value = r.geetest_validate;
                        document.getElementById("geetest_seccode").value = r.geetest_seccode;
                        if (_geetestNeedSubmit) { _geetestNeedSubmit = false; document.querySelector("' . $formSelector . '").submit(); }
                    });
                });
            }
        });
        document.querySelector("' . $formSelector . '").addEventListener("submit",function(e){
            if (!_geetestObj) { e.preventDefault(); alert("验证码加载中，请稍候"); return; }
            if (document.getElementById("geetest_challenge").value) return;
            e.preventDefault();
            _geetestNeedSubmit = true;
            _geetestObj.verify();
        });
        </script>';
    }
    return $js;
}

// AJAX 场景用：返回极验参数对象（供 fetch body 使用）
function geetest_ajax_params_js() {
    if (!geetest_enabled()) return '';
    return '<script>
    function getGeetestParams() {
        return {
            geetest_lot_number: document.getElementById("geetest_lot_number").value,
            geetest_captcha_output: document.getElementById("geetest_captcha_output").value,
            geetest_pass_token: document.getElementById("geetest_pass_token").value,
            geetest_gen_time: document.getElementById("geetest_gen_time").value,
            geetest_challenge: document.getElementById("geetest_challenge").value,
            geetest_validate: document.getElementById("geetest_validate").value,
            geetest_seccode: document.getElementById("geetest_seccode").value
        };
    }
    function geetestValidated() {
        return !!(document.getElementById("geetest_lot_number").value || document.getElementById("geetest_challenge").value);
    }
    </script>';
}


// ============================================================
// 在线升级系统
// ============================================================
define('UPGRADE_API_URL', 'https://www.xhehm.com/api/projects/Pages/new.php');
define('UPGRADE_BACKUP_DIR', __DIR__ . '/backup_upgrade');
define('UPGRADE_TEMP_DIR', sys_get_temp_dir() . '/pages_upgrade');

function upgradeGetCurrentVersion() {
    $v = getConfig('current_version');
    return $v ?: '2.3.0';
}

function upgradeSetCurrentVersion($version) {
    updateConfig('current_version', $version);
}

function upgradeCheckUpdate($force = false) {
    $cacheFile = sys_get_temp_dir() . '/pages_update_cache.json';
    if (!$force && file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) return $cached;
    }
    $url = UPGRADE_API_URL . '?version=' . urlencode(upgradeGetCurrentVersion());
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200 || !$resp) {
        return ['error' => '无法连接更新服务器（HTTP ' . $httpCode . '）'];
    }
    $data = json_decode($resp, true);
    if (!$data) return ['error' => '更新服务器返回数据格式错误'];
    file_put_contents($cacheFile, json_encode($data));
    return $data;
}

function upgradeBackupSiteFiles() {
    if (!is_dir(UPGRADE_BACKUP_DIR)) mkdir(UPGRADE_BACKUP_DIR, 0755, true);
    $siteDir = dirname(__DIR__);
    $backupFile = UPGRADE_BACKUP_DIR . '/site_' . date('Ymd_His') . '.zip';
    $cmd = sprintf(
        'cd %s && zip -rq %s . -x "*/node_modules/*" -x "*/.git/*" -x "*/vendor/phpmailer/*" 2>&1',
        escapeshellarg($siteDir), escapeshellarg($backupFile)
    );
    exec($cmd, $output, $returnCode);
    if ($returnCode !== 0 || !file_exists($backupFile)) {
        return ['success' => false, 'message' => '文件备份失败：' . implode(' ', array_slice($output, 0, 3))];
    }
    return ['success' => true, 'file' => $backupFile, 'size' => filesize($backupFile)];
}

function upgradeBackupDatabase() {
    if (!is_dir(UPGRADE_BACKUP_DIR)) mkdir(UPGRADE_BACKUP_DIR, 0755, true);
    $backupFile = UPGRADE_BACKUP_DIR . '/db_' . date('Ymd_His') . '.sql';
    $pdo = getDB();
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sql = '';
    foreach ($tables as $table) {
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $sql .= "DROP TABLE IF EXISTS `$table`;\n" . $create['Create Table'] . ";\n\n";
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $values = array_map(function($v) use ($pdo) {
                return $v === null ? 'NULL' : $pdo->quote($v);
            }, array_values($row));
            $cols = array_map(function($c) { return "`$c`"; }, array_keys($row));
            $sql .= "INSERT INTO `$table` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $values) . ");\n";
        }
        $sql .= "\n";
    }
    file_put_contents($backupFile, $sql);
    return ['success' => true, 'file' => $backupFile, 'size' => filesize($backupFile)];
}

function upgradeDownloadPackage($url, $checksum = '') {
    if (!is_dir(UPGRADE_TEMP_DIR)) mkdir(UPGRADE_TEMP_DIR, 0755, true);
    $zipFile = UPGRADE_TEMP_DIR . '/upgrade.zip';
    $fp = fopen($zipFile, 'w+');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Pages-Upgrade/2.4');
    $success = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    fclose($fp);
    if (!$success || $httpCode !== 200) {
        @unlink($zipFile);
        return ['success' => false, 'message' => '下载失败（HTTP ' . $httpCode . '）' . ($error ? ": $error" : '')];
    }
    if ($checksum && md5_file($zipFile) !== $checksum) {
        @unlink($zipFile);
        return ['success' => false, 'message' => '文件校验失败（MD5 不匹配）'];
    }
    return ['success' => true, 'file' => $zipFile, 'size' => filesize($zipFile)];
}

function upgradeExtractPackage($zipFile) {
    $extractDir = UPGRADE_TEMP_DIR . '/extracted';
    if (is_dir($extractDir)) upgradeRrmdir($extractDir);
    mkdir($extractDir, 0755, true);
    $cmd = sprintf('unzip -o -q %s -d %s 2>&1', escapeshellarg($zipFile), escapeshellarg($extractDir));
    exec($cmd, $output, $returnCode);
    if ($returnCode !== 0) {
        return ['success' => false, 'message' => '解压失败：' . implode(' ', array_slice($output, 0, 3))];
    }
    return ['success' => true, 'dir' => $extractDir];
}

function upgradeApplyFiles($extractDir) {
    $siteDir = dirname(__DIR__);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $count = 0; $errors = [];
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $relativePath = substr($file->getPathname(), strlen($extractDir) + 1);
        if ($relativePath === 'migration.sql' || $relativePath === 'upgrade.json') continue;
        $targetPath = $siteDir . DIRECTORY_SEPARATOR . $relativePath;
        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        if (!copy($file->getPathname(), $targetPath)) $errors[] = $relativePath;
        else $count++;
    }
    return ['success' => count($errors) === 0, 'count' => $count, 'errors' => $errors];
}

function upgradeApplySql($extractDir) {
    $sqlFile = $extractDir . '/migration.sql';
    if (!file_exists($sqlFile)) return ['success' => true, 'message' => '无数据库迁移', 'executed' => 0];
    $pdo = getDB();
    $sql = file_get_contents($sqlFile);
    $statements = []; $buffer = ''; $inComment = false; $inString = false; $stringChar = '';
    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i]; $next = $sql[$i + 1] ?? '';
        if ($inString) { $buffer .= $char; if ($char === $stringChar && ($i === 0 || $sql[$i - 1] !== '\\')) $inString = false; continue; }
        if ($inComment) { if ($char === "\n") { $inComment = false; $buffer .= $char; } continue; }
        if ($char === '-' && $next === '-') { $inComment = true; continue; }
        if ($char === "'" || $char === '"' || $char === '`') { $inString = true; $stringChar = $char; $buffer .= $char; continue; }
        if ($char === ';') { $statement = trim($buffer); if ($statement !== '') $statements[] = $statement; $buffer = ''; continue; }
        $buffer .= $char;
    }
    if (trim($buffer) !== '') $statements[] = trim($buffer);
    $executed = 0; $errors = [];
    foreach ($statements as $stmt) {
        try { $pdo->exec($stmt); $executed++; }
        catch (PDOException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'already exists') === false && strpos($msg, 'Duplicate') === false &&
                strpos($msg, '1060') === false && strpos($msg, '1061') === false && strpos($msg, '1091') === false) {
                $errors[] = $msg;
            }
        }
    }
    return ['success' => count($errors) === 0, 'executed' => $executed, 'errors' => $errors];
}

function upgradeRrmdir($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) upgradeRrmdir($path); else @unlink($path);
    }
    @rmdir($dir);
}

function upgradeCleanupTemp() {
    if (is_dir(UPGRADE_TEMP_DIR)) upgradeRrmdir(UPGRADE_TEMP_DIR);
}

function doUpgrade($targetVersion, $downloadUrl, $checksum = '') {
    $log = [];
    $log[] = '当前版本：' . upgradeGetCurrentVersion() . ' → 目标版本：' . $targetVersion;
    $log[] = '正在备份站点文件...';
    $fileBackup = upgradeBackupSiteFiles();
    if (!$fileBackup['success']) return ['success' => false, 'log' => $log, 'error' => $fileBackup['message']];
    $log[] = '文件备份完成：' . basename($fileBackup['file']) . ' (' . formatBytes($fileBackup['size']) . ')';
    $log[] = '正在备份数据库...';
    $dbBackup = upgradeBackupDatabase();
    if (!$dbBackup['success']) return ['success' => false, 'log' => $log, 'error' => $dbBackup['message']];
    $log[] = '数据库备份完成：' . basename($dbBackup['file']) . ' (' . formatBytes($dbBackup['size']) . ')';
    $log[] = '正在下载升级包...';
    $download = upgradeDownloadPackage($downloadUrl, $checksum);
    if (!$download['success']) return ['success' => false, 'log' => $log, 'error' => $download['message']];
    $log[] = '下载完成（' . formatBytes($download['size']) . '）';
    $log[] = '正在解压升级包...';
    $extract = upgradeExtractPackage($download['file']);
    if (!$extract['success']) return ['success' => false, 'log' => $log, 'error' => $extract['message']];
    $log[] = '解压完成';
    $log[] = '正在更新文件...';
    $apply = upgradeApplyFiles($extract['dir']);
    $log[] = '已更新 ' . $apply['count'] . ' 个文件';
    if (!empty($apply['errors'])) $log[] = '警告：' . implode(', ', array_slice($apply['errors'], 0, 5));
    $log[] = '正在执行数据库迁移...';
    $sqlApply = upgradeApplySql($extract['dir']);
    $log[] = '数据库迁移完成（执行 ' . $sqlApply['executed'] . ' 条语句）';
    if (!empty($sqlApply['errors'])) $log[] = '警告：' . implode('; ', array_slice($sqlApply['errors'], 0, 3));
    upgradeSetCurrentVersion($targetVersion);
    $log[] = '版本号已更新为 ' . $targetVersion;
    upgradeCleanupTemp();
    $log[] = '临时文件已清理';
    $log[] = '升级完成！';
    return ['success' => true, 'log' => $log, 'version' => $targetVersion, 'backup_files' => $fileBackup['file'], 'backup_db' => $dbBackup['file']];
}
