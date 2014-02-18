ALTER TABLE `EditedPostHistory`
DROP FOREIGN KEY `EditedPostHistory_ibfk_1`,
ADD FOREIGN KEY (`EditedPostId`) REFERENCES `EditedPostHistory` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;