CREATE TABLE `ForumCategoryPresets` (
  `TopicCategoryId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Hidden` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Toggles visibility of forum category',
  PRIMARY KEY (`TopicCategoryId`,`UserId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `ForumCategoryPresets_ibfk_4` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `ForumCategoryPresets_ibfk_3` FOREIGN KEY (`TopicCategoryId`) REFERENCES `TopicCategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci