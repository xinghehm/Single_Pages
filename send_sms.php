<?php
require_once 'functions.php';

header('Content-Type: application/json');

$phone = $_POST['phone'] ?? $_GET['phone'] ?? '';
$type = $_POST['type'] ?? $_GET['type'] ?? '';

if (empty($phone) || !preg_match('/^1[3-9]\d{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => '请输入有效的手机号']);
    exit;
}

if (!canSendSms($phone)) {
    echo json_encode(['success' => false, 'message' => '请等待60秒后再试']);
    exit;
}

$code = generateSmsCode();
$result = sendSmsCode($phone, $code);

if ($result['success']) {
    storeSmsCode($phone, $code);
    recordSmsSend($phone);
    echo json_encode(['success' => true, 'message' => '验证码已发送']);
} else {
    echo json_encode(['success' => false, 'message' => $result['message'] ?? '发送失败']);
}