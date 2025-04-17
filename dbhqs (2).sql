-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 15, 2025 at 09:34 AM
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
  `name` enum('Billing','Pharmacy','Medical Records','Ultrasound','X-ray','Rehabilitation','Dialysis','Laboratory','Admitting','HMO','Information','CIM') NOT NULL,
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
(9, 'Admitting', 'Handles patient admissions and registration', '2025-04-14 22:27:13'),
(10, 'HMO', 'Handles insurance and health maintenance organization coordination', '2025-04-15 10:23:00'),
(11, 'Information', 'Provides information and assistance to patients and visitors', '2025-04-15 10:23:44'),
(12, 'CIM', 'Information Technology', '2025-04-15 14:43:36');

-- --------------------------------------------------------

--
-- Table structure for table `queues`
--

CREATE TABLE `queues` (
  `qid` int(11) NOT NULL,
  `queue_num` varchar(20) NOT NULL,
  `status` enum('waiting','in-progress','completed') DEFAULT 'waiting',
  `service_name` enum('Blood Test','Urinalysis','Medication Pickup','Prescription Refill','Physical Therapy','X-ray Scan','CT Scan','Hemodialysis','Payment','Billing Inquiry','Ultrasound Scan','Record Request','Record Update') NOT NULL,
  `priority` enum('Normal','Emergency','PWD','Senior_Citizen','Pregnant') DEFAULT 'Normal',
  `department_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(50) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_name`, `department_id`) VALUES
(1, 'Blood Test', 8),
(2, 'Urinalysis', 8),
(3, 'X-Ray', 5),
(4, 'Ultrasound', 4),
(5, 'Medication Pickup', 2),
(6, 'Physical Therapy', 6),
(7, 'Dialysis Treatment', 7),
(8, 'Billing Inquiry', 1),
(9, 'Patient Registration', 9),
(10, 'Insurance Claim', 10),
(11, 'Information Request', 11);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','User','HMO','Admitting','Information') NOT NULL,
  `dept_id` int(11) NOT NULL,
  `status` tinyint(4) DEFAULT 2,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `dept_id`, `status`, `created_at`, `updated_at`) VALUES
(2, 'Xen', '$2y$10$Z58UUPiBZWX5b4y6Zyor8.7mE2YLKZdQzRu9nsbKzwr8QFFmSY1ie', 'Admitting', 9, 2, '2025-04-15 02:31:55', '2025-04-15 02:31:55'),
(3, 'DONG', '$2y$10$GoNBVN06aGqEmhhAo5ZztO7TQnxc/ngkWxtPl89YJ5xJIvEwxHkUy', '', 8, 2, '2025-04-15 02:32:31', '2025-04-15 02:32:31'),
(4, 'Nyx', '$2y$10$VMOJCUh09IYJkl.ejuCQcevbycoB9gW/7auGXzj8bZhqqoMkQAegW', 'Information', 11, 2, '2025-04-15 04:05:06', '2025-04-15 04:05:06'),
(5, 'Yen', '$2y$10$42wkSUWL1p8XOnHICA0uyu2Ma8btx.LWsOUqjtwwFbRm1Cm6q6wPW', 'HMO', 10, 2, '2025-04-15 04:05:35', '2025-04-15 04:05:35'),
(6, 'Heh', '$2y$10$KcjduysYYlO0i8trLhownutfHmo12uVo/8QKZAAxdV5hlky7pUvxC', 'User', 8, 2, '2025-04-15 04:06:53', '2025-04-15 04:06:53'),
(7, 'luh', '$2y$10$VwQSem/XEIre2Rxpgbt3EO.puv1K.Nb12ZW4.umrLzfMhPHARVRWe', 'User', 1, 2, '2025-04-15 04:07:24', '2025-04-15 04:07:24'),
(10, 'Tin', '$2y$10$HxFf4zny7DRl0.ChIVOH1.pZFsKKZW40HafT31rly2lGGUgxLOwNm', 'Admin', 12, 2, '2025-04-15 06:44:17', '2025-04-15 06:44:17');

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
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

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
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `queues`
--
ALTER TABLE `queues`
  MODIFY `qid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=276;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `queues`
--
ALTER TABLE `queues`
  ADD CONSTRAINT `fk_queues_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`dept_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`dept_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_department` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
