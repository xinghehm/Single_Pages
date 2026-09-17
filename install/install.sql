SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `registered_at` int(11) NOT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expire` int(11) DEFAULT NULL,
  `quota` int(11) DEFAULT 500 COMMENT '存储配额(MB)',
  `group_id` int(11) DEFAULT NULL COMMENT '用户组ID',
  `bg_color` varchar(50) DEFAULT NULL COMMENT '专属背景颜色',
  `group_expire_at` int(11) DEFAULT NULL COMMENT '用户组到期时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '用户组名称',
  `project_limit` int(11) NOT NULL DEFAULT 5 COMMENT '项目数限制',
  `description` text COMMENT '描述',
  `is_default` tinyint(1) DEFAULT 0 COMMENT '是否默认组',
  `is_public` tinyint(1) DEFAULT 0 COMMENT '是否公开售卖',
  `price` decimal(10,2) DEFAULT 0.00 COMMENT '价格(元)',
  `duration` int(11) DEFAULT 0 COMMENT '到期天数(0=永久)',
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `user_groups` (`name`, `project_limit`, `description`, `is_default`, `created_at`) VALUES
('免费用户', 5, '新注册用户默认组，最多5个项目', 1, UNIX_TIMESTAMP());

CREATE TABLE IF NOT EXISTS `guest_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pro_id` varchar(16) NOT NULL,
  `access_key` varchar(64) NOT NULL COMMENT '游客管理密钥',
  `name` varchar(100) NOT NULL DEFAULT 'guest_project',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `file_count` int(11) DEFAULT 0,
  `total_size` int(11) DEFAULT 0 COMMENT '字节',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pro_id` (`pro_id`),
  UNIQUE KEY `access_key` (`access_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `out_trade_no` varchar(64) NOT NULL COMMENT '商户订单号',
  `trade_no` varchar(64) DEFAULT NULL COMMENT '平台订单号',
  `money` decimal(10,2) NOT NULL COMMENT '支付金额',
  `type` varchar(20) DEFAULT NULL COMMENT '支付方式',
  `status` tinyint(1) DEFAULT 0 COMMENT '0未支付 1已支付',
  `created_at` int(11) NOT NULL,
  `paid_at` int(11) DEFAULT NULL,
  `expire_at` int(11) DEFAULT NULL COMMENT '用户组到期时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `out_trade_no` (`out_trade_no`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pro_id` varchar(16) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pro_id` (`pro_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `project_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pro_id` varchar(16) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `is_index` tinyint(1) DEFAULT 0,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pro_id` (`pro_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `site_config` (
  `key` varchar(50) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `site_config` (`key`, `value`) VALUES
('site_name', '云上云诺'),
('site_logo', 'https://www.xhehm.com/assets/img/LOGO-Pages.png'),
('site_footer', '© 2026 云上云诺 - 静态页面托管平台'),
('stats_projects', '100+'),
('stats_features', '10+'),
('tagline', '轻松托管，全程赋能'),
('tags', '企业级托管服务 上线更快|无需服务器 上传即用|新用户永久免费福利'),
('smtp_host', ''),
('smtp_port', '465'),
('smtp_user', ''),
('smtp_pass', ''),
('smtp_secure', 'ssl'),
('smtp_from_email', ''),
('smtp_from_name', '云上云诺'),
('sms_api_url', ''),
('sms_api_key', ''),
('sms_enabled', '0'),
('email_verify_enabled', '1'),
('sms_verify_enabled', '0'),
('force_bind_phone', '0'),
('default_quota', '500'),
('recommended_sms_platform', 'https://sms.losels.eu.org/'),
('yipay_url', ''),
('yipay_pid', ''),
('yipay_key', '');

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `admin_users` (`username`, `password`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
