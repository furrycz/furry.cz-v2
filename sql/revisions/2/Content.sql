ALTER TABLE `Content`
DROP FOREIGN KEY `Content_ibfk_1`,
ADD FOREIGN KEY (`LastModifiedByUser`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Content`
DROP FOREIGN KEY `Content_ibfk_3`,
ADD FOREIGN KEY (`DefaultPermissions`) REFERENCES `Permissions` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;