-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2025 at 04:34 PM
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
-- Database: `dbhqs`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `name`, `description`, `date_created`) VALUES
(1, 'Billing', 'Handles patient billing and financial transactions', '2025-04-14 22:27:13'),
(2, 'Pharmacy', 'Manages and dispenses medications', '2025-04-14 22:27:13'),
(3, 'Medical Records', 'Maintains and secures patient health records', '2025-04-14 22:27:13'),
(4, 'Ultrasound', 'Conducts diagnostic ultrasound imaging', '2025-04-14 22:27:13'),
(5, 'X-ray', 'Performs radiographic imaging for diagnostics', '2025-04-14 22:27:13'),
(6, 'Rehabilitation', 'Provides physical therapy and rehabilitation services', '2025-04-14 22:27:13'),
(7, 'Dialysis', 'Provides treatment for kidney failure patients', '2025-04-14 22:27:13'),
(8, 'Laboratory', 'Conducts tests on patient specimens', '2025-04-14 22:27:13'),
(9, 'Admitting', 'Handles patient admissions and registration', '2025-04-14 22:27:13');

-- --------------------------------------------------------

--
-- Table structure for table `queues`
--

CREATE TABLE `queues` (
  `qid` int(11) NOT NULL,
  `queue_num` varchar(20) NOT NULL,
  `status` enum('waiting','in-progress','completed') DEFAULT 'waiting',
  `service_name` varchar(30) NOT NULL,
  `priority` enum('normal','urgent','emergency','PWD','Senior_Citizen') DEFAULT 'normal',
  `department_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queues`
--

INSERT INTO `queues` (`qid`, `queue_num`, `status`, `service_name`, `priority`, `department_id`, `created_at`, `updated_at`) VALUES
(1, 'BIL-001', 'waiting', 'CBC w/ Platelet', 'normal', 1, '2025-04-14 22:33:24', '2025-04-14 22:33:24'),
(2, 'LAB-001', 'in-progress', 'urinalysis', 'normal', 8, '2025-04-14 22:33:51', '2025-04-14 22:33:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','doctor','nurse','receptionist') NOT NULL,
  `dept_id` int(11) NOT NULL,
  `status` tinyint(4) DEFAULT 2,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `dept_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'DONG', '$2y$10$TbGIOb4wI8zjZG4qAy7dq.utY2ekYs5HnNEI5JCdnF8fPJZ/bAHfO', 'doctor', 8, 2, '2025-04-14 14:30:26', '2025-04-14 14:30:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `queues`
--
ALTER TABLE `queues`
  ADD PRIMARY KEY (`qid`),
  ADD KEY `fk_queues_department` (`department_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_users_department` (`dept_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `queues`
--
ALTER TABLE `queues`
  MODIFY `qid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `queues`
--
ALTER TABLE `queues`
  ADD CONSTRAINT `fk_queues_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`dept_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_department` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
