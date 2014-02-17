CREATE TABLE `Content` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Type` enum('Topic','Image','CMS','Writing','Event') COLLATE utf8_czech_ci NOT NULL,
  `TimeCreated` datetime NOT NULL,
  `Deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `DefaultPermissions` int(10) unsigned NOT NULL COMMENT 'FK - Permission Id',
  `LastModifiedTime` datetime NOT NULL,
  `LastModifiedByUser` int(10) unsigned DEFAULT NULL COMMENT 'FK - User Id',
  `IsForRegisteredOnly` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `IsForAdultsOnly` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `IsDiscussionAllowed` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `IsRatingAllowed` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`Id`),
  KEY `LastModifiedByUser` (`LastModifiedByUser`),
  KEY `DefaultPermissions` (`DefaultPermissions`),
  CONSTRAINT `Content_ibfk_1` FOREIGN KEY (`LastModifiedByUser`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Content_ibfk_3` FOREIGN KEY (`DefaultPermissions`) REFERENCES `permissions` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci