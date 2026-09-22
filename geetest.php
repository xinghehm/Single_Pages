<?php
/**
 * 极验 v3 register 接口（获取 challenge）
 * v4 不需要此接口
 */
require_once 'functions.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
if ($action === 'register') {
    $result = geetest_v3_register();
    echo json_encode($result);
} else {
    echo json_encode(['success' => 0, 'challenge' => '', 'gt' => '']);
}
