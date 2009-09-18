DROP TABLE IF EXISTS `define`;
~
DROP TABLE IF EXISTS `locale`;
~
CREATE TABLE `define`
(
	`id` INTEGER  NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(63)  NOT NULL,
	`description` VARCHAR(1023),
	`value` VARCHAR(255),
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`)
) Type=MEMORY;

~

CREATE TABLE `locale`
(
	`id` INTEGER  NOT NULL AUTO_INCREMENT,
	`code` VARCHAR(63),
	PRIMARY KEY (`id`),
	UNIQUE KEY `code` (`code`)
) Type=MEMORY;

~

