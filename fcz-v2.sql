-- Adminer 3.3.3 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `Access`;
CREATE TABLE `Access` (
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `PermissionId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ContentId`,`UserId`),
  KEY `UserId` (`UserId`),
  KEY `PermissionId` (`PermissionId`),
  CONSTRAINT `Access_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION,
  CONSTRAINT `Access_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION,
  CONSTRAINT `Access_ibfk_3` FOREIGN KEY (`PermissionId`) REFERENCES `Permissions` (`Id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `BookmarkCategories`;
CREATE TABLE `BookmarkCategories` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `BookmarkCategories_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Bookmarks`;
CREATE TABLE `Bookmarks` (
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `TopicId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `CategoryId` int(11) unsigned DEFAULT NULL COMMENT 'FK',
  PRIMARY KEY (`UserId`,`TopicId`),
  KEY `CategoryId` (`CategoryId`),
  CONSTRAINT `Bookmarks_ibfk_1` FOREIGN KEY (`CategoryId`) REFERENCES `BookmarkCategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `CalendarReminders`;
CREATE TABLE `CalendarReminders` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Time` datetime NOT NULL,
  `Name` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Description` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `CalendarLabel` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `CalendarReminders_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `CmsPages`;
CREATE TABLE `CmsPages` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(10) unsigned DEFAULT NULL COMMENT 'FK',
  `Name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci,
  `Text` mediumtext COLLATE utf8_czech_ci NOT NULL,
  `Alias` varchar(25) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'URL alias',
  PRIMARY KEY (`Id`),
  KEY `Alias` (`Alias`),
  KEY `ContentId` (`ContentId`),
  CONSTRAINT `CmsPages_ibfk_3` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `ContactTypes`;
CREATE TABLE `ContactTypes` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Url` varchar(50) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Contacts`;
CREATE TABLE `Contacts` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK',
  `TypeId` int(10) unsigned DEFAULT NULL COMMENT 'FK - Contact type Id. NULL = user defined type',
  `Name` varchar(25) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'For user defined types',
  `Value` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `UserId` (`UserId`),
  KEY `TypeId` (`TypeId`),
  CONSTRAINT `Contacts_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Contacts_ibfk_2` FOREIGN KEY (`TypeId`) REFERENCES `ContactTypes` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Content`;
CREATE TABLE `Content` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Type` enum('Topic','Image','CMS','Writing','Event') COLLATE utf8_czech_ci NOT NULL,
  `TimeCreated` datetime NOT NULL,
  `Deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `DefaultPermissions` int(10) unsigned NOT NULL COMMENT 'FK - Permission Id',
  `LastModifiedTime` datetime NOT NULL,
  `LastModifiedByUser` int(10) unsigned DEFAULT NULL COMMENT 'FK - User Id',
  `IsForRegisteredOnly` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `IsForAdultsOnly` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `IsDiscussionAllowed` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `IsRatingAllowed` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`Id`),
  KEY `LastModifiedByUser` (`LastModifiedByUser`),
  KEY `DefaultPermissions` (`DefaultPermissions`),
  CONSTRAINT `Content_ibfk_1` FOREIGN KEY (`LastModifiedByUser`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Content_ibfk_3` FOREIGN KEY (`DefaultPermissions`) REFERENCES `Permissions` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `EditedPostHistory`;
CREATE TABLE `EditedPostHistory` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `EditedPostId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Text` text COLLATE utf8_czech_ci NOT NULL,
  `TimeEdited` datetime NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `EditedPostId` (`EditedPostId`),
  CONSTRAINT `EditedPostHistory_ibfk_1` FOREIGN KEY (`EditedPostId`) REFERENCES `EditedPostHistory` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `EventAttendances`;
CREATE TABLE `EventAttendances` (
  `EventId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Attending` enum('Yes','No','Maybe') COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`EventId`,`UserId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `EventAttendances_ibfk_1` FOREIGN KEY (`EventId`) REFERENCES `Events` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `EventAttendances_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Events`;
CREATE TABLE `Events` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci,
  `StartTime` datetime DEFAULT NULL,
  `EndTime` datetime DEFAULT NULL,
  `CalendarLabel` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  `Header` int(10) unsigned DEFAULT NULL COMMENT 'FK - CMS page id',
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `Header` (`Header`),
  CONSTRAINT `Events_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Events_ibfk_2` FOREIGN KEY (`Header`) REFERENCES `CmsPages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `ForumCategoryPresets`;
CREATE TABLE `ForumCategoryPresets` (
  `TopicCategoryId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Hidden` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Toggles visibility of forum category',
  PRIMARY KEY (`TopicCategoryId`,`UserId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `ForumCategoryPresets_ibfk_1` FOREIGN KEY (`TopicCategoryId`) REFERENCES `TopicCategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `ForumCategoryPresets_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `ForumTopicPresets`;
CREATE TABLE `ForumTopicPresets` (
  `TopicId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Uninteresting` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Marks topic as uninteresting',
  PRIMARY KEY (`TopicId`,`UserId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `ForumTopicPresets_ibfk_1` FOREIGN KEY (`TopicId`) REFERENCES `Topics` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `ForumTopicPresets_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Ignorelist`;
CREATE TABLE `Ignorelist` (
  `IgnoringUserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `IgnoredUserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  PRIMARY KEY (`IgnoringUserId`,`IgnoredUserId`),
  KEY `IgnoredUserId` (`IgnoredUserId`),
  CONSTRAINT `Ignorelist_ibfk_1` FOREIGN KEY (`IgnoringUserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Ignorelist_ibfk_2` FOREIGN KEY (`IgnoredUserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `ImageExpositions`;
CREATE TABLE `ImageExpositions` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `Owner` int(11) unsigned NOT NULL COMMENT 'FK - User Id',
  `Description` tinytext COLLATE utf8_czech_ci,
  `Thumbnail` int(11) unsigned DEFAULT NULL COMMENT 'FK - Uploaded file Id',
  `Presentation` int(11) unsigned DEFAULT NULL COMMENT 'FK - CMS page Id',
  PRIMARY KEY (`Id`),
  KEY `Thumbnail` (`Thumbnail`),
  KEY `Presentation` (`Presentation`),
  KEY `Owner` (`Owner`),
  CONSTRAINT `ImageExpositions_ibfk_1` FOREIGN KEY (`Thumbnail`) REFERENCES `UploadedFiles` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `ImageExpositions_ibfk_2` FOREIGN KEY (`Presentation`) REFERENCES `CmsPages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `ImageExpositions_ibfk_3` FOREIGN KEY (`Owner`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Images`;
CREATE TABLE `Images` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(11) unsigned NOT NULL COMMENT 'FK',
  `UploadedFileId` int(11) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `Description` text COLLATE utf8_czech_ci NOT NULL,
  `Exposition` int(10) unsigned DEFAULT NULL COMMENT 'FK',
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `UploadedFileId` (`UploadedFileId`),
  CONSTRAINT `Images_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Images_ibfk_2` FOREIGN KEY (`UploadedFileId`) REFERENCES `UploadedFiles` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `LastVisits`;
CREATE TABLE `LastVisits` (
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Time` datetime NOT NULL,
  PRIMARY KEY (`ContentId`,`UserId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `LastVisits_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `LastVisits_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Ownership`;
CREATE TABLE `Ownership` (
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  PRIMARY KEY (`ContentId`,`UserId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `Ownership_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION,
  CONSTRAINT `Ownership_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Permissions`;
CREATE TABLE `Permissions` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `CanListContent` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `CanViewContent` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `CanEditContentAndAttributes` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `CanEditHeader` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `CanEditOwnPosts` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `CanDeleteOwnPosts` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `CanReadPosts` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `CanDeletePosts` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `CanWritePosts` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `CanEditPermissions` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `CanEditPolls` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `PollAnswers`;
CREATE TABLE `PollAnswers` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `PollId` int(11) unsigned NOT NULL COMMENT 'FK',
  `Text` tinytext COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `PollId` (`PollId`),
  CONSTRAINT `PollAnswers_ibfk_1` FOREIGN KEY (`PollId`) REFERENCES `Polls` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `PollVotes`;
CREATE TABLE `PollVotes` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `PollId` int(11) unsigned NOT NULL COMMENT 'FK',
  `AnswerId` int(11) unsigned NOT NULL COMMENT 'FK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK',
  PRIMARY KEY (`Id`),
  KEY `PollId` (`PollId`),
  KEY `AnswerId` (`AnswerId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `PollVotes_ibfk_1` FOREIGN KEY (`PollId`) REFERENCES `Polls` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `PollVotes_ibfk_2` FOREIGN KEY (`AnswerId`) REFERENCES `PollAnswers` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `PollVotes_ibfk_3` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Polls`;
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
  CONSTRAINT `Polls_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Posts`;
CREATE TABLE `Posts` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Author` int(10) unsigned NOT NULL COMMENT 'FK - User Id',
  `Text` text COLLATE utf8_czech_ci NOT NULL,
  `TimeCreated` datetime NOT NULL,
  `Deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `Author` (`Author`),
  CONSTRAINT `Posts_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Posts_ibfk_2` FOREIGN KEY (`Author`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `PrivateMessages`;
CREATE TABLE `PrivateMessages` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `SenderId` int(11) unsigned NOT NULL COMMENT 'FK - user Id',
  `AddresseeId` int(11) unsigned NOT NULL COMMENT 'FK - user Id',
  `Text` text COLLATE utf8_czech_ci NOT NULL,
  `TimeSent` datetime NOT NULL,
  `ReadByAddressee` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`),
  KEY `SenderId` (`SenderId`),
  KEY `AddresseeId` (`AddresseeId`),
  CONSTRAINT `PrivateMessages_ibfk_1` FOREIGN KEY (`SenderId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `PrivateMessages_ibfk_2` FOREIGN KEY (`AddresseeId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Ratings`;
CREATE TABLE `Ratings` (
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Rating` int(11) NOT NULL,
  PRIMARY KEY (`ContentId`,`UserId`),
  KEY `UserId` (`UserId`),
  CONSTRAINT `Ratings_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Ratings_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `TopicCategories`;
CREATE TABLE `TopicCategories` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `Description` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Topics`;
CREATE TABLE `Topics` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` tinytext COLLATE utf8_czech_ci NOT NULL,
  `ContentId` int(11) unsigned NOT NULL COMMENT 'FK',
  `CategoryId` int(11) unsigned DEFAULT NULL COMMENT 'FK',
  `Header` int(11) unsigned NOT NULL COMMENT 'FK - CMS page Id',
  `HeaderForDisallowedUsers` int(11) unsigned DEFAULT NULL COMMENT 'FK - CMS page Id',
  `IsFlame` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Marks topic as flamewar',
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `CategoryId` (`CategoryId`),
  KEY `Header` (`Header`),
  KEY `HeaderForDisallowedUsers` (`HeaderForDisallowedUsers`),
  CONSTRAINT `Topics_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Topics_ibfk_2` FOREIGN KEY (`CategoryId`) REFERENCES `TopicCategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Topics_ibfk_3` FOREIGN KEY (`Header`) REFERENCES `CmsPages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Topics_ibfk_4` FOREIGN KEY (`HeaderForDisallowedUsers`) REFERENCES `CmsPages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `UploadedFiles`;
CREATE TABLE `UploadedFiles` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Key` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `FileName` tinytext COLLATE utf8_czech_ci NOT NULL COMMENT 'Server file path',
  `Name` varchar(50) COLLATE utf8_czech_ci NOT NULL COMMENT 'File description',
  `SourceType` enum('GalleryImage','CmsImage','CmsAttachment','ForumImage','ForumAttachment','SpecialCmsImage','SpecialCmsAttachment','ExpositionThumbnail') COLLATE utf8_czech_ci DEFAULT NULL,
  `SourceId` int(11) unsigned DEFAULT NULL COMMENT 'FK - Id of event/topic/cms etc. where the file was uploaded from.',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Users`;
CREATE TABLE `Users` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Username` varchar(50) COLLATE utf8_czech_ci NOT NULL COMMENT 'Login only',
  `Nickname` varchar(100) COLLATE utf8_czech_ci NOT NULL COMMENT 'Display name',
  `Password` tinytext COLLATE utf8_czech_ci NOT NULL,
  `Salt` tinytext COLLATE utf8_czech_ci NOT NULL,
  `OtherNicknames` tinytext COLLATE utf8_czech_ci COMMENT 'Furry',
  `Species` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'Furry',
  `FurrySex` enum('Male','Female','Herm','Sexless') COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'Furry',
  `ShortDescriptionForMembers` text COLLATE utf8_czech_ci COMMENT 'Furry',
  `ShortDescriptionForGuests` text COLLATE utf8_czech_ci COMMENT 'Furry',
  `ProfileForMembers` int(11) unsigned DEFAULT NULL COMMENT 'FK - CMS page id',
  `ProfileForGuests` int(11) unsigned DEFAULT NULL COMMENT 'FK - CMS page id',
  `ImageGalleryPresentation` int(11) unsigned DEFAULT NULL COMMENT 'FK - CMS page id',
  `WritingsPresentation` int(11) unsigned DEFAULT NULL COMMENT 'FK - CMS page id',
  `AvatarFilename` varchar(50) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'Furry',
  `FullName` varchar(50) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'Real',
  `Address` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'Real',
  `RealSex` enum('Male','Female') COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'Real',
  `DateOfBirth` date NOT NULL COMMENT 'Real',
  `FavouriteWebsites` text COLLATE utf8_czech_ci COMMENT 'Real',
  `ProfilePhotoFilename` varchar(50) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'Real',
  `Hobbies` text COLLATE utf8_czech_ci COMMENT 'Real',
  `GoogleMapsLink` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'Real',
  `DistanceFromPrague` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'Real',
  `WillingnessToTravel` enum('Small','Medium','Big') COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'Real',
  `Email` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'Real',
  `LastLogin` datetime DEFAULT NULL COMMENT 'Cache',
  `LastVisitedPage` varchar(25) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'Cache',
  `LastActivityTime` datetime DEFAULT NULL COMMENT 'Cache',
  `SendIntercomToMail` tinyint(1) unsigned DEFAULT '0' COMMENT 'Config',
  `PostsOrdering` enum('NewestOnTop','OldestOnTop') COLLATE utf8_czech_ci NOT NULL DEFAULT 'NewestOnTop' COMMENT 'Config',
  `PostsPerPage` int(10) unsigned NOT NULL DEFAULT '25' COMMENT 'Config',
  `IsAdmin` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Admin',
  `IsApproved` tinyint(1) unsigned DEFAULT '0' COMMENT 'Admin',
  `IsBanned` tinyint(1) unsigned DEFAULT '0' COMMENT 'Admin',
  `IsFrozen` tinyint(1) unsigned DEFAULT '0' COMMENT 'Admin',
  `Deleted` tinyint(1) unsigned DEFAULT '0' COMMENT 'Admin',
  PRIMARY KEY (`Id`),
  KEY `ImageGalleryPresentation` (`ImageGalleryPresentation`),
  KEY `ProfileForGuests` (`ProfileForGuests`),
  KEY `WritingsPresentation` (`WritingsPresentation`),
  KEY `ProfileForMembers` (`ProfileForMembers`),
  CONSTRAINT `Users_ibfk_10` FOREIGN KEY (`ProfileForGuests`) REFERENCES `CmsPages` (`Id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `Users_ibfk_11` FOREIGN KEY (`WritingsPresentation`) REFERENCES `CmsPages` (`Id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `Users_ibfk_13` FOREIGN KEY (`ProfileForMembers`) REFERENCES `CmsPages` (`Id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `Users_ibfk_9` FOREIGN KEY (`ImageGalleryPresentation`) REFERENCES `CmsPages` (`Id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `WritingCategories`;
CREATE TABLE `WritingCategories` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci,
  `IsForAdultsOnly` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `Writings`;
CREATE TABLE `Writings` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci,
  `Text` text COLLATE utf8_czech_ci NOT NULL,
  `CategoryId` int(10) unsigned NOT NULL COMMENT 'FK',
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `CategoryId` (`CategoryId`),
  CONSTRAINT `Writings_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Writings_ibfk_2` FOREIGN KEY (`CategoryId`) REFERENCES `WritingCategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


-- 2014-01-14 12:36:01
