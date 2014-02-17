CREATE TABLE `Bookmarks` (
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `TopicId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `CategoryId` int(11) unsigned DEFAULT NULL COMMENT 'FK',
  PRIMARY KEY (`UserId`,`TopicId`),
  KEY `CategoryId` (`CategoryId`),
  CONSTRAINT `Bookmarks_ibfk_1` FOREIGN KEY (`CategoryId`) REFERENCES `bookmarkcategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci