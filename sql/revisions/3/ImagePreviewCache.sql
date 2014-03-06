-- Image thumbnail cache

CREATE TABLE `ImagePreviewCache` (
  `Id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `UploadedFile` int(11) unsigned NOT NULL COMMENT 'FK',
  `Profile` varchar(25) COLLATE 'utf8_czech_ci' NOT NULL,
  `Filename` varchar(100) COLLATE 'utf8_czech_ci' NOT NULL,
  FOREIGN KEY (`UploadedFile`) REFERENCES `UploadedFiles` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) COMMENT='' ENGINE='InnoDB' COLLATE 'utf8_czech_ci'; -- 0.006 s

ALTER TABLE `ImagePreviewCache`
ADD INDEX `Profile` (`Profile`); -- 0.028 s
