<?php
require_once 'functions.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方式错误']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => '请选择要上传的文件']);
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

$guest = createGuestProject();
if (!$guest) {
    echo json_encode(['success' => false, 'message' => '创建项目失败，请稍后重试']);
    exit;
}

$proId  = $guest['pro_id'];
$accessKey = $guest['access_key'];
$destDir = __DIR__ . "/users/guests/projects/$proId";

if ($ext === 'zip') {
    $result = safeExtractZip($file['tmp_name'], $destDir, 200);
    if (!$result['success']) {
        clearGuestProjectFiles($proId);
        $pdo = getDB();
        $pdo->prepare("DELETE FROM guest_projects WHERE pro_id = ?")->execute([$proId]);
        echo json_encode(['success' => false, 'message' => $result['message']]);
        exit;
    }

    if (!file_exists($destDir . '/index.html')) {
        $files = getGuestProjectFiles($proId);
        $foundHtml = false;
        foreach ($files as $f) {
            if (preg_match('/\.html?$/i', $f['filename'])) {
                copy($destDir . '/' . $f['filename'], $destDir . '/index.html');
                $foundHtml = true;
                break;
            }
        }
        if (!$foundHtml) {
            clearGuestProjectFiles($proId);
            $pdo = getDB();
            $pdo->prepare("DELETE FROM guest_projects WHERE pro_id = ?")->execute([$proId]);
            echo json_encode(['success' => false, 'message' => 'zip 内没有 HTML 文件，无法部署']);
            exit;
        }
    }
} else {
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    move_uploaded_file($file['tmp_name'], $destDir . '/index.html');
}

touchGuestProject($proId);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$url    = $scheme . '://' . $host . '/' . $proId;

echo json_encode([
    'success'    => true,
    'url'        => $url,
    'access_key' => $accessKey,
    'pro_id'     => $proId,
]);