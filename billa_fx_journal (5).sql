-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2026 at 06:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `billa_fx_journal`
--

-- --------------------------------------------------------

--
-- Table structure for table `chart_snapshots`
--

CREATE TABLE `chart_snapshots` (
  `id` int(11) NOT NULL,
  `trade_id` int(11) NOT NULL,
  `timeframe` enum('1D','4H','1H','15m','5m','After') NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chart_snapshots`
--

INSERT INTO `chart_snapshots` (`id`, `trade_id`, `timeframe`, `image_path`, `uploaded_at`) VALUES
(1, 2, '4H', '2_4H_1773672499.jpeg', '2026-03-16 14:48:19');

-- --------------------------------------------------------

--
-- Table structure for table `daily_notes`
--

CREATE TABLE `daily_notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note_date` date NOT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_rituals`
--

CREATE TABLE `daily_rituals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ritual_date` date NOT NULL,
  `readiness_score` int(11) DEFAULT 0,
  `pre_market_completed` tinyint(1) DEFAULT 0,
  `slept_well` tinyint(1) DEFAULT 0,
  `mentally_ready` tinyint(1) DEFAULT 0,
  `accepted_risk` tinyint(1) DEFAULT 0,
  `completed` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_rituals`
--

INSERT INTO `daily_rituals` (`id`, `user_id`, `ritual_date`, `readiness_score`, `pre_market_completed`, `slept_well`, `mentally_ready`, `accepted_risk`, `completed`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-03-17', 100, 1, 1, 1, 1, 1, NULL, '2026-03-17 18:57:51', '2026-03-17 18:57:51'),
(2, 1, '2026-03-18', 100, 1, 1, 1, 1, 1, NULL, '2026-03-18 17:22:01', '2026-03-18 17:22:01'),
(3, 3, '2026-03-18', 100, 1, 1, 1, 1, 1, NULL, '2026-03-18 18:19:34', '2026-03-18 18:19:34'),
(4, 3, '2026-03-21', 100, 1, 1, 1, 1, 1, NULL, '2026-03-21 16:56:17', '2026-03-21 16:56:17');

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `direction_scope` enum('Both','Bullish','Bearish') DEFAULT 'Both',
  `session_scope` enum('Both','London','New York','Asian') DEFAULT 'Both',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `templates`
--

INSERT INTO `templates` (`id`, `user_id`, `name`, `description`, `direction_scope`, `session_scope`, `created_at`) VALUES
(18, 1, 'BILLA FX', 'hhhhh', 'Both', 'Both', '2026-03-15 20:21:15'),
(21, 2, 'BILLA FX', 'waa sax', 'Both', 'Both', '2026-03-16 13:14:20'),
(22, 3, 'nASRA', 'JGHHHH', 'Bullish', 'Both', '2026-03-18 18:22:54');

-- --------------------------------------------------------

--
-- Table structure for table `template_rules`
--

CREATE TABLE `template_rules` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `label_bullish` varchar(255) DEFAULT NULL,
  `label_bearish` varchar(255) DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `rule_type` enum('HTF','LTF') DEFAULT NULL,
  `required` tinyint(1) DEFAULT 0,
  `group_type` enum('single','either_or') DEFAULT 'single',
  `position` int(11) DEFAULT 0,
  `direction_scope` enum('Both','Bullish','Bearish') DEFAULT 'Both',
  `session_scope` enum('Both','London','New York','Asian') DEFAULT 'Both'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `template_rules`
--

INSERT INTO `template_rules` (`id`, `template_id`, `parent_id`, `label_bullish`, `label_bearish`, `label`, `rule_type`, `required`, `group_type`, `position`, `direction_scope`, `session_scope`) VALUES
(5, 21, NULL, NULL, NULL, 'GET HTF ORDER FOLOW (4hr/1hr).', 'HTF', 1, 'single', 0, 'Both', 'Both'),
(6, 21, NULL, NULL, NULL, 'entry rulles', 'LTF', 1, 'either_or', 1, 'Both', 'Both'),
(7, 21, 6, NULL, NULL, '15minit entry', NULL, 0, 'single', 0, 'Both', 'Both'),
(14, 18, NULL, NULL, NULL, 'xcvcf', 'HTF', 1, 'either_or', 0, 'Both', 'Both'),
(15, 18, 14, NULL, NULL, 'fedfef', NULL, 0, 'single', 0, 'Both', 'Both'),
(16, 18, NULL, NULL, NULL, 'efwwwwwwwwwwwww', 'HTF', 1, 'single', 1, 'Both', 'Both'),
(17, 18, NULL, NULL, NULL, 'ewwwwwwwwwwwwww', 'HTF', 1, 'single', 2, 'Both', 'Both'),
(18, 22, NULL, NULL, NULL, 'HJFFHFH', 'HTF', 1, 'single', 0, 'Both', 'Both');

-- --------------------------------------------------------

--
-- Table structure for table `trader_profile`
--

CREATE TABLE `trader_profile` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `avatar_color` varchar(20) DEFAULT '#3b82f6',
  `primary_session` varchar(50) DEFAULT 'New York',
  `trading_style` varchar(50) DEFAULT 'Swing',
  `experience_level` varchar(50) DEFAULT 'Beginner',
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trader_profile`
--

INSERT INTO `trader_profile` (`id`, `user_id`, `avatar_color`, `primary_session`, `trading_style`, `experience_level`, `bio`, `created_at`, `updated_at`) VALUES
(3, 2, 'blue', 'New York', 'Swing', 'Beginner', NULL, '2026-03-16 12:59:56', '2026-03-16 12:59:56'),
(4, 1, '#3b82f6', 'New York', 'Swing', 'Beginner', NULL, '2026-03-18 17:22:07', '2026-03-18 17:22:07'),
(5, 3, 'blue', 'New York', 'Swing', 'Beginner', NULL, '2026-03-18 18:19:26', '2026-03-18 18:19:26');

-- --------------------------------------------------------

--
-- Table structure for table `trades`
--

CREATE TABLE `trades` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `trade_date` date NOT NULL,
  `pair` varchar(20) NOT NULL,
  `direction` enum('Bullish','Bearish') NOT NULL,
  `session` enum('London','New York','Asian') NOT NULL,
  `entry_price` decimal(10,5) DEFAULT NULL,
  `exit_price` decimal(10,5) DEFAULT NULL,
  `stop_loss` decimal(10,5) DEFAULT NULL,
  `take_profit` decimal(10,5) DEFAULT NULL,
  `position_size` decimal(10,2) DEFAULT NULL,
  `profit_loss` decimal(15,2) DEFAULT 0.00,
  `outcome` enum('Win','Loss','Breakeven','Pending','Skipped') DEFAULT 'Pending',
  `trade_grade` varchar(5) DEFAULT 'C',
  `htf_rules_met` int(11) DEFAULT 0,
  `ltf_rules_met` int(11) DEFAULT 0,
  `compliance_percentage` decimal(5,2) DEFAULT 0.00,
  `skip_reason` varchar(255) DEFAULT NULL,
  `skip_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `pnl_mode` enum('$','%') DEFAULT '$',
  `pnl_value` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trades`
--

INSERT INTO `trades` (`id`, `user_id`, `template_id`, `trade_date`, `pair`, `direction`, `session`, `entry_price`, `exit_price`, `stop_loss`, `take_profit`, `position_size`, `profit_loss`, `outcome`, `trade_grade`, `htf_rules_met`, `ltf_rules_met`, `compliance_percentage`, `skip_reason`, `skip_notes`, `notes`, `pnl_mode`, `pnl_value`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, '2026-03-15', 'EUR/USD', 'Bullish', 'London', NULL, NULL, NULL, NULL, NULL, 60.00, 'Win', 'C', 0, 0, 0.00, NULL, NULL, '', '$', 60.00, '2026-03-15 19:25:18', '2026-03-15 19:27:28'),
(2, 2, NULL, '2026-03-16', 'EUR/USD', 'Bullish', 'New York', NULL, NULL, NULL, NULL, NULL, 10.00, 'Win', 'A+', 0, 0, 0.00, NULL, NULL, '', '%', 1.00, '2026-03-16 14:48:19', '2026-03-16 14:48:59'),
(3, 2, NULL, '2026-03-16', 'EUR/USD', 'Bullish', 'London', NULL, NULL, NULL, NULL, NULL, 101.00, 'Skipped', 'C', 0, 0, 0.00, 'Not enough HTF confluence', 'xxxxx', '', '%', 10.00, '2026-03-16 14:55:59', '2026-03-16 15:02:01'),
(4, 2, NULL, '2026-03-16', 'NAS', 'Bullish', 'London', NULL, NULL, NULL, NULL, NULL, 0.00, 'Pending', 'B+', 0, 0, 0.00, NULL, NULL, '', '$', 0.00, '2026-03-16 15:01:32', '2026-03-16 15:01:32'),
(5, 3, NULL, '2026-03-19', 'EUR/USD', 'Bullish', 'London', NULL, NULL, NULL, NULL, NULL, -60.00, 'Loss', 'B+', 0, 0, 0.00, NULL, NULL, '', '%', 1.00, '2026-03-19 18:55:53', '2026-03-19 18:58:05');

-- --------------------------------------------------------

--
-- Table structure for table `trade_accounts`
--

CREATE TABLE `trade_accounts` (
  `id` int(11) NOT NULL,
  `trade_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `allocated_pnl` decimal(15,2) NOT NULL DEFAULT 0.00,
  `allocated_r` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trade_accounts`
--

INSERT INTO `trade_accounts` (`id`, `trade_id`, `account_id`, `allocated_pnl`, `allocated_r`, `created_at`) VALUES
(4, 1, 1, 60.00, 1.20, '2026-03-15 19:27:28'),
(5, 2, 2, 10.00, 1.00, '2026-03-16 14:48:59'),
(6, 3, 2, 101.00, 10.00, '2026-03-16 15:02:01'),
(7, 5, 3, -10.00, -1.00, '2026-03-19 18:58:05'),
(8, 5, 4, -50.00, -1.00, '2026-03-19 18:58:05');

-- --------------------------------------------------------

--
-- Table structure for table `trade_checklists`
--

CREATE TABLE `trade_checklists` (
  `id` int(11) NOT NULL,
  `trade_id` int(11) NOT NULL,
  `rule_id` int(11) DEFAULT NULL,
  `checklist_type` enum('HTF','LTF') NOT NULL,
  `item_key` varchar(255) NOT NULL,
  `checked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trade_psychology`
--

CREATE TABLE `trade_psychology` (
  `id` int(11) NOT NULL,
  `trade_id` int(11) NOT NULL,
  `emotion` varchar(50) NOT NULL,
  `custom_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trade_psychology`
--

INSERT INTO `trade_psychology` (`id`, `trade_id`, `emotion`, `custom_note`, `created_at`) VALUES
(1, 1, 'FOMO', '', '2026-03-15 19:25:18'),
(2, 2, 'FOMO', '', '2026-03-16 14:48:19'),
(3, 5, 'FOMO', '', '2026-03-19 18:55:54');

-- --------------------------------------------------------

--
-- Table structure for table `trading_accounts`
--

CREATE TABLE `trading_accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `starting_balance` decimal(15,2) NOT NULL,
  `current_balance` decimal(15,2) NOT NULL,
  `risk_mode` enum('percent','fixed') DEFAULT 'percent',
  `risk_percent` decimal(5,2) DEFAULT 1.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trading_accounts`
--

INSERT INTO `trading_accounts` (`id`, `user_id`, `account_name`, `starting_balance`, `current_balance`, `risk_mode`, `risk_percent`, `created_at`) VALUES
(1, 1, 'billa', 5000.00, 5060.00, 'fixed', 1.00, '2026-03-15 19:26:30'),
(2, 2, 'Main Account', 1000.00, 1111.00, 'percent', 1.00, '2026-03-16 12:59:56'),
(3, 3, 'Main Account', 1000.00, 990.00, 'percent', 1.00, '2026-03-18 18:19:27'),
(4, 3, 'thfghfg', 5000.00, 4950.00, 'fixed', 1.00, '2026-03-19 18:57:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `profile_badge` varchar(50) DEFAULT 'Student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `profile_badge`, `created_at`) VALUES
(1, 'bile', 'bile@gmail.com', '$2y$10$YourHashedPasswordHere', 'bille cade', 'Student', '2026-03-13 18:06:06'),
(2, 'bile3', 'Abdirahmanbilow69@gmail.com', '$2y$10$pqEtnVeCmMPfe/A8HafC8uF8wZf12eBvMGKyMs/LZmOlo1DBpNN82', 'abdirahman', 'Student', '2026-03-16 12:59:56'),
(3, 'NASRA', 'NASRA@GMAIL.COM', '$2y$10$zrNO1kpL13DzP1RPvtlrcOfzM/28YmspmyQtJ9j6Nh6GuMjf4hViy', 'NASRA MOHAMED ', 'Student', '2026-03-18 18:19:26');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_outlooks`
--

CREATE TABLE `weekly_outlooks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `week_starting` date NOT NULL,
  `pair` varchar(20) NOT NULL,
  `bias` enum('Bullish','Bearish','Neutral') NOT NULL,
  `analysis` text DEFAULT NULL,
  `chart_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `weekly_outlooks`
--

INSERT INTO `weekly_outlooks` (`id`, `user_id`, `week_starting`, `pair`, `bias`, `analysis`, `chart_image`, `created_at`) VALUES
(1, 1, '2026-03-16', 'EUR/USD', 'Bullish', 'f erferferf', 'outlook_1773773835_1.jpeg', '2026-03-17 18:57:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chart_snapshots`
--
ALTER TABLE `chart_snapshots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trade_id` (`trade_id`);

--
-- Indexes for table `daily_notes`
--
ALTER TABLE `daily_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_daily_note` (`user_id`,`note_date`);

--
-- Indexes for table `daily_rituals`
--
ALTER TABLE `daily_rituals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_daily_ritual` (`user_id`,`ritual_date`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `template_rules`
--
ALTER TABLE `template_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `trader_profile`
--
ALTER TABLE `trader_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `trades`
--
ALTER TABLE `trades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `trade_accounts`
--
ALTER TABLE `trade_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trade_id` (`trade_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `trade_checklists`
--
ALTER TABLE `trade_checklists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trade_id` (`trade_id`),
  ADD KEY `rule_id` (`rule_id`);

--
-- Indexes for table `trade_psychology`
--
ALTER TABLE `trade_psychology`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trade_id` (`trade_id`);

--
-- Indexes for table `trading_accounts`
--
ALTER TABLE `trading_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `weekly_outlooks`
--
ALTER TABLE `weekly_outlooks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chart_snapshots`
--
ALTER TABLE `chart_snapshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `daily_notes`
--
ALTER TABLE `daily_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_rituals`
--
ALTER TABLE `daily_rituals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `template_rules`
--
ALTER TABLE `template_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `trader_profile`
--
ALTER TABLE `trader_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `trades`
--
ALTER TABLE `trades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `trade_accounts`
--
ALTER TABLE `trade_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `trade_checklists`
--
ALTER TABLE `trade_checklists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trade_psychology`
--
ALTER TABLE `trade_psychology`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `trading_accounts`
--
ALTER TABLE `trading_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `weekly_outlooks`
--
ALTER TABLE `weekly_outlooks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chart_snapshots`
--
ALTER TABLE `chart_snapshots`
  ADD CONSTRAINT `chart_snapshots_ibfk_1` FOREIGN KEY (`trade_id`) REFERENCES `trades` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_notes`
--
ALTER TABLE `daily_notes`
  ADD CONSTRAINT `daily_notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_rituals`
--
ALTER TABLE `daily_rituals`
  ADD CONSTRAINT `daily_rituals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `templates`
--
ALTER TABLE `templates`
  ADD CONSTRAINT `templates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template_rules`
--
ALTER TABLE `template_rules`
  ADD CONSTRAINT `template_rules_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `template_rules_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `template_rules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trader_profile`
--
ALTER TABLE `trader_profile`
  ADD CONSTRAINT `trader_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trades`
--
ALTER TABLE `trades`
  ADD CONSTRAINT `trades_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trades_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `trade_accounts`
--
ALTER TABLE `trade_accounts`
  ADD CONSTRAINT `trade_accounts_ibfk_1` FOREIGN KEY (`trade_id`) REFERENCES `trades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trade_accounts_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `trading_accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trade_checklists`
--
ALTER TABLE `trade_checklists`
  ADD CONSTRAINT `trade_checklists_ibfk_1` FOREIGN KEY (`trade_id`) REFERENCES `trades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trade_checklists_ibfk_2` FOREIGN KEY (`rule_id`) REFERENCES `template_rules` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `trade_psychology`
--
ALTER TABLE `trade_psychology`
  ADD CONSTRAINT `trade_psychology_ibfk_1` FOREIGN KEY (`trade_id`) REFERENCES `trades` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trading_accounts`
--
ALTER TABLE `trading_accounts`
  ADD CONSTRAINT `trading_accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `weekly_outlooks`
--
ALTER TABLE `weekly_outlooks`
  ADD CONSTRAINT `weekly_outlooks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
