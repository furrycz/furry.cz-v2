ALTER TABLE `PrivateMessages`
DROP `ReadByAddressee`,
ADD `Read` tinyint(1) unsigned NOT NULL,
ADD `ReadTime` datetime NOT NULL AFTER `Read`,
ADD `File` varchar(500) COLLATE 'utf8_czech_ci' NOT NULL AFTER `ReadTime`,
COMMENT='';
