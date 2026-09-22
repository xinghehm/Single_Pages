<?php
require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$proId = $_GET['pro'] ?? '';
if (deleteProject($proId, $user['id'])) {
    header('Location: projects.php?deleted=1');
} else {
    die('删除失败');
}
?>