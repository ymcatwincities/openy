-- Adminer 4.0.3 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = '+00:00';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `block_content_basic`;
CREATE TABLE `block_content_basic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `info` varchar(255) NOT NULL,
  `body` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `block_content_basic` (`id`, `info`, `body`) VALUES
(1,	'Example basic block #1',	'Here the text of the block...'),
(2,	'Example basic block #2',	'Here the text of the block...'),
(3,	'Example basic block #3',	'Here the text of the block...');

-- 2015-10-28 12:29:12
