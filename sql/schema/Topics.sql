CREATE TABLE `Topics` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` tinytext COLLATE utf8_czech_ci NOT NULL,
  `ContentId` int(11) unsigned NOT NULL COMMENT 'FK',
  `CategoryId` int(11) unsigned DEFAULT NULL COMMENT 'FK',
  `Header` int(11) unsigned NOT NULL COMMENT 'FK - CMS page Id',
  `HeaderForDisallowedUsers` int(11) unsigned DEFAULT NULL COMMENT 'FK - CMS page Id',
  `IsFlame` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Marks topic as flamewar',
  `Type` int(10) unsigned NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `CategoryId` (`CategoryId`),
  KEY `Header` (`Header`),
  KEY `HeaderForDisallowedUsers` (`HeaderForDisallowedUsers`),
  CONSTRAINT `Topics_ibfk_8` FOREIGN KEY (`HeaderForDisallowedUsers`) REFERENCES `CmsPages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Topics_ibfk_5` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Topics_ibfk_6` FOREIGN KEY (`CategoryId`) REFERENCES `TopicCategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Topics_ibfk_7` FOREIGN KEY (`Header`) REFERENCES `CmsPages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci