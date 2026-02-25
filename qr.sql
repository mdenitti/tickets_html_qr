-- -------------------------------------------------------------
-- TablePlus 6.8.0(654)
--
-- https://tableplus.com/
--
-- Database: qrtickets
-- Generation Time: 2026-02-24 11:51:40.3040
-- -------------------------------------------------------------


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `date` datetime NOT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `max_tickets` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `amount_tickets` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `order_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('unused','checked-in') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unused',
  `checked_in_at` datetime DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` enum('unused','checked-in') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unused',
  `checked_in_at` datetime DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount_person` int DEFAULT NULL,
  `amount_total` float DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `order_date` datetime DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','client') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'client',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `profile_pic` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `events` (`id`, `title`, `description`, `date`, `location`, `max_tickets`, `created_at`, `updated_at`) VALUES
(1, 'Summer Music Festival', 'Annual summer music festival featuring local bands', '2026-07-15 14:00:00', 'Thor park', 1000, '2025-02-08 15:52:16', '2025-02-08 15:52:16');

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `amount_tickets`, `total_price`, `order_date`, `created_at`, `status`, `checked_in_at`, `event_date`) VALUES
(1, 38, 'ORD-698c420e1a469', 20, 900.00, '2026-02-11 09:47:10', '2026-02-11 09:47:10', 'unused', NULL, NULL),
(2, 38, 'ORD-698c4b993b7a1', 3, 135.00, '2026-02-11 10:27:53', '2026-02-11 10:27:53', 'unused', NULL, NULL),
(3, 38, 'ORD-698c4c95f1878', 3, 135.00, '2026-02-11 10:32:05', '2026-02-11 10:32:05', 'unused', NULL, NULL),
(4, 38, 'ORD-698c4cb3e147a', 3, 135.00, '2026-02-11 10:32:35', '2026-02-11 10:32:35', 'unused', NULL, NULL),
(5, 38, 'ORD-698c4df0c7055', 3, 135.00, '2026-02-11 10:37:52', '2026-02-11 10:37:52', 'unused', NULL, NULL),
(6, 38, 'ORD-698c4e07b92e2', 3, 135.00, '2026-02-11 10:38:15', '2026-02-11 10:38:15', 'unused', NULL, NULL),
(7, 38, 'ORD-698c4e2708947', 3, 135.00, '2026-02-11 10:38:47', '2026-02-11 10:38:47', 'unused', NULL, NULL),
(8, 38, 'ORD-698c508923182', 3, 135.00, '2026-02-11 10:48:57', '2026-02-11 10:48:57', 'unused', NULL, NULL),
(9, 38, 'ORD-698c50987035a', 3, 135.00, '2026-02-11 10:49:12', '2026-02-11 10:49:12', 'unused', NULL, NULL),
(10, 38, 'ORD-698c50a09c654', 3, 135.00, '2026-02-11 10:49:20', '2026-02-11 10:49:20', 'unused', NULL, NULL),
(11, 38, 'ORD-698c510ca447e', 3, 135.00, '2026-02-11 10:51:08', '2026-02-11 10:51:08', 'unused', NULL, NULL),
(12, 38, 'ORD-698c516a46667', 3, 135.00, '2026-02-11 10:52:42', '2026-02-11 10:52:42', 'unused', NULL, NULL),
(13, 38, 'ORD-698ded07199ab', 20, 900.00, '2026-02-12 16:08:55', '2026-02-12 16:08:55', 'checked-in', '2026-02-12 16:11:34', NULL),
(14, 38, 'ORD-698ee0d4d6425', 4, 180.00, '2026-02-13 09:29:08', '2026-02-13 09:29:08', 'checked-in', '2026-02-13 09:33:30', NULL),
(15, 40, 'ORD-699c48735e6c3', 20, 900.00, '2026-02-23 13:30:43', '2026-02-23 13:30:43', 'unused', NULL, NULL);

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`, `profile_pic`) VALUES
(23, 'test', 'test@test5.com', 'pafkldjslfksjd', 'client', NULL, NULL, NULL),
(24, 'Dj', 'Flipie', 'lkfjsdlkfjslfkj', 'client', NULL, NULL, NULL),
(25, 'Maya', 'maya@sesame.com', 'sdsdfs', 'client', NULL, NULL, NULL),
(26, 'Miles', 'miles@sesame.com', 'fdslkfjsdlkf', 'client', NULL, NULL, NULL),
(27, 'ljlkj', 'mas@mas.com', 'welcome', 'client', NULL, NULL, NULL),
(28, 'Massimo2', 'mas@mas2.com', '$2y$12$4rYdK5sasXKSN7kxnhgDg.znJwbtcBL1c7RQ5jZV3eO4iHbYipCT.', 'client', NULL, NULL, 'uploads/signature.jpg'),
(30, 'Massimo', 'mas@mas4.com', '$2y$12$7dJu1.vU6aacEqWt355Jr..cI3emItIoe0dkDc7l6Fpn57FkNPLNa', 'client', NULL, NULL, ''),
(31, 'Massimo', 'mas@mas5.com', '$2y$12$.KfsT6OGrUk6VWitjdrUQOw2agSMftkG/KGB0PSbP/7E9v78hBrrC', 'client', NULL, NULL, ''),
(32, 'Massimo', 'mas@mas6.com', '$2y$12$mYIJOnutSKNYLhJXTJ6yS.44npriZksiZD31et9gAXm2J/UMoWzfe', 'client', NULL, NULL, ''),
(33, 'Massimo', 'mas@mas7.com', '$2y$12$ep20X0f4QspAOZCOvHE88.Ct8FHhQKc629daSuFUEg8D2qb7X1EfK', 'client', NULL, NULL, ''),
(34, 'Massimo', 'mas@mas8.com', '$2y$12$3cHWRj.OcrjH9qKO1LwNvO.LtAQ9sxbDOQA9p4uc789yxkRbAwJhy', 'client', NULL, NULL, ''),
(35, 'Massimo', 'mas@mas9.com', '$2y$12$oqXYS/wqSBtRaY0FCnWSWe3vtLxjo/DB/9eYykLNrrEKgydMY7Tiu', 'client', NULL, NULL, ''),
(36, 'Massimo', 'mas@mas10.com', '$2y$12$.s/4lIR18/Z308mllQgIuOyjZiYV4MgZvIQscfLISE.1O2RY2uib6', 'client', NULL, NULL, ''),
(38, 'Massimo', 'mas@mas13.com', '$2y$12$AKlxSd0A4E52SFNcomgkp.MBnbHyj56LA4YIxxoTYaWANe7mk6kqC', 'admin', NULL, NULL, ''),
(39, 'TestClientWithDefaultRole', 'test@client.be', '$2y$12$6Q0yaHwu.6MpEJADt3Zpouf9kF3r3y8gRWYbkcFVaSZtG/44qEKm6', 'client', NULL, NULL, ''),
(40, 'Massimo De Nittis', 'massimodn77@gmail.com', '$2y$12$CGObby13KsTcFub7SqQLzuPS0TVh/JdK0GHbjak4yW4wHpXxGse4y', 'client', NULL, NULL, 'https://lh3.googleusercontent.com/a/ACg8ocL1xYFu3ZQqS1dUC0r3vwZ0sHCk2NuD07ZnRGyiRHNbNVSMzQ=s96-c');



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;