-----------------------+++++++++++++++++++++++++++++++-----------------------

ALTER TABLE `boxes` ADD COLUMN `is_archived` INT(11) DEFAULT 0 NULL AFTER `edited_at`;

-----------------------+++++++++++++++++++++++++++++++-----------------------

ALTER TABLE `box_comments` ADD KEY `boxid` (`box_id`) , ADD FULLTEXT INDEX `comment` (`title`);

ALTER TABLE `boxes` ADD FULLTEXT INDEX `serialno` (`serial_no`);

ALTER TABLE `index_items` ADD KEY `box_id` (`box_id`) , ADD  FULLTEXT INDEX `title` (`title`);

ALTER TABLE `boxes`
    ADD COLUMN `edit_user_id` INT(11) DEFAULT 0 NULL AFTER `box_image`,
	ADD COLUMN `edit_user_status` INT(11) DEFAULT 0 NULL AFTER `edit_user_id`,
	ADD COLUMN `edited_at` TIMESTAMP NULL AFTER `edit_user_status`;

ALTER TABLE `boxes`
    ADD KEY `edit_userid` (`edit_user_id`) ,
  ADD  KEY `edit_user_status` (`edit_user_status`);
