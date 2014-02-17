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
  CONSTRAINT `Topics_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Topics_ibfk_2` FOREIGN KEY (`CategoryId`) REFERENCES `topiccategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Topics_ibfk_3` FOREIGN KEY (`Header`) REFERENCES `cmspages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Topics_ibfk_4` FOREIGN KEY (`HeaderForDisallowedUsers`) REFERENCES `cmspages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci