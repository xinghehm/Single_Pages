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
<?= outputUserBgStyle() ?>
<style>
/* 移动端顶部导航 - 内联防止CSS丢失 */
.mobile-topbar {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(0,0,0,0.06);
    z-index: 999;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
}
.mobile-topbar .mobile-logo {
    display: flex; align-items: center; gap: 8px;
    font-weight: 600; font-size: 15px;
}
.mobile-topbar .logo-icon {
    width: 32px; height: 32px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 18px;
}
.hamburger-btn {
    width: 40px; height: 40px;
    background: rgba(0,0,0,0.05);
    border: none; border-radius: 10px;
    cursor: pointer;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 5px;
    transition: all 0.3s ease;
}
.hamburger-btn span {
    display: block;
    width: 20px; height: 2px;
    background: #333;
    border-radius: 2px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hamburger-btn.active span:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}
.hamburger-btn.active span:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
}
.hamburger-btn.active span:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(2px);
    z-index: 998;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.sidebar-overlay.show {
    display: block;
    opacity: 1;
}
@media (max-width: 768px) {
    .mobile-topbar { display: flex; }
    .sidebar {
        position: fixed !important;
        left: -280px !important;
        top: 0; bottom: 0;
        z-index: 1000 !important;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        width: 280px !important;
        box-shadow: 4px 0 24px rgba(0,0,0,0.12);
        padding-top: 70px !important;
    }
    .sidebar.show { left: 0 !important; }
    main {
        padding: 16px !important;
        padding-top: 72px !important;
        min-width: 0 !important;
        overflow-x: hidden !important;
    }
    body { overflow-x: hidden !important; }
    .overflow-x-auto {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        width: 100% !important;
    }
    .overflow-x-auto table {
        min-width: 600px !important;
        width: auto !important;
    }
}
</style>
</head>
<body class="p-6">
<div class="mobile-topbar">
    <div class="mobile-logo">
        <div class="logo-icon"><i class="ri-cloud-line"></i></div>
        <span><?= htmlspecialchars($config['site_name'] ?? '单页工坊') ?></span>
    </div>
    <button class="hamburger-btn" onclick="toggleSidebar(this)" aria-label="菜单">
        <span></span><span></span><span></span>
    </button>
</div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>
function toggleSidebar(btn) {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
    if (btn) btn.classList.toggle('active');
    else document.querySelector('.hamburger-btn').classList.toggle('active');
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sidebar a').forEach(function(a) {
        a.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('show');
            document.querySelector('.sidebar-overlay').classList.remove('show');
            document.querySelector('.hamburger-btn').classList.remove('active');
        });
    });
});
</script>

    <div class="glass-card max-w-5xl mx-auto p-6">
        <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
            <h2 class="text-xl font-bold">编辑文件：<?= htmlspecialchars($filename) ?></h2>
            <a href="project_detail.php?pro=<?= $proId ?>" class="text-gray-500 hover:text-blue-600"><i class="ri-arrow-left-line"></i> 返回</a>
        </div>

        <div class="flex gap-2 mb-3 flex-wrap items-center">
            <button type="button" onclick="document.getElementById('quickZip').click()"
                    class="btn-secondary text-sm px-3 py-1"><i class="ri-file-zip-line"></i> 上传 ZIP 解压</button>
            <button type="button" onclick="document.getElementById('quickImg').click()"
                    class="btn-secondary text-sm px-3 py-1"><i class="ri-image-line"></i> 上传图片</button>
            <input type="file" id="quickZip" accept=".zip" style="display:none;" onchange="quickUpload('zip')">
            <input type="file" id="quickImg" accept="image/*" style="display:none;" onchange="quickUpload('img')">
            <span id="quickMsg" class="text-sm self-center"></span>
        </div>

        <form method="post">
            <textarea name="content" rows="20" class="w-full p-4 font-mono text-sm bg-white/50 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500"><?= htmlspecialchars($content) ?></textarea>
            <div class="mt-4 flex gap-3">
                <button type="submit" class="btn-primary">保存</button>
                <a href="project_detail.php?pro=<?= $proId ?>" class="btn-secondary">取消</a>
            </div>
        </form>
    </div>

    <script>
    function quickUpload(type) {
        const input = type === 'zip' ? document.getElementById('quickZip') : document.getElementById('quickImg');
        const file = input.files[0];
        if (!file) return;

        const msg = document.getElementById('quickMsg');
        msg.textContent = '上传中...';
        msg.className = 'text-sm text-blue-600 self-center';

        const fd = new FormData();
        const url = type === 'zip'
            ? 'upload_zip_action.php?pro=<?= $proId ?>'
            : 'upload_file_action.php?pro=<?= $proId ?>';
        fd.append(type === 'zip' ? 'zip' : 'file', file);

        fetch(url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                msg.textContent = d.message;
                msg.className = d.success
                    ? 'text-sm text-green-600 self-center'
                    : 'text-sm text-red-600 self-center';
                if (d.success) setTimeout(() => location.reload(), 1000);
            })
            .catch(() => {
                msg.textContent = '上传失败，请重试';
                msg.className = 'text-sm text-red-600 self-center';
            });

        input.value = '';
    }
    </script>
</body>
</html>