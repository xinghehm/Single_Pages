# 单页工坊 Pages

一个没什么花样的静态页面托管平台。注册、建项目、写 HTML，完事。

**作者**：星河 hm（www.xhehm.com）  
**演示站**：net.xhhe.cn

##文件目录
/
├── index.php              # 前台首页（支持游客项目访问）
├── deploy.php             # 游客免登录部署入口
├── deploy_action.php      # 游客上传处理
├── guest_manage.php       # 游客项目管理
├── guest_manage_action.php # 游客重新上传处理
├── guest_edit.php         # 游客在线编辑文件
├── login.php              # 用户登录
├── register.php           # 用户注册
├── forgot_password.php    # 找回密码
├── logout.php             # 退出
├── functions.php          # 核心函数库（含游客/zip/文件校验）
├── style.css              # 全局样式
├── config.php             # 数据库配置（安装生成）
├── phpmailer/             # PHPMailer 库
├── users/                 # 用户中心
│   ├── dashboard.php      # 控制台
│   ├── projects.php       # 项目列表
│   ├── project_detail.php # 项目详情（文件管理）
│   ├── project_new.php    # 新建项目
│   ├── project_delete.php # 删除项目
│   ├── file_edit.php      # 编辑文件（含快捷上传工具栏）
│   ├── file_new.php       # 新建文件
│   ├── file_delete.php    # 删除文件
│   ├── upload_zip.php     # 上传 ZIP 解压
│   ├── upload_zip_action.php  # ZIP 上传 AJAX 处理
│   ├── upload_file.php    # 上传图片等文件
│   ├── upload_file_action.php # 文件上传 AJAX 处理
│   ├── profile.php        # 个人中心
│   └── guests/            # 游客项目文件存储
│       └── projects/
├── admin/                 # 后台管理
│   ├── login.php          # 后台登录
│   ├── index.php          # 仪表盘
│   ├── users.php          # 用户管理
│   ├── projects.php       # 项目管理
│   └── config.php         # 网站配置
└── install/               # 安装向导
    ├── index.php
    ├── install.sql
    └── install_v2.sql     # 新增：游客项目表
    
    
## 功能

### 核心功能
- 用户注册/登录（支持邮箱、手机验证码）
- 创建多个项目，每个项目可管理多个 `.html` 文件
- 在线编辑器，保存即生效
- 项目访问链接：`域名/项目ID`（首页）、`域名/项目ID/文件名.html`（其他页面）
- 后台管理：网站配置、用户管理、项目管理、SMTP/短信接口配置
- 手机端适配

### 新增功能
- **游客免登录一键部署**：无需注册，上传 ZIP 或 HTML 文件即可获得访问链接和管理密钥
- **游客管理密钥**：通过密钥可随时修改、重新上传、在线编辑游客项目
- **项目支持上传 ZIP 解压**：登录用户在项目详情页可上传 ZIP 文件，系统自动解压
- **项目支持上传图片等静态资源**：支持 jpg/png/css/js/字体等文件，禁止上传可执行文件
- **编辑器快捷上传工具栏**：在编辑页可直接上传 ZIP 或图片

## 快速开始

### 环境要求
- PHP 7.4 或更高（需启用 PDO、curl、ZipArchive、fileinfo 扩展）
- MySQL 5.7 或更高
- Nginx / Apache

### 安装步骤

1. 克隆代码到服务器 web 目录
   ```bash
   git clone https://github.com/xinghehm/single-page.git
   cd single-page
2.设置目录权限chmod 755 ./
chmod 777 install/
chmod 777 users/
chmod 777 log/
mkdir -p users/guests/projects
chmod 777 users/guests/
3. 浏览器访问 http://你的域名/install/，按提示填写数据库信息、管理员账号、网站信息、SMTP/短信配置
4. 安装完成后，执行 install/install_v2.sql 创建游客项目表（或在 phpMyAdmin 中导入）
5. 访问前台首页，注册用户或直接使用免登录部署

后台管理

· 地址：http://你的域名/admin/login.php
· 默认账号：安装时设置的管理员用户名和密码
游客免登录部署说明

游客部署流程与 Cloudflare Drop 类似：

1. 访问 域名/deploy.php
2. 拖拽或选择 .zip / .html / .htm 文件
3. 系统自动创建独立项目并返回：
   · 访问链接：域名/项目ID（8位随机ID，以 g 开头）
   · 管理密钥：32位随机字符串，用于后续管理
4. 通过 域名/guest_manage.php?key=管理密钥 进入管理页面

游客项目与注册用户项目完全隔离，存储在 users/guests/projects/ 目录下，使用独立的 guest_projects 表管理。

游客项目限制

· ZIP 文件不超过 5MB，最多 200 个文件
· 单 HTML 文件不超过 2MB
· 自动过滤 .php / .exe / .apk / .sh 等可执行文件
· 无数量限制

文件上传安全策略

系统采用黑名单优先 + 白名单 + MIME 双重校验的安全模型：

1. 永久黑名单（恒覆盖）：php / phtml / exe / bat / sh / apk / jsp / asp 等可执行文件类型，无论配置如何一律拒绝
2. 白名单：仅允许 html/css/js/图片/字体/文档等静态资源
3. MIME 校验：使用 finfo_file 检测文件真实类型，与白名单交叉验证
4. ZIP 安全解压：过滤目录遍历（../）、空字节、绝对路径、__MACOSX 等
5. 文件大小限制：ZIP 5MB，普通文件 2MB

伪静态配置

Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
rewrite ^/([a-zA-Z0-9]+)/([a-zA-Z0-9_\-]+)\.html$ /index.php?pro=$1&file=$2.html last;
rewrite ^/([a-zA-Z0-9]+)$ /index.php?pro=$1 last;
```

Apache (.htaccess)

```apache
RewriteEngine On
RewriteRule ^([a-zA-Z0-9]+)/([a-zA-Z0-9_\-]+)\.html$ index.php?pro=$1&file=$2.html [L,QSA]
RewriteRule ^([a-zA-Z0-9]+)$ index.php?pro=$1 [L,QSA]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

文件存储

· 登录用户：/users/{用户名}/projects/{项目ID}/
· 游客项目：/users/guests/projects/{项目ID}/
· 同时存入数据库和文件系统，读取时优先文件系统

短信与邮件

· 短信：接口地址为 https://sms.losels.eu.org/api/send.php，API Key 在后台填写
· 邮件：使用 PHPMailer，SMTP 参数在后台配置
· 验证码有效期 5 分钟，60 秒发送频率限制

开源协议

MIT License。可自由使用、修改、商用。

问题反馈

GitHub Issues 区提问题，但不保证及时修复。欢迎 Pull Request。

```

---

## 十六、安装后必做操作

1. **执行数据库变更**：在 phpMyAdmin 或命令行中执行 `install/install_v2.sql`
2. **创建游客目录**：
   ```bash
   mkdir -p users/guests/projects
   chmod 777 users/guests/
```

3. 确认 PHP 扩展：ZipArchive 和 fileinfo 需启用（通常默认开启）

如果 ZipArchive 未启用，在 php.ini 中取消注释 extension=zip 并重启 PHP；fileinfo 同理取消注释 extension=fileinfo。