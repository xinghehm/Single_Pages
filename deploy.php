<?php
require_once 'functions.php';
$config = getConfig();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>免登录部署 - <?= htmlspecialchars($config['site_name'] ?? '单页工坊Pages') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=241">
    <style>
        .drop-zone {
            border: 2px dashed rgba(22,119,255,0.4);
            border-radius: 8px;
            padding: 60px 30px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            background: #ffffff;
        }
        .drop-zone:hover, .drop-zone.dragover {
            border-color: #2563eb;
            background: rgba(22,119,255,0.05);
            transform: scale(1.01);
        }
        .progress-bar {
            height: 6px;
            background: rgba(22,119,255,0.15);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 20px;
            display: none;
        }
        .progress-bar-inner {
            height: 100%;
            background: #2563eb;
            width: 0%;
            transition: width 0.3s;
        }
        .result-card {
            display: none;
            background: #ffffff;
            border-radius: 8px;
            padding: 28px;
            margin-top: 24px;
        }
        .result-card.show { display: block; }
        .key-display {
            background: #1e1e2e;
            color: #6ee7b7;
            padding: 12px 16px;
            border-radius: 12px;
            font-family: monospace;
            font-size: 16px;
            word-break: break-all;
            position: relative;
        }
        .copy-btn {
            position: absolute;
            right: 8px;
            top: 8px;
            background: rgba(255,255,255,0.15);
            border: none;
            color: #e0e0e0;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }
        .copy-btn:hover { background: #e5e7eb; }
        @media (prefers-color-scheme: dark) {
            .drop-zone { background: #1a1d27; }
            .result-card { background: rgba(30,35,48,0.7); }
        }
    </style>
</head>
<body class="min-h-screen">
    <nav class="glass-card mx-4 mt-4 px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center text-white"><i class="ri-file-code-line"></i></div>
            <span class="font-bold text-lg"><?= htmlspecialchars($config['site_name'] ?? '单页工坊Pages') ?></span>
        </div>
        <div class="flex gap-4">
            <a href="index.php" class="text-gray-600 hover:text-blue-600">首页</a>
            <a href="guest_manage.php" class="text-gray-600 hover:text-blue-600">管理项目</a>
            <a href="login.php" class="btn-primary text-sm px-4 py-1">登录</a>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold text-center mb-2">免登录部署</h1>
        <p class="text-center text-gray-500 mb-8">上传 HTML 或 ZIP 文件，即刻获得访问链接和管理密钥</p>

        <form id="uploadForm" enctype="multipart/form-data">
            <div class="drop-zone" id="dropZone">
                <i class="ri-upload-cloud-2-line text-5xl text-blue-500 mb-4"></i>
                <p class="text-lg font-medium">拖拽文件到此处，或点击选择</p>
                <p class="text-sm text-gray-500 mt-2">支持 .zip / .html / .htm 文件，zip 大小不超过 5MB</p>
                <input type="file" name="file" id="fileInput" accept=".zip,.html,.htm" style="display:none;">
            </div>
            <div class="progress-bar" id="progressBar">
                <div class="progress-bar-inner" id="progressInner"></div>
            </div>
        </form>

        <div class="result-card" id="resultCard">
            <h3 class="text-xl font-bold mb-4"><i class="ri-checkbox-circle-line text-green-500"></i> 部署成功</h3>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">访问链接</label>
                    <div class="key-display mt-1" style="color:#60a5fa;">
                        <span id="resultUrl"></span>
                        <button class="copy-btn" onclick="copyText('resultUrl')">复制</button>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">管理密钥（用于后续修改，请妥善保存）</label>
                    <div class="key-display mt-1">
                        <span id="resultKey"></span>
                        <button class="copy-btn" onclick="copyText('resultKey')">复制</button>
                    </div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800">
                    <i class="ri-alert-line"></i> 请务必保存管理密钥。丢失后无法找回，也无法修改项目。
                </div>
                <div class="flex gap-3 mt-4">
                    <a id="manageLink" href="#" class="btn-primary flex-1 text-center py-2">管理项目</a>
                    <button onclick="resetForm()" class="btn-secondary px-6 py-2">再传一个</button>
                </div>
            </div>
        </div>

        <div id="errorBox" class="error mt-4" style="display:none;"></div>
    </div>

    <script>
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const uploadForm = document.getElementById('uploadForm');
    const progressBar = document.getElementById('progressBar');
    const progressInner = document.getElementById('progressInner');
    const resultCard = document.getElementById('resultCard');
    const errorBox = document.getElementById('errorBox');

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            doUpload();
        }
    });
    fileInput.addEventListener('change', () => { if (fileInput.files.length) doUpload(); });

    function doUpload() {
        const file = fileInput.files[0];
        if (!file) return;

        const name = file.name.toLowerCase();
        if (!name.endsWith('.zip') && !name.endsWith('.html') && !name.endsWith('.htm')) {
            showError('只支持 .zip / .html / .htm 文件');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showError('文件不能超过 5MB');
            return;
        }

        errorBox.style.display = 'none';
        resultCard.classList.remove('show');
        progressBar.style.display = 'block';
        progressInner.style.width = '10%';

        const fd = new FormData();
        fd.append('file', file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'deploy_action.php', true);

        xhr.upload.onprogress = e => {
            if (e.lengthComputable) {
                const p = Math.round((e.loaded / e.total) * 90) + 5;
                progressInner.style.width = p + '%';
            }
        };

        xhr.onload = function() {
            progressInner.style.width = '100%';
            setTimeout(() => { progressBar.style.display = 'none'; progressInner.style.width = '0%'; }, 400);

            try {
                const data = JSON.parse(xhr.responseText);
                if (data.success) {
                    document.getElementById('resultUrl').textContent = data.url;
                    document.getElementById('resultKey').textContent = data.access_key;
                    document.getElementById('manageLink').href = 'guest_manage.php?key=' + data.access_key;
                    resultCard.classList.add('show');
                } else {
                    showError(data.message || '部署失败');
                }
            } catch(e) {
                showError('服务器响应异常');
            }
        };

        xhr.onerror = function() {
            progressBar.style.display = 'none';
            showError('网络错误，请重试');
        };

        xhr.send(fd);
    }

    function showError(msg) {
        errorBox.textContent = msg;
        errorBox.style.display = 'block';
    }

    function copyText(id) {
        const text = document.getElementById(id).textContent;
        navigator.clipboard.writeText(text).then(() => {
            event.target.textContent = '已复制';
            setTimeout(() => event.target.textContent = '复制', 1500);
        });
    }

    function resetForm() {
        fileInput.value = '';
        resultCard.classList.remove('show');
        errorBox.style.display = 'none';
    }
    </script>
</body>
</html>