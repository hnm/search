-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server Version:               10.1.21-MariaDB - mariadb.org binary distribution
-- Server Betriebssystem:        Win32
-- HeidiSQL Version:             9.4.0.5125
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Exportiere Struktur von Tabelle halter.search_entry
CREATE TABLE IF NOT EXISTS `search_entry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `keywords_str` varchar(255) DEFAULT NULL,
  `url_str` varchar(255) DEFAULT NULL,
  `n2n_locale` varchar(12) DEFAULT NULL,
  `searchable_text` text,
  `group_key` varchar(255) DEFAULT NULL,
  `last_checked` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url_str` (`url_str`),
  FULLTEXT KEY `searchable_text` (`searchable_text`),
  FULLTEXT KEY `keywords_str` (`keywords_str`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle halter.search_group
CREATE TABLE IF NOT EXISTS `search_group` (
  `key` varchar(255) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle halter.search_group_t
CREATE TABLE IF NOT EXISTS `search_group_t` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_key` int(10) unsigned DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `url_str` varchar(255) DEFAULT NULL,
  `n2n_locale` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `search_group_t_index_1` (`group_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle halter.search_stat
CREATE TABLE IF NOT EXISTS `search_stat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(255) DEFAULT NULL,
  `result_amount` varchar(255) DEFAULT NULL,
  `search_amount` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Daten Export vom Benutzer nicht ausgewählt
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;