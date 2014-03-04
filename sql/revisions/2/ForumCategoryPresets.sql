ALTER TABLE `ForumCategoryPresets`
DROP FOREIGN KEY `ForumCategoryPresets_ibfk_1`,
ADD FOREIGN KEY (`TopicCategoryId`) REFERENCES `TopicCategories` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `ForumCategoryPresets`
DROP FOREIGN KEY `ForumCategoryPresets_ibfk_2`,
ADD FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION;