CREATE TABLE `Contacts` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK',
  `TypeId` int(10) unsigned DEFAULT NULL COMMENT 'FK - Contact type Id. NULL = user defined type',
  `Name` varchar(25) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'For user defined types',
  `Value` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `UserId` (`UserId`),
  KEY `TypeId` (`TypeId`),
  CONSTRAINT `Contacts_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Contacts_ibfk_2` FOREIGN KEY (`TypeId`) REFERENCES `contacttypes` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci