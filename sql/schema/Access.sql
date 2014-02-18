CREATE TABLE `Access` (
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `PermissionId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ContentId`,`UserId`),
  KEY `UserId` (`UserId`),
  KEY `PermissionId` (`PermissionId`),
  CONSTRAINT `Access_ibfk_6` FOREIGN KEY (`PermissionId`) REFERENCES `Permissions` (`Id`) ON DELETE NO ACTION,
  CONSTRAINT `Access_ibfk_4` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION,
  CONSTRAINT `Access_ibfk_5` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci