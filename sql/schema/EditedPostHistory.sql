CREATE TABLE `EditedPostHistory` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `EditedPostId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Text` text COLLATE utf8_czech_ci NOT NULL,
  `TimeEdited` datetime NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `EditedPostId` (`EditedPostId`),
  CONSTRAINT `EditedPostHistory_ibfk_2` FOREIGN KEY (`EditedPostId`) REFERENCES `EditedPostHistory` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci