<?php
require_once '../functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$projectName = $_GET['name'] ?? '';
$templateId = intval($_GET['template'] ?? 0);

if (!preg_match('/^[a-zA-Z0-9\x{4e00}-\x{9fa5}_\-]+$/u', $projectName)) {
    die('项目名无效（仅允许中英文、数字、下划线、短横线）');
}

$userId = getCurrentUser()['id'];
if (!canCreateProject($userId)) {
    $limit = getUserProjectLimit($userId);
    die("项目数量已达上限（{$limit}个），请升级用户组或删除旧项目");
}

if ($templateId > 0) {
    $proId = createProjectFromTemplate($userId, $projectName, $templateId);
} else {
    $proId = createProject($userId, $projectName);
}

if ($proId) {
    header("Location: project_detail.php?pro=$proId");
    exit;
} else {
    die('创建项目失败，请检查目录权限');
}
?>
