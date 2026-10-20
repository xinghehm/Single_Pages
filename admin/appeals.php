<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once '../functions.php';
requireAdminPermission('manage_appeals');
$config = getConfig();
$pdo = getDB();

$msg = ''; $msgType = '';
$statusFilter = $_GET['status'] ?? 'all';

// 处理申诉
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'process') {
        $id = intval($_POST['appeal_id']);
        $status = intval($_POST['appeal_status']); // 1=通过解封 2=拒绝
        $remark = trim($_POST['admin_remark'] ?? '');
        processAppeal($id, $status, $remark);
        $msg = $status == 1 ? '申诉已通过，用户已解封' : '申诉已拒绝';
        $msgType = 'success';
    } elseif ($action === 'delete') {
        $id = intval($_POST['appeal_id']);
        $pdo->prepare("DELETE FROM user_appeals WHERE id = ?")->execute([$id]);
        $msg = '申诉记录已删除'; $msgType = 'success';
    }
}

$appeals = getAllAppeals($statusFilter === 'all' ? null : intval($statusFilter));
$pendingCount = count(getAllAppeals(0));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>申诉管理 - <?= htmlspecialchars($config['site_name']) ?></title>
<link href="/assets/remixicon/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css?v=247">
</head>
<body class="has-sidebar">
<div class="mobile-topbar"><button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button><div class="mobile-logo"><div class="logo-icon"><i class="ri-inbox-line"></i></div><span>申诉管理</span></div></div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<?php include 'sidebar.php'; ?>

<main>
<div class="page-header">
    <div><h1>申诉管理</h1><p class="subtitle">共 <?= count($appeals) ?> 条申诉<?= $pendingCount > 0 ? "，待处理 $pendingCount 条" : '' ?></p></div>
</div>

<?php if ($msg): ?><div class="msg <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- 筛选 -->
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
    <a href="?status=all" class="config-tab" style="padding:8px 16px;font-size:13px;border-radius:8px;text-decoration:none;<?= $statusFilter=='all'?'background:#3b82f6;color:#fff;':'background:#f3f4f6;color:#6b7280;' ?>">全部</a>
    <a href="?status=0" class="config-tab" style="padding:8px 16px;font-size:13px;border-radius:8px;text-decoration:none;<?= $statusFilter=='0'?'background:#3b82f6;color:#fff;':'background:#f3f4f6;color:#6b7280;' ?>">待处理 <?= $pendingCount ?></a>
    <a href="?status=1" class="config-tab" style="padding:8px 16px;font-size:13px;border-radius:8px;text-decoration:none;<?= $statusFilter=='1'?'background:#3b82f6;color:#fff;':'background:#f3f4f6;color:#6b7280;' ?>">已通过</a>
    <a href="?status=2" class="config-tab" style="padding:8px 16px;font-size:13px;border-radius:8px;text-decoration:none;<?= $statusFilter=='2'?'background:#3b82f6;color:#fff;':'background:#f3f4f6;color:#6b7280;' ?>">已拒绝</a>
</div>

<div class="table-wrap">
<table>
<thead>
<tr><th>ID</th><th>用户名</th><th>邮箱</th><th>联系方式</th><th>申诉理由</th><th>证明</th><th>状态</th><th>提交时间</th><th>操作</th></tr>
</thead>
<tbody>
<?php foreach ($appeals as $a): ?>
<tr>
    <td><?= $a['id'] ?></td>
    <td><?= htmlspecialchars($a['username']) ?></td>
    <td class="text-small"><?= htmlspecialchars($a['email']) ?></td>
    <td class="text-small"><?= htmlspecialchars($a['contact']) ?></td>
    <td style="max-width:200px;"><div style="font-size:12px;color:#374151;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= htmlspecialchars($a['reason']) ?></div></td>
    <td><?php if (!empty($a['proof_image'])): ?><a href="/<?= htmlspecialchars($a['proof_image']) ?>" target="_blank" style="color:#3b82f6;font-size:12px;">查看图片</a><?php else: ?><span style="color:#9ca3af;font-size:12px;">无</span><?php endif; ?></td>
    <td>
        <?php if ($a['status'] == 0): ?><span class="badge" style="background:#fef3c7;color:#92400e;">待处理</span>
        <?php elseif ($a['status'] == 1): ?><span class="badge badge-success">已通过</span>
        <?php else: ?><span class="badge" style="background:#fee2e2;color:#991b1b;">已拒绝</span><?php endif; ?>
    </td>
    <td class="text-small text-muted"><?= date('Y-m-d H:i', $a['created_at'] ?? time()) ?></td>
    <td>
        <div style="display:flex;gap:4px;flex-wrap:wrap;">
            <button onclick="viewAppeal(<?= $a['id'] ?>)" class="btn-secondary btn-sm"><i class="ri-eye-line"></i> 查看</button>
            <?php if ($a['status'] == 0): ?>
            <button onclick="processAppealModal(<?= $a['id'] ?>, 1)" class="btn-primary btn-sm" style="background:#16a34a;border-color:#16a34a;"><i class="ri-check-line"></i> 通过</button>
            <button onclick="processAppealModal(<?= $a['id'] ?>, 2)" class="btn-danger btn-sm"><i class="ri-close-line"></i> 拒绝</button>
            <?php endif; ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('确定删除该申诉记录？')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="appeal_id" value="<?= $a['id'] ?>">
                <button type="submit" class="btn-secondary btn-sm" style="color:#9ca3af;"><i class="ri-delete-bin-line"></i></button>
            </form>
        </div>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($appeals)): ?><tr><td colspan="9" class="p-8 text-center text-gray-400">暂无申诉记录</td></tr><?php endif; ?>
</tbody>
</table>
</div>
</main>

<!-- 查看详情弹窗 -->
<div id="viewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:520px;margin:16px;max-height:85vh;overflow-y:auto;">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:16px;">申诉详情</h3>
        <div id="viewContent"></div>
        <div style="display:flex;gap:10px;margin-top:20px;">
            <button onclick="closeViewModal()" class="btn-secondary" style="flex:1;">关闭</button>
        </div>
    </div>
</div>

<!-- 处理申诉弹窗 -->
<div id="processModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="width:100%;max-width:420px;margin:16px;">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:12px;" id="processTitle">处理申诉</h3>
        <form method="POST">
            <input type="hidden" name="action" value="process">
            <input type="hidden" name="appeal_id" id="processId">
            <input type="hidden" name="appeal_status" id="processStatus">
            <div class="form-group"><label>处理备注（可选）</label><textarea name="admin_remark" rows="3" placeholder="填写处理说明..." style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;resize:vertical;"></textarea></div>
            <div style="display:flex;gap:10px;margin-top:16px;">
                <button type="button" onclick="closeProcessModal()" class="btn-secondary" style="flex:1;">取消</button>
                <button type="submit" class="btn-primary" style="flex:1;" id="processBtn">确认</button>
            </div>
        </form>
    </div>
</div>

<script>
const appeals = <?= json_encode($appeals) ?>;
function viewAppeal(id) {
    const a = appeals.find(x => x.id == id);
    if (!a) return;
    const statusText = a.status == 0 ? '待处理' : (a.status == 1 ? '已通过' : '已拒绝');
    document.getElementById('viewContent').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
            <div><span style="color:#9ca3af;">用户名：</span><strong>${a.username}</strong></div>
            <div><span style="color:#9ca3af;">状态：</span>${statusText}</div>
            <div><span style="color:#9ca3af;">邮箱：</span>${a.email}</div>
            <div><span style="color:#9ca3af;">联系方式：</span>${a.contact}</div>
        </div>
        <div style="margin-top:14px;"><span style="color:#9ca3af;font-size:13px;">申诉理由：</span><div style="margin-top:6px;padding:12px;background:#f9fafb;border-radius:8px;font-size:13px;line-height:1.6;color:#374151;">${a.reason}</div></div>
        ${a.proof_image ? `<div style="margin-top:12px;"><span style="color:#9ca3af;font-size:13px;">证明图片：</span><br><img src="/${a.proof_image}" style="max-width:100%;max-height:200px;border-radius:8px;margin-top:6px;"></div>` : ''}
        ${a.admin_remark ? `<div style="margin-top:12px;"><span style="color:#9ca3af;font-size:13px;">处理备注：</span><div style="margin-top:6px;padding:10px;background:#eff6ff;border-radius:8px;font-size:13px;color:#1e40af;">${a.admin_remark}</div></div>` : ''}
        <div style="margin-top:12px;font-size:12px;color:#9ca3af;">提交时间：${new Date(a.created_at * 1000).toLocaleString('zh-CN')}</div>
    `;
    document.getElementById('viewModal').style.display = 'flex';
}
function closeViewModal() { document.getElementById('viewModal').style.display = 'none'; }
document.getElementById('viewModal').addEventListener('click', function(e) { if(e.target===this) closeViewModal(); });

function processAppealModal(id, status) {
    document.getElementById('processId').value = id;
    document.getElementById('processStatus').value = status;
    document.getElementById('processTitle').textContent = status == 1 ? '通过申诉并解封' : '拒绝申诉';
    document.getElementById('processBtn').textContent = status == 1 ? '确认通过' : '确认拒绝';
    document.getElementById('processBtn').style.background = status == 1 ? '#16a34a' : '#dc2626';
    document.getElementById('processModal').style.display = 'flex';
}
function closeProcessModal() { document.getElementById('processModal').style.display = 'none'; }
document.getElementById('processModal').addEventListener('click', function(e) { if(e.target===this) closeProcessModal(); });

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}
</script>
</body>
</html>


