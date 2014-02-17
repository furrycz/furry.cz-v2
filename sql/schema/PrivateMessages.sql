CREATE TABLE `PrivateMessages` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `SenderId` int(11) unsigned NOT NULL COMMENT 'FK - user Id',
  `AddresseeId` int(11) unsigned NOT NULL COMMENT 'FK - user Id',
  `Text` text COLLATE utf8_czech_ci NOT NULL,
  `TimeSent` datetime NOT NULL,
  `Deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Read` tinyint(1) unsigned NOT NULL,
  `ReadTime` datetime NOT NULL,
  `File` varchar(500) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `SenderId` (`SenderId`),
  KEY `AddresseeId` (`AddresseeId`),
  CONSTRAINT `PrivateMessages_ibfk_1` FOREIGN KEY (`SenderId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `PrivateMessages_ibfk_2` FOREIGN KEY (`AddresseeId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci