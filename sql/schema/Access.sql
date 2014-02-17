CREATE TABLE `Access` (
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `PermissionId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ContentId`,`UserId`),
  KEY `UserId` (`UserId`),
  KEY `PermissionId` (`PermissionId`),
  CONSTRAINT `Access_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION,
  CONSTRAINT `Access_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION,
  CONSTRAINT `Access_ibfk_3` FOREIGN KEY (`PermissionId`) REFERENCES `permissions` (`Id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci