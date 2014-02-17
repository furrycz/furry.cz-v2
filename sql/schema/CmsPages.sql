CREATE TABLE `CmsPages` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(10) unsigned DEFAULT NULL COMMENT 'FK',
  `Name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci,
  `Text` mediumtext COLLATE utf8_czech_ci NOT NULL,
  `Alias` varchar(25) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'URL alias',
  PRIMARY KEY (`Id`),
  KEY `Alias` (`Alias`),
  KEY `ContentId` (`ContentId`),
  CONSTRAINT `CmsPages_ibfk_3` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci