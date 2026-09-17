<?php
require_once 'functions.php';

// 易支付异步通知回调
// 接收GET参数
$params = $_GET;

// 验证签名
if (!verifyYiPaySign($params)) {
    echo 'fail';
    exit;
}

// 检查支付状态
$tradeStatus = $params['trade_status'] ?? '';
if ($tradeStatus !== 'TRADE_SUCCESS') {
    echo 'fail';
    exit;
}

// 获取订单信息
$outTradeNo = $params['out_trade_no'] ?? '';
$tradeNo = $params['trade_no'] ?? '';
$money = $params['money'] ?? 0;

if (empty($outTradeNo)) {
    echo 'fail';
    exit;
}

// 处理支付成功的订单
$result = processPaidOrder($outTradeNo, $tradeNo);

if ($result) {
    echo 'success';
} else {
    echo 'fail';
}
