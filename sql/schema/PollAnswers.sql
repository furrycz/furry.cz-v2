CREATE TABLE `PollAnswers` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `PollId` int(11) unsigned NOT NULL COMMENT 'FK',
  `Text` tinytext COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `PollId` (`PollId`),
  CONSTRAINT `PollAnswers_ibfk_1` FOREIGN KEY (`PollId`) REFERENCES `polls` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci