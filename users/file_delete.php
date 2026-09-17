<?php
require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$proId = $_GET['pro'] ?? '';
$filename = $_GET['file'] ?? '';
if (!isProjectOwner($proId, $user['id'])) die('无权操作');
deleteFile($proId, $filename);
header("Location: project_detail.php?pro=$proId");
?>