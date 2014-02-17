CREATE TABLE `PollVotes` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `PollId` int(11) unsigned NOT NULL COMMENT 'FK',
  `AnswerId` int(11) unsigned NOT NULL COMMENT 'FK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK',
  PRIMARY KEY (`Id`),
  KEY `PollId` (`PollId`),
  KEY `AnswerId` (`AnswerId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `PollVotes_ibfk_1` FOREIGN KEY (`PollId`) REFERENCES `polls` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `PollVotes_ibfk_2` FOREIGN KEY (`AnswerId`) REFERENCES `pollanswers` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `PollVotes_ibfk_3` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci