CREATE TABLE `RatingsPost` (
  `ContentId` int(10) unsigned NOT NULL,
  `PostId` int(10) unsigned NOT NULL,
  `UserId` int(11) unsigned NOT NULL,
  `Rating` int(11) NOT NULL,
  KEY `PostId` (`PostId`),
  KEY `UserId` (`UserId`),
  KEY `ContentId` (`ContentId`),
  CONSTRAINT `ratingspost_ibfk_1` FOREIGN KEY (`PostId`) REFERENCES `posts` (`Id`),
  CONSTRAINT `ratingspost_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`),
  CONSTRAINT `ratingspost_ibfk_3` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin