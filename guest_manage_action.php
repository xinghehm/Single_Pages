<?php
require_once 'functions.php';
header('Content-Type: application/json; charset=utf-8');

$accessKey = $_POST['access_key'] ?? '';
$project = $accessKey ? getGuestProjectByKey($accessKey) : null;

if (!$project) {
    echo json_encode(['success' => false, 'message' => '密钥无效']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => '请选择文件']);
    exit;
}

$file = $_FILES['file'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['zip', 'html', 'htm'])) {
    echo json_encode(['success' => false, 'message' => '只支持 .zip / .html / .htm 文件']);
    exit;
}

$maxSize = ($ext === 'zip') ? 5 * 1024 * 1024 : 2 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => '文件超过大小限制']);
    exit;
}

$proId   = $project['pro_id'];
$destDir = __DIR__ . "/users/guests/projects/$proId";

clearGuestProjectFiles($proId);

if ($ext === 'zip') {
    $result = safeExtractZip($file['tmp_name'], $destDir, 200);
    if (!$result['success']) {
        echo json_encode(['success' => false, 'message' => $result['message']]);
        exit;
    }
    if (!file_exists($destDir . '/index.html')) {
        $files = getGuestProjectFiles($proId);
        foreach ($files as $f) {
            if (preg_match('/\.html?$/i', $f['filename'])) {
                copy($destDir . '/' . $f['filename'], $destDir . '/index.html');
                break;
            }
        }
    }
} else {
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    move_uploaded_file($file['tmp_name'], $destDir . '/index.html');
}

touchGuestProject($proId);

echo json_encode(['success' => true, 'message' => '重新上传成功']);