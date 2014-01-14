-- phpMyAdmin SQL Dump
-- version 4.0.1
-- http://www.phpmyadmin.net
--
-- Počítač: localhost
-- Vygenerováno: Úte 14. led 2014, 20:39
-- Verze serveru: 5.6.12-log
-- Verze PHP: 5.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Databáze: `fcz-v2`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `access`
--

CREATE TABLE IF NOT EXISTS `access` (
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `PermissionId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ContentId`,`UserId`),
  KEY `UserId` (`UserId`),
  KEY `PermissionId` (`PermissionId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `bookmarkcategories`
--

CREATE TABLE IF NOT EXISTS `bookmarkcategories` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `bookmarks`
--

CREATE TABLE IF NOT EXISTS `bookmarks` (
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `TopicId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `CategoryId` int(11) unsigned DEFAULT NULL COMMENT 'FK',
  PRIMARY KEY (`UserId`,`TopicId`),
  KEY `CategoryId` (`CategoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `calendarreminders`
--

CREATE TABLE IF NOT EXISTS `calendarreminders` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Time` datetime NOT NULL,
  `Name` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Description` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  `CalendarLabel` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `cmspages`
--

CREATE TABLE IF NOT EXISTS `cmspages` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(10) unsigned DEFAULT NULL COMMENT 'FK',
  `Name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci,
  `Text` mediumtext COLLATE utf8_czech_ci NOT NULL,
  `Alias` varchar(25) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'URL alias',
  PRIMARY KEY (`Id`),
  KEY `Alias` (`Alias`),
  KEY `ContentId` (`ContentId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=3 ;

--
-- Vypisuji data pro tabulku `cmspages`
--

INSERT INTO `cmspages` (`Id`, `ContentId`, `Name`, `Description`, `Text`, `Alias`) VALUES
(1, NULL, 'Registrace byla úspěšná!', NULL, 'byl jsi zaregistrován čekej na schválení administrátory!', 'registration-sent-ok'),
(2, NULL, 'Topic header (ContentId: 1)', NULL, 'Test je quest!', NULL);

-- --------------------------------------------------------

--
-- Struktura tabulky `contacts`
--

CREATE TABLE IF NOT EXISTS `contacts` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK',
  `TypeId` int(10) unsigned DEFAULT NULL COMMENT 'FK - Contact type Id. NULL = user defined type',
  `Name` varchar(25) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'For user defined types',
  `Value` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `UserId` (`UserId`),
  KEY `TypeId` (`TypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `contacttypes`
--

CREATE TABLE IF NOT EXISTS `contacttypes` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Url` varchar(50) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `content`
--

CREATE TABLE IF NOT EXISTS `content` (
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
  KEY `DefaultPermissions` (`DefaultPermissions`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=6 ;

--
-- Vypisuji data pro tabulku `content`
--

INSERT INTO `content` (`Id`, `Type`, `TimeCreated`, `Deleted`, `DefaultPermissions`, `LastModifiedTime`, `LastModifiedByUser`, `IsForRegisteredOnly`, `IsForAdultsOnly`, `IsDiscussionAllowed`, `IsRatingAllowed`) VALUES
(1, 'Topic', '2014-01-14 16:37:41', 0, 1, '0000-00-00 00:00:00', NULL, 0, 0, 1, 1),
(5, 'Event', '2014-01-14 20:47:38', 0, 5, '0000-00-00 00:00:00', NULL, 1, 0, 1, 1);

-- --------------------------------------------------------

--
-- Struktura tabulky `editedposthistory`
--

CREATE TABLE IF NOT EXISTS `editedposthistory` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `EditedPostId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Text` text COLLATE utf8_czech_ci NOT NULL,
  `TimeEdited` datetime NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `EditedPostId` (`EditedPostId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `eventattendances`
--

CREATE TABLE IF NOT EXISTS `eventattendances` (
  `EventId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Attending` enum('Yes','No','Maybe') COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`EventId`,`UserId`),
  KEY `UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `events`
--

CREATE TABLE IF NOT EXISTS `events` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci,
  `StartTime` datetime DEFAULT NULL,
  `EndTime` datetime DEFAULT NULL,
  `CalendarLabel` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  `Header` int(10) unsigned DEFAULT NULL COMMENT 'FK - CMS page id',
  `Capacity` int(20) NOT NULL,
  `Place` varchar(500) COLLATE utf8_czech_ci NOT NULL,
  `GPS` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `Header` (`Header`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=2 ;

--
-- Vypisuji data pro tabulku `events`
--

INSERT INTO `events` (`Id`, `ContentId`, `Name`, `Description`, `StartTime`, `EndTime`, `CalendarLabel`, `Header`, `Capacity`, `Place`, `GPS`) VALUES
(1, 5, 'Test', 'test popisu...', '2014-01-02 00:00:00', '2014-01-02 00:00:00', '', NULL, 0, 'Ostrava-Vítkovice', '(49.81623625788925, 18.271496146917343)');

-- --------------------------------------------------------

--
-- Struktura tabulky `forumcategorypresets`
--

CREATE TABLE IF NOT EXISTS `forumcategorypresets` (
  `TopicCategoryId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Hidden` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Toggles visibility of forum category',
  PRIMARY KEY (`TopicCategoryId`,`UserId`),
  KEY `UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `forumtopicpresets`
--

CREATE TABLE IF NOT EXISTS `forumtopicpresets` (
  `TopicId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Uninteresting` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Marks topic as uninteresting',
  PRIMARY KEY (`TopicId`,`UserId`),
  KEY `UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `ignorelist`
--

CREATE TABLE IF NOT EXISTS `ignorelist` (
  `IgnoringUserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `IgnoredUserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  PRIMARY KEY (`IgnoringUserId`,`IgnoredUserId`),
  KEY `IgnoredUserId` (`IgnoredUserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `imageexpositions`
--

CREATE TABLE IF NOT EXISTS `imageexpositions` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `Owner` int(11) unsigned NOT NULL COMMENT 'FK - User Id',
  `Description` tinytext COLLATE utf8_czech_ci,
  `Thumbnail` int(11) unsigned DEFAULT NULL COMMENT 'FK - Uploaded file Id',
  `Presentation` int(11) unsigned DEFAULT NULL COMMENT 'FK - CMS page Id',
  PRIMARY KEY (`Id`),
  KEY `Thumbnail` (`Thumbnail`),
  KEY `Presentation` (`Presentation`),
  KEY `Owner` (`Owner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `images`
--

CREATE TABLE IF NOT EXISTS `images` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(11) unsigned NOT NULL COMMENT 'FK',
  `UploadedFileId` int(11) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `Description` text COLLATE utf8_czech_ci NOT NULL,
  `Exposition` int(10) unsigned DEFAULT NULL COMMENT 'FK',
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `UploadedFileId` (`UploadedFileId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `lastvisits`
--

CREATE TABLE IF NOT EXISTS `lastvisits` (
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Time` datetime NOT NULL,
  PRIMARY KEY (`ContentId`,`UserId`),
  KEY `UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `ownership`
--

CREATE TABLE IF NOT EXISTS `ownership` (
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK, compound PK',
  PRIMARY KEY (`ContentId`,`UserId`),
  KEY `UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

--
-- Vypisuji data pro tabulku `ownership`
--

INSERT INTO `ownership` (`ContentId`, `UserId`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Struktura tabulky `permissions`
--

CREATE TABLE IF NOT EXISTS `permissions` (
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=6 ;

--
-- Vypisuji data pro tabulku `permissions`
--

INSERT INTO `permissions` (`Id`, `CanListContent`, `CanViewContent`, `CanEditContentAndAttributes`, `CanEditHeader`, `CanEditOwnPosts`, `CanDeleteOwnPosts`, `CanReadPosts`, `CanDeletePosts`, `CanWritePosts`, `CanEditPermissions`, `CanEditPolls`) VALUES
(1, 1, 1, 0, 0, 1, 1, 0, 0, 1, 0, 0),
(5, 1, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Struktura tabulky `pollanswers`
--

CREATE TABLE IF NOT EXISTS `pollanswers` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `PollId` int(11) unsigned NOT NULL COMMENT 'FK',
  `Text` tinytext COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `PollId` (`PollId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `polls`
--

CREATE TABLE IF NOT EXISTS `polls` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(11) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci NOT NULL,
  `MaxVotesPerUser` int(11) DEFAULT NULL,
  `SaveIndividualVotes` tinyint(1) unsigned NOT NULL,
  `DisplayVotersTo` enum('Nobody,','OtherVoters,','Everybody') COLLATE utf8_czech_ci NOT NULL COMMENT 'Controls who can see voter names',
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `pollvotes`
--

CREATE TABLE IF NOT EXISTS `pollvotes` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `PollId` int(11) unsigned NOT NULL COMMENT 'FK',
  `AnswerId` int(11) unsigned NOT NULL COMMENT 'FK',
  `UserId` int(11) unsigned NOT NULL COMMENT 'FK',
  PRIMARY KEY (`Id`),
  KEY `PollId` (`PollId`),
  KEY `AnswerId` (`AnswerId`),
  KEY `UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `posts`
--

CREATE TABLE IF NOT EXISTS `posts` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Author` int(10) unsigned NOT NULL COMMENT 'FK - User Id',
  `Text` text COLLATE utf8_czech_ci NOT NULL,
  `TimeCreated` datetime NOT NULL,
  `Deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `Author` (`Author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `privatemessages`
--

CREATE TABLE IF NOT EXISTS `privatemessages` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `SenderId` int(11) unsigned NOT NULL COMMENT 'FK - user Id',
  `AddresseeId` int(11) unsigned NOT NULL COMMENT 'FK - user Id',
  `Text` text COLLATE utf8_czech_ci NOT NULL,
  `TimeSent` datetime NOT NULL,
  `ReadByAddressee` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `Deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`),
  KEY `SenderId` (`SenderId`),
  KEY `AddresseeId` (`AddresseeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `ratings`
--

CREATE TABLE IF NOT EXISTS `ratings` (
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `UserId` int(10) unsigned NOT NULL COMMENT 'FK, compound PK',
  `Rating` int(11) NOT NULL,
  PRIMARY KEY (`ContentId`,`UserId`),
  KEY `UserId` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `topiccategories`
--

CREATE TABLE IF NOT EXISTS `topiccategories` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `Description` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `topics`
--

CREATE TABLE IF NOT EXISTS `topics` (
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
  KEY `HeaderForDisallowedUsers` (`HeaderForDisallowedUsers`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=2 ;

--
-- Vypisuji data pro tabulku `topics`
--

INSERT INTO `topics` (`Id`, `Name`, `ContentId`, `CategoryId`, `Header`, `HeaderForDisallowedUsers`, `IsFlame`) VALUES
(1, 'Test', 1, NULL, 2, NULL, 0);

-- --------------------------------------------------------

--
-- Struktura tabulky `uploadedfiles`
--

CREATE TABLE IF NOT EXISTS `uploadedfiles` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Key` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `FileName` tinytext COLLATE utf8_czech_ci NOT NULL COMMENT 'Server file path',
  `Name` varchar(50) COLLATE utf8_czech_ci NOT NULL COMMENT 'File description',
  `SourceType` enum('GalleryImage','CmsImage','CmsAttachment','ForumImage','ForumAttachment','SpecialCmsImage','SpecialCmsAttachment','ExpositionThumbnail') COLLATE utf8_czech_ci DEFAULT NULL,
  `SourceId` int(11) unsigned DEFAULT NULL COMMENT 'FK - Id of event/topic/cms etc. where the file was uploaded from.',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE IF NOT EXISTS `users` (
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
  KEY `ProfileForMembers` (`ProfileForMembers`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=2 ;

--
-- Vypisuji data pro tabulku `users`
--

INSERT INTO `users` (`Id`, `Username`, `Nickname`, `Password`, `Salt`, `OtherNicknames`, `Species`, `FurrySex`, `ShortDescriptionForMembers`, `ShortDescriptionForGuests`, `ProfileForMembers`, `ProfileForGuests`, `ImageGalleryPresentation`, `WritingsPresentation`, `AvatarFilename`, `FullName`, `Address`, `RealSex`, `DateOfBirth`, `FavouriteWebsites`, `ProfilePhotoFilename`, `Hobbies`, `GoogleMapsLink`, `DistanceFromPrague`, `WillingnessToTravel`, `Email`, `LastLogin`, `LastVisitedPage`, `LastActivityTime`, `SendIntercomToMail`, `PostsOrdering`, `PostsPerPage`, `IsAdmin`, `IsApproved`, `IsBanned`, `IsFrozen`, `Deleted`) VALUES
(1, 'natsu', 'Natsu', '$2y$07$a7t95tlr4ccti80wlfsjueJ2FbANexRtwljY6LNkgqB/uZMD6kXc2', '$2y$07$a7t95tlr4ccti80wlfsjui', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '1996-08-13', '', NULL, '', NULL, NULL, NULL, 'kubat130@gmail.com', NULL, NULL, NULL, 0, 'NewestOnTop', 25, 1, 1, 0, 0, 0);

-- --------------------------------------------------------

--
-- Struktura tabulky `writingcategories`
--

CREATE TABLE IF NOT EXISTS `writingcategories` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `Name` varchar(25) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci,
  `IsForAdultsOnly` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `writings`
--

CREATE TABLE IF NOT EXISTS `writings` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(10) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `Description` tinytext COLLATE utf8_czech_ci,
  `Text` text COLLATE utf8_czech_ci NOT NULL,
  `CategoryId` int(10) unsigned NOT NULL COMMENT 'FK',
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `CategoryId` (`CategoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1 ;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `access`
--
ALTER TABLE `access`
  ADD CONSTRAINT `Access_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION,
  ADD CONSTRAINT `Access_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION,
  ADD CONSTRAINT `Access_ibfk_3` FOREIGN KEY (`PermissionId`) REFERENCES `permissions` (`Id`) ON DELETE NO ACTION;

--
-- Omezení pro tabulku `bookmarkcategories`
--
ALTER TABLE `bookmarkcategories`
  ADD CONSTRAINT `BookmarkCategories_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD CONSTRAINT `Bookmarks_ibfk_1` FOREIGN KEY (`CategoryId`) REFERENCES `bookmarkcategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `calendarreminders`
--
ALTER TABLE `calendarreminders`
  ADD CONSTRAINT `CalendarReminders_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `cmspages`
--
ALTER TABLE `cmspages`
  ADD CONSTRAINT `CmsPages_ibfk_3` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `Contacts_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Contacts_ibfk_2` FOREIGN KEY (`TypeId`) REFERENCES `contacttypes` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `content`
--
ALTER TABLE `content`
  ADD CONSTRAINT `Content_ibfk_1` FOREIGN KEY (`LastModifiedByUser`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Content_ibfk_3` FOREIGN KEY (`DefaultPermissions`) REFERENCES `permissions` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `editedposthistory`
--
ALTER TABLE `editedposthistory`
  ADD CONSTRAINT `EditedPostHistory_ibfk_1` FOREIGN KEY (`EditedPostId`) REFERENCES `editedposthistory` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `eventattendances`
--
ALTER TABLE `eventattendances`
  ADD CONSTRAINT `EventAttendances_ibfk_1` FOREIGN KEY (`EventId`) REFERENCES `events` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `EventAttendances_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `Events_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Events_ibfk_2` FOREIGN KEY (`Header`) REFERENCES `cmspages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `forumcategorypresets`
--
ALTER TABLE `forumcategorypresets`
  ADD CONSTRAINT `ForumCategoryPresets_ibfk_1` FOREIGN KEY (`TopicCategoryId`) REFERENCES `topiccategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `ForumCategoryPresets_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `forumtopicpresets`
--
ALTER TABLE `forumtopicpresets`
  ADD CONSTRAINT `ForumTopicPresets_ibfk_1` FOREIGN KEY (`TopicId`) REFERENCES `topics` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `ForumTopicPresets_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `ignorelist`
--
ALTER TABLE `ignorelist`
  ADD CONSTRAINT `Ignorelist_ibfk_1` FOREIGN KEY (`IgnoringUserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Ignorelist_ibfk_2` FOREIGN KEY (`IgnoredUserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `imageexpositions`
--
ALTER TABLE `imageexpositions`
  ADD CONSTRAINT `ImageExpositions_ibfk_1` FOREIGN KEY (`Thumbnail`) REFERENCES `uploadedfiles` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `ImageExpositions_ibfk_2` FOREIGN KEY (`Presentation`) REFERENCES `cmspages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `ImageExpositions_ibfk_3` FOREIGN KEY (`Owner`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `Images_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Images_ibfk_2` FOREIGN KEY (`UploadedFileId`) REFERENCES `uploadedfiles` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `lastvisits`
--
ALTER TABLE `lastvisits`
  ADD CONSTRAINT `LastVisits_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `LastVisits_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `ownership`
--
ALTER TABLE `ownership`
  ADD CONSTRAINT `Ownership_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION,
  ADD CONSTRAINT `Ownership_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION;

--
-- Omezení pro tabulku `pollanswers`
--
ALTER TABLE `pollanswers`
  ADD CONSTRAINT `PollAnswers_ibfk_1` FOREIGN KEY (`PollId`) REFERENCES `polls` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `polls`
--
ALTER TABLE `polls`
  ADD CONSTRAINT `Polls_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `pollvotes`
--
ALTER TABLE `pollvotes`
  ADD CONSTRAINT `PollVotes_ibfk_1` FOREIGN KEY (`PollId`) REFERENCES `polls` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `PollVotes_ibfk_2` FOREIGN KEY (`AnswerId`) REFERENCES `pollanswers` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `PollVotes_ibfk_3` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `Posts_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Posts_ibfk_2` FOREIGN KEY (`Author`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `privatemessages`
--
ALTER TABLE `privatemessages`
  ADD CONSTRAINT `PrivateMessages_ibfk_1` FOREIGN KEY (`SenderId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `PrivateMessages_ibfk_2` FOREIGN KEY (`AddresseeId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `Ratings_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Ratings_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `topics`
--
ALTER TABLE `topics`
  ADD CONSTRAINT `Topics_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Topics_ibfk_2` FOREIGN KEY (`CategoryId`) REFERENCES `topiccategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Topics_ibfk_3` FOREIGN KEY (`Header`) REFERENCES `cmspages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Topics_ibfk_4` FOREIGN KEY (`HeaderForDisallowedUsers`) REFERENCES `cmspages` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `Users_ibfk_10` FOREIGN KEY (`ProfileForGuests`) REFERENCES `cmspages` (`Id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  ADD CONSTRAINT `Users_ibfk_11` FOREIGN KEY (`WritingsPresentation`) REFERENCES `cmspages` (`Id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  ADD CONSTRAINT `Users_ibfk_13` FOREIGN KEY (`ProfileForMembers`) REFERENCES `cmspages` (`Id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  ADD CONSTRAINT `Users_ibfk_9` FOREIGN KEY (`ImageGalleryPresentation`) REFERENCES `cmspages` (`Id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Omezení pro tabulku `writings`
--
ALTER TABLE `writings`
  ADD CONSTRAINT `Writings_ibfk_1` FOREIGN KEY (`ContentId`) REFERENCES `content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `Writings_ibfk_2` FOREIGN KEY (`CategoryId`) REFERENCES `writingcategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
