CREATE TABLE `Notifications` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Parent` varchar(100) COLLATE utf8_bin NOT NULL,
  `Text` varchar(150) COLLATE utf8_bin NOT NULL,
  `Time` datetime NOT NULL,
  `Href` varchar(100) COLLATE utf8_bin NOT NULL,
  `Image` varchar(200) COLLATE utf8_bin NOT NULL,
  `IsNotifed` tinyint(1) NOT NULL,
  `IsView` tinyint(1) NOT NULL,
  `UserId` int(11) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin