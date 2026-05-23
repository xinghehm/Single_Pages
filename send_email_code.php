<?php
require_once 'functions.php';

header('Content-Type: application/json');

$email = $_POST['email'] ?? $_GET['email'] ?? '';
$type = $_POST['type'] ?? $_GET['type'] ?? 'register';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => '请输入有效的邮箱地址']);
    exit;
}

// 检查发送频率
if (isset($_SESSION['email_last_send'][$email]) && $_SESSION['email_last_send'][$email] > time() - 60) {
    echo json_encode(['success' => false, 'message' => '请等待60秒后再试']);
    exit;
}

$code = generateSmsCode();

// 根据类型发送不同内容的邮件
if ($type === 'register') {
    $subject = '=?UTF-8?B?' . base64_encode('注册验证码 - ' . getConfig('site_name')) . '?=';
    $body = '<!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body>
        <h2>欢迎注册 ' . getConfig('site_name') . '</h2>
        <p>您的注册验证码是：</p>
        <p style="font-size:24px;color:#1677ff;font-weight:bold;">' . $code . '</p>
        <p>验证码有效期为5分钟，请勿泄露给他人。</p>
        <p>如果不是您本人操作，请忽略此邮件。</p>
    </body>
    </html>';
} elseif ($type === 'reset') {
    $subject = '=?UTF-8?B?' . base64_encode('找回密码验证码 - ' . getConfig('site_name')) . '?=';
    $body = '<!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body>
        <h2>找回密码</h2>
        <p>您正在申请找回密码，验证码是：</p>
        <p style="font-size:24px;color:#1677ff;font-weight:bold;">' . $code . '</p>
        <p>验证码有效期为5分钟。</p>
        <p>如果不是您本人操作，请忽略此邮件。</p>
    </body>
    </html>';
} elseif ($type === 'bind') {
    $subject = '=?UTF-8?B?' . base64_encode('绑定邮箱验证码 - ' . getConfig('site_name')) . '?=';
    $body = '<!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body>
        <h2>绑定/修改邮箱</h2>
        <p>您的验证码是：</p>
        <p style="font-size:24px;color:#1677ff;font-weight:bold;">' . $code . '</p>
        <p>验证码有效期为5分钟，请勿泄露给他人。</p>
        <p>如果不是您本人操作，请忽略此邮件。</p>
    </body>
    </html>';
} else {
    $subject = '=?UTF-8?B?' . base64_encode('验证码 - ' . getConfig('site_name')) . '?=';
    $body = '<!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body>
        <p>您的验证码是：</p>
        <p style="font-size:24px;color:#1677ff;font-weight:bold;">' . $code . '</p>
        <p>验证码有效期为5分钟。</p>
    </body>
    </html>';
}

// 使用 PHPMailer 发送（支持 UTF-8）
$result = sendMail($email, $subject, $body);

if ($result) {
    storeSmsCode($email, $code);
    $_SESSION['email_last_send'][$email] = time();
    echo json_encode(['success' => true, 'message' => '验证码已发送到邮箱']);
} else {
    echo json_encode(['success' => false, 'message' => '邮件发送失败，请检查SMTP配置']);
}
?>