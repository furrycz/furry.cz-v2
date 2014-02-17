CREATE TABLE `ImageExpositions` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `Owner` int(11) unsigned NOT NULL COMMENT 'FK - User Id',
  `Description` tinytext COLLATE utf8_czech_ci,
  `Thumbnail` int(11) unsigned DEFAULT NULL COMMENT 'FK - Uploaded file Id',
  `Presentation` int(11) unsigned DEFAULT NULL COMMENT 'FK - CMS page Id',
  PRIMARY KEY (`Id`),
  KEY `Thumbnail` (`Thumbnail`),
  KEY `Presentation` (`Presentation`),
  KEY `Owner` (`Owner`),
  CONSTRAINT `ImageExpositions_ibfk_1` FOREIGN KEY (`Thumbnail`) REFERENCES `uploadedfiles` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `ImageExpositions_ibfk_2` FOREIGN KEY (`Presentation`) REFERENCES `cmspages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `ImageExpositions_ibfk_3` FOREIGN KEY (`Owner`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci