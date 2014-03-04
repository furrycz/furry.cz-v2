CREATE TABLE `RatingsPost` (
  `ContentId` int(10) unsigned NOT NULL,
  `PostId` int(10) unsigned NOT NULL,
  `UserId` int(11) unsigned NOT NULL,
  `Rating` int(11) NOT NULL,
  KEY `PostId` (`PostId`),
  KEY `UserId` (`UserId`),
  KEY `ContentId` (`ContentId`),
  CONSTRAINT `RatingsPost_ibfk_3` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`),
  CONSTRAINT `RatingsPost_ibfk_1` FOREIGN KEY (`PostId`) REFERENCES `Posts` (`Id`),
  CONSTRAINT `RatingsPost_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin