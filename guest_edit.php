<?php
require_once 'functions.php';
$accessKey = $_GET['key'] ?? '';
$filename = $_GET['file'] ?? '';
$project = $accessKey ? getGuestProjectByKey($accessKey) : null;
if (!$project) { http_response_code(404); die('密钥无效'); }
$proId = $project['pro_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newContent = $_POST['content'] ?? '';
    $destDir = __DIR__ . "/users/guests/projects/$proId";
    if (strpos($filename, '..') !== false || strpos($filename, "\0") !== false) die('非法文件名');
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    file_put_contents($destDir . '/' . $filename, $newContent);
    touchGuestProject($proId);
    header("Location: guest_manage.php?key=" . urlencode($accessKey) . "&msg=" . urlencode('保存成功'));
    exit;
}

$content = getGuestFileContent($proId, $filename);
if ($content === null) die('文件不存在');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑 - <?= htmlspecialchars($filename) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=243">
</head>
<body class="p-6">
    <div class="glass-card max-w-5xl mx-auto p-6">
        <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
            <h2 class="text-xl font-bold">编辑：<?= htmlspecialchars($filename) ?></h2>
            <a href="guest_manage.php?key=<?= urlencode($accessKey) ?>" class="text-gray-500 hover:text-blue-600">
                <i class="ri-arrow-left-line"></i> 返回
            </a>
        </div>
        <form method="post">
            <textarea name="content" rows="20"
                class="w-full p-4 font-mono text-sm bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"><?= htmlspecialchars($content) ?></textarea>
            <div class="mt-4 flex gap-3">
                <button type="submit" class="btn-primary">保存</button>
                <a href="guest_manage.php?key=<?= urlencode($accessKey) ?>" class="btn-secondary">取消</a>
            </div>
        </form>
    </div>
</body>
</html>