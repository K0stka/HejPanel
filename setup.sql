DROP DATABASE hejpanel;
CREATE DATABASE hejpanel;

DELETE USER 'hejpanel'@'localhost';
FLUSH PRIVILEGES;
CREATE USER 'hejpanel'@'localhost' IDENTIFIED BY 'hejpanel';
GRANT ALL PRIVILEGES ON hejpanel.* TO 'hejpanel'@'localhost';

USE hejpanel;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
CREATE TABLE `codes` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `type` enum('admin','superadmin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `jidelna_cache` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `date_fetched` datetime NOT NULL DEFAULT current_timestamp(),
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `user` int(11) DEFAULT NULL,
  `fingerprint` varchar(500) NOT NULL,
  `session` varchar(100) NOT NULL,
  `note` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `panels` (
  `id` int(11) NOT NULL,
  `posted_by` int(11) NOT NULL,
  `posted_at` datetime DEFAULT current_timestamp(),
  `approved` tinyint(1) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `show_from` datetime NOT NULL DEFAULT current_timestamp(),
  `show_till` datetime NOT NULL,
  `show_override` enum('show','hide') DEFAULT NULL,
  `type` enum('image','text') NOT NULL DEFAULT 'image',
  `content` text NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `mail` varchar(200) DEFAULT NULL,
  `note` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `sessions` (
  `session_id` varchar(100) NOT NULL,
  `expires` int(11) NOT NULL,
  `fingerprint` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fingerprint`)),
  `user` int(11) DEFAULT NULL,
  `auth` int(11) DEFAULT NULL,
  `subscription` text DEFAULT NULL,
  `data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `nickname` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `type` enum('temp','admin','superadmin') NOT NULL DEFAULT 'temp',
  `last_fingerprint` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `auth_version` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `codes`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);
ALTER TABLE `jidelna_cache`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `log`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `panels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `posted_by` (`posted_by`);
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user`);
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `jidelna_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `panels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `panels`
  ADD CONSTRAINT `panels_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `panels_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;
