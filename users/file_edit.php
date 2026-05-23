<?php
require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$proId = $_GET['pro'] ?? '';
$filename = $_GET['file'] ?? '';
if (!isProjectOwner($proId, $user['id']) || empty($filename)) die('参数错误');
$content = getFileContent($proId, $filename);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newContent = $_POST['content'] ?? '';
    addOrUpdateFile($proId, $filename, $newContent, $filename === 'index.html' ? 1 : 0);
    header("Location: project_detail.php?pro=$proId&saved=1");
    exit;
}
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑 - <?= htmlspecialchars($filename) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="p-6">
    <div class="glass-card max-w-5xl mx-auto p-6">
        <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
            <h2 class="text-xl font-bold">编辑文件：<?= htmlspecialchars($filename) ?></h2>
            <a href="project_detail.php?pro=<?= $proId ?>" class="text-gray-500 hover:text-blue-600"><i class="ri-arrow-left-line"></i> 返回</a>
        </div>
        <form method="post">
            <textarea name="content" rows="20" class="w-full p-4 font-mono text-sm bg-white/50 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500"><?= htmlspecialchars($content) ?></textarea>
            <div class="mt-4 flex gap-3">
                <button type="submit" class="btn-primary">保存</button>
                <a href="project_detail.php?pro=<?= $proId ?>" class="btn-secondary">取消</a>
            </div>
        </form>
    </div>
</body>
</html>