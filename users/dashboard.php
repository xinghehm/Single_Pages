<?php
require_once '../functions.php';
if (!isLoggedIn()) { header('Location: ../login.php'); exit; }
$user = getCurrentUser();
$projects = getUserProjects($user['id']);
$stats = getUserStats($user['id']);
$currentGroup = getUserGroup($user['group_id'] ?? 1);
// 会员到期时间显示
if ($currentGroup && ($currentGroup['duration'] ?? 0) == 0 && $user['group_id'] > 1) {
    $expireDisplay = '永久有效';
} elseif (!empty($user['group_expire_at'])) {
    $expireDisplay = date('Y-m-d', $user['group_expire_at']);
} else {
    $expireDisplay = '免费用户';
}
$config = getConfig();

// 获取最新登录IP
$pdo = getDB();
$stmt = $pdo->prepare("SELECT ip FROM login_logs WHERE user_id = ? AND status = 1 ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user['id']]);
$lastLogin = $stmt->fetch();
$lastIp = $lastLogin['ip'] ?? '未知';

// 计算剩余空间
$totalQuota = $user['quota'] ?? 500;
$usedSpace = $stats['total_size_mb'] ?? 0;
$remainSpace = max(0, $totalQuota - $usedSpace);
$projectLimit = getUserProjectLimit($user['id']);
$balance = getUserBalance($user['id']);
$announcements = getAnnouncements(5);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>控制台 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f4f8;min-height:100vh;color:#1a1a2e;line-height:1.6}
a{text-decoration:none;color:inherit}

/* 顶部导航 */
.topbar{position:sticky;top:0;z-index:100;background:#fff;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.topbar .left{display:flex;align-items:center;gap:12px}
.menu-btn{width:40px;height:40px;background:#f3f4f6;border:none;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:20px;color:#374151}
.page-title{display:flex;align-items:center;gap:8px;font-size:17px;font-weight:700}
.page-title .home-icon{width:32px;height:32px;background:#3b82f6;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px}
.topbar .right{display:flex;align-items:center;gap:10px}
.user-id{font-size:14px;font-weight:600;color:#374151}
.avatar{width:36px;height:36px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:18px;cursor:pointer}

/* 主内容 */
.container{max-width:480px;margin:0 auto;padding:16px}

/* 用户卡片 */
.user-card{background:#fff;border-radius:16px;padding:24px 20px;margin-bottom:16px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.user-info{display:flex;align-items:center;gap:16px;margin-bottom:20px}
.user-avatar{width:64px;height:64px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:32px;flex-shrink:0}
.user-meta h2{font-size:22px;font-weight:800;margin-bottom:4px}
.user-meta p{font-size:13px;color:#9ca3af}
.stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.stat-box{background:#f8fafc;border-radius:12px;padding:16px;text-align:center;border:1px solid #eef0f3}
.stat-box .label{font-size:12px;color:#9ca3af;margin-bottom:6px}
.stat-box .value{font-size:20px;font-weight:800;color:#1a1a2e}

/* 快捷访问 */
.quick-card{background:#fff;border-radius:16px;padding:20px;margin-bottom:16px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.card-title{display:flex;align-items:center;gap:8px;font-size:17px;font-weight:700;margin-bottom:16px}
.card-title i{color:#3b82f6;font-size:20px}
.quick-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.quick-item{display:flex;flex-direction:column;align-items:center;gap:8px;padding:12px 4px;border-radius:12px;transition:all .15s;cursor:pointer}
.quick-item:active{background:#f3f4f6;transform:scale(.95)}
.quick-icon{width:44px;height:44px;border-radius:12px;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-size:22px;color:#3b82f6}
.quick-label{font-size:12px;color:#374151;font-weight:500;text-align:center}

/* 最近项目 */
.project-card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.project-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.view-all{font-size:13px;color:#3b82f6;font-weight:500;display:flex;align-items:center;gap:4px}
.project-list{display:flex;flex-direction:column;gap:10px}
.project-item{display:flex;align-items:center;justify-content:space-between;padding:14px;background:#f8fafc;border-radius:12px;border:1px solid #eef0f3}
.project-item .pname{font-size:14px;font-weight:600;display:flex;align-items:center;gap:8px}
.project-item .pname .dot{width:8px;height:8px;border-radius:50%;background:#22c55e}
.project-item .paction{font-size:13px;color:#3b82f6;font-weight:500}
.empty-state{text-align:center;padding:32px 0;color:#9ca3af;font-size:14px}
.empty-state i{font-size:40px;display:block;margin-bottom:8px;color:#d1d5db}

/* 侧边栏遮罩 */
.sidebar-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.4);z-index:200}
.sidebar-overlay.show{display:block}

/* 侧边栏 */
.sidebar{position:fixed;top:0;left:-280px;bottom:0;width:280px;background:#fff;z-index:300;transition:left .3s;display:flex;flex-direction:column;padding-top:20px}
.sidebar.show{left:0}
.sidebar .brand{display:flex;align-items:center;gap:10px;padding:0 20px 20px;border-bottom:1px solid #eef0f3;margin-bottom:12px}
.sidebar .logo{width:36px;height:36px;background:#3b82f6;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px}
.sidebar .name{font-weight:700;font-size:14px}
.sidebar .sub{font-size:11px;color:#9ca3af}
.sidebar nav{flex:1;padding:8px 0}
.sidebar nav a{display:flex;align-items:center;gap:10px;padding:12px 20px;color:#4b5563;font-size:14px;transition:all .15s}
.sidebar nav a:hover{background:#f3f4f6}
.sidebar nav a.active{background:#eff6ff;color:#3b82f6;font-weight:600}
.sidebar .footer{padding:12px 20px;border-top:1px solid #eef0f3}
.sidebar .footer a{display:flex;align-items:center;gap:8px;color:#dc2626;font-size:14px;padding:8px 0}

.hamburger-btn{width:40px;height:40px;background:#f3f4f6;border:none;border-radius:10px;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px}
.hamburger-btn span{display:block;width:20px;height:2px;background:#333;border-radius:2px}
</style>
</head>
<body>

<!-- 顶部导航 -->
<div class="topbar">
<div class="left">
<button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
<div class="page-title"><div class="home-icon"><i class="ri-home-line"></i></div>控制台</div>
</div>
<div class="right">
<span class="user-id"><?= htmlspecialchars($user['username']) ?></span>
<a href="profile.php"><div class="avatar"><i class="ri-user-line"></i></div></a>
</div>
</div>

<!-- 侧边栏 -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<?php include 'sidebar.php'; ?>

<div class="container">
<!-- 用户信息卡片 -->
<div class="user-card">
<div class="user-info">
<div class="user-avatar"><i class="ri-user-line"></i></div>
<div class="user-meta">
<h2><?= htmlspecialchars($user['username']) ?></h2>
<p>ID: <?= intval($user['id']) ?> · <?= htmlspecialchars($user['email']) ?></p>
<p style="font-size:12px;color:#9ca3af;margin-top:2px">登录IP: <?= htmlspecialchars($lastIp) ?></p>
</div>
</div>
<div class="stats-grid">
<div class="stat-box"><div class="label">已用空间</div><div class="value"><?= intval($usedSpace) ?> MB</div></div>
<div class="stat-box"><div class="label">会员到期</div><div class="value" style="font-size:15px;"><?= htmlspecialchars($expireDisplay) ?></div></div>
<div class="stat-box"><div class="label">当前套餐</div><div class="value" style="font-size:16px"><?= htmlspecialchars($currentGroup['name'] ?? '免费用户') ?></div></div>
<div class="stat-box"><div class="label">账户余额</div><div class="value">¥<?= number_format($balance, 2) ?></div></div>
</div>
</div>

<!-- 快捷访问 -->
<div class="quick-card">
<div class="card-title"><i class="ri-lightbulb-flash-line"></i>快捷访问</div>
<div class="quick-grid">
<a href="projects.php" class="quick-item"><div class="quick-icon"><i class="ri-folder-line"></i></div><span class="quick-label">我的项目</span></a>
<a href="templates.php" class="quick-item"><div class="quick-icon"><i class="ri-apps-line"></i></div><span class="quick-label">模板市场</span></a>
<a href="buy_group.php" class="quick-item"><div class="quick-icon"><i class="ri-shopping-bag-line"></i></div><span class="quick-label">购买套餐</span></a>
<a href="profile.php" class="quick-item"><div class="quick-icon"><i class="ri-user-line"></i></div><span class="quick-label">个人中心</span></a>
<a href="login_logs.php" class="quick-item"><div class="quick-icon"><i class="ri-history-line"></i></div><span class="quick-label">登录日志</span></a>
</div>
</div>

<!-- 系统公告 -->
<div class="project-card" style="margin-bottom:16px">
<div class="project-header">
<div class="card-title" style="margin-bottom:0"><i class="ri-megaphone-line"></i>系统公告</div>
</div>
<?php if (empty($announcements)): ?>
<div class="empty-state"><i class="ri-information-line"></i>暂无公告</div>
<?php else: ?>
<div class="project-list">
<?php foreach ($announcements as $ann): ?>
<div class="project-item">
<div class="pname"><?php if ($ann['is_pinned']): ?><span style="background:#fef3c7;color:#d97706;font-size:10px;padding:2px 6px;border-radius:4px;font-weight:600;margin-right:6px">置顶</span><?php endif; ?><?= htmlspecialchars($ann['title']) ?></div>
<span style="font-size:11px;color:#9ca3af;flex-shrink:0"><?= date('m-d', $ann['created_at']) ?></span>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<!-- 最近项目 -->
<div class="project-card">
<div class="project-header">
<div class="card-title" style="margin-bottom:0"><i class="ri-folder-line"></i>最近项目</div>
<a href="projects.php" class="view-all">查看全部 <i class="ri-arrow-right-s-line"></i></a>
</div>
<?php if (empty($projects)): ?>
<div class="empty-state"><i class="ri-folder-open-line"></i>暂无项目，去创建一个吧</div>
<?php else: ?>
<div class="project-list">
<?php foreach (array_slice($projects, 0, 5) as $pro): ?>
<div class="project-item">
<div class="pname"><span class="dot"></span><?= htmlspecialchars($pro['name']) ?></div>
<a href="project_detail.php?pro=<?= htmlspecialchars($pro['pro_id']) ?>" class="paction">管理</a>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>

<script>
function toggleSidebar(){
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}
document.querySelectorAll('.sidebar nav a').forEach(function(a){
    a.addEventListener('click', function(){
        document.querySelector('.sidebar').classList.remove('show');
        document.querySelector('.sidebar-overlay').classList.remove('show');
    });
});
</script>
</body>
</html>






