<?php
require_once '../functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$projectName = $_GET['name'] ?? '';

if (!preg_match('/^[a-zA-Z0-9]+$/', $projectName)) {
    die('项目名无效（仅允许英文和数字）');
}

$userId = getCurrentUser()['id'];
$proId = createProject($userId, $projectName);

if ($proId) {
    header("Location: project_detail.php?pro=$proId");
    exit;
} else {
    die('创建项目失败，请检查目录权限');
}
?>