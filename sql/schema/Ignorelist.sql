CREATE TABLE `Ignorelist` (
  `IgnoringUserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `IgnoredUserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `IgnoreType` int(10) unsigned NOT NULL,
  PRIMARY KEY (`IgnoringUserId`,`IgnoredUserId`),
  KEY `IgnoredUserId` (`IgnoredUserId`),
  CONSTRAINT `Ignorelist_ibfk_4` FOREIGN KEY (`IgnoredUserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Ignorelist_ibfk_3` FOREIGN KEY (`IgnoringUserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci