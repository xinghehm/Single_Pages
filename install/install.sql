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
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
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
('recommended_sms_platform', 'https://sms.losels.eu.org/');

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `admin_users` (`username`, `password`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');