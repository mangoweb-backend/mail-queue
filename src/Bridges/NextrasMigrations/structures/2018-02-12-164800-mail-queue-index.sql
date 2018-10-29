ALTER TABLE `mails`
	ADD INDEX `sent_at_created_at` (`sent_at`, `created_at`);
