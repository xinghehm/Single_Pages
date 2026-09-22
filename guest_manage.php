<?php
require_once 'functions.php';
$config = getConfig();

$accessKey = $_GET['key'] ?? '';
$project = $accessKey ? getGuestProjectByKey($accessKey) : null;

// 校验接口：?key=xxx&check=1
if (isset($_GET['check']) && $_GET['check'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['valid' => $project !== null]);
    exit;
}

// 没有 key 或 key 无效时，显示密钥输入页
if (!$project) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>项目管理 - <?= htmlspecialchars($config['site_name'] ?? '单页工坊Pages') ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
        <link rel="stylesheet" href="style.css?v=241">
    </head>
    <body class="min-h-screen">
        <nav class="glass-card mx-4 mt-4 px-6 py-3 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center text-white"><i class="ri-file-code-line"></i></div>
                <span class="font-bold text-lg"><?= htmlspecialchars($config['site_name'] ?? '单页工坊Pages') ?></span>
            </div>
            <a href="index.php" class="text-gray-600 hover:text-blue-600">返回首页</a>
        </nav>

        <div class="max-w-md mx-auto px-4 py-16">
            <div class="glass-card p-8">
                <h1 class="text-2xl font-bold mb-2 text-center">管理我的项目</h1>
                <p class="text-gray-500 text-sm text-center mb-6">输入部署时获得的密钥</p>

                <div id="errorBox" class="error mb-4" style="display:none;"></div>

                <form id="keyForm">
                    <input type="text" id="keyInput" placeholder="粘贴你的管理密钥"
                           class="w-full px-4 py-3 rounded-lg mb-4 font-mono text-sm"
                           autocomplete="off">
                    <button type="submit" class="btn-primary w-full py-3">
                        <i class="ri-login-circle-line"></i> 进入管理
                    </button>
                </form>

                <div class="mt-6 text-center text-sm text-gray-500">
                    还没有项目？<a href="deploy.php" class="text-blue-600">免登录部署一个</a>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm mt-6">
                    <p class="text-gray-600"><i class="ri-information-line"></i> 密钥是部署成功后系统生成的 32 位字符串，形如 <code class="bg-white px-1 rounded">a1b2c3d4...</code></p>
                </div>
            </div>
        </div>

        <script>
        // 自动填上次保存的密钥
        const lastKey = localStorage.getItem('last_guest_key');
        if (lastKey && /^[a-f0-9]{32}$/i.test(lastKey)) {
            document.getElementById('keyInput').value = lastKey;
        }

        document.getElementById('keyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const key = document.getElementById('keyInput').value.trim();
            const err = document.getElementById('errorBox');

            if (!key) {
                err.textContent = '请输入密钥';
                err.style.display = 'block';
                return;
            }
            if (!/^[a-f0-9]{32}$/i.test(key)) {
                err.textContent = '密钥格式不正确（应为 32 位十六进制字符）';
                err.style.display = 'block';
                return;
            }

            fetch('guest_manage.php?key=' + encodeURIComponent(key) + '&check=1')
                .then(r => r.json())
                .then(d => {
                    if (d.valid) {
                        localStorage.setItem('last_guest_key', key);
                        location.href = 'guest_manage.php?key=' + encodeURIComponent(key);
                    } else {
                        err.textContent = '密钥无效，请检查后重试';
                        err.style.display = 'block';
                    }
                })
                .catch(() => {
                    err.textContent = '网络错误，请重试';
                    err.style.display = 'block';
                });
        });
        </script>
    </body>
    </html>
    <?php
    exit;
}

$proId = $project['pro_id'];
$files = getGuestProjectFiles($proId);
$message = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理项目 - <?= htmlspecialchars($config['site_name'] ?? '单页工坊Pages') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=241">
    <style>
        .key-badge {
            background: #1e1e2e;
            color: #6ee7b7;
            padding: 6px 12px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .key-badge button {
            background: rgba(255,255,255,0.15);
            border: none;
            color: #e0e0e0;
            padding: 2px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
        }
        .key-badge button:hover { background: #e5e7eb; }
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999;
            padding: 16px;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: rgba(255,255,255,0.95);
            border-radius: 8px;
            padding: 28px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        @media (prefers-color-scheme: dark) {
            .modal-box { background: rgba(30,35,48,0.98); color: #e6f0ff; }
        }
        .drop-zone-sm {
            border: 2px dashed rgba(22,119,255,0.4);
            border-radius: 16px;
            padding: 30px 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            background: rgba(22,119,255,0.03);
        }
        .drop-zone-sm:hover, .drop-zone-sm.dragover {
            border-color: #2563eb;
            background: rgba(22,119,255,0.08);
        }
        .upload-progress {
            height: 6px;
            background: rgba(22,119,255,0.15);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 12px;
            display: none;
        }
        .upload-progress-inner {
            height: 100%;
            background: #2563eb;
            width: 0%;
            transition: width 0.3s;
        }
    </style>
</head>
<body class="min-h-screen">
    <nav class="glass-card mx-4 mt-4 px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center text-white"><i class="ri-file-code-line"></i></div>
            <span class="font-bold text-lg"><?= htmlspecialchars($config['site_name'] ?? '单页工坊Pages') ?></span>
        </div>
        <a href="index.php" class="text-gray-600 hover:text-blue-600">返回首页</a>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- 顶部：当前密钥 + 操作按钮 -->
        <div class="glass-card p-4 mb-6">
            <div class="flex justify-between items-center flex-wrap gap-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm text-gray-500">当前密钥：</span>
                    <div class="key-badge">
                        <span id="currentKey"><?= htmlspecialchars($accessKey) ?></span>
                        <button onclick="copyKey()">复制</button>
                    </div>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <a href="guest_manage.php" class="btn-secondary text-sm px-3 py-1">
                        <i class="ri-swap-line"></i> 切换项目
                    </a>
                    <a href="guest_file_new.php?key=<?= urlencode($accessKey) ?>" class="btn-secondary text-sm px-3 py-1">
                        <i class="ri-add-line"></i> 新建文件
                    </a>
                    <button onclick="openReupload()" class="btn-primary text-sm px-3 py-1">
                        <i class="ri-upload-2-line"></i> 重新上传
                    </button>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold">项目管理</h1>
                <p class="text-gray-500 text-sm mt-1">
                    访问链接：
                    <a href="/<?= htmlspecialchars($proId) ?>" target="_blank" class="text-blue-600">
                        /<?= htmlspecialchars($proId) ?> <i class="ri-external-link-line"></i>
                    </a>
                </p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="success mb-4"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="glass-card p-4">
            <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
                <div class="flex items-center gap-2 text-gray-600">
                    <i class="ri-file-list-line"></i> 文件列表（<?= count($files) ?> 个文件）
                </div>
                <a href="guest_file_new.php?key=<?= urlencode($accessKey) ?>" class="btn-secondary text-sm px-3 py-1">
                    <i class="ri-add-line"></i> 新建文件
                </a>
            </div>

            <?php if (empty($files)): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="ri-file-line text-6xl text-gray-300 mb-3 block"></i>
                    <p class="mb-4">暂无文件</p>
                    <a href="guest_file_new.php?key=<?= urlencode($accessKey) ?>" class="btn-primary text-sm px-4 py-1">
                        <i class="ri-add-line"></i> 新建一个文件
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-gray-500 border-b">
                            <tr>
                                <th class="text-left p-3">文件名</th>
                                <th class="text-left p-3">大小</th>
                                <th class="text-left p-3">更新时间</th>
                                <th class="text-left p-3">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($files as $f): ?>
                            <tr class="border-t">
                                <td class="p-3">
                                    <?= htmlspecialchars($f['filename']) ?>
                                    <?php if ($f['is_index']): ?>
                                        <span class="bg-green-100 text-green-600 px-2 py-0.5 rounded text-xs ml-1">首页</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3"><?= round($f['size'] / 1024, 1) ?> KB</td>
                                <td class="p-3"><?= date('Y-m-d H:i', $f['updated_at']) ?></td>
                                <td class="p-3">
                                    <a href="guest_edit.php?key=<?= urlencode($accessKey) ?>&file=<?= urlencode($f['filename']) ?>"
                                       class="text-blue-600">编辑</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass-card p-4 mt-6">
            <h3 class="font-medium mb-3">管理说明</h3>
            <ul class="text-sm text-gray-600 space-y-2 list-disc list-inside">
                <li>密钥请妥善保存，丢失后无法找回</li>
                <li>访问链接可直接分享给他人浏览</li>
                <li>点击"新建文件"可创建一个空的 HTML 模板</li>
                <li>点击"重新上传"可上传新的 ZIP 或 HTML 文件覆盖当前项目</li>
                <li>点击文件名旁的"编辑"可在线修改 HTML 内容</li>
                <li>游客项目与注册用户项目完全独立</li>
            </ul>
        </div>
    </div>

    <!-- 重新上传模态框 -->
    <div class="modal-overlay" id="reuploadModal">
        <div class="modal-box">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">重新上传</h3>
                <button onclick="closeReupload()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800 mb-4">
                <i class="ri-alert-line"></i> 重新上传会<strong>清空当前所有文件</strong>并覆盖，确定继续。
            </div>

            <div class="drop-zone-sm" id="modalDropZone">
                <i class="ri-upload-cloud-2-line text-4xl text-blue-500 mb-2"></i>
                <p class="font-medium">拖拽文件到此处，或点击选择</p>
                <p class="text-xs text-gray-500 mt-1">支持 .zip / .html / .htm，zip 不超过 5MB</p>
                <input type="file" id="modalFileInput" accept=".zip,.html,.htm" style="display:none;">
            </div>

            <div class="upload-progress" id="modalProgress">
                <div class="upload-progress-inner" id="modalProgressInner"></div>
            </div>

            <div id="modalMsg" class="text-sm mt-3" style="display:none;"></div>

            <div class="flex gap-3 mt-4">
                <button onclick="closeReupload()" class="btn-secondary flex-1">取消</button>
            </div>
        </div>
    </div>

    <script>
    const ACCESS_KEY = '<?= htmlspecialchars($accessKey, ENT_QUOTES) ?>';

    // 保存到本地
    localStorage.setItem('last_guest_key', ACCESS_KEY);

    function copyKey() {
        navigator.clipboard.writeText(ACCESS_KEY).then(() => {
            const btn = event.target;
            btn.textContent = '已复制';
            setTimeout(() => btn.textContent = '复制', 1500);
        });
    }

    function openReupload() {
        document.getElementById('reuploadModal').classList.add('show');
        document.getElementById('modalMsg').style.display = 'none';
        document.getElementById('modalProgress').style.display = 'none';
    }
    function closeReupload() {
        document.getElementById('reuploadModal').classList.remove('show');
        document.getElementById('modalFileInput').value = '';
    }

    const modalDropZone = document.getElementById('modalDropZone');
    const modalFileInput = document.getElementById('modalFileInput');

    modalDropZone.addEventListener('click', () => modalFileInput.click());
    modalDropZone.addEventListener('dragover', e => { e.preventDefault(); modalDropZone.classList.add('dragover'); });
    modalDropZone.addEventListener('dragleave', () => modalDropZone.classList.remove('dragover'));
    modalDropZone.addEventListener('drop', e => {
        e.preventDefault();
        modalDropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            modalFileInput.files = e.dataTransfer.files;
            doReupload();
        }
    });
    modalFileInput.addEventListener('change', () => { if (modalFileInput.files.length) doReupload(); });

    document.getElementById('reuploadModal').addEventListener('click', function(e) {
        if (e.target === this) closeReupload();
    });

    function doReupload() {
        const file = modalFileInput.files[0];
        if (!file) return;

        const name = file.name.toLowerCase();
        if (!name.endsWith('.zip') && !name.endsWith('.html') && !name.endsWith('.htm')) {
            showModalMsg('只支持 .zip / .html / .htm 文件', false);
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showModalMsg('文件不能超过 5MB', false);
            return;
        }

        document.getElementById('modalProgress').style.display = 'block';
        document.getElementById('modalProgressInner').style.width = '10%';
        document.getElementById('modalMsg').style.display = 'none';

        const fd = new FormData();
        fd.append('access_key', ACCESS_KEY);
        fd.append('file', file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'guest_manage_action.php', true);

        xhr.upload.onprogress = e => {
            if (e.lengthComputable) {
                const p = Math.round((e.loaded / e.total) * 90) + 5;
                document.getElementById('modalProgressInner').style.width = p + '%';
            }
        };

        xhr.onload = function() {
            document.getElementById('modalProgressInner').style.width = '100%';
            setTimeout(() => {
                document.getElementById('modalProgress').style.display = 'none';
                document.getElementById('modalProgressInner').style.width = '0%';
            }, 400);

            try {
                const d = JSON.parse(xhr.responseText);
                if (d.success) {
                    showModalMsg('上传成功，正在刷新...', true);
                    setTimeout(() => {
                        location.href = 'guest_manage.php?key=' + encodeURIComponent(ACCESS_KEY) + '&msg=' + encodeURIComponent('重新上传成功');
                    }, 800);
                } else {
                    showModalMsg(d.message || '上传失败', false);
                }
            } catch(e) {
                showModalMsg('服务器响应异常', false);
            }
        };

        xhr.onerror = function() {
            document.getElementById('modalProgress').style.display = 'none';
            showModalMsg('网络错误，请重试', false);
        };

        xhr.send(fd);
    }

    function showModalMsg(msg, ok) {
        const el = document.getElementById('modalMsg');
        el.textContent = msg;
        el.style.display = 'block';
        el.className = 'text-sm mt-3 ' + (ok ? 'text-green-600' : 'text-red-600');
    }
    </script>
</body>
</html>