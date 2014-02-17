CREATE TABLE `Events` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci,
  `StartTime` datetime DEFAULT NULL,
  `EndTime` datetime DEFAULT NULL,
  `CalendarLabel` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  `Header` int(10) unsigned DEFAULT NULL COMMENT 'FK - CMS page id',
  `Capacity` int(20) NOT NULL,
  `Place` varchar(500) COLLATE utf8_czech_ci NOT NULL,
  `GPS` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `Header` (`Header`),
  CONSTRAINT `Events_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Events_ibfk_2` FOREIGN KEY (`Header`) REFERENCES `cmspages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci