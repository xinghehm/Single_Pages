<?php
require_once 'functions.php';

$accessKey = $_GET['key'] ?? '';
$project = $accessKey ? getGuestProjectByKey($accessKey) : null;

if (!$project) {
    http_response_code(404);
    die('密钥无效或项目不存在');
}

$proId = $project['pro_id'];
$destDir = __DIR__ . "/users/guests/projects/$proId";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = trim($_POST['filename'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.html$/', $filename)) {
        $error = '文件名只能包含字母数字下划线横线，并以 .html 结尾';
    } else {
        // 安全检查：防止目录遍历
        if (strpos($filename, '..') !== false) {
            $error = '非法文件名';
        } else {
            // 检查是否已存在
            if (file_exists($destDir . '/' . $filename)) {
                $error = '同名文件已存在';
            } else {
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);

                $content = "<!DOCTYPE html>\n<html>\n<head>\n    <meta charset='UTF-8'>\n    <title>New Page</title>\n    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n</head>\n<body>\n    <h1>New Page</h1>\n    <p>由 <a href='/'>单页工坊Pages</a> 提供服务</p>\n</body>\n</html>";

                if (file_put_contents($destDir . '/' . $filename, $content) !== false) {
                    touchGuestProject($proId);
                    header("Location: guest_manage.php?key=" . urlencode($accessKey) . "&msg=" . urlencode('文件创建成功'));
                    exit;
                } else {
                    $error = '文件创建失败，请检查目录写入权限';
                }
            }
        }
    }
}

$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新建文件 - <?= htmlspecialchars($config['site_name'] ?? '单页工坊Pages') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=243">
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full p-6">
        <h2 class="text-xl font-bold mb-4"><i class="ri-file-add-line"></i> 新建 HTML 文件</h2>

        <?php if ($error): ?>
            <div class="error mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm mb-4">
            <p class="text-gray-600">文件名示例：</p>
            <p class="text-gray-600 font-mono text-xs mt-1">about.html &nbsp; contact.html &nbsp; page2.html</p>
        </div>

        <form method="post">
            <input type="text" name="filename" placeholder="文件名，如 about.html" required
                   class="w-full px-4 py-2 rounded-lg mb-4"
                   autocomplete="off" autofocus>
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">创建</button>
                <a href="guest_manage.php?key=<?= urlencode($accessKey) ?>" class="btn-secondary">取消</a>
            </div>
        </form>
    </div>
</body>
</html>