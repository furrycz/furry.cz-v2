CREATE TABLE `PollVotes` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `PollId` int(11) unsigned NOT NULL COMMENT 'FK',
  `AnswerId` int(11) unsigned NOT NULL COMMENT 'FK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK',
  PRIMARY KEY (`Id`),
  KEY `PollId` (`PollId`),
  KEY `AnswerId` (`AnswerId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `PollVotes_ibfk_6` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `PollVotes_ibfk_4` FOREIGN KEY (`PollId`) REFERENCES `Polls` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `PollVotes_ibfk_5` FOREIGN KEY (`AnswerId`) REFERENCES `PollAnswers` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci