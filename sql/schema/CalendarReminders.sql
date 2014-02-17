CREATE TABLE `CalendarReminders` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Time` datetime NOT NULL,
  `Name` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Description` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `CalendarLabel` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `CalendarReminders_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci