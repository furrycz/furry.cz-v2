CREATE TABLE `ForumTopicPresets` (
  `TopicId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Uninteresting` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Marks topic as uninteresting',
  PRIMARY KEY (`TopicId`,`UserId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `ForumTopicPresets_ibfk_4` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `ForumTopicPresets_ibfk_3` FOREIGN KEY (`TopicId`) REFERENCES `Topics` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci