<?php
require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$proId = $_GET['pro'] ?? '';
if (!isProjectOwner($proId, $user['id'])) die('无权操作');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = trim($_POST['filename'] ?? '');
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.html$/', $filename)) {
        $error = '文件名只能包含字母数字下划线横线，并以 .html 结尾';
    } else {
        $content = "<!DOCTYPE html>\n<html>\n<head>\n    <meta charset='UTF-8'>\n    <title>New Page</title>\n</head>\n<body>\n    <h1>New Page</h1>\n</body>\n</html>";
        addOrUpdateFile($proId, $filename, $content, 0);
        header("Location: project_detail.php?pro=$proId");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新建文件</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="glass-card max-w-md w-full p-6">
        <h2 class="text-xl font-bold mb-4">新建HTML文件</h2>
        <?php if ($error): ?><div class="error mb-4"><?= $error ?></div><?php endif; ?>
        <form method="post">
            <input type="text" name="filename" placeholder="文件名，如 about.html" required class="w-full px-4 py-2 rounded-full bg-white/40 border border-gray-200 mb-4">
            <div class="flex gap-3">
                <button type="submit" class="btn-primary">创建</button>
                <a href="project_detail.php?pro=<?= $proId ?>" class="btn-secondary">取消</a>
            </div>
        </form>
    </div>
</body>
</html>