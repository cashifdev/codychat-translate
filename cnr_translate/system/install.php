<?php
/* Translate add-on for Codychat 10.1 | Author: M Kashif | MIT License */
if(!defined('BOOM')){ die(); }
/* custom1 = site on (1/0), custom2 = engine (google|azure), custom3 = Azure key, custom4 = Azure region, custom5 = lowest rank that can translate, custom6 = uncached translations per member per minute */
$ad = array('name' => 'cnr_translate', 'access' => 1, 'custom1' => '1', 'custom2' => 'google', 'custom3' => '', 'custom4' => '', 'custom5' => '0', 'custom6' => '40');
$mysqli->query("CREATE TABLE IF NOT EXISTS `cnr_tr_cache` (
	`kind` TINYINT(1) NOT NULL,
	`mid` INT(11) NOT NULL,
	`lang` VARCHAR(12) NOT NULL,
	`src` VARCHAR(12) NOT NULL DEFAULT '',
	`txt` TEXT NOT NULL,
	`made` INT(11) NOT NULL DEFAULT 0,
	PRIMARY KEY (`kind`, `mid`, `lang`), KEY `idx_made` (`made`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$mysqli->query("CREATE TABLE IF NOT EXISTS `cnr_tr_pref` (
	`user_id` INT(11) NOT NULL,
	`lang` VARCHAR(12) NOT NULL DEFAULT '',
	`auto` TINYINT(1) NOT NULL DEFAULT 0,
	`changed` INT(11) NOT NULL DEFAULT 0,
	PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
?>
