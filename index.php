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
        $project = getProjectBySlug($proId);
        if ($project) {
            $proId = $project['pro_id'];
        }
    }


    // 检查用户项目是否被封禁
    if ($project && !empty($project['banned'])) {
        http_response_code(403);
        $reason = htmlspecialchars($project['ban_reason'] ?? '违反平台规定');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>项目已被禁止访问</title><style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f3f4f6;font-family:sans-serif;}.box{text-align:center;padding:40px;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:400px;margin:16px;}h1{font-size:22px;color:#1f2937;margin:0 0 8px;}p{color:#6b7280;font-size:14px;line-height:1.6;margin:0;}.reason{background:#fef2f2;color:#991b1b;padding:10px 14px;border-radius:8px;margin-top:16px;font-size:13px;}.icon{width:56px;height:56px;margin:0 auto 16px;color:#ef4444;}</style></head><body><div class="box"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="9" y1="12" x2="15" y2="12"/></svg><h1>该项目已被禁止访问</h1><p>此项目存在违规内容，已被平台封禁。</p><div class="reason">封禁原因：' . $reason . '</div></div></body></html>';
        exit;
    }

    if (!$project) {
        // 尝试游客项目
        $guestProject = getGuestProjectByProId($proId);
        if ($guestProject) {
            // 检查访客项目是否被封禁
            if (!empty($guestProject['banned'])) {
                http_response_code(403);
                $reason = htmlspecialchars($guestProject['ban_reason'] ?? '违反平台规定');
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>项目已被禁止访问</title><style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f3f4f6;font-family:sans-serif;}.box{text-align:center;padding:40px;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:400px;margin:16px;}h1{font-size:22px;color:#1f2937;margin:0 0 8px;}p{color:#6b7280;font-size:14px;line-height:1.6;margin:0;}.reason{background:#fef2f2;color:#991b1b;padding:10px 14px;border-radius:8px;margin-top:16px;font-size:13px;}.icon{width:56px;height:56px;margin:0 auto 16px;color:#ef4444;}</style></head><body><div class="box"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="9" y1="12" x2="15" y2="12"/></svg><h1>该项目已被禁止访问</h1><p>此项目存在违规内容，已被平台封禁。</p><div class="reason">封禁原因：' . $reason . '</div></div></body></html>';
                exit;
            }
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




