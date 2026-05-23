```markdown
# 单页工坊 Pages

一个没什么花样的静态页面托管平台。注册、建项目、写 HTML，完事。

**作者**：星河 hm（[www.xhehm.com](https://www.xhehm.com)）  
**演示站**：[net.xhhe.cn](https://net.xhhe.cn)（官方部署的示例站，数据可能不定期重置，仅供体验功能，请勿用于正式项目）

## 功能

- 用户注册/登录（支持邮箱、手机验证码）
- 创建多个项目，每个项目可管理多个 `.html` 文件
- 在线编辑器，保存即生效
- 项目访问链接：`域名/项目ID`（首页）、`域名/项目ID/文件名.html`（其他页面）
- 后台管理：网站配置、用户管理、项目管理、SMTP/短信接口配置
- 手机端适配

## 技术栈

- PHP 7.4+
- MySQL 5.7+
- 原生 HTML/CSS/JS（毛玻璃蓝白风格）
- PHPMailer（邮件发送）
- PDO 数据库操作

## 快速开始

### 环境要求

- PHP 7.4 或更高（需启用 PDO、curl、GD 扩展）
- MySQL 5.7 或更高
- Nginx / Apache

### 安装步骤

1. 克隆代码到服务器 web 目录
   ```bash
   git clone https://github.com/yourname/single-page-workshop.git
   cd single-page-workshop
```

1. 设置目录权限（确保 PHP 有写入权限）
   ```bash
   chmod 755 ./
   chmod 777 install/
   chmod 777 users/
   chmod 777 log/
   ```
2. 浏览器访问 http://你的域名/install/，按提示填写：
   · 数据库信息
   · 管理员账号
   · 网站基本信息
   · SMTP / 短信 API 配置（可选，可在后台修改）
3. 安装完成后，访问前台首页，注册用户开始使用。

后台管理

· 地址：http://你的域名/admin/login.php
· 默认账号：安装时设置的管理员用户名和密码

目录结构

```
/
├── index.php              # 前台首页
├── login.php              # 用户登录
├── register.php           # 用户注册
├── forgot_password.php    # 找回密码
├── logout.php             # 退出
├── functions.php          # 核心函数库
├── style.css              # 全局样式
├── config.php             # 数据库配置（安装生成）
├── phpmailer/             # PHPMailer 库
├── users/                 # 用户中心
│   ├── dashboard.php      # 控制台
│   ├── projects.php       # 项目列表
│   ├── project_detail.php # 项目详情（文件管理）
│   ├── project_new.php    # 新建项目
│   ├── project_delete.php # 删除项目
│   ├── file_edit.php      # 编辑文件
│   ├── file_new.php       # 新建文件
│   ├── file_delete.php    # 删除文件
│   └── profile.php        # 个人中心
├── admin/                 # 后台管理
│   ├── login.php          # 后台登录
│   ├── index.php          # 仪表盘
│   ├── users.php          # 用户管理
│   ├── projects.php       # 项目管理
│   └── config.php         # 网站配置
└── install/               # 安装向导
    ├── index.php
    └── install.sql
```

伪静态配置

Nginx

在 server 块中添加：

```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
rewrite ^/([a-zA-Z0-9]+)/([a-zA-Z0-9_\-]+)\.html$ /index.php?pro=$1&file=$2.html last;
rewrite ^/([a-zA-Z0-9]+)$ /index.php?pro=$1 last;
```

Apache（.htaccess）

在网站根目录创建 .htaccess：

```apache
RewriteEngine On
RewriteRule ^([a-zA-Z0-9]+)/([a-zA-Z0-9_\-]+)\.html$ index.php?pro=$1&file=$2.html [L,QSA]
RewriteRule ^([a-zA-Z0-9]+)$ index.php?pro=$1 [L,QSA]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

文件存储

用户上传的 HTML 同时存入数据库和文件系统。文件系统路径：/users/{用户名}/projects/{项目ID}/。读取时优先从文件系统读取，提高性能。

短信与邮件

· 短信：接口地址硬编码为 https://sms.losels.eu.org/api/send.php，API Key 在后台填写。发送时 POST 参数：api_key、phone、code（6位数字）。
· 邮件：使用 PHPMailer，SMTP 参数在后台配置。
· 验证码有效期5分钟，60秒发送频率限制。

开源协议

MIT License。可自由使用、修改、商用。

问题反馈

GitHub Issues 区提问题，但不保证及时修复。欢迎 Pull Request。

```