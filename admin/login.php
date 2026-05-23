<?php session_start();
if (isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once '../functions.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) { $_SESSION['admin_id'] = $admin['id']; header('Location: index.php'); exit; }
    else $error = '用户名或密码错误';
}
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>后台登录 - <?= htmlspecialchars($config['site_name']) ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><link rel="stylesheet" href="../style.css"></head>
<body class="min-h-screen flex items-center justify-center p-4"><div class="glass-card max-w-md w-full p-8"><div class="text-center mb-6"><i class="ri-shield-line text-5xl text-blue-500"></i><h2 class="text-2xl font-bold mt-2">管理后台</h2></div><?php if ($error): ?><div class="error mb-4"><?= $error ?></div><?php endif; ?>
<form method="post"><input type="text" name="username" placeholder="管理员用户名" required class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-3"><input type="password" name="password" placeholder="密码" required class="w-full px-4 py-3 rounded-full bg-white/40 border border-gray-200 mb-4"><button type="submit" class="btn-primary w-full py-3">登录</button></form></div></body></html>