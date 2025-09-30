-- Adminer 4.8.1 MySQL 9.0.1 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1,	'admin',	'4a1ca4650efb1cfb1760c37222830da6967109d090cc66aa6637fbbcaf06ba5b');

DROP TABLE IF EXISTS `links`;
CREATE TABLE `links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `add_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `js_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `urlredgrim_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `redirect_delay` int NOT NULL DEFAULT '0',
  `mobile_off` tinyint(1) NOT NULL DEFAULT '0',
  `desktop_off` tinyint(1) NOT NULL DEFAULT '0',
  `browser` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os_system` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `add_date` (`add_date`),
  KEY `mobile_off` (`mobile_off`),
  KEY `desktop_off` (`desktop_off`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2025-02-06 21:57:01
