CREATE TABLE `BookmarkCategories` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `BookmarkCategories_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci