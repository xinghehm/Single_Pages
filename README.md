# Single_Pages 单页工坊

一个轻量级的静态页面托管平台，支持用户注册、项目管理、文件编辑、免登录部署、访客项目管理等功能。

## 功能特性

### 用户功能
- 用户注册/登录（邮箱、手机、验证码）
- 项目创建与管理（项目数限制）
- 在线文件编辑器（支持HTML/CSS/JS）
- ZIP打包上传/下载
- 专属背景颜色自定义
- 用户组系统（免费/付费用户组）
- 购买用户组（易支付对接，支付宝/微信/QQ）

### 访客功能
- 免登录部署静态页面
- 访客项目管理（密钥验证）
- 项目直接访问（/{pro_id}）

### 后台管理
- 仪表盘（用户/项目/文件统计、趋势图表）
- 用户管理（增删改查、设置用户组、修改配额）
- 用户组管理（增删改查、设默认、售卖设置）
- 项目管理（用户项目/访客项目、文件查看、直接跳转）
- 网站配置（基本设置、SMTP邮件、短信验证码、易支付）
- 订单管理

## 技术栈
- PHP 8.2+
- MySQL 5.7+
- Tailwind CSS
- Remix Icon
- Chart.js

## 安装部署

1. 将代码上传到网站根目录
2. 访问 `/install/` 进行安装
3. 配置数据库信息
4. 安装完成后删除 `/install/` 目录或保留 `install.lock`

### Nginx配置
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /www/wwwroot/your-domain;
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-82.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 目录结构
```
├── admin/              # 后台管理
├── users/              # 用户中心
├── assets/             # 静态资源
│   └── YiPay/          # 易支付SDK
├── install/            # 安装程序
├── functions.php       # 核心函数库
├── config.php          # 配置文件（安装后生成）
├── style.css           # 全局样式
├── pay_notify.php      # 支付异步回调
├── pay_return.php      # 支付同步回调
├── deploy.php          # 免登录部署
├── guest_manage.php    # 访客项目管理
└── README.md
```

## 更新日志

### v2.4.2 (2026-09-23)

**修复优化：**
- 修复在线升级系统在禁用 exec() 环境下的报错
  - 文件备份改用 PharData 逐文件打包（不依赖系统 zip 命令）
  - 解压升级包改用 PharData::extractTo（不依赖系统 unzip 命令）
  - 自动识别 GitHub zip 套一层文件夹的结构并正确解压
- 自动排除 .git/node_modules/backup_upgrade 目录

### v2.3 (2026-09-17)

**新增功能：**
- 🎉 用户组系统 + 项目数限制
  - 后台可创建/编辑/删除用户组
  - 每个用户组可设置项目数上限
  - 新注册用户默认加入免费用户组（5个项目）
  - 后台用户管理可直接给用户切换用户组
- 💳 易支付对接 + 用户组购买
  - 后台配置易支付（URL、商户号、密钥）
  - 用户组可设置是否公开售卖、价格、有效期
  - 用户端购买页面，支持支付宝/微信/QQ支付
  - 支付回调自动验签、开通用户组、设置到期时间
  - 登录时自动检查用户组到期，过期降回免费组
- 📱 后台移动端适配优化
  - 顶部毛玻璃导航栏 + 动画汉堡按钮
  - 侧边栏滑出动画 + 半透明遮罩
  - 表格横向滚动，无需缩放
  - 统计卡片响应式布局
- 🎨 用户专属背景颜色
  - 用户可在个人设置自定义背景色（纯色/渐变）
  - 全局生效

**修复bug：**
- 修复后台短信配置重复显示的问题
- 修复functions.php PHP闭合标签导致代码输出的问题
- 修复移动端侧边栏不显示的问题
- 修复flex布局溢出导致移动端媒体查询不触发的问题

**其他：**
- install.sql更新至8个表（含orders、user_groups售卖字段）
- 后台所有页面添加用户组管理导航入口

### v2.2 (2026-09-16)
- 后台项目管理添加访客文件查看功能
- 访客项目管理添加密钥输入入口
- 修复多个已知bug

### v2.1
- 免登录部署功能
- 访客项目管理
- 在线文件编辑器优化

### v2.0
- 全新UI设计
- 用户注册/登录系统
- 项目管理与文件编辑
- 后台管理系统

## 配置说明

### 易支付配置
1. 后台 → 网站配置 → 易支付配置
2. 填写易支付网站URL（如 `https://pay.liohg.top/`）
3. 填写商户号（PID）和商户密钥（Key）
4. 在用户组管理中设置需要售卖的用户组（勾选公开售卖、填写价格和有效期）

### 邮件配置
1. 后台 → 网站配置 → SMTP邮件配置
2. 填写SMTP服务器、端口、账号、密码
3. 开启邮箱验证功能

### 短信配置
1. 后台 → 网站配置 → 短信验证码配置
2. 填写短信API地址和密钥
3. 开启短信验证功能

## 许可证
MIT License

## 作者
星河hm - https://www.xhehm.com
