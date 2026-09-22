<?php
require_once '../functions.php';
header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'未登录']); exit; }
$user = getCurrentUser();
$proId = $_GET['pro'] ?? '';
if (!isProjectOwner($proId, $user['id'])) { echo json_encode(['success'=>false,'message'=>'无权操作']); exit; }

$check = validateUploadFile($_FILES['file'] ?? []);
if (!$check['valid']) { echo json_encode(['success'=>false,'message'=>$check['message']]); exit; }

$destDir = __DIR__ . "/../users/{$user['username']}/projects/$proId";
if (!is_dir($destDir)) mkdir($destDir, 0755, true);
$filename = basename($_FILES['file']['name']);
if (move_uploaded_file($_FILES['file']['tmp_name'], $destDir . '/' . $filename)) {
    addOrUpdateFile($proId, $filename, file_get_contents($destDir.'/'.$filename), 0);
    echo json_encode(['success'=>true,'message'=>'上传成功']);
} else {
    echo json_encode(['success'=>false,'message'=>'保存失败']);
}