-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2025 at 08:16 PM
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
-- Database: `hqs`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `name` enum('Billing','Pharmacy','Medical Records','Ultrasound','X-ray','Rehabilitation','Dialysis','Laboratory','Admitting','HMO','Information','CIM','Emergency Room','Social Worker') NOT NULL,
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
(12, 'CIM', 'Information Technology', '2025-04-15 14:43:36'),
(13, 'Emergency Room', NULL, '2025-04-18 01:59:58'),
(14, 'Social Worker', NULL, '2025-04-18 02:00:06');

-- --------------------------------------------------------

--
-- Table structure for table `queues`
--

CREATE TABLE `queues` (
  `qid` int(11) NOT NULL,
  `queue_num` varchar(20) NOT NULL,
  `status` enum('waiting','in-progress','completed','postponed','pending') DEFAULT 'waiting',
  `service_name` enum('Blood Test','Urinalysis','Medication Pickup','Prescription Refill','Physical Therapy','X-ray Scan','CT Scan','Hemodialysis','Payment','Billing Inquiry','Ultrasound Scan','Record Request','Record Update') NOT NULL,
  `priority` enum('Normal','Emergency','PWD','Senior_Citizen','Pregnant') DEFAULT 'Normal',
  `department_id` int(11) NOT NULL,
  `announcement_count` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queues`
--

INSERT INTO `queues` (`qid`, `queue_num`, `status`, `service_name`, `priority`, `department_id`, `announcement_count`, `created_at`, `updated_at`) VALUES
(276, 'XR-001', '', '', 'Emergency', 5, 3, '2025-04-17 23:20:06', '2025-04-17 23:22:05'),
(277, 'XR-002', 'completed', '', 'Normal', 5, 1, '2025-04-17 23:20:14', '2025-04-17 23:26:19'),
(278, 'XR-003', 'completed', '', 'Normal', 5, 0, '2025-04-18 00:17:15', '2025-04-18 00:17:30'),
(279, 'XR-004', 'completed', '', 'Emergency', 5, 2, '2025-04-17 23:20:28', '2025-04-17 23:24:17'),
(280, 'XR-005', 'completed', '', 'Normal', 5, 3, '2025-04-17 23:41:58', '2025-04-18 00:10:54'),
(281, 'XR-006', 'completed', '', 'Normal', 5, 1, '2025-04-18 00:17:28', '2025-04-18 00:31:14'),
(282, 'XR-007', 'completed', '', 'Emergency', 5, 3, '2025-04-18 00:10:53', '2025-04-18 00:14:05'),
(283, 'XR-008', 'completed', '', 'Senior_Citizen', 5, 1, '2025-04-18 00:14:38', '2025-04-18 00:15:45'),
(284, 'XR-009', 'completed', '', 'Normal', 5, 0, '2025-04-18 00:31:56', '2025-04-18 00:42:15'),
(285, 'XR-010', 'completed', '', 'Normal', 5, 0, '2025-04-18 00:32:04', '2025-04-18 00:42:40'),
(286, 'XR-011', 'completed', '', 'PWD', 5, 1, '2025-04-18 00:32:12', '2025-04-18 00:42:10'),
(287, 'XR-012', 'completed', '', 'Emergency', 5, 1, '2025-04-18 00:42:12', '2025-04-18 00:42:41'),
(288, 'XR-013', 'completed', '', 'Normal', 5, 1, '2025-04-18 00:51:40', '2025-04-18 01:00:33'),
(289, 'XR-014', 'completed', '', 'Normal', 5, 1, '2025-04-18 00:49:27', '2025-04-18 00:51:42'),
(290, 'XR-015', 'completed', '', 'PWD', 5, 0, '2025-04-18 00:50:55', '2025-04-18 00:51:17'),
(291, 'XR-016', 'completed', '', 'Emergency', 5, 1, '2025-04-18 00:49:46', '2025-04-18 00:50:43'),
(292, 'XR-017', 'completed', '', 'Emergency', 5, 0, '2025-04-18 01:02:51', '2025-04-18 01:33:35'),
(293, 'REH-018', 'completed', 'Physical Therapy', 'PWD', 6, 0, '2025-04-18 01:32:20', '2025-04-18 01:55:36'),
(294, 'XR-019', 'completed', '', 'Emergency', 5, 1, '2025-04-18 01:32:29', '2025-04-18 01:34:47'),
(295, 'BIL-020', 'completed', 'Billing Inquiry', 'Emergency', 1, 4, '2025-04-18 01:32:48', '2025-04-18 01:47:48'),
(296, 'XR-021', 'completed', '', 'Normal', 5, 0, '2025-04-18 01:32:56', '2025-04-18 01:34:51'),
(297, 'PHA-022', 'pending', 'Medication Pickup', 'PWD', 2, 1, '2025-04-18 01:33:03', '2025-04-18 01:51:47'),
(298, 'XR-023', 'completed', '', 'Normal', 5, 0, '2025-04-18 01:33:10', '2025-04-18 01:34:53'),
(299, 'XR-024', 'completed', '', 'Normal', 5, 0, '2025-04-18 01:33:16', '2025-04-18 01:37:41'),
(300, 'XR-025', 'completed', '', 'Normal', 5, 0, '2025-04-18 01:37:58', '2025-04-18 01:42:36'),
(301, 'XR-026', 'completed', '', 'Emergency', 5, 0, '2025-04-18 01:38:05', '2025-04-18 01:40:33'),
(302, 'XR-027', 'completed', '', 'Emergency', 5, 0, '2025-04-18 01:38:12', '2025-04-18 01:40:34'),
(303, 'XR-028', 'completed', '', 'Emergency', 5, 0, '2025-04-18 01:38:18', '2025-04-18 01:40:35'),
(304, 'XR-029', 'pending', '', 'Emergency', 5, 0, '2025-04-18 01:45:27', '2025-04-18 01:45:42'),
(305, 'XR-030', 'waiting', '', 'Normal', 5, 0, '2025-04-18 02:07:32', '2025-04-18 02:07:32'),
(306, 'XR-031', 'in-progress', '', 'Normal', 5, 1, '2025-04-18 01:43:01', '2025-04-18 01:54:19'),
(307, 'XR-032', 'waiting', '', 'Normal', 5, 0, '2025-04-18 01:43:07', '2025-04-18 01:43:07'),
(308, 'MED-033', 'completed', '', 'Normal', 3, 1, '2025-04-18 01:53:22', '2025-04-18 01:53:44'),
(309, 'ULT-034', 'pending', '', 'Normal', 4, 1, '2025-04-18 01:54:31', '2025-04-18 01:54:55'),
(310, 'REH-035', 'pending', 'Physical Therapy', 'Emergency', 6, 1, '2025-04-18 01:55:14', '2025-04-18 01:55:27'),
(311, 'DIA-036', 'completed', '', 'Normal', 7, 1, '2025-04-18 01:56:01', '2025-04-18 01:56:27'),
(312, 'LAB-037', 'pending', 'Blood Test', 'PWD', 8, 0, '2025-04-18 01:57:18', '2025-04-18 01:57:22'),
(313, 'ER-038', 'completed', '', 'Normal', 13, 1, '2025-04-18 02:04:53', '2025-04-18 02:05:14'),
(314, 'SW-039', 'pending', '', 'Emergency', 14, 3, '2025-04-18 02:05:33', '2025-04-18 02:06:02');

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
(11, 'Information Request', 11),
(12, ' Release of Information (ROI)', 3),
(13, 'Triage', 13),
(14, 'Discharge Planning Assistance', 14);

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
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `queues`
--
ALTER TABLE `queues`
  MODIFY `qid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=315;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
