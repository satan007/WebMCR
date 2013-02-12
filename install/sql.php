<?php 

// SQL BASE IMPORT

BD("SET FOREIGN_KEY_CHECKS=0;");

if ($mysql_rewrite) 
BD("DROP TABLE IF EXISTS `{$bd_names['users']}`,
                         `{$bd_names['ip_banning']}`,
						 `{$bd_names['news']}`,
						 `{$bd_names['news_categorys']}`,
                         `{$bd_names['groups']}`,
						 `{$bd_names['data']}`,
						 `{$bd_names['comments']}`,
                         `{$bd_names['servers']}`;");

BD("CREATE TABLE IF NOT EXISTS `{$bd_names['news']}` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `category_id` int(10) NOT NULL DEFAULT 1,
  `title` char(255) NOT NULL,
  `message` TEXT NOT NULL,
  `message_full` MEDIUMTEXT NOT NULL,
  `time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

BD("CREATE TABLE IF NOT EXISTS `{$bd_names['news_categorys']}` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` char(255) NOT NULL,
  `description` char(255) NOT NULL,
  `priority` int(10) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2;");

BD("CREATE TABLE IF NOT EXISTS `{$bd_names['servers']}` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `last_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `online` tinyint(1) DEFAULT 0,
  `rcon` varchar(255) DEFAULT '',
  `players` text default NULL,
  `method` tinyint(1) DEFAULT 0,
  `address` varchar(255) default NULL,
  `port` int(10) DEFAULT 25565,
  `name` varchar(255) default NULL,
  `info` char(255) default NULL,
  `numpl` char(32) default NULL,
  `slots` char(32) default NULL,
  `main_page` tinyint(1) DEFAULT 0,
  `news_page` tinyint(1) DEFAULT 0,
  `stat_page` tinyint(1) DEFAULT 0,
  `priority` tinyint(1) DEFAULT 0,
  `main` tinyint(1) DEFAULT 0,
  `refresh_time` smallint(3) NOT NULL DEFAULT '5',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

BD("CREATE TABLE IF NOT EXISTS `{$bd_names['groups']}` (
  `id`      int(10) NOT NULL AUTO_INCREMENT,
  `name`   char(64) NOT NULL,
  `lvl`     int(10) NOT NULL DEFAULT 1,
  `system` tinyint(1) NOT NULL DEFAULT 0,
  `change_skin` tinyint(1) NOT NULL DEFAULT 0,
  `change_pass` tinyint(1) NOT NULL DEFAULT 0,
  `change_login` tinyint(1) NOT NULL DEFAULT 0,
  `change_cloak` tinyint(1) NOT NULL DEFAULT 0,
  `add_news` tinyint(1) NOT NULL DEFAULT 0,
  `add_comm` tinyint(1) NOT NULL DEFAULT 0,
  `adm_comm` tinyint(1) NOT NULL DEFAULT 0,
  `max_fsize` int(10) NOT NULL DEFAULT 20,  
  `max_ratio` int(10) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=100;");

BD("CREATE TABLE IF NOT EXISTS `{$bd_names['comments']}` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `item_id` bigint(20) NOT NULL,
  `message` varchar(255) NOT NULL,
  `time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
BD("CREATE TABLE IF NOT EXISTS `{$bd_names['users']}` (
  `{$bd_users['id']}` bigint(20) NOT NULL AUTO_INCREMENT,
  `{$bd_users['login']}` char(32) DEFAULT NULL,
  `{$bd_users['female']}` tinyint(1) NOT NULL DEFAULT '0',
  `{$bd_users['email']}` varchar(50) default NULL,
  `{$bd_users['password']}` char(32) DEFAULT NULL,
  `{$bd_users['tmp']}` char(32) NOT NULL DEFAULT '0',
  `{$bd_users['ip']}` varchar(16) DEFAULT NULL,
  `{$bd_users['group']}` int(10) NOT NULL DEFAULT 1,
  `comments_num` int(10) NOT NULL,
  `gameplay_last` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `{$bd_users['ctime']}` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active_last` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `play_times` int(10) NOT NULL,
  `undress_times` int(10) NOT NULL,
  `default_skin` tinyint(1) NOT NULL DEFAULT '1',
  `{$bd_users['session']}` varchar(255) default NULL,
  `{$bd_users['server']}` varchar(255) default NULL,  

  PRIMARY KEY (`{$bd_users['id']}`),
  UNIQUE KEY `Login` (`{$bd_users['login']}`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

BD ("ALTER TABLE `{$bd_names['users']}` (
ADD `female` tinyint(1) NOT NULL DEFAULT '0',
ADD `tmp` char(32) NOT NULL DEFAULT '0',
ADD `ip` varchar(16) DEFAULT NULL,
ADD `group` int(10) NOT NULL DEFAULT 1,
ADD `comments_num` int(10) NOT NULL,
ADD `gameplay_last` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
ADD `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
ADD `active_last` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
ADD `play_times` int(10) NOT NULL,
ADD `undress_times` int(10) NOT NULL,
ADD `default_skin` tinyint(1) NOT NULL DEFAULT '1',
ADD `session` varchar(255) default NULL,
ADD `server` varchar(255) default NULL,
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

BD("CREATE TABLE IF NOT EXISTS `{$bd_names['ip_banning']}` (
  `IP` varchar(16) NOT NULL,
  `time_start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ban_until` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`IP`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

BD("CREATE TABLE IF NOT EXISTS `{$bd_names['data']}` (
  `property` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  UNIQUE KEY `property` (`property`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

BD("INSERT INTO `{$bd_names['news_categorys']}` (`id`,`name`) VALUES (1,'Без категории');");

BD("INSERT INTO `{$bd_names['data']}` (`property`, `value`) VALUES
('latest-game-build', '10746'),
('launcher-version', '13'),
('next-reg-time', '2'),
('email-verification', '0'),
('rcon-port', '0'),
('rcon-pass', '0'),
('rcon-serv', '0');");

BD("INSERT INTO `{$bd_names['groups']}` 
(`id`,`name`,`lvl`,`system`,`change_skin`,`change_pass`,`change_login`,`change_cloak`,`add_news`,`add_comm`,`adm_comm`) VALUES 
(1,'Пользователь',2,1,1,1,0,0,0,1,0), 
(2,'Заблокированный',0,1,0,0,0,0,0,0,0), 
(3,'Администратор',15,1,1,1,1,1,1,1,1), 
(4,'Непроверенный',1,1,0,0,0,0,0,0,0), 
(5,'VIP Игрок',5,0,1,1,1,1,0,1,0);");	
?>