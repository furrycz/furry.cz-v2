CREATE TABLE `Ratings` (
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Rating` int(11) NOT NULL,
  PRIMARY KEY (`ContentId`,`UserId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `Ratings_ibfk_4` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Ratings_ibfk_3` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci