<?php
require_once 'functions.php';

// 获取项目ID和文件名
$proId = $_GET['pro'] ?? '';
$filename = $_GET['file'] ?? '';

// 如果没有pro参数，尝试从URL路径获取（支持 Nginx 重写）
if (empty($proId)) {
    $requestUri = $_SERVER['REQUEST_URI'];
    if (preg_match('/^\/([a-zA-Z0-9]+)\/([a-zA-Z0-9_\-\.]+)\.html$/', $requestUri, $matches)) {
        $proId = $matches[1];
        $filename = $matches[2] . '.html';
    } elseif (preg_match('/^\/([a-zA-Z0-9]+)$/', $requestUri, $matches)) {
        $proId = $matches[1];
        $filename = 'index.html';
    }
}

if (!empty($proId) && empty($filename)) {
    $filename = 'index.html';
}

// 如果有项目ID，显示项目内容
if (!empty($proId)) {
    $project = getProjectByProId($proId);

    if (!$project) {
        // 尝试游客项目
        $guestProject = getGuestProjectByProId($proId);
        if ($guestProject) {
            $content = getGuestFileContent($proId, $filename);
            if ($content === null && $filename !== 'index.html') {
                $content = getGuestFileContent($proId, 'index.html');
            }
            if ($content === null) {
                http_response_code(404);
                die('文件不存在：' . htmlspecialchars($filename));
            }
            // 根据扩展名设置 Content-Type
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mimeMap = [
                'html' => 'text/html', 'htm' => 'text/html',
                'css' => 'text/css', 'js' => 'application/javascript',
                'json' => 'application/json', 'txt' => 'text/plain',
                'xml' => 'text/xml', 'svg' => 'image/svg+xml',
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png', 'gif' => 'image/gif',
                'webp' => 'image/webp', 'ico' => 'image/x-icon',
                'pdf' => 'application/pdf',
                'woff' => 'font/woff', 'woff2' => 'font/woff2',
                'ttf' => 'font/ttf', 'eot' => 'application/vnd.ms-fontobject',
                'mp3' => 'audio/mpeg', 'mp4' => 'video/mp4', 'webm' => 'video/webm',
            ];
            $mime = $mimeMap[$ext] ?? 'text/html';
            header('Content-Type: ' . $mime . '; charset=utf-8');
            echo $content;
            exit;
        }

        http_response_code(404);
        die('项目不存在');
    }

    $content = getFileContent($proId, $filename);

    if ($content === null && $filename !== 'index.html') {
        $content = getFileContent($proId, 'index.html');
    }

    if ($content === null) {
        http_response_code(404);
        die('文件不存在：' . htmlspecialchars($filename));
    }

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimeMap = [
        'html' => 'text/html', 'htm' => 'text/html',
        'css' => 'text/css', 'js' => 'application/javascript',
        'json' => 'application/json', 'txt' => 'text/plain',
        'xml' => 'text/xml', 'svg' => 'image/svg+xml',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'gif' => 'image/gif',
        'webp' => 'image/webp', 'ico' => 'image/x-icon',
        'pdf' => 'application/pdf',
    ];
    $mime = $mimeMap[$ext] ?? 'text/html';
    header('Content-Type: ' . $mime . '; charset=utf-8');
    // 仅记录 HTML 页面访问
    if ($mime === 'text/html') {
        recordProjectVisit($proId);
    }
    echo $content;
    exit;
}

// 没有pro参数时显示首页（使用模板系统）
$config = getConfig();
renderHomeTemplate();
