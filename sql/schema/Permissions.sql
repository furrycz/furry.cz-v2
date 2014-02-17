CREATE TABLE `Permissions` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `CanListContent` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `CanViewContent` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `CanEditContentAndAttributes` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `CanEditHeader` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `CanEditOwnPosts` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `CanDeleteOwnPosts` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `CanReadPosts` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `CanDeletePosts` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `CanWritePosts` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `CanEditPermissions` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `CanEditPolls` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci