CREATE TABLE `ImagePreviewCache` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `UploadedFile` int(11) unsigned NOT NULL COMMENT 'FK',
  `Profile` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Filename` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `UploadedFile` (`UploadedFile`),
  KEY `Profile` (`Profile`),
  CONSTRAINT `ImagePreviewCache_ibfk_1` FOREIGN KEY (`UploadedFile`) REFERENCES `UploadedFiles` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci