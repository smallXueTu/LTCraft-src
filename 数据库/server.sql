-- Adminer 3.3.3 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `expblocks`;
CREATE TABLE `expblocks` (
  `X` int(10) NOT NULL,
  `Y` int(10) NOT NULL,
  `Z` int(10) NOT NULL,
  `L` char(10) NOT NULL,
  KEY `index_Z` (`Z`),
  KEY `index_X` (`X`),
  KEY `index_Y` (`Y`),
  KEY `index_L` (`L`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
/*!50100 PARTITION BY HASH (X)
PARTITIONS 5 */;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `back` char(64) NOT NULL,
  `name` varchar(16) NOT NULL,
  `password` varchar(16) NOT NULL,
  `config` text NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `qq` bigint(100) DEFAULT NULL,
  `ip` char(15) DEFAULT NULL,
  `VIP` char(100) DEFAULT NULL,
  `moveCheck` tinyint(1) DEFAULT '0',
  `register_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `balance` double NOT NULL,
  `cumulative` double DEFAULT '0',
  `user_icon` char(30) DEFAULT NULL,
  `bbs_uid` int(11) DEFAULT NULL,
  `register` char(32) DEFAULT NULL,
  `login` char(32) DEFAULT NULL,
  `last_play_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_name` (`name`),
  KEY `IP` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- 2020-08-17 19:07:27
