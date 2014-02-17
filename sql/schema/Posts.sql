CREATE TABLE `Posts` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Author` int(10) unsigned NOT NULL COMMENT 'FK - User Id',
  `Text` text COLLATE utf8_czech_ci NOT NULL,
  `TimeCreated` datetime NOT NULL,
  `Deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Edited` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `Author` (`Author`),
  CONSTRAINT `Posts_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Posts_ibfk_2` FOREIGN KEY (`Author`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci