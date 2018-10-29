CREATE TABLE `mails` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`created_at` datetime NOT NULL,
	`sent_at` datetime DEFAULT NULL,
	`last_failed_at` datetime DEFAULT NULL,
	`failure_count` int(11) DEFAULT '0',
	`failure_message` varchar(255) DEFAULT NULL,
	`recipients` text NOT NULL,
	`subject` text NOT NULL,
	`body` mediumtext NOT NULL,
	`message` mediumblob NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
