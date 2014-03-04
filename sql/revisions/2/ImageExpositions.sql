ALTER TABLE `ImageExpositions`
DROP FOREIGN KEY `ImageExpositions_ibfk_1`,
ADD FOREIGN KEY (`Thumbnail`) REFERENCES `UploadedFiles` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `ImageExpositions`
DROP FOREIGN KEY `ImageExpositions_ibfk_2`,
ADD FOREIGN KEY (`Presentation`) REFERENCES `CmsPages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `ImageExpositions`
DROP FOREIGN KEY `ImageExpositions_ibfk_3`,
ADD FOREIGN KEY (`Owner`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;