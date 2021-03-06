CREATE TABLE `Polls` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(11) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci NOT NULL,
  `MaxVotesPerUser` int(11) DEFAULT NULL,
  `SaveIndividualVotes` tinyint(1) unsigned NOT NULL,
  `DisplayVotersTo` enum('Nobody,','OtherVoters,','Everybody') COLLATE utf8_czech_ci NOT NULL COMMENT 'Controls who can see voter names',
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  CONSTRAINT `Polls_ibfk_2` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci