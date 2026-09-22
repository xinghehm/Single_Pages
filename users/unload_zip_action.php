<?php
require_once '../functions.php';
header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'未登录']); exit; }
$user = getCurrentUser();
$proId = $_GET['pro'] ?? '';
if (!isProjectOwner($proId, $user['id'])) { echo json_encode(['success'=>false,'message'=>'无权操作']); exit; }

if (!isset($_FILES['zip']) || $_FILES['zip']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'message'=>'请选择 ZIP 文件']); exit;
}
$file = $_FILES['zip'];
if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'zip') {
    echo json_encode(['success'=>false,'message'=>'只支持 .zip']); exit;
}
if ($file['size'] > 5*1024*1024) { echo json_encode(['success'=>false,'message'=>'不能超过 5MB']); exit; }

$destDir = __DIR__ . "/../users/{$user['username']}/projects/$proId";
$result = safeExtractZip($file['tmp_name'], $destDir, 200);
if (!$result['success']) { echo json_encode(['success'=>false,'message'=>$result['message']]); exit; }

// 同步到数据库
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($destDir, RecursiveDirectoryIterator::SKIP_DOTS)
);
$syncCount = 0;
foreach ($iterator as $f) {
    if ($f->isFile()) {
        $relative = str_replace($destDir . '/', '', $f->getPathname());
        addOrUpdateFile($proId, $relative, file_get_contents($f->getPathname()), $relative==='index.html'?1:0);
        $syncCount++;
    }
}
echo json_encode(['success'=>true,'message'=>"解压成功，同步 {$syncCount} 个文件"]);