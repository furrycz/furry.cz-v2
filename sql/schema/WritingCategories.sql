CREATE TABLE `WritingCategories` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci,
  `IsForAdultsOnly` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci