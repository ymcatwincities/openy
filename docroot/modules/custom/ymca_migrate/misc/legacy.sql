-- Adminer 4.0.3 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = '+00:00';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `legacy__block_content_basic`;
CREATE TABLE `legacy__block_content_basic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `info` varchar(255) NOT NULL,
  `body` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `legacy__block_content_basic` (`id`, `info`, `body`) VALUES
(1,	'Example basic block #1',	'Here the text of the block...'),
(2,	'Example basic block #2',	'Here the text of the block...'),
(3,	'Example basic block #3',	'Here the text of the block...');

DROP TABLE IF EXISTS `legacy__file`;
CREATE TABLE `legacy__file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `legacy__file` (`id`, `url`) VALUES
(1,	'http://www.ymcatwincities.org/_asset/qrq5fn/landing_promo_swim_lessons_050114.jpg'),
(2,	'http://www.ymcatwincities.org/_asset/dbdh7e/landing_promo_sac_101715.jpg'),
(3,	'http://www.ymcatwincities.org/_asset/3fcxe3/landing_promo_classes_080114.jpg');

-- 2015-10-29 08:31:52
