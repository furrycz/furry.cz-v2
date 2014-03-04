CREATE TABLE `Writings` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci,
  `Text` text COLLATE utf8_czech_ci NOT NULL,
  `CategoryId` int(10) unsigned NOT NULL COMMENT 'FK',
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `CategoryId` (`CategoryId`),
  CONSTRAINT `Writings_ibfk_4` FOREIGN KEY (`CategoryId`) REFERENCES `WritingCategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Writings_ibfk_3` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci