    CREATE TABLE IF NOT EXISTS `search_entry` (
      `id` INT NOT NULL AUTO_INCREMENT,
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `search_group` (
      `key` varchar(255) NOT NULL,
      PRIMARY KEY (`key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `search_group_t` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `group_key` varchar(50) NULL DEFAULT NULL,
      `label` varchar(255) DEFAULT NULL,
      `url_str` varchar(255) DEFAULT NULL,
      `n2n_locale` varchar(12) DEFAULT NULL,
      PRIMARY KEY (`id`),
        KEY `search_group_t_index_1` (`group_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `search_stat` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `text` varchar(255) DEFAULT NULL,
      `result_amount` varchar(255) DEFAULT NULL,
      `search_amount` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
