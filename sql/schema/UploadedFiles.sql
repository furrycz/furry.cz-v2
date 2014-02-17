CREATE TABLE `UploadedFiles` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Key` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `FileName` tinytext COLLATE utf8_czech_ci NOT NULL COMMENT 'Server file path',
  `Name` varchar(50) COLLATE utf8_czech_ci NOT NULL COMMENT 'File description',
  `SourceType` enum('GalleryImage','CmsImage','CmsAttachment','ForumImage','ForumAttachment','SpecialCmsImage','SpecialCmsAttachment','ExpositionThumbnail') COLLATE utf8_czech_ci DEFAULT NULL,
  `SourceId` int(11) unsigned DEFAULT NULL COMMENT 'FK - Id of event/topic/cms etc. where the file was uploaded from.',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci