CREATE TABLE `ContactTypes` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Url` varchar(50) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci