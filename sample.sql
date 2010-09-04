DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
	`user_id` bigint unsigned NOT NULL  AUTO_INCREMENT,
	`email` varchar(100)  NOT NULL,
	`password` char(32)  NOT NULL,
	`create_date` datetime  NOT NULL,
	`update_date` datetime  NOT NULL,
	PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `post`;
CREATE TABLE `post` (
	`post_id` bigint unsigned NOT NULL  AUTO_INCREMENT,
	`title` varchar(255)  NOT NULL,
	`uri` varchar(100)  NOT NULL,
	`text` text  NOT NULL,
	`publish` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`publish_date` datetime  NOT NULL,
	`create_date` datetime  NOT NULL,
	`update_date` datetime  NOT NULL,
	PRIMARY KEY (`post_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

