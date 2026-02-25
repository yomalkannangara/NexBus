-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 28, 2026 at 12:04 PM
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
-- Database: `nexbus`
--
CREATE DATABASE IF NOT EXISTS `nexbus` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `nexbus`;

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `complaint_id` int(11) NOT NULL,
  `passenger_id` int(11) DEFAULT NULL,
  `operator_type` enum('Private','SLTB') DEFAULT NULL,
  `bus_reg_no` varchar(20) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `trip_pointer` varchar(40) DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Open','In Progress','Resolved','Closed') DEFAULT 'Open',
  `assigned_to_user_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  `reply_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`complaint_id`, `passenger_id`, `operator_type`, `bus_reg_no`, `route_id`, `trip_pointer`, `category`, `description`, `status`, `assigned_to_user_id`, `created_at`, `resolved_at`, `reply_text`) VALUES
(1, 1001, 'SLTB', 'NB-1001', 1, 'TT-4-2025-09-12-06:30', 'complaint', 'Departed late from Maharagama; arrived 20 min late to Pettah.', 'In Progress', 53, '2025-09-12 07:05:00', NULL, NULL),
(2, 1001, 'Private', 'NB2341', 1, 'TT-3-2025-09-12-11:16', 'feedback', 'Very clean bus and polite conductor. Please add more morning trips.', 'Resolved', 34, '2025-09-12 12:05:00', '2025-09-12 15:30:00', NULL),
(3, 1002, 'SLTB', 'NB-2001', 2, 'TT-6-2025-09-11-07:00', 'complaint', 'Overcrowded; skipped Moratuwa stop during peak time.', 'In Progress', 31, '2025-09-11 08:30:00', NULL, NULL),
(4, 1002, 'Private', 'PB-1001', 6, 'TT-10-2025-09-10-06:00', 'complaint', 'AC not working but charged full fare. Please inspect.', 'Closed', 30, '2025-09-10 07:20:00', '2025-09-11 09:10:00', NULL),
(5, 1001, 'Private', 'PB-2001', 2, 'TT-12-2025-09-12-08:00', 'complaint', 'Driver was rude and refused to stop at requested halt.', 'Resolved', 34, '2025-09-12 09:30:00', '2025-09-12 15:20:00', 'We apologize for the inconvenience. Driver has been warned and monitored.'),
(6, 1001, 'SLTB', 'NB-1001', 5, NULL, 'feedback', 'testing', 'Open', NULL, '2025-09-13 11:30:13', NULL, NULL),
(7, 1001, 'Private', 'undefined', 5, NULL, 'feedback', 'testing', 'In Progress', NULL, '2025-09-13 11:33:06', NULL, NULL),
(8, 1001, 'SLTB', 'undefined', 1, NULL, 'complaint', 'esdgfhhrtdefhghhg', 'Open', NULL, '2025-11-12 12:41:34', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `depot_attendance`
--

CREATE TABLE `depot_attendance` (
  `id` int(11) NOT NULL,
  `sltb_depot_id` int(11) NOT NULL,
  `attendance_key` varchar(64) NOT NULL,
  `work_date` date NOT NULL,
  `mark_absent` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `depot_attendance`
--

INSERT INTO `depot_attendance` (`id`, `sltb_depot_id`, `attendance_key`, `work_date`, `mark_absent`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'driver:1', '2026-01-28', 0, 'seeded', '2026-01-28 09:49:09', '2026-01-28 09:49:09'),
(2, 1, 'driver:2', '2026-01-28', 0, 'seeded', '2026-01-28 09:49:09', '2026-01-28 09:49:09'),
(3, 1, 'driver:8', '2026-01-28', 0, 'seeded', '2026-01-28 09:49:09', '2026-01-28 09:49:09'),
(4, 1, 'driver:11', '2026-01-28', 0, 'seeded', '2026-01-28 09:49:09', '2026-01-28 09:49:09'),
(8, 1, 'conductor:1', '2026-01-28', 0, 'seeded', '2026-01-28 09:49:09', '2026-01-28 09:49:09'),
(9, 1, 'conductor:2', '2026-01-28', 0, 'seeded', '2026-01-28 09:49:09', '2026-01-28 09:49:09'),
(10, 1, 'conductor:8', '2026-01-28', 0, 'seeded', '2026-01-28 09:49:09', '2026-01-28 09:49:09'),
(11, 1, 'conductor:11', '2026-01-28', 0, 'seeded', '2026-01-28 09:49:09', '2026-01-28 09:49:09'),
(15, 1, 'user:31', '2025-10-10', 0, 'Shift A', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(16, 1, 'user:32', '2025-10-10', 0, 'Morning shift', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(17, 2, 'user:39', '2025-10-10', 0, 'Dispatch support', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(18, 1, 'user:19', '2025-10-10', 0, 'Manager on duty', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(19, 3, 'user:41', '2025-10-10', 0, 'Admin review', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(20, 1, 'user:32', '2025-10-11', 0, 'Morning shift', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(21, 1, 'user:56', '2025-10-22', 1, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(22, 1, 'user:19', '2025-10-22', 0, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(23, 1, 'user:31', '2025-10-22', 0, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(24, 1, 'user:32', '2025-10-22', 0, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(25, 1, 'user:53', '2025-10-22', 0, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(26, 1, 'user:54', '2025-10-22', 0, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(27, 1, 'user:10001', '2025-10-22', 0, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(28, 2, 'user:39', '2025-10-23', 0, 'Officer on duty', '2026-01-28 09:53:36', '2026-01-28 09:53:36');

-- --------------------------------------------------------

--
-- Table structure for table `earnings`
--

CREATE TABLE `earnings` (
  `earning_id` int(11) NOT NULL,
  `operator_type` enum('Private','SLTB') NOT NULL,
  `bus_reg_no` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `source` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `earnings`
--

INSERT INTO `earnings` (`earning_id`, `operator_type`, `bus_reg_no`, `date`, `amount`, `source`) VALUES
(1, 'SLTB', 'NB-1001', '2025-10-10', 84500.00, 'cash'),
(2, 'SLTB', 'NB-1002', '2025-10-10', 67250.00, 'cash'),
(3, 'SLTB', 'NB-2001', '2025-10-10', 90500.00, 'cash'),
(4, 'SLTB', 'NB-2002', '2025-10-10', 51200.00, 'cash'),
(5, 'SLTB', 'NB-3001', '2025-10-10', 78150.00, 'cash'),
(6, 'SLTB', 'NB-3002', '2025-10-10', 73600.00, 'cash'),
(7, 'Private', 'PB-1001', '2025-10-10', 63500.00, 'cash'),
(8, 'Private', 'PB-1002', '2025-10-10', 59800.00, 'cash'),
(9, 'Private', 'PB-2001', '2025-10-10', 70250.00, 'cash'),
(10, 'Private', 'PB-2002', '2025-10-10', 52100.00, 'cash'),
(11, 'Private', 'PB-3001', '2025-10-10', 74550.00, 'cash'),
(12, 'Private', 'PB-3002', '2025-10-10', 68900.00, 'cash'),
(13, 'Private', 'PA-1001', '2025-10-10', 71200.00, 'cash'),
(14, 'Private', 'PA-1002', '2025-10-10', 69500.00, 'cash'),
(15, 'SLTB', 'NB-3101', '2025-10-10', 76300.00, 'cash');

-- --------------------------------------------------------

--
-- Table structure for table `fares`
--

CREATE TABLE `fares` (
  `fare_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `stage_number` smallint(6) NOT NULL,
  `super_luxury` decimal(10,2) DEFAULT NULL,
  `luxury` decimal(10,2) DEFAULT NULL,
  `semi_luxury` decimal(10,2) DEFAULT NULL,
  `normal_service` decimal(10,2) DEFAULT NULL,
  `is_super_luxury_active` tinyint(1) DEFAULT 0,
  `is_luxury_active` tinyint(1) DEFAULT 0,
  `is_semi_luxury_active` tinyint(1) DEFAULT 0,
  `is_normal_service_active` tinyint(1) DEFAULT 1,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fares`
--

INSERT INTO `fares` (`fare_id`, `route_id`, `stage_number`, `super_luxury`, `luxury`, `semi_luxury`, `normal_service`, `is_super_luxury_active`, `is_luxury_active`, `is_semi_luxury_active`, `is_normal_service_active`, `effective_from`, `effective_to`) VALUES
(1, 1, 1, 50.00, 40.00, 35.00, 30.00, 1, 1, 1, 1, '2025-08-21', NULL),
(2, 1, 1, 120.00, 90.00, 72.00, 50.00, 1, 1, 1, 1, '2025-08-01', NULL),
(3, 1, 2, 150.00, 110.00, 80.00, 60.00, 1, 1, 1, 1, '2025-08-01', NULL),
(4, 1, 3, 200.00, 150.00, 100.00, 70.00, 1, 1, 1, 1, '2025-08-01', NULL),
(6, 2, 2, 160.00, 120.00, 85.00, 65.00, 1, 1, 1, 1, '2025-08-01', NULL),
(7, 2, 3, 210.00, 160.00, 110.00, 80.00, 1, 1, 1, 1, '2025-08-01', NULL),
(8, 3, 1, 100.00, 80.00, 60.00, 40.00, 1, 1, 1, 1, '2025-08-01', NULL),
(9, 3, 2, 140.00, 100.00, 75.00, 55.00, 1, 1, 1, 1, '2025-08-01', NULL),
(10, 4, 4, 12.00, 54.00, 36.00, 0.00, 1, 1, 1, 0, '2025-12-15', NULL),
(11, 4, 1, 100.00, 75.00, 55.00, 40.00, 1, 1, 1, 1, '2025-12-15', NULL),
(12, 4, 2, 140.00, 105.00, 75.00, 55.00, 1, 1, 1, 1, '2025-12-15', NULL),
(13, 4, 3, 180.00, 135.00, 95.00, 70.00, 1, 1, 1, 1, '2025-12-15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('System','Delay','Complaint','Timetable','Message','Alert','Breakdown') DEFAULT 'System',
  `message` text NOT NULL,
  `is_seen` tinyint(1) DEFAULT 0,
  `priority` enum('normal','urgent','critical') DEFAULT 'normal',
  `metadata` json DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `is_seen`, `created_at`) VALUES
(10, 19, 'System', 'Welcome to NexBus dashboard.', 1, '2025-10-12 09:00:00'),
(11, 31, 'Delay', 'Route 138 experiencing 15 min delay.', 0, '2025-10-12 09:05:00'),
(12, 32, 'Complaint', 'New complaint assigned to you.', 0, '2025-10-12 09:10:00'),
(13, 34, 'Timetable', 'Sunday timetable updated for private ops.', 0, '2025-10-12 09:15:00'),
(14, 35, 'Message', 'Owner portal maintenance tonight.', 0, '2025-10-12 09:20:00'),
(15, 37, 'System', 'Thanks for joining NexBus!', 1, '2025-10-12 09:25:00'),
(16, 38, 'Delay', 'Galle–Matara (700) late by 10 min.', 0, '2025-10-12 09:30:00'),
(17, 39, 'System', 'Weekly report is ready.', 0, '2025-10-12 09:35:00'),
(18, 41, 'Timetable', 'Kandy depot holiday schedule posted.', 0, '2025-10-12 09:40:00'),
(19, 42, 'Message', 'New feature: favourites notifications.', 1, '2025-10-12 09:45:00'),
(20, 19, 'Message', 'hi', 0, '2025-10-20 10:09:25');

-- --------------------------------------------------------

--
-- Table structure for table `passengers`
--

CREATE TABLE `passengers` (
  `passenger_id` int(11) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`passenger_id`, `first_name`, `last_name`, `user_id`, `email`, `phone`, `password_hash`, `created_at`) VALUES
(1, NULL, '', 42, 'testp@gmail.com', '0712345678', '$2y$10$demoHashForTesting', '2025-09-12 14:44:39'),
(1001, NULL, '', NULL, 'kavindu123@gmail.com', '0711111111', 'hash_seed_amal', '2025-09-13 11:04:15'),
(1002, NULL, '', 37, 'dilshan.w@gmail.com', '0772222222', 'hash_seed_nimesha', '2025-09-13 11:04:15'),
(1003, NULL, '', 44, 'testppp@gmail.com', '2344564456', '$2y$10$PK6QMZs4izD8ccy78vAzJeU06C04Dqe.ZgtXF/nYlyGamN8bttwNS', '2025-10-16 21:12:44'),
(1004, 'Deleted', 'User', 46, 'deleted+46@20251017092752.invalid', NULL, NULL, '2025-10-17 00:35:40'),
(1005, 'testrt', '', 52, 'tesetry@gmail.com', '244312343', '$2y$10$0KTJKEUEa3nOgvcbWpw7KeGkS39phT9awHOBYfZjdEN7S3NNHh36a', '2025-10-17 00:47:33'),
(1006, 'Passenger', '', 45, 'passenger@gmail.com', '0774564456456', NULL, '2025-10-22 07:11:23'),
(1007, 'yomal', 'kannangara', 10003, 'yomal4@gmail.com', '0776844788', '$2y$10$C2BBkVSH6WDFvwgxQiWN5.AYlVpzSCqQBJzCaeod3mAFSx.MNQ/Pa', '2025-10-22 11:21:34'),
(1008, 'yomal', '', NULL, 'yomalkannangara@gmail.com', '076586584', '$2y$10$wdzTsBeLJOUTqHnkUBnWiu.RaYLMkc.roS3Sql3M8YF8KsHzvqhNq', '2025-10-22 12:42:29');

-- --------------------------------------------------------

--
-- Table structure for table `passenger_favourites`
--

CREATE TABLE `passenger_favourites` (
  `favourite_id` int(11) NOT NULL,
  `passenger_id` int(11) NOT NULL,
  `operator_type` enum('Private','SLTB') NOT NULL,
  `route_id` int(11) DEFAULT NULL,
  `notify_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `passenger_favourites`
--

INSERT INTO `passenger_favourites` (`favourite_id`, `passenger_id`, `operator_type`, `route_id`, `notify_enabled`, `created_at`) VALUES
(48, 1, 'SLTB', 2, 0, '2025-09-12 14:44:49'),
(51, 1, 'SLTB', 6, 1, '2025-09-12 14:44:49'),
(65, 1, 'Private', 10, 1, '2025-09-12 22:44:27');

-- --------------------------------------------------------

--
-- Table structure for table `private_assignments`
--

CREATE TABLE `private_assignments` (
  `assignment_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `shift` enum('Morning','Evening','Night') DEFAULT 'Morning',
  `bus_reg_no` varchar(20) NOT NULL,
  `private_driver_id` int(11) NOT NULL,
  `private_conductor_id` int(11) NOT NULL,
  `private_operator_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `private_assignments`
--

INSERT INTO `private_assignments` (`assignment_id`, `assigned_date`, `shift`, `bus_reg_no`, `private_driver_id`, `private_conductor_id`, `private_operator_id`) VALUES
(3, '2025-10-22', 'Morning', 'PA-1001', 5101, 6101, 1),
(4, '2025-10-23', 'Morning', 'PA-1002', 1, 2, 1),
(5, '2025-10-23', 'Evening', 'PB-2001', 4, 3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `private_buses`
--

CREATE TABLE `private_buses` (
  `reg_no` varchar(20) NOT NULL,
  `private_operator_id` int(11) NOT NULL,
  `chassis_no` varchar(60) DEFAULT NULL,
  `capacity` smallint(6) DEFAULT NULL,
  `status` enum('Active','Maintenance','Inactive') DEFAULT 'Active',
  `driver_id` int(11) DEFAULT NULL,
  `conductor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `private_buses`
--

INSERT INTO `private_buses` (`reg_no`, `private_operator_id`, `chassis_no`, `capacity`, `status`, `driver_id`, `conductor_id`) VALUES
('213123', 10, 'df456456', 54, 'Active', NULL, NULL),
('NB2341', 10, 'df456456', 54, 'Active', NULL, NULL),
('PA-1001', 1, NULL, NULL, 'Active', NULL, NULL),
('PA-1002', 1, NULL, NULL, 'Active', NULL, NULL),
('PB-1001', 1, 'PCHS-001', 50, 'Active', NULL, NULL),
('PB-1002', 1, 'PCHS-002', 50, 'Maintenance', NULL, NULL),
('PB-2001', 2, 'PCHS-101', 45, 'Active', NULL, NULL),
('PB-2002', 2, 'PCHS-102', 45, 'Inactive', NULL, NULL),
('PB-3001', 3, 'PCHS-201', 52, 'Active', NULL, NULL),
('PB-3002', 3, 'PCHS-202', 52, 'Active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `private_bus_owners`
--

CREATE TABLE `private_bus_owners` (
  `private_operator_id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `reg_no` varchar(60) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `contact_email` varchar(30) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `owner_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `private_bus_owners`
--

INSERT INTO `private_bus_owners` (`private_operator_id`, `name`, `reg_no`, `contact_phone`, `contact_email`, `city`, `owner_user_id`) VALUES
(1, 'Prime Transport Services', 'PR-1001', '0771234567', 'info@primetransport.lk', 'Colombo', NULL),
(2, 'CityExpress Buses', 'PR-1002', '0772222222', NULL, 'Kandy', NULL),
(3, 'Sunrise Travels', 'PR-1003', '0773333333', NULL, 'Galle', NULL),
(4, 'Matara Express Buses', 'PR-1004', '0773456789', 'info@mataraexpress.lk', 'Matara', NULL),
(5, 'Negombo City Transport', 'PR-1005', '0774567890', 'contact@negomboct.lk', 'Negombo', NULL),
(6, 'Jaffna Northern Travels', 'PR-1006', '0775678901', 'admin@jaffnatravels.lk', 'Jaffna', NULL),
(7, 'Kurunegala Central Buses', 'PR-1007', '0776789012', 'support@kurunegalacb.lk', 'Kurunegala', NULL),
(8, 'Anuradhapura Heritage Transport', 'PR-1008', '0777890123', 'info@anuradhapuraht.lk', 'Anuradhapura', NULL),
(13, 'test', 'test', 'test', 'test', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `private_conductors`
--

CREATE TABLE `private_conductors` (
  `private_conductor_id` int(11) NOT NULL,
  `private_operator_id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` enum('Active','Suspended') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `private_conductors`
--

INSERT INTO `private_conductors` (`private_conductor_id`, `private_operator_id`, `full_name`, `phone`, `status`) VALUES
(1, 1, 'Indunil Priyankara', '0714100101', 'Suspended'),
(2, 1, 'Roshan Kumara', '0714100102', 'Active'),
(3, 2, 'Kanishka Dilshan', '0714200101', 'Active'),
(4, 2, 'Isuru Madushan', '0714200102', 'Active'),
(5, 2, 'Shalitha Gunasekara', '0714200103', 'Active'),
(6, 3, 'Chathura Sandaruwan', '0714300101', 'Active'),
(7, 3, 'Lahiru Wijesinghe', '0714300102', 'Active'),
(8, 3, 'Thusitha Priyantha', '0714300103', 'Active'),
(9, 1, 'Eranga Jayasena', '0714100103', 'Active'),
(10, 2, 'Dinuka Ranasinghe', '0714200104', 'Suspended'),
(6101, 0, 'Ishara Fernando', NULL, 'Active'),
(6102, 2, 'Buddhika Samaraweera', '0714200105', 'Active'),
(6103, 3, 'Nalin Rajapaksa', '0714300104', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `private_drivers`
--

CREATE TABLE `private_drivers` (
  `private_driver_id` int(11) NOT NULL,
  `private_operator_id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `license_no` varchar(60) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` enum('Active','Suspended') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `private_drivers`
--

INSERT INTO `private_drivers` (`private_driver_id`, `private_operator_id`, `full_name`, `license_no`, `phone`, `status`) VALUES
(1, 1, 'Kasun Perera', 'L-PRV-1001', '0771001002', 'Active'),
(2, 1, 'Saman Jayawarden323', 'L-PRV-1002', '0771001002', 'Suspended'),
(3, 1, 'Anura Silva  aaa', 'L-PRV-1003', '0771001002', 'Active'),
(4, 2, 'Ruwan Fernando', 'L-PRV-2001', '0772001001', 'Active'),
(5, 2, 'Tharindu Weerasekara', 'L-PRV-2002', '0772001002', 'Active'),
(7, 3, 'Nilantha Dissanayake', 'L-PRV-3001', '0773001001', 'Active'),
(8, 3, 'Mahesh Bandara', 'L-PRV-3002', '0773001002', 'Active'),
(9, 3, 'Chamara Senanayake', 'L-PRV-3003', '0773001003', 'Active'),
(10, 3, 'Dilshan Karunarathne', 'L-PRV-3004', '0773001004', 'Suspended'),
(5101, 0, 'Tharindu Jayasinghe', NULL, NULL, 'Active'),
(5108, 1, 'gayashab', '1234', '0753800728', 'Suspended'),
(5109, 1, 'yomal kannagara', '12334', '0753800728', 'Active'),
(5110, 2, 'Gamini Rodrigo', 'L-PRV-2003', '0772001003', 'Active'),
(5111, 3, 'Ajith Gunasekara', 'L-PRV-3005', '0773001005', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `private_trips`
--

CREATE TABLE `private_trips` (
  `private_trip_id` int(11) NOT NULL,
  `timetable_id` int(11) NOT NULL,
  `bus_reg_no` varchar(20) NOT NULL,
  `trip_date` date DEFAULT NULL,
  `scheduled_departure_time` time DEFAULT NULL,
  `scheduled_arrival_time` time DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `private_driver_id` int(11) DEFAULT NULL,
  `private_conductor_id` int(11) DEFAULT NULL,
  `private_operator_id` int(11) DEFAULT NULL,
  `turn_no` int(11) DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `status` enum('Planned','InProgress','Completed','Cancelled') DEFAULT 'Planned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `private_trips`
--

INSERT INTO `private_trips` (`private_trip_id`, `timetable_id`, `bus_reg_no`, `trip_date`, `scheduled_departure_time`, `scheduled_arrival_time`, `route_id`, `private_driver_id`, `private_conductor_id`, `private_operator_id`, `turn_no`, `departure_time`, `arrival_time`, `status`) VALUES
(1, 10, 'PB-1001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '06:00:00', '07:10:00', 'Completed'),
(2, 11, 'PB-1002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '07:30:00', '08:50:00', 'Completed'),
(3, 12, 'PB-2001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '08:00:00', '09:00:00', 'Completed'),
(4, 13, 'PB-2002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09:30:00', '11:15:00', 'Completed'),
(5, 14, 'PB-3001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '06:45:00', '07:40:00', 'Completed'),
(6, 15, 'PB-3002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '08:15:00', '09:10:00', 'Completed'),
(7, 21, 'PB-1001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '07:30:00', '09:30:00', 'Completed'),
(8, 22, 'PB-1002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10:00:00', '12:00:00', 'Completed'),
(9, 23, 'PB-2001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '06:45:00', '08:15:00', 'Completed'),
(10, 24, 'PB-2002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09:30:00', '11:00:00', 'Completed'),
(11, 36, 'PB-1001', '2025-10-21', '06:00:00', '07:15:00', 2, NULL, NULL, 1, 1, '23:58:06', '23:58:15', 'Completed'),
(12, 10, 'PB-1001', '2025-10-21', '06:00:00', '07:10:00', 6, NULL, NULL, 1, 2, '23:58:07', '23:58:16', 'Completed'),
(13, 21, 'PB-1001', '2025-10-21', '07:30:00', '09:30:00', 5, NULL, NULL, 1, 3, '23:58:09', '23:58:18', 'Completed'),
(14, 11, 'PB-1002', '2025-10-21', '07:30:00', '08:50:00', 7, NULL, NULL, 1, 1, '23:58:10', '23:58:19', 'Completed'),
(15, 28, 'PB-1001', '2025-10-21', '14:30:00', '15:30:00', 5, NULL, NULL, 1, 6, '23:58:27', NULL, 'InProgress'),
(16, 39, 'PB-1002', '2025-10-21', '14:00:00', '15:15:00', 2, NULL, NULL, 1, 2, '23:58:26', NULL, 'InProgress'),
(17, 38, 'PB-1001', '2025-10-21', '11:00:00', '12:15:00', 2, NULL, NULL, 1, 5, '23:58:26', NULL, 'InProgress'),
(19, 22, 'PB-1002', '2025-10-21', '10:00:00', '12:00:00', 5, NULL, NULL, 1, 3, '23:58:28', NULL, 'InProgress'),
(20, 94001, 'PA-1001', '2025-10-22', '23:57:43', '00:57:43', 12001, 5101, 6101, 1, 1, '23:57:43', NULL, 'InProgress'),
(21, 39, 'PB-1002', '2025-10-22', '14:00:00', '15:15:00', 2, NULL, NULL, 1, 1, '00:02:57', NULL, 'InProgress'),
(22, 37, 'PB-1002', '2025-10-22', '08:30:00', '09:45:00', 2, NULL, NULL, 1, 2, '00:02:59', NULL, 'InProgress');

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `route_id` int(11) NOT NULL,
  `route_no` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `stops_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`stops_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`route_id`, `route_no`, `is_active`, `stops_json`) VALUES
(1, '1', 1, '[\"CMB\", \"KAL\", \"HIK\", \"GAL\"]'),
(2, '2', 1, '[\"CMB\", \"AVS\", \"KEL\", \"KDY\"]'),
(3, '3', 1, '[\"CMB\", \"PET\", \"WAT\", \"NGB\"]'),
(4, '4', 1, '[\"CMB\", \"MTL\", \"MOR\", \"PND\"]'),
(5, '2', 1, '[{\"stop\":\"Colombo\"},{\"stop\":\"Hikkaduwa\"},{\"stop\":\"Galle\"}]'),
(6, '400', 1, '[{\"stop\":\"Colombo\"},{\"stop\":\"Wattala\"},{\"stop\":\"Gampaha\"}]'),
(7, '401', 1, '[{\"stop\":\"Colombo\"},{\"stop\":\"Ja-Ela\"},{\"stop\":\"Negombo\"}]'),
(8, '600', 1, '[{\"stop\":\"Kandy\"},{\"stop\":\"Akuranay\"},{\"stop\":\"Matale\"}]'),
(9, '601', 1, '[{\"stop\":\"Kandy\"},{\"stop\":\"Gampola\"},{\"stop\":\"Nuwara Eliya\"}]'),
(10, '700', 1, '[{\"stop\":\"Galle\"},{\"stop\":\"Weligama\"},{\"stop\":\"Matara\"}]'),
(12001, '120', 1, '[{\"seq\": 1, \"code\": \"CMB\", \"name\": \"Colombo Fort\", \"km\": 0}, {\"seq\": 2, \"code\": \"HRN\", \"name\": \"Horana\", \"km\": 49.0}]'),
(12002, '122', 1, '[{\"seq\":1,\"stop\":\"COLOMBO\"},{\"seq\":2,\"stop\":\"Awissawella\"}]'),
(12003, '121', 1, '[{\"seq\":1,\"stop\":\"Kaduwela\"},{\"seq\":2,\"stop\":\"Malabe\"},{\"seq\":3,\"stop\":\"Battaramulla\"}]'),
(12004, '123', 1, '[{\"seq\":1,\"stop\":\"Dehiwala\"},{\"seq\":2,\"stop\":\"Mount Lavinia\"},{\"seq\":3,\"stop\":\"Ratmalana\"}]'),
(12005, '121', 1, '[{\"seq\":1,\"stop\":\"Kaduwela\"},{\"seq\":2,\"stop\":\"Malabe\"},{\"seq\":3,\"stop\":\"Battaramulla\"}]'),
(12006, '123', 1, '[{\"seq\":1,\"stop\":\"Dehiwala\"},{\"seq\":2,\"stop\":\"Mount Lavinia\"},{\"seq\":3,\"stop\":\"Ratmalana\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `version` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`version`) VALUES
('20250820182641');

-- --------------------------------------------------------

--
-- Table structure for table `sltb_assignments`
--

CREATE TABLE `sltb_assignments` (
  `assignment_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `shift` enum('Morning','Evening','Night') DEFAULT 'Morning',
  `bus_reg_no` varchar(20) NOT NULL,
  `sltb_driver_id` int(11) NOT NULL,
  `sltb_conductor_id` int(11) NOT NULL,
  `sltb_depot_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sltb_assignments`
--

INSERT INTO `sltb_assignments` (`assignment_id`, `assigned_date`, `shift`, `bus_reg_no`, `sltb_driver_id`, `sltb_conductor_id`, `sltb_depot_id`) VALUES
(1, '2025-10-21', 'Morning', 'NB-1001', 1001, 2001, 1),
(2, '2025-10-22', 'Morning', 'NB-1001', 2, 8, 1),
(7, '2025-10-23', 'Morning', 'NA-2024', 2, 8, 1),
(8, '2025-10-23', 'Morning', 'NB-3104', 1001, 2, 1),
(9, '2025-10-24', 'Morning', 'NB-1002', 1001, 2001, 1),
(10, '2025-10-24', 'Evening', 'NB-2001', 3, 3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `sltb_buses`
--

CREATE TABLE `sltb_buses` (
  `reg_no` varchar(20) NOT NULL,
  `sltb_depot_id` int(11) NOT NULL,
  `chassis_no` varchar(60) DEFAULT NULL,
  `capacity` smallint(6) DEFAULT NULL,
  `status` enum('Active','Maintenance','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sltb_buses`
--

INSERT INTO `sltb_buses` (`reg_no`, `sltb_depot_id`, `chassis_no`, `capacity`, `status`) VALUES
('NA-2024', 1, '', 48, 'Active'),
('NA-2025', 1, '', 54, 'Active'),
('NA-2030', 1, '', 54, 'Active'),
('NA-23233', 1, '', 54, 'Active'),
('NA-2420', 1, '', 48, 'Active'),
('NB-1001', 1, 'CHS-13801', 54, 'Active'),
('NB-1002', 1, 'CHS-13802', 54, 'Active'),
('NB-1003', 1, 'CHASSIS-1003', 54, 'Active'),
('NB-1004', 1, 'CHASSIS-1004', 54, 'Active'),
('NB-1005', 1, 'CHASSIS-1005', 54, 'Active'),
('NB-1006', 1, 'CHASSIS-1006', 54, 'Active'),
('NB-1007', 1, 'CHASSIS-1007', 54, 'Active'),
('NB-1008', 1, 'CHASSIS-1008', 54, 'Active'),
('NB-1009', 1, 'CHASSIS-1009', 54, 'Active'),
('NB-1010', 1, 'CHASSIS-1010', 54, 'Active'),
('NB-2001', 2, 'CHS-10001', 60, 'Active'),
('NB-2002', 2, 'CHS-10002', 60, 'Inactive'),
('NB-3001', 3, 'CHS-00201', 55, 'Active'),
('NB-3002', 3, 'CHS-00202', 55, 'Active'),
('NB-3101', 1, NULL, NULL, 'Active'),
('NB-3102', 1, NULL, NULL, 'Active'),
('NB-3103', 1, NULL, NULL, 'Active'),
('NB-3104', 1, NULL, NULL, 'Active'),
('NB-3105', 1, NULL, NULL, 'Active'),
('NB-3106', 1, NULL, NULL, 'Active'),
('NB-3107', 1, NULL, NULL, 'Active'),
('NB-3108', 1, NULL, NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `sltb_conductors`
--

CREATE TABLE `sltb_conductors` (
  `sltb_conductor_id` int(11) NOT NULL,
  `sltb_depot_id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `employee_no` varchar(60) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` enum('Active','Suspended') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sltb_conductors`
--

INSERT INTO `sltb_conductors` (`sltb_conductor_id`, `sltb_depot_id`, `full_name`, `employee_no`, `phone`, `status`) VALUES
(1, 1, 'S. Dissanayake', 'C-CC-1001', '0715101001', 'Active'),
(2, 1, 'R. Jayalath', 'C-CC-1002', '0715101002', 'Active'),
(3, 2, 'P. Gunawardhana', 'C-KY-2001', '0715102001', 'Active'),
(4, 2, 'W. Weerasinghe', 'C-KY-2002', '0715102002', 'Active'),
(5, 3, 'H. Fernando', 'C-GL-3001', '0715103001', 'Active'),
(6, 3, 'U. Weerasekara', 'C-GL-3002', '0715103002', 'Active'),
(7, 4, 'Test Conductor', 'C-TT-4001', '0715104001', 'Active'),
(8, 1, 'K. Ranasinghe', 'C-CC-1003', '0715101003', 'Active'),
(9, 2, 'D. Abeysinghe', 'C-KY-2003', '0715102003', 'Suspended'),
(10, 3, 'L. Kumari', 'C-GL-3003', '0715103003', 'Active'),
(11, 1, 'Pradeep Kumara', 'C-CC-1004', '0715101004', 'Active'),
(12, 2, 'Udaya Perera', 'C-KY-2004', '0715102004', 'Active'),
(13, 3, 'Sampath Fernando', 'C-GL-3004', '0715103004', 'Active'),
(2001, 1, 'Sunil Silva', 'CON-2001', '0772002001', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `sltb_depots`
--

CREATE TABLE `sltb_depots` (
  `sltb_depot_id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `address` varchar(120) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sltb_depots`
--

INSERT INTO `sltb_depots` (`sltb_depot_id`, `name`, `address`, `phone`, `code`) VALUES
(1, 'Colombo Depot', 'Colombo', '011-2000000', 'CMB'),
(2, 'Kandy Depot', 'Kandy', '0812233445', NULL),
(3, 'Galle Depot', 'Galle', '0912233445', NULL),
(4, 'test', 'testtest', 'testsetst', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sltb_drivers`
--

CREATE TABLE `sltb_drivers` (
  `sltb_driver_id` int(11) NOT NULL,
  `sltb_depot_id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `employee_no` varchar(60) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` enum('Active','Suspended') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sltb_drivers`
--

INSERT INTO `sltb_drivers` (`sltb_driver_id`, `sltb_depot_id`, `full_name`, `employee_no`, `phone`, `status`) VALUES
(1, 1, 'Sunimal Perera', 'D-CC-1001', '0715001001', 'Active'),
(2, 1, 'Hemantha Priyantha', 'D-CC-1002', '0715001002', 'Active'),
(3, 2, 'K.G. Wijeratne', 'D-KY-2001', '0715002001', 'Active'),
(4, 2, 'M.D. Jayasena', 'D-KY-2002', '0715002002', 'Active'),
(5, 3, 'A.M. Priyashantha', 'D-GL-3001', '0715003001', 'Active'),
(6, 3, 'B.S. Liyanage', 'D-GL-3002', '0715003002', 'Active'),
(7, 4, 'Test Driver', 'D-TT-4001', '0715004001', 'Active'),
(8, 1, 'Manjula Hettiarachchi', 'D-CC-1003', '0715001003', 'Active'),
(9, 2, 'Chinthaka Ranawaka', 'D-KY-2003', '0715002003', 'Suspended'),
(10, 3, 'Sujeewa Lakmali', 'D-GL-3003', '0715003003', 'Active'),
(11, 1, 'Nimal Bandara', 'D-CC-1004', '0715001004', 'Active'),
(12, 2, 'Asanka Wickramasinghe', 'D-KY-2004', '0715002004', 'Active'),
(13, 3, 'Janaka Silva', 'D-GL-3004', '0715003004', 'Active'),
(1001, 1, 'Kamal Perera', 'DRV-1001', '0771001001', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `sltb_trips`
--

CREATE TABLE `sltb_trips` (
  `sltb_trip_id` int(11) NOT NULL,
  `timetable_id` int(11) NOT NULL,
  `bus_reg_no` varchar(20) NOT NULL,
  `trip_date` date DEFAULT NULL,
  `scheduled_departure_time` time DEFAULT NULL,
  `scheduled_arrival_time` time DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `sltb_driver_id` int(11) DEFAULT NULL,
  `sltb_conductor_id` int(11) DEFAULT NULL,
  `sltb_depot_id` int(11) DEFAULT NULL,
  `turn_no` int(11) DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `status` enum('Planned','InProgress','Completed','Cancelled') DEFAULT 'Planned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sltb_trips`
--

INSERT INTO `sltb_trips` (`sltb_trip_id`, `timetable_id`, `bus_reg_no`, `trip_date`, `scheduled_departure_time`, `scheduled_arrival_time`, `route_id`, `sltb_driver_id`, `sltb_conductor_id`, `sltb_depot_id`, `turn_no`, `departure_time`, `arrival_time`, `status`) VALUES
(1, 4, 'NB-1001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '06:30:00', '07:30:00', 'Completed'),
(2, 5, 'NB-1002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '08:00:00', '09:00:00', 'Completed'),
(3, 6, 'NB-2001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '07:00:00', '08:15:00', 'Completed'),
(4, 7, 'NB-2002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09:00:00', '10:30:00', 'Completed'),
(5, 8, 'NB-3001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '06:45:00', '07:40:00', 'Completed'),
(6, 9, 'NB-3002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '08:15:00', '09:10:00', 'Completed'),
(7, 16, 'NB-1001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '07:00:00', '08:00:00', 'Completed'),
(8, 17, 'NB-1002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09:00:00', '10:00:00', 'Completed'),
(9, 25, 'NB-3001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '08:00:00', '09:30:00', 'Completed'),
(10, 26, 'NB-3002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10:15:00', '11:45:00', 'Completed'),
(11, 4, 'NB-1001', '2025-10-22', '06:30:00', '07:30:00', 1, NULL, NULL, 1, 1, '07:12:16', NULL, 'InProgress'),
(12, 85, 'NB-3101', '2025-10-22', '10:30:00', '11:15:00', 1, NULL, NULL, 1, 1, '10:43:19', '10:43:29', 'Completed'),
(13, 30, 'NB-1001', '2025-10-22', '05:30:00', '06:30:00', 1, 2, 8, 1, 1, '12:51:05', NULL, 'InProgress'),
(14, 30, 'NB-1001', '2025-10-23', '05:30:00', '06:30:00', 1, NULL, NULL, 1, 1, '09:51:57', NULL, 'InProgress'),
(15, 62, 'NB-3102', '2025-10-23', '06:10:00', '07:05:00', 1, NULL, NULL, 1, 1, '10:01:23', NULL, 'InProgress'),
(16, 63, 'NB-3103', '2025-10-23', '06:20:00', '07:00:00', 3, NULL, NULL, 1, 1, '11:31:23', NULL, 'InProgress'),
(17, 30, 'NB-1001', '2025-11-12', '05:30:00', '06:30:00', 1, NULL, NULL, 1, 1, '12:18:00', '12:21:30', 'Completed');

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance`
--

CREATE TABLE `staff_attendance` (
  `attendance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `mark_absent` tinyint(1) DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_attendance`
--

INSERT INTO `staff_attendance` (`attendance_id`, `user_id`, `work_date`, `mark_absent`, `notes`, `created_at`, `updated_at`) VALUES
(1, 31, '2025-10-10', 0, 'Shift A', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(2, 32, '2025-10-10', 0, 'Morning shift', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(3, 39, '2025-10-10', 0, 'Dispatch support', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(4, 19, '2025-10-10', 0, 'Manager on duty', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(5, 30, '2025-10-10', 0, 'System audit', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(6, 34, '2025-10-10', 1, 'Suspended', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(7, 35, '2025-10-10', 0, 'Owner meeting', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(8, 38, '2025-10-10', 0, 'Private TK shift', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(9, 41, '2025-10-10', 0, 'Admin review', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(10, 14, '2025-10-10', 0, 'Owner tasks', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(11, 40, '2025-10-10', 0, 'Owner tasks', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(12, 32, '2025-10-11', 0, 'Morning shift', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(13, 56, '2025-10-22', 1, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(14, 19, '2025-10-22', 0, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(15, 31, '2025-10-22', 0, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(16, 32, '2025-10-22', 0, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(17, 53, '2025-10-22', 0, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(18, 54, '2025-10-22', 0, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(19, 10001, '2025-10-22', 0, '', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(20, 30, '2025-10-23', 0, 'Admin duties', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(21, 38, '2025-10-23', 0, 'Timekeeper shift', '2026-01-28 09:53:36', '2026-01-28 09:53:36'),
(22, 39, '2025-10-23', 0, 'Officer on duty', '2026-01-28 09:53:36', '2026-01-28 09:53:36');

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `timetable_id` int(11) NOT NULL,
  `operator_type` enum('Private','SLTB') NOT NULL,
  `route_id` int(11) NOT NULL,
  `bus_reg_no` varchar(20) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `departure_time` time NOT NULL,
  `arrival_time` time DEFAULT NULL,
  `start_seq` smallint(6) DEFAULT NULL,
  `end_seq` smallint(6) DEFAULT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetables`
--

INSERT INTO `timetables` (`timetable_id`, `operator_type`, `route_id`, `bus_reg_no`, `day_of_week`, `departure_time`, `arrival_time`, `start_seq`, `end_seq`, `effective_from`, `effective_to`) VALUES
(2, 'SLTB', 1, 'NB-5667', 0, '17:04:00', '21:46:00', 1, 11, '2025-08-22', NULL),
(3, 'Private', 1, 'NB2341', 0, '11:16:00', '00:17:00', 1, 11, '2025-08-23', NULL),
(5, 'SLTB', 1, 'NB-1002', 1, '08:00:00', '09:00:00', 1, 3, '2025-08-01', NULL),
(6, 'SLTB', 2, 'NB-2001', 1, '07:00:00', '08:15:00', 1, 3, '2025-08-01', NULL),
(7, 'SLTB', 2, 'NB-2002', 2, '09:00:00', '10:30:00', 1, 3, '2025-08-01', NULL),
(8, 'SLTB', 3, 'NB-3001', 1, '06:45:00', '07:40:00', 1, 3, '2025-08-01', NULL),
(9, 'SLTB', 3, 'NB-3002', 3, '08:15:00', '09:10:00', 1, 3, '2025-08-01', NULL),
(11, 'Private', 7, 'PB-1002', 2, '07:30:00', '08:50:00', 1, 3, '2025-08-01', NULL),
(12, 'Private', 8, 'PB-2001', 1, '08:00:00', '09:00:00', 1, 3, '2025-08-01', NULL),
(13, 'Private', 9, 'PB-2002', 3, '09:30:00', '11:15:00', 1, 3, '2025-08-01', NULL),
(14, 'Private', 10, 'PB-3001', 1, '06:45:00', '07:40:00', 1, 3, '2025-08-01', NULL),
(15, 'Private', 10, 'PB-3002', 5, '08:15:00', '09:10:00', 1, 3, '2025-08-01', NULL),
(16, 'SLTB', 1, 'NB-1001', 5, '07:00:00', '08:00:00', 1, 3, '2025-09-12', NULL),
(17, 'SLTB', 1, 'NB-1002', 5, '09:00:00', '10:00:00', 1, 3, '2025-09-12', NULL),
(18, 'SLTB', 1, 'NB-1001', 5, '11:30:00', '12:30:00', 1, 3, '2025-09-12', NULL),
(19, 'SLTB', 2, 'NB-2001', 5, '08:15:00', '09:15:00', 1, 3, '2025-09-12', NULL),
(20, 'SLTB', 2, 'NB-2002', 5, '10:45:00', '11:45:00', 1, 3, '2025-09-12', NULL),
(22, 'Private', 5, 'PB-1002', 5, '10:00:00', '12:00:00', 1, 3, '2025-09-12', NULL),
(23, 'Private', 7, 'PB-2001', 5, '06:45:00', '08:15:00', 1, 3, '2025-09-12', NULL),
(24, 'Private', 7, 'PB-2002', 5, '09:30:00', '11:00:00', 1, 3, '2025-09-12', NULL),
(25, 'SLTB', 10, 'NB-3001', 5, '08:00:00', '09:30:00', 1, 3, '2025-09-12', NULL),
(26, 'SLTB', 10, 'NB-3002', 5, '10:15:00', '11:45:00', 1, 3, '2025-09-12', NULL),
(27, 'SLTB', 1, 'NB-1001', 5, '15:00:00', '16:00:00', 1, 3, '2025-09-12', NULL),
(29, 'SLTB', 10, 'NB-3002', 5, '15:10:00', '15:50:00', 1, 3, '2025-09-12', NULL),
(30, 'SLTB', 1, 'NB-1001', 5, '05:30:00', '06:30:00', 1, 3, '2025-09-12', NULL),
(31, 'SLTB', 1, 'NB-1002', 5, '07:00:00', '08:00:00', 1, 3, '2025-09-12', NULL),
(32, 'SLTB', 1, 'NB-1001', 5, '09:30:00', '10:30:00', 1, 3, '2025-09-12', NULL),
(33, 'SLTB', 1, 'NB-1002', 5, '12:00:00', '13:00:00', 1, 3, '2025-09-12', NULL),
(34, 'SLTB', 1, 'NB-1001', 5, '15:00:00', '16:00:00', 1, 3, '2025-09-12', NULL),
(35, 'SLTB', 1, 'NB-1002', 5, '18:00:00', '19:00:00', 1, 3, '2025-09-12', NULL),
(37, 'Private', 2, 'PB-1002', 5, '08:30:00', '09:45:00', 1, 3, '2025-09-12', NULL),
(39, 'Private', 2, 'PB-1002', 5, '14:00:00', '15:15:00', 1, 3, '2025-09-12', NULL),
(41, 'Private', 2, 'PB-1002', 5, '20:00:00', '21:15:00', 1, 3, '2025-09-12', NULL),
(42, 'SLTB', 3, 'NB-2001', 5, '05:45:00', '06:45:00', 1, 3, '2025-09-12', NULL),
(43, 'SLTB', 3, 'NB-2002', 5, '07:15:00', '08:15:00', 1, 3, '2025-09-12', NULL),
(44, 'SLTB', 3, 'NB-2001', 5, '10:00:00', '11:00:00', 1, 3, '2025-09-12', NULL),
(45, 'SLTB', 3, 'NB-2002', 5, '13:00:00', '14:00:00', 1, 3, '2025-09-12', NULL),
(46, 'SLTB', 3, 'NB-2001', 5, '16:00:00', '17:00:00', 1, 3, '2025-09-12', NULL),
(47, 'SLTB', 3, 'NB-2002', 5, '19:00:00', '20:00:00', 1, 3, '2025-09-12', NULL),
(48, 'Private', 7, 'PB-2001', 5, '06:15:00', '07:45:00', 1, 3, '2025-09-12', NULL),
(49, 'Private', 7, 'PB-2002', 5, '09:00:00', '10:30:00', 1, 3, '2025-09-12', NULL),
(50, 'Private', 7, 'PB-2001', 5, '12:00:00', '13:30:00', 1, 3, '2025-09-12', NULL),
(51, 'Private', 7, 'PB-2002', 5, '15:00:00', '16:30:00', 1, 3, '2025-09-12', NULL),
(52, 'Private', 7, 'PB-2001', 5, '18:00:00', '19:30:00', 1, 3, '2025-09-12', NULL),
(53, 'Private', 7, 'PB-2002', 5, '21:00:00', '22:30:00', 1, 3, '2025-09-12', NULL),
(54, 'SLTB', 4, 'NB-3001', 5, '05:00:00', '08:00:00', 1, 3, '2025-09-12', NULL),
(55, 'SLTB', 4, 'NB-3002', 5, '09:00:00', '12:00:00', 1, 3, '2025-09-12', NULL),
(56, 'SLTB', 4, 'NB-3001', 5, '13:00:00', '16:00:00', 1, 3, '2025-09-12', NULL),
(57, 'SLTB', 4, 'NB-3002', 5, '17:00:00', '20:00:00', 1, 3, '2025-09-12', NULL),
(62, 'SLTB', 1, 'NB-3102', 3, '06:10:00', '07:05:00', 1, 4, '2025-10-21', NULL),
(63, 'SLTB', 3, 'NB-3103', 3, '06:20:00', '07:00:00', 1, 4, '2025-10-21', NULL),
(64, 'SLTB', 4, 'NB-3104', 3, '06:30:00', '07:20:00', 1, 4, '2025-10-21', NULL),
(65, 'SLTB', 1, 'NB-3105', 3, '06:40:00', '07:25:00', 1, 4, '2025-10-21', NULL),
(66, 'SLTB', 2, 'NB-3106', 3, '06:50:00', '07:45:00', 1, 4, '2025-10-21', NULL),
(67, 'SLTB', 3, 'NB-3107', 3, '07:00:00', '07:45:00', 1, 4, '2025-10-21', NULL),
(68, 'SLTB', 4, 'NB-3108', 3, '07:10:00', '07:55:00', 1, 4, '2025-10-21', NULL),
(69, 'SLTB', 1, 'NB-3109', 3, '07:20:00', '08:05:00', 1, 4, '2025-10-21', NULL),
(70, 'SLTB', 2, 'NB-3110', 3, '07:30:00', '08:20:00', 1, 4, '2025-10-21', NULL),
(71, 'SLTB', 3, 'NB-3111', 3, '07:40:00', '08:25:00', 1, 4, '2025-10-21', NULL),
(72, 'SLTB', 4, 'NB-3112', 3, '07:50:00', '08:40:00', 1, 4, '2025-10-21', NULL),
(73, 'SLTB', 1, 'NB-3101', 3, '08:00:00', '08:45:00', 1, 4, '2025-10-21', NULL),
(74, 'SLTB', 2, 'NB-3102', 3, '08:10:00', '09:05:00', 1, 4, '2025-10-21', NULL),
(75, 'SLTB', 3, 'NB-3103', 3, '08:20:00', '09:00:00', 1, 4, '2025-10-21', NULL),
(76, 'SLTB', 4, 'NB-3104', 3, '08:30:00', '09:20:00', 1, 4, '2025-10-21', NULL),
(77, 'SLTB', 1, 'NB-3105', 3, '08:40:00', '09:25:00', 1, 4, '2025-10-21', NULL),
(78, 'SLTB', 2, 'NB-3106', 3, '08:50:00', '09:45:00', 1, 4, '2025-10-21', NULL),
(79, 'SLTB', 3, 'NB-3107', 3, '09:00:00', '09:45:00', 1, 4, '2025-10-21', NULL),
(80, 'SLTB', 4, 'NB-3108', 3, '09:10:00', '09:55:00', 1, 4, '2025-10-21', NULL),
(81, 'SLTB', 1, 'NB-3109', 3, '09:20:00', '10:05:00', 1, 4, '2025-10-21', NULL),
(82, 'SLTB', 2, 'NB-3110', 3, '09:30:00', '10:20:00', 1, 4, '2025-10-21', NULL),
(83, 'SLTB', 3, 'NB-3111', 3, '09:40:00', '10:25:00', 1, 4, '2025-10-21', NULL),
(84, 'SLTB', 4, 'NB-3112', 3, '09:50:00', '10:40:00', 1, 4, '2025-10-21', NULL),
(85, 'SLTB', 1, 'NB-3101', 0, '10:31:00', '11:15:00', 1, 4, '2025-10-21', NULL),
(86, 'SLTB', 2, 'NB-3102', 0, '10:45:00', '11:35:00', 1, 4, '2025-10-21', NULL),
(87, 'SLTB', 3, 'NB-3103', 0, '11:00:00', '11:40:00', 1, 4, '2025-10-21', NULL),
(88, 'SLTB', 4, 'NB-3104', 0, '11:15:00', '12:05:00', 1, 4, '2025-10-21', NULL),
(94001, 'Private', 12001, 'PA-1001', 4, '23:57:43', '00:57:43', 1, 2, NULL, NULL),
(94002, 'Private', 12001, 'PA-1002', 4, '01:02:43', '02:32:43', 1, 2, NULL, NULL),
(94010, 'Private', 1, 'PB-3001', 0, '12:35:00', '12:36:00', NULL, NULL, NULL, NULL),
(94011, 'Private', 12002, 'PB-1001', 0, '06:00:00', '07:00:00', NULL, NULL, NULL, NULL),
(94012, 'Private', 12002, 'PB-1001', 0, '08:00:00', '09:00:00', NULL, NULL, NULL, NULL),
(94013, 'Private', 12002, 'PB-1001', 0, '10:00:00', '11:00:00', NULL, NULL, NULL, NULL),
(94014, 'Private', 12002, 'PB-1001', 0, '12:00:00', '13:00:00', NULL, NULL, NULL, NULL),
(94015, 'Private', 12002, 'PB-1001', 1, '06:00:00', '07:00:00', NULL, NULL, NULL, NULL),
(94016, 'Private', 12002, 'PB-1001', 1, '08:00:00', '09:00:00', NULL, NULL, NULL, NULL),
(94017, 'Private', 12002, 'PB-1001', 1, '10:00:00', '11:00:00', NULL, NULL, NULL, NULL),
(94018, 'Private', 12002, 'PB-1001', 1, '12:00:00', '13:00:00', NULL, NULL, NULL, NULL),
(94019, 'Private', 12002, 'PB-1001', 2, '06:00:00', '07:00:00', NULL, NULL, NULL, NULL),
(94020, 'Private', 12002, 'PB-1001', 2, '08:00:00', '09:00:00', NULL, NULL, NULL, NULL),
(94021, 'Private', 12002, 'PB-1001', 2, '10:00:00', '11:00:00', NULL, NULL, NULL, NULL),
(94022, 'Private', 12002, 'PB-1001', 2, '12:00:00', '13:00:00', NULL, NULL, NULL, NULL),
(94023, 'Private', 12002, 'PB-1001', 3, '06:00:00', '07:00:00', NULL, NULL, NULL, NULL),
(94024, 'Private', 12002, 'PB-1001', 3, '08:00:00', '09:00:00', NULL, NULL, NULL, NULL),
(94025, 'Private', 12002, 'PB-1001', 3, '10:00:00', '11:00:00', NULL, NULL, NULL, NULL),
(94026, 'Private', 12002, 'PB-1001', 3, '12:00:00', '13:00:00', NULL, NULL, NULL, NULL),
(94027, 'Private', 12002, 'PB-1001', 4, '06:00:00', '07:00:00', NULL, NULL, NULL, NULL),
(94028, 'Private', 12002, 'PB-1001', 4, '08:00:00', '09:00:00', NULL, NULL, NULL, NULL),
(94029, 'Private', 12002, 'PB-1001', 4, '10:00:00', '11:00:00', NULL, NULL, NULL, NULL),
(94030, 'Private', 12002, 'PB-1001', 4, '12:00:00', '13:00:00', NULL, NULL, NULL, NULL),
(94031, 'Private', 12002, 'PB-1001', 5, '06:00:00', '07:00:00', NULL, NULL, NULL, NULL),
(94032, 'Private', 12002, 'PB-1001', 5, '08:00:00', '09:00:00', NULL, NULL, NULL, NULL),
(94033, 'Private', 12002, 'PB-1001', 5, '10:00:00', '11:00:00', NULL, NULL, NULL, NULL),
(94034, 'Private', 12002, 'PB-1001', 5, '12:00:00', '13:00:00', NULL, NULL, NULL, NULL),
(94035, 'Private', 12002, 'PB-1001', 6, '06:00:00', '07:00:00', NULL, NULL, NULL, NULL),
(94036, 'Private', 12002, 'PB-1001', 6, '08:00:00', '09:00:00', NULL, NULL, NULL, NULL),
(94037, 'Private', 12002, 'PB-1001', 6, '10:00:00', '11:00:00', NULL, NULL, NULL, NULL),
(94038, 'Private', 12002, 'PB-1001', 6, '12:00:00', '13:00:00', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tracking_monitoring`
--

CREATE TABLE `tracking_monitoring` (
  `track_id` bigint(20) NOT NULL,
  `operator_type` enum('Private','SLTB') NOT NULL,
  `bus_reg_no` varchar(20) NOT NULL,
  `snapshot_at` datetime NOT NULL,
  `lat` decimal(9,6) DEFAULT NULL,
  `lng` decimal(9,6) DEFAULT NULL,
  `speed` decimal(5,2) DEFAULT NULL,
  `heading` smallint(6) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `timetable_id` int(11) DEFAULT NULL,
  `trip_ref` varchar(40) DEFAULT NULL,
  `operational_status` enum('OnTime','Delayed','Breakdown','OffDuty') DEFAULT 'OnTime',
  `on_time_score` decimal(5,2) DEFAULT NULL,
  `avg_delay_min` decimal(6,2) DEFAULT NULL,
  `speed_violations` int(11) DEFAULT NULL,
  `breakdowns_count` int(11) DEFAULT NULL,
  `route_adherence_pct` decimal(5,2) DEFAULT NULL,
  `utilization_pct` decimal(5,2) DEFAULT NULL,
  `complaints_today` int(11) DEFAULT NULL,
  `reliability_index` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tracking_monitoring`
--

INSERT INTO `tracking_monitoring` (`track_id`, `operator_type`, `bus_reg_no`, `snapshot_at`, `lat`, `lng`, `speed`, `heading`, `route_id`, `timetable_id`, `trip_ref`, `operational_status`, `on_time_score`, `avg_delay_min`, `speed_violations`, `breakdowns_count`, `route_adherence_pct`, `utilization_pct`, `complaints_today`, `reliability_index`) VALUES
(1, 'SLTB', 'NB-1001', '2025-10-18 07:00:00', 6.846000, 79.927000, 32.50, 90, 1, 4, 'TT-4-2025-10-18-0700', 'OnTime', 95.00, 1.20, 0, 0, 98.00, 76.00, 0, 96.00),
(2, 'SLTB', 'NB-1002', '2025-10-18 08:10:00', 6.866500, 79.900100, 28.00, 110, 1, 5, 'TT-5-2025-10-18-0800', 'Delayed', 88.00, 8.00, 0, 0, 94.00, 70.00, 1, 90.00),
(3, 'SLTB', 'NB-2001', '2025-10-18 07:25:00', 6.715000, 79.902000, 35.20, 85, 2, 6, 'TT-6-2025-10-18-0700', 'OnTime', 92.00, 2.00, 1, 0, 97.00, 68.00, 0, 93.00),
(4, 'SLTB', 'NB-3001', '2025-10-18 06:55:00', 6.930000, 79.860000, 26.70, 60, 3, 8, 'TT-8-2025-10-18-0645', 'OnTime', 94.00, 1.00, 0, 0, 96.50, 66.00, 0, 95.00),
(5, 'Private', 'PB-1001', '2025-10-18 06:20:00', 6.950000, 79.880000, 29.00, 75, 7, 48, 'TT-48-2025-10-18-0615', 'OnTime', 93.00, 1.50, 0, 0, 97.20, 73.00, 0, 94.00),
(6, 'Private', 'PB-1002', '2025-10-18 07:45:00', 6.990000, 79.870000, 31.00, 70, 7, 49, 'TT-49-2025-10-18-0900', 'OnTime', 92.50, 1.00, 0, 0, 96.00, 71.00, 0, 93.50),
(7, 'Private', 'PB-2001', '2025-10-18 08:10:00', 5.960000, 80.540000, 27.00, 40, 10, 14, 'TT-14-2025-10-18-0645', 'OnTime', 91.00, 2.00, 0, 0, 95.00, 69.00, 0, 92.00),
(8, 'Private', 'PB-2002', '2025-10-18 09:20:00', 5.980000, 80.560000, 25.00, 30, 10, 15, 'TT-15-2025-10-18-0815', 'OnTime', 90.00, 2.50, 0, 0, 94.00, 67.00, 0, 91.00),
(9, 'Private', 'PB-3001', '2025-10-18 07:05:00', 6.140000, 80.100000, 33.00, 120, 5, 21, 'TT-21-2025-10-18-0730', 'OnTime', 92.00, 1.00, 0, 0, 96.00, 72.00, 0, 94.00),
(10, 'Private', 'PB-3002', '2025-10-18 08:30:00', 6.160000, 80.120000, 24.00, 135, 5, 22, 'TT-22-2025-10-18-1000', 'OnTime', 91.50, 1.50, 0, 0, 95.50, 70.00, 0, 93.00),
(29, 'SLTB', 'NB-3101', '2025-10-21 06:05:00', NULL, NULL, NULL, NULL, 1, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 'SLTB', 'NB-3102', '2025-10-21 06:12:00', NULL, NULL, NULL, NULL, 2, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(31, 'SLTB', 'NB-3103', '2025-10-21 06:18:00', NULL, NULL, NULL, NULL, 3, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(32, 'SLTB', 'NB-3104', '2025-10-21 06:24:00', NULL, NULL, NULL, NULL, 4, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, 'SLTB', 'NB-3105', '2025-10-21 06:33:00', NULL, NULL, NULL, NULL, 1, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(34, 'SLTB', 'NB-3106', '2025-10-21 06:41:00', NULL, NULL, NULL, NULL, 2, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(35, 'SLTB', 'NB-3107', '2025-10-21 06:49:00', NULL, NULL, NULL, NULL, 3, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 'SLTB', 'NB-3108', '2025-10-21 06:57:00', NULL, NULL, NULL, NULL, 4, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, 'SLTB', 'NB-3109', '2025-10-21 07:05:00', NULL, NULL, NULL, NULL, 1, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(38, 'SLTB', 'NB-3110', '2025-10-21 07:14:00', NULL, NULL, NULL, NULL, 2, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(39, 'SLTB', 'NB-3111', '2025-10-21 07:22:00', NULL, NULL, NULL, NULL, 3, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, 'SLTB', 'NB-3112', '2025-10-21 07:31:00', NULL, NULL, NULL, NULL, 4, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(41, 'SLTB', 'NB-3101', '2025-10-21 08:03:00', NULL, NULL, NULL, NULL, 1, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(42, 'SLTB', 'NB-3102', '2025-10-21 08:11:00', NULL, NULL, NULL, NULL, 2, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(43, 'SLTB', 'NB-3103', '2025-10-21 08:19:00', NULL, NULL, NULL, NULL, 3, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 'SLTB', 'NB-3104', '2025-10-21 08:27:00', NULL, NULL, NULL, NULL, 4, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, 'SLTB', 'NB-3105', '2025-10-21 08:35:00', NULL, NULL, NULL, NULL, 1, NULL, NULL, 'Breakdown', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 'SLTB', 'NB-3106', '2025-10-21 08:44:00', NULL, NULL, NULL, NULL, 2, NULL, NULL, 'OnTime', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(47, 'SLTB', 'NB-3107', '2025-10-21 08:52:00', NULL, NULL, NULL, NULL, 3, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 'SLTB', 'NB-3108', '2025-10-21 09:01:00', NULL, NULL, NULL, NULL, 4, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(49, 'SLTB', 'NB-3109', '2025-10-21 09:10:00', NULL, NULL, NULL, NULL, 1, NULL, NULL, 'OnTime', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(50, 'SLTB', 'NB-3110', '2025-10-21 09:18:00', NULL, NULL, NULL, NULL, 2, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(51, 'SLTB', 'NB-3111', '2025-10-21 09:27:00', NULL, NULL, NULL, NULL, 3, NULL, NULL, 'Delayed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(52, 'SLTB', 'NB-3112', '2025-10-21 09:36:00', NULL, NULL, NULL, NULL, 4, NULL, NULL, 'Breakdown', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `role` enum('NTCAdmin','DepotManager','DepotOfficer','SLTBTimekeeper','PrivateTimekeeper','PrivateBusOwner','Passenger') NOT NULL,
  `first_name` varchar(120) NOT NULL,
  `last_name` varchar(120) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `status` enum('Active','Suspended') DEFAULT 'Active',
  `profile_image` varchar(255) DEFAULT NULL COMMENT 'Path to user profile image',
  `private_operator_id` int(11) DEFAULT NULL,
  `sltb_depot_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `status`, `profile_image`, `private_operator_id`, `sltb_depot_id`, `created_at`, `last_login`) VALUES
(14, 'PrivateBusOwner', 'testr', NULL, 'test123123@gmail.com', '12123', '$2y$10$obFTn6ZUCS3xcNZPHq3YluTK9oxtKoLq3xjnkas6LOGZ7gg6KXcT.', 'Active', NULL, 2, NULL, '2025-08-20 11:32:39', NULL),
(19, 'DepotManager', 'pasidu Perera', NULL, 'test@gmail.sdfom', '1234444', '$2y$10$LTE4cy0KGTyC1KRxB27hjeHpxA9xb5Motcy7qju1qZXl2o.0wGPi6', 'Active', NULL, NULL, 1, '2025-08-23 20:42:19', NULL),
(30, 'NTCAdmin', 'Ruwan Perera', NULL, 'ruwan.ntc@gmail.com', '0711234567', 'hash_ntc_001', 'Active', NULL, NULL, NULL, '2025-08-24 12:14:29', NULL),
(31, 'DepotManager', 'Sunil Silva', NULL, 'sunil.manager@sltb.lk', '0772345678', 'hash_dm_002', 'Active', NULL, NULL, 1, '2025-08-24 12:14:29', NULL),
(32, 'DepotOfficer', 'Chamara Fernando', NULL, 'chamara.officer@sltb.lk', '0759876543', 'hash_do_003', 'Active', NULL, NULL, 1, '2025-08-24 12:14:29', NULL),
(34, 'PrivateTimekeeper', 'Nuwan Jayasuriya', NULL, 'nuwan.timekeeper@gmail.com', '0719988776', 'hash_pt_005', 'Suspended', NULL, 2, NULL, '2025-08-24 12:14:29', NULL),
(35, 'PrivateBusOwner', 'Rohan Abeysekara', NULL, 'rohan.owner@gmail.com', '0776655443', 'hash_po_006', 'Active', NULL, 1, NULL, '2025-08-24 12:14:29', NULL),
(37, 'Passenger', 'Dilshan Wickrama', NULL, 'dilshan.w@gmail.com', '0745566778', 'hash_p_008', 'Active', NULL, NULL, NULL, '2025-08-24 12:14:29', NULL),
(38, 'PrivateTimekeeper', 'Sanduni Karunaratne', NULL, 'sanduni.tk@gmail.com', '0712244668', 'hash_pt_009', 'Active', NULL, 3, NULL, '2025-08-24 12:14:29', NULL),
(39, 'DepotOfficer', 'Lakshan Weerasinghe', NULL, 'lakshan.officer@sltb.lk', '0759988772', 'hash_do_010', 'Active', NULL, NULL, 2, '2025-08-24 12:14:29', NULL),
(40, 'PrivateBusOwner', 'test', NULL, 'test@ga.com', '234234234', '$2y$10$o7dISXlpowYE0CMOSXptz.amRebok1OkE/Cmkt8oHiNb11KxNXT5K', 'Active', NULL, 3, NULL, '2025-08-25 02:18:09', NULL),
(41, 'NTCAdmin', 'Admin', NULL, 'admin@gmail.com', '123123123', '$2y$10$q7olUvGzjx.LyOtpRe0oferXX1xFdkgGYYDOQ6aAqtOLejxlmhraC', 'Active', NULL, NULL, 3, '2025-08-30 16:56:12', NULL),
(42, 'Passenger', 'testp', NULL, 'testp@gmail.com', '77684577848', '$2y$10$VeLHoBlrxvjzIerAcTpZZeM5u9YDjNXv1wY5Fue86X.9126we9DC2', 'Active', NULL, NULL, NULL, '2025-10-16 19:56:37', NULL),
(43, 'Passenger', 'testpp', NULL, 'testpp@gmail.com', '3454564564', '$2y$10$WYYf3zPveDf7IiOoeClXxOZHW1o/QvwgJei8ul74eXbzfELqABwN6', 'Active', NULL, NULL, NULL, '2025-10-16 21:11:43', NULL),
(44, 'Passenger', 'testppp', NULL, 'testppp@gmail.com', '2344564456', '$2y$10$PK6QMZs4izD8ccy78vAzJeU06C04Dqe.ZgtXF/nYlyGamN8bttwNS', 'Active', NULL, NULL, NULL, '2025-10-16 21:12:44', NULL),
(45, 'Passenger', 'Passenger', 'TEST', 'passenger@gmail.com', '0774564456456', '$2y$10$OCoQUYi/DyV/jAar7ke/eudZrM.XtxDExOoRemNNz2vRFnqEsKQgC', 'Active', NULL, NULL, NULL, '2025-10-17 00:30:17', NULL),
(46, 'Passenger', 'Deleted User', NULL, 'deleted+46@20251017092752.invalid', NULL, NULL, 'Suspended', NULL, NULL, NULL, '2025-10-17 00:35:40', NULL),
(52, 'Passenger', 'testrt', NULL, 'tesetry@gmail.com', '244312343', '$2y$10$0KTJKEUEa3nOgvcbWpw7KeGkS39phT9awHOBYfZjdEN7S3NNHh36a', 'Active', NULL, NULL, NULL, '2025-10-17 00:47:33', NULL),
(53, 'DepotOfficer', 'depotofficer', NULL, 'depotofficer@gmail.com', '7767878767', '$2y$10$u0U1OrzOettCJ447PJL6K.247hhylhUKqTXjOyVW0R08lO8mRHDRK', 'Active', NULL, NULL, 1, '2025-10-18 22:16:40', NULL),
(54, 'SLTBTimekeeper', 'sltbtimekeeper', NULL, 'sltbtimekeeper@gmail.com', '567647874', '$2y$10$9l8m0h9lUfQoLAguCJZ.4e9Rp2GXVU/8n1bYPy0vsQ1iP5LWTO0P2', 'Active', NULL, NULL, 1, '2025-10-20 10:46:31', NULL),
(55, 'PrivateBusOwner', 'test2', NULL, 'owner@gmail.com', '35723123', '$2y$10$ftJw4ezyJOL7q8f.Bl0ZuOmq7m3bxcKupz1EaOjyR1b1GCnAjNNFi', 'Active', NULL, 1, NULL, '2025-10-20 15:46:47', NULL),
(56, 'DepotManager', 'DepotManager', NULL, 'manager@gmail.com', '674536742', '$2y$10$uhJz0jRBcIQLe7KJcmn81.lQnAmMvK4OctRTvWblAQ2ZnMM3qbhhW', 'Active', NULL, NULL, 1, '2025-10-21 13:56:16', NULL),
(10001, 'SLTBTimekeeper', 'Test TK', NULL, 'tk@sltb.lk', '077-0000000', '$2y$10$abcdefghijklmnopqrstuv/0nx2cBqZ1kQ6Vxk9tD5mY5oR6E', 'Active', NULL, NULL, 1, '2025-10-21 14:14:18', NULL),
(10002, 'PrivateTimekeeper', 'PrivateTimekeeper', NULL, 'privatetimekeeper@gmail.com', '456456456456', '$2y$10$RwJtGZqggLJL0WYRML..KeTtnXIkJ0HoUouviCj.wcuIzngWXo6pC', 'Active', NULL, 1, NULL, '2025-10-21 23:55:56', NULL),
(10003, 'Passenger', 'yomal kannangara', NULL, 'yomal4@gmail.com', '0776844788', '$2y$10$C2BBkVSH6WDFvwgxQiWN5.AYlVpzSCqQBJzCaeod3mAFSx.MNQ/Pa', 'Active', NULL, NULL, NULL, '2025-10-22 11:21:34', NULL),
(10008, 'DepotOfficer', 'yomal', NULL, 'yomalkannangara2@gmail.com', '45745345345', '$2y$10$m6kR1ipmdrtEKGpiGqwwTOPyrIhQKUDx1TvCaOJmzZZ6ET7OSFE1W', 'Active', NULL, NULL, 1, '2025-10-22 20:02:29', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`complaint_id`),
  ADD KEY `fk_comp_passenger` (`passenger_id`),
  ADD KEY `fk_comp_route` (`route_id`),
  ADD KEY `fk_comp_user` (`assigned_to_user_id`);

--
-- Indexes for table `depot_attendance`
--
ALTER TABLE `depot_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_attendance` (`sltb_depot_id`,`attendance_key`,`work_date`);

--
-- Indexes for table `earnings`
--
ALTER TABLE `earnings`
  ADD PRIMARY KEY (`earning_id`);

--
-- Indexes for table `fares`
--
ALTER TABLE `fares`
  ADD PRIMARY KEY (`fare_id`),
  ADD KEY `fk_fare_route` (`route_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `passengers`
--
ALTER TABLE `passengers`
  ADD PRIMARY KEY (`passenger_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_first_name` (`first_name`);

--
-- Indexes for table `passenger_favourites`
--
ALTER TABLE `passenger_favourites`
  ADD PRIMARY KEY (`favourite_id`),
  ADD KEY `fk_fav_passenger` (`passenger_id`),
  ADD KEY `fk_fav_route` (`route_id`);

--
-- Indexes for table `private_assignments`
--
ALTER TABLE `private_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `uniq_bus_day_shift` (`bus_reg_no`,`assigned_date`,`shift`),
  ADD KEY `idx_op_date` (`private_operator_id`,`assigned_date`),
  ADD KEY `fk_pva_drv` (`private_driver_id`),
  ADD KEY `fk_pva_cond` (`private_conductor_id`);

--
-- Indexes for table `private_buses`
--
ALTER TABLE `private_buses`
  ADD PRIMARY KEY (`reg_no`),
  ADD KEY `fk_pb_operator` (`private_operator_id`),
  ADD KEY `idx_driver_id` (`driver_id`),
  ADD KEY `idx_conductor_id` (`conductor_id`);

--
-- Indexes for table `private_bus_owners`
--
ALTER TABLE `private_bus_owners`
  ADD PRIMARY KEY (`private_operator_id`),
  ADD KEY `fk_po_owner_user` (`owner_user_id`);

--
-- Indexes for table `private_conductors`
--
ALTER TABLE `private_conductors`
  ADD PRIMARY KEY (`private_conductor_id`),
  ADD KEY `fk_pcon_operator` (`private_operator_id`);

--
-- Indexes for table `private_drivers`
--
ALTER TABLE `private_drivers`
  ADD PRIMARY KEY (`private_driver_id`),
  ADD UNIQUE KEY `license_no` (`license_no`),
  ADD KEY `fk_pdrv_operator` (`private_operator_id`);

--
-- Indexes for table `private_trips`
--
ALTER TABLE `private_trips`
  ADD PRIMARY KEY (`private_trip_id`),
  ADD UNIQUE KEY `uniq_p_tt_per_day` (`timetable_id`,`trip_date`),
  ADD KEY `fk_ptrip_tt` (`timetable_id`),
  ADD KEY `fk_ptrip_bus` (`bus_reg_no`),
  ADD KEY `idx_ptrip_scope` (`trip_date`,`status`),
  ADD KEY `idx_ptrip_bus_day` (`bus_reg_no`,`trip_date`),
  ADD KEY `fk_ptrip_route` (`route_id`),
  ADD KEY `fk_ptrip_drv` (`private_driver_id`),
  ADD KEY `fk_ptrip_cond` (`private_conductor_id`),
  ADD KEY `fk_ptrip_owner` (`private_operator_id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`route_id`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `sltb_assignments`
--
ALTER TABLE `sltb_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `uniq_bus_day_shift` (`bus_reg_no`,`assigned_date`,`shift`),
  ADD KEY `idx_depot_date` (`sltb_depot_id`,`assigned_date`),
  ADD KEY `fk_sla_drv` (`sltb_driver_id`),
  ADD KEY `fk_sla_cond` (`sltb_conductor_id`);

--
-- Indexes for table `sltb_buses`
--
ALTER TABLE `sltb_buses`
  ADD PRIMARY KEY (`reg_no`),
  ADD KEY `fk_sb_depot` (`sltb_depot_id`);

--
-- Indexes for table `sltb_conductors`
--
ALTER TABLE `sltb_conductors`
  ADD PRIMARY KEY (`sltb_conductor_id`),
  ADD UNIQUE KEY `employee_no` (`employee_no`),
  ADD KEY `fk_scon_depot` (`sltb_depot_id`);

--
-- Indexes for table `sltb_depots`
--
ALTER TABLE `sltb_depots`
  ADD PRIMARY KEY (`sltb_depot_id`);

--
-- Indexes for table `sltb_drivers`
--
ALTER TABLE `sltb_drivers`
  ADD PRIMARY KEY (`sltb_driver_id`),
  ADD UNIQUE KEY `employee_no` (`employee_no`),
  ADD KEY `fk_sdrv_depot` (`sltb_depot_id`);

--
-- Indexes for table `sltb_trips`
--
ALTER TABLE `sltb_trips`
  ADD PRIMARY KEY (`sltb_trip_id`),
  ADD UNIQUE KEY `uniq_tt_per_day` (`timetable_id`,`trip_date`),
  ADD KEY `fk_strip_tt` (`timetable_id`),
  ADD KEY `fk_strip_bus` (`bus_reg_no`),
  ADD KEY `idx_trip_scope` (`trip_date`,`status`),
  ADD KEY `idx_trip_bus_day` (`bus_reg_no`,`trip_date`),
  ADD KEY `fk_strip_route` (`route_id`),
  ADD KEY `fk_strip_drv` (`sltb_driver_id`),
  ADD KEY `fk_strip_cond` (`sltb_conductor_id`),
  ADD KEY `fk_strip_depot` (`sltb_depot_id`);

--
-- Indexes for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `fk_att_user` (`user_id`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`timetable_id`),
  ADD KEY `fk_tt_route` (`route_id`);

--
-- Indexes for table `tracking_monitoring`
--
ALTER TABLE `tracking_monitoring`
  ADD PRIMARY KEY (`track_id`),
  ADD KEY `fk_trk_route` (`route_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_sltb_depot` (`sltb_depot_id`),
  ADD KEY `idx_users_private_operator_id` (`private_operator_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `complaint_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `depot_attendance`
--
ALTER TABLE `depot_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `earnings`
--
ALTER TABLE `earnings`
  MODIFY `earning_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `fares`
--
ALTER TABLE `fares`
  MODIFY `fare_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `passengers`
--
ALTER TABLE `passengers`
  MODIFY `passenger_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1010;

--
-- AUTO_INCREMENT for table `passenger_favourites`
--
ALTER TABLE `passenger_favourites`
  MODIFY `favourite_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `private_assignments`
--
ALTER TABLE `private_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `private_bus_owners`
--
ALTER TABLE `private_bus_owners`
  MODIFY `private_operator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `private_conductors`
--
ALTER TABLE `private_conductors`
  MODIFY `private_conductor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6104;

--
-- AUTO_INCREMENT for table `private_drivers`
--
ALTER TABLE `private_drivers`
  MODIFY `private_driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5112;

--
-- AUTO_INCREMENT for table `private_trips`
--
ALTER TABLE `private_trips`
  MODIFY `private_trip_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `route_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12007;

--
-- AUTO_INCREMENT for table `sltb_assignments`
--
ALTER TABLE `sltb_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sltb_conductors`
--
ALTER TABLE `sltb_conductors`
  MODIFY `sltb_conductor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2002;

--
-- AUTO_INCREMENT for table `sltb_depots`
--
ALTER TABLE `sltb_depots`
  MODIFY `sltb_depot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sltb_drivers`
--
ALTER TABLE `sltb_drivers`
  MODIFY `sltb_driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1002;

--
-- AUTO_INCREMENT for table `sltb_trips`
--
ALTER TABLE `sltb_trips`
  MODIFY `sltb_trip_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `timetable_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94039;

--
-- AUTO_INCREMENT for table `tracking_monitoring`
--
ALTER TABLE `tracking_monitoring`
  MODIFY `track_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10014;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `fk_comp_passenger` FOREIGN KEY (`passenger_id`) REFERENCES `passengers` (`passenger_id`),
  ADD CONSTRAINT `fk_comp_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`),
  ADD CONSTRAINT `fk_comp_user_new` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `fares`
--
ALTER TABLE `fares`
  ADD CONSTRAINT `fk_fare_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `passengers`
--
ALTER TABLE `passengers`
  ADD CONSTRAINT `fk_passengers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_passengers_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `passenger_favourites`
--
ALTER TABLE `passenger_favourites`
  ADD CONSTRAINT `fk_fav_passenger` FOREIGN KEY (`passenger_id`) REFERENCES `passengers` (`passenger_id`),
  ADD CONSTRAINT `fk_fav_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`);

--
-- Constraints for table `private_assignments`
--
ALTER TABLE `private_assignments`
  ADD CONSTRAINT `fk_pva_bus` FOREIGN KEY (`bus_reg_no`) REFERENCES `private_buses` (`reg_no`),
  ADD CONSTRAINT `fk_pva_cond` FOREIGN KEY (`private_conductor_id`) REFERENCES `private_conductors` (`private_conductor_id`),
  ADD CONSTRAINT `fk_pva_drv` FOREIGN KEY (`private_driver_id`) REFERENCES `private_drivers` (`private_driver_id`),
  ADD CONSTRAINT `fk_pva_owner` FOREIGN KEY (`private_operator_id`) REFERENCES `private_bus_owners` (`private_operator_id`);

--
-- Constraints for table `private_buses`
--
ALTER TABLE `private_buses`
  ADD CONSTRAINT `fk_pb_operator` FOREIGN KEY (`private_operator_id`) REFERENCES `private_bus_owners` (`private_operator_id`);

--
-- Constraints for table `private_bus_owners`
--
ALTER TABLE `private_bus_owners`
  ADD CONSTRAINT `fk_po_owner_user` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `private_conductors`
--
ALTER TABLE `private_conductors`
  ADD CONSTRAINT `fk_pcon_operator` FOREIGN KEY (`private_operator_id`) REFERENCES `private_bus_owners` (`private_operator_id`);

--
-- Constraints for table `private_drivers`
--
ALTER TABLE `private_drivers`
  ADD CONSTRAINT `fk_pdrv_operator` FOREIGN KEY (`private_operator_id`) REFERENCES `private_bus_owners` (`private_operator_id`);

--
-- Constraints for table `private_trips`
--
ALTER TABLE `private_trips`
  ADD CONSTRAINT `fk_ptrip_bus` FOREIGN KEY (`bus_reg_no`) REFERENCES `private_buses` (`reg_no`),
  ADD CONSTRAINT `fk_ptrip_cond` FOREIGN KEY (`private_conductor_id`) REFERENCES `private_conductors` (`private_conductor_id`),
  ADD CONSTRAINT `fk_ptrip_drv` FOREIGN KEY (`private_driver_id`) REFERENCES `private_drivers` (`private_driver_id`),
  ADD CONSTRAINT `fk_ptrip_owner` FOREIGN KEY (`private_operator_id`) REFERENCES `private_bus_owners` (`private_operator_id`),
  ADD CONSTRAINT `fk_ptrip_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`),
  ADD CONSTRAINT `fk_ptrip_tt` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`timetable_id`);

--
-- Constraints for table `sltb_assignments`
--
ALTER TABLE `sltb_assignments`
  ADD CONSTRAINT `fk_sla_bus` FOREIGN KEY (`bus_reg_no`) REFERENCES `sltb_buses` (`reg_no`),
  ADD CONSTRAINT `fk_sla_cond` FOREIGN KEY (`sltb_conductor_id`) REFERENCES `sltb_conductors` (`sltb_conductor_id`),
  ADD CONSTRAINT `fk_sla_depot` FOREIGN KEY (`sltb_depot_id`) REFERENCES `sltb_depots` (`sltb_depot_id`),
  ADD CONSTRAINT `fk_sla_drv` FOREIGN KEY (`sltb_driver_id`) REFERENCES `sltb_drivers` (`sltb_driver_id`);

--
-- Constraints for table `sltb_buses`
--
ALTER TABLE `sltb_buses`
  ADD CONSTRAINT `fk_sb_depot` FOREIGN KEY (`sltb_depot_id`) REFERENCES `sltb_depots` (`sltb_depot_id`);

--
-- Constraints for table `sltb_conductors`
--
ALTER TABLE `sltb_conductors`
  ADD CONSTRAINT `fk_scon_depot` FOREIGN KEY (`sltb_depot_id`) REFERENCES `sltb_depots` (`sltb_depot_id`);

--
-- Constraints for table `sltb_drivers`
--
ALTER TABLE `sltb_drivers`
  ADD CONSTRAINT `fk_sdrv_depot` FOREIGN KEY (`sltb_depot_id`) REFERENCES `sltb_depots` (`sltb_depot_id`);

--
-- Constraints for table `sltb_trips`
--
ALTER TABLE `sltb_trips`
  ADD CONSTRAINT `fk_strip_bus` FOREIGN KEY (`bus_reg_no`) REFERENCES `sltb_buses` (`reg_no`),
  ADD CONSTRAINT `fk_strip_cond` FOREIGN KEY (`sltb_conductor_id`) REFERENCES `sltb_conductors` (`sltb_conductor_id`),
  ADD CONSTRAINT `fk_strip_depot` FOREIGN KEY (`sltb_depot_id`) REFERENCES `sltb_depots` (`sltb_depot_id`),
  ADD CONSTRAINT `fk_strip_drv` FOREIGN KEY (`sltb_driver_id`) REFERENCES `sltb_drivers` (`sltb_driver_id`),
  ADD CONSTRAINT `fk_strip_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`),
  ADD CONSTRAINT `fk_strip_tt` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`timetable_id`);

--
-- Constraints for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD CONSTRAINT `fk_att_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `fk_tt_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`);

--
-- Constraints for table `tracking_monitoring`
--
ALTER TABLE `tracking_monitoring`
  ADD CONSTRAINT `fk_trk_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_private_operator` FOREIGN KEY (`private_operator_id`) REFERENCES `private_bus_owners` (`private_operator_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_sltb_depot` FOREIGN KEY (`sltb_depot_id`) REFERENCES `sltb_depots` (`sltb_depot_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- --------------------------------------------------------
-- Additional migrations appended on 2026-02-22
-- Migration: Add arrival_depot_id and completed_by to sltb_trips
-- Purpose: Enforce Option B business rule (only the timekeeper at the
-- route's designated ending depot may complete a trip). This section
-- adds storage columns; enforcement logic is implemented in PHP models.

ALTER TABLE `sltb_trips`
  ADD COLUMN `arrival_depot_id` int(11) DEFAULT NULL AFTER `arrival_time`,
  ADD COLUMN `completed_by` int(11) DEFAULT NULL AFTER `arrival_depot_id`;

ALTER TABLE `sltb_trips`
  ADD INDEX `idx_arrival_depot` (`arrival_depot_id`),
  ADD INDEX `idx_completed_by` (`completed_by`);

-- Optional foreign keys (uncomment if referential integrity desired):
-- ALTER TABLE `sltb_trips`
--   ADD CONSTRAINT `fk_sltbtrips_arrival_depot` FOREIGN KEY (`arrival_depot_id`) REFERENCES `sltb_depots`(`sltb_depot_id`),
--   ADD CONSTRAINT `fk_sltbtrips_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users`(`user_id`);

-- Notes:
-- - The application resolves a route's last stop token (route.stops_json)
--   and attempts to match it to sltb_depots.code or sltb_depots.name when
--   validating who can complete a trip.
-- - If route stop tokens don't map to depots in your dataset, populate
--   `sltb_depots.code` or adjust the matching logic in TurnModel::complete().

-- --------------------------------------------------------
-- Additional migration appended on 2026-02-22: private_trips completion fields
ALTER TABLE `private_trips`
  ADD COLUMN `arrival_depot_id` int(11) DEFAULT NULL AFTER `arrival_time`,
  ADD COLUMN `completed_by` int(11) DEFAULT NULL AFTER `arrival_depot_id`;

ALTER TABLE `private_trips`
  ADD INDEX `idx_arrival_depot` (`arrival_depot_id`),
  ADD INDEX `idx_completed_by` (`completed_by`);

-- Optional FKs (uncomment if desired):
-- ALTER TABLE `private_trips`
--   ADD CONSTRAINT `fk_ptrips_arrival_depot` FOREIGN KEY (`arrival_depot_id`) REFERENCES `sltb_depots`(`sltb_depot_id`),
--   ADD CONSTRAINT `fk_ptrips_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users`(`user_id`);

-- --------------------------------------------------------
-- Additional cancellation columns appended on 2026-02-22
ALTER TABLE `sltb_trips`
  ADD COLUMN `cancelled_by` int(11) DEFAULT NULL AFTER `completed_by`,
  ADD COLUMN `cancel_reason` text DEFAULT NULL AFTER `cancelled_by`,
  ADD COLUMN `cancelled_at` timestamp NULL DEFAULT NULL AFTER `cancel_reason`;

ALTER TABLE `private_trips`
  ADD COLUMN `cancelled_by` int(11) DEFAULT NULL AFTER `completed_by`,
  ADD COLUMN `cancel_reason` text DEFAULT NULL AFTER `cancelled_by`,
  ADD COLUMN `cancelled_at` timestamp NULL DEFAULT NULL AFTER `cancel_reason`;

ALTER TABLE `sltb_trips` ADD INDEX `idx_cancelled_by` (`cancelled_by`);
ALTER TABLE `private_trips` ADD INDEX `idx_cancelled_by` (`cancelled_by`);

-- --------------------------------------------------------
-- Migration appended on 2026-02-22: assignment override fields
-- Adds columns to record when an assignment is overridden and why
ALTER TABLE `sltb_assignments`
  ADD COLUMN `override_remark` TEXT DEFAULT NULL AFTER `sltb_depot_id`,
  ADD COLUMN `overridden_by` int(11) DEFAULT NULL AFTER `override_remark`,
  ADD COLUMN `override_at` datetime DEFAULT NULL AFTER `overridden_by`;

ALTER TABLE `sltb_assignments`
  ADD INDEX `idx_overridden_by` (`overridden_by`);

-- Note: optionally add a FK to users.overridden_by if desired
-- ALTER TABLE `sltb_assignments` ADD CONSTRAINT `fk_sla_overridden_by` FOREIGN KEY (`overridden_by`) REFERENCES `users`(`user_id`);

 
-- --------------------------------------------------------
-- Migration appended on 2026-02-22: create audit table for assignment overrides
CREATE TABLE `sltb_assignment_overrides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) DEFAULT NULL,
  `assigned_date` date NOT NULL,
  `shift` enum('Morning','Evening','Night') DEFAULT 'Morning',
  `bus_reg_no` varchar(20) NOT NULL,
  `previous_bus_reg_no` varchar(20) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `conductor_id` int(11) DEFAULT NULL,
  `override_remark` text DEFAULT NULL,
  `overridden_by` int(11) DEFAULT NULL,
  `override_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `sltb_assignment_overrides`
  ADD INDEX `idx_overridden_by` (`overridden_by`),
  ADD INDEX `idx_assignment` (`assignment_id`);

-- Optional FK constraints (uncomment to enable):
-- ALTER TABLE `sltb_assignment_overrides`
--   ADD CONSTRAINT `fk_aov_overridden_by` FOREIGN KEY (`overridden_by`) REFERENCES `users`(`user_id`),
--   ADD CONSTRAINT `fk_aov_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `sltb_assignments`(`assignment_id`);



-- STEP 2D — SQL migration  (run once on your database)


-- 1. Widen the notifications.type ENUM so 'Urgent' and 'Breakdown' are valid.
--    (Safe to run again — MySQL ignores it if the values already exist.)
ALTER TABLE notifications
    MODIFY COLUMN `type` ENUM(
        'Message',
        'Delay',
        'Timetable',
        'Alert',
        'Urgent',
        'Breakdown',
        'System'
    ) NOT NULL DEFAULT 'Message';

-- 2. Add a performance index so the depot inbox query stays fast even with
--    thousands of notification rows.
--    (CREATE INDEX … IF NOT EXISTS requires MySQL 8+; use the plain form on 5.7)
CREATE INDEX IF NOT EXISTS idx_notif_depot_type_time
    ON notifications (user_id, type, created_at);

-- ─────────────────────────────────────────────────────────────────────────────
-- OPTIONAL (for a future richer messages table — skip for now):
-- ─────────────────────────────────────────────────────────────────────────────
/*
CREATE TABLE IF NOT EXISTS depot_messages (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    depot_id     INT UNSIGNED NOT NULL,
    sender_id    INT UNSIGNED NOT NULL,
    scope        ENUM('individual','role','depot') NOT NULL DEFAULT 'individual',
    priority     ENUM('normal','urgent','critical') NOT NULL DEFAULT 'normal',
    body         TEXT NOT NULL,
    related_type VARCHAR(40)  NULL,   -- 'assignment' | 'trip' | 'bus'
    related_id   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dm_depot (depot_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS depot_message_recipients (
    message_id INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    read_at    DATETIME NULL,
    ack_at     DATETIME NULL,
    PRIMARY KEY (message_id, user_id),
    INDEX idx_dmr_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- MESSAGING SYSTEM ENHANCEMENTS (Depot Officer Messages MVP)
-- ============================================================================
-- Indexes for optimized notification queries
ALTER TABLE notifications ADD INDEX IF NOT EXISTS idx_user_seen (user_id, is_seen, created_at);
ALTER TABLE notifications ADD INDEX IF NOT EXISTS idx_type_created (type, created_at);
ALTER TABLE notifications ADD INDEX IF NOT EXISTS idx_created_at (created_at);
*/

