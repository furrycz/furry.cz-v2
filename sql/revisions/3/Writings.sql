ALTER TABLE `Writings`
CHANGE `CategoryId` `CategoryId` int(10) unsigned NULL COMMENT 'FK' AFTER `Text`,
COMMENT=''; -- 0.575 s
