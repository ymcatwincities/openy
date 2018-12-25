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

DROP TABLE IF EXISTS `legacy__block_content_promo_block`;
CREATE TABLE `legacy__block_content_promo_block` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `header` varchar(255) NOT NULL,
  `image` int(11) NOT NULL,
  `link` varchar(255) NOT NULL,
  `body` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `legacy__block_content_promo_block` (`id`, `header`, `image`, `link`, `body`) VALUES
(1,	'Example promo block #1',	1,	'http://example.com',	'<p>Here is a text for the block...</p>'),
(2,	'Example promo block #2',	2,	'http://example.com',	'<p>Here is a text for the block...</p>'),
(3,	'Example promo block #3',	3,	'http://example.com',	'<p>Here is a text for the block...</p>');

DROP TABLE IF EXISTS `legacy__file`;
CREATE TABLE `legacy__file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `legacy__file` (`id`, `url`) VALUES
(1,	'_asset/qrq5fn/landing_promo_swim_lessons_050114.jpg'),
(2,	'_asset/dbdh7e/landing_promo_sac_101715.jpg'),
(3,	'non_existent_file.jpg'),
(4,	'_asset/3fcxe3/landing_promo_classes_080114.jpg');

DROP TABLE IF EXISTS `legacy__node_article`;
CREATE TABLE `legacy__node_article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `header_image` int(11) NOT NULL,
  `parent` int(11) NOT NULL,
  `weight` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `legacy__node_article` (`id`, `title`, `header_image`, `parent`, `weight`) VALUES
(1,	'Swim Lessons',	3,	0,	1),
(2,	'Swim Team',	3,	0,	2),
(3,	'Blazers',	3,	2,	3),
(4,	'Barracudas',	3,	2,	4),
(5,	'Certifications and Training',	3,	0,	5),
(6,	'Lifeguard Training',	3,	5,	6),
(7,	'Lifeguard Training',	3,	6,	7),
(8,	'Lifeguard Training Review',	3,	6,	8),
(9,	'Lifeguard Module',	3,	6,	9),
(10,	'CPR Training',	3,	5,	10),
(11,	'Full Course',	3,	10,	11),
(12,	'Renewal',	3,	10,	12),
(13,	'Challange',	3,	10,	13);

-- 2015-11-10 11:04:41
