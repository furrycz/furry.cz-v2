CREATE TABLE `Ignorelist` (
  `IgnoringUserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `IgnoredUserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `IgnoreType` int(10) unsigned NOT NULL,
  PRIMARY KEY (`IgnoringUserId`,`IgnoredUserId`),
  KEY `IgnoredUserId` (`IgnoredUserId`),
  CONSTRAINT `Ignorelist_ibfk_1` FOREIGN KEY (`IgnoringUserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Ignorelist_ibfk_2` FOREIGN KEY (`IgnoredUserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci