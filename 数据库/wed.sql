-- Adminer 3.3.3 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` char(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `type` char(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `name` char(100) COLLATE utf32_bin NOT NULL,
  `count` int(10) NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_bin;


DROP TABLE IF EXISTS `promote`;
CREATE TABLE `promote` (
  `username` char(16) NOT NULL,
  `ips` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `weapons`;
CREATE TABLE `weapons` (
  `name` char(64) COLLATE utf8_bin NOT NULL,
  `type` char(10) CHARACTER SET utf32 COLLATE utf32_bin NOT NULL,
  `user` char(16) COLLATE utf8_bin NOT NULL,
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- 2020-08-17 19:12:27
