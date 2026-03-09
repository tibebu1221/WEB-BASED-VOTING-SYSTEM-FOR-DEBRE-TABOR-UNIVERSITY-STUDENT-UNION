-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 20, 2026 at 07:39 AM
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
-- Database: `onlinevote`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `activity_id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `activity_details` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidate`
--

CREATE TABLE `candidate` (
  `c_id` int(11) NOT NULL,
  `u_id` varchar(50) DEFAULT NULL,
  `fname` varchar(20) NOT NULL,
  `mname` varchar(20) NOT NULL,
  `lname` varchar(20) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `year` int(1) DEFAULT NULL,
  `sex` varchar(10) NOT NULL,
  `age` int(11) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(20) NOT NULL,
  `experience` varchar(25) NOT NULL,
  `candidate_photo` varchar(40) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `cgpa` decimal(3,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `candidate`
--

INSERT INTO `candidate` (`c_id`, `u_id`, `fname`, `mname`, `lname`, `student_id`, `year`, `sex`, `age`, `department`, `phone`, `email`, `experience`, `candidate_photo`, `username`, `password`, `cgpa`, `is_active`, `status`) VALUES
(10, '456', 'etabeba', 'sisay', 'manaye', NULL, NULL, 'fmale', 24, NULL, '0922740114', 'etabeba@gmail.com', '2 year', '1e.jpg', 'etabeba', 'd9f6e636e369552839e7bb8057aeb8da', 2.80, 1, 1),
(980, 'dtu14r1136', 'dani', 'tibebu', 'yayeh', NULL, NULL, 'male', 25, NULL, '0990909098', 'dessiet@gmail.com', '0', 'Uploads/candidates/candidate_0980.jpg', 'dani', '$2y$10$p/U1B7i559kiZ9QGds5aYuW.yISJ59Pn2BJ.FNq/yqSZBEE3RbISK', 0.00, 1, 1),
(1221, 'dtu14r1136', 'dessieti', 'tibebu', 'yayeh', NULL, NULL, 'male', 34, NULL, '0946084668', 'kirubeltsegaye80@gma', '2', '1.jpg', 'tibebud', '$2y$10$otAXNmxxpx4yLyhpFRUlv.GAMtqkTsiL8Y5VwzE/9kGpXoyQuXTpq', 0.00, 1, 1),
(1224, 'dtu14r1136', 'dessieta', 'tibebu', 'yayeh', NULL, NULL, 'male', 35, NULL, '0975524798', 'kirubeltsegaye80@gma', '2', '4.jpg', 'dtu', '$2y$10$z6XaeZdu3MGWygog36c7UuMYJgMRxtArwHSDW6gklZk4mIIF088km', 0.00, 1, 1),
(11123, 'dtu14r1136', 'dessie', 'tibebu', 'yayeh', '4214', 2, 'male', 25, 'IT', '0975524797', 'desalegn@gmail.com', '0', 'Uploads/candidates/candidate_11123.jpg', 'des', '$2y$10$dgLzTWHVJ3qn3aqpJ0KRs.Pmh.kfFaTpaPKvVKJp7aKvBQZ.OIDIG', 3.00, 1, 1),
(12231, 'dtu14r1136', 'dessie', 'tibebu', 'yayeh', '1212', 2, 'male', 25, 'CS', '0937884157', 'dessiet70@gmail.com', '0', 'Uploads/candidates/candidate_12231.jpg', 'ddd', '$2y$10$T0Vg4iSoqErR0aU2PBu5yejNRW0ziUGO53h26MrQnERvoujWzFBI2', 3.00, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `candidate_reg_date`
--

CREATE TABLE `candidate_reg_date` (
  `start` date NOT NULL,
  `end` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `candidate_reg_date`
--

INSERT INTO `candidate_reg_date` (`start`, `end`) VALUES
('2026-01-18', '2026-01-19');

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `c_id` int(22) NOT NULL,
  `u_id` varchar(200) NOT NULL,
  `name` varchar(60) NOT NULL,
  `email` varchar(20) NOT NULL,
  `content` varchar(500) NOT NULL,
  `date` varchar(20) NOT NULL,
  `status` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `comment`
--

INSERT INTO `comment` (`c_id`, `u_id`, `name`, `email`, `content`, `date`, `status`) VALUES
(12, '111', 'meseret', 'mesi@gmail.com', 'thanks for the service', '10/06/2017', 'read'),
(13, '111', 'etabeba sisay', 'etab@gmail.com', 'nice election date for all', '19/05/2017', 'read'),
(19, '111', 'bell g', 'bell@gmail.com', 'hello ', '02/06/2017', 'read'),
(25, '456', 'etabeba sisay', 'etabeba@gmail.com', 'my name is desalegn tibebu yayeh   this website is develops ', '05/09/2025', 'read'),
(26, '111', 'meseret sisay', 'mesi@gmail.com', 'thanks my voter ', '06/09/2025', 'read'),
(27, '456', 'etabeba sisay', 'etabeba@gmail.com', 'good ', '08/09/2025', 'read'),
(28, '456', 'etabeba sisay', 'etabeba@gmail.com', 'thanks god', '13/09/2025', 'read'),
(29, '111', 'sada tibebu', 'tibebud18@gmail.com', 'king of king sada candidate', '14/09/2025', 'read'),
(30, 'dtu14r1136', 'de sa a', 'dtibebu551@gmail.com', 'dddddddddddddd', '12/01/2026', 'read'),
(31, '1001', 'de d d', 'dtibebu551@gmail.com', 'fffffffffffff', '12/01/2026', 'read'),
(32, '1001', 'mmm nnn', 'dtibebu551@gmail.com', 'dmq.,m,effqf', '15/01/2026', 'read'),
(33, '1001', 'kl;l,m,.meg', 'tibebud18@gmail.com', 'gergrvtgbgtg', '15/01/2026', 'read'),
(34, 'dtu14r1136', 'dddas', 'dessiet70@gmail.com', 'dddddddddd', '18/01/2026', 'read');

-- --------------------------------------------------------

--
-- Table structure for table `department_nominees`
--

CREATE TABLE `department_nominees` (
  `id` int(11) NOT NULL,
  `candidate_id` varchar(50) NOT NULL,
  `candidate_name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `nominated_by` varchar(100) DEFAULT NULL,
  `nominated_at` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_students`
--

CREATE TABLE `department_students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `department` varchar(100) NOT NULL,
  `section` varchar(10) NOT NULL,
  `cgpa` decimal(3,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_nominated` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(100) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `department_students`
--

INSERT INTO `department_students` (`id`, `student_id`, `student_name`, `email`, `department`, `section`, `cgpa`, `is_active`, `is_nominated`, `created_at`, `created_by`, `updated_at`) VALUES
(1, '12231', 'dsaa', 'tibebud18@gmail.com', 'Information Technology', 'B', 2.97, 1, 0, '2026-01-14 06:56:43', '4444 - Department Officer', '2026-01-14 06:56:43');

-- --------------------------------------------------------

--
-- Table structure for table `discipline_records`
--

CREATE TABLE `discipline_records` (
  `recordID` int(11) NOT NULL,
  `candidateID` varchar(20) DEFAULT NULL,
  `record_details` text NOT NULL,
  `status` varchar(20) NOT NULL,
  `checked_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discipline_records`
--

INSERT INTO `discipline_records` (`recordID`, `candidateID`, `record_details`, `status`, `checked_at`) VALUES
(10002, '101', 'no', 'disciplinary_action', '2025-11-22 15:56:01'),
(10011, '7070', 'mnmn', 'disciplinary_action', '2025-12-11 00:00:00'),
(10012, '2121', 'www', 'disciplinary_action', '2025-12-11 00:00:00'),
(10013, '3030', 'dsa', 'disciplinary_action', '2025-12-11 00:00:00'),
(10015, '234', 'fef', 'disciplinary_action', '2025-12-11 00:00:00'),
(10016, '101', 'clear', 'clear', '2025-12-11 22:52:55'),
(10018, '1022', 'eee', 'disciplinary_action', '2025-12-11 23:15:16'),
(10019, '777', 'mmmmm', 'disciplinary_action', '2025-12-12 00:00:00'),
(10020, 'dtu14r1221', 'clear', 'clear', '2025-12-12 20:30:51'),
(10021, '5555', 'clear', 'disciplinary_action', '2025-12-12 20:39:17'),
(10022, '645', 'dfsafd', 'disciplinary_action', '2025-12-12 00:00:00'),
(10023, '3455', 'fgdfgdf', 'disciplinary_action', '2025-12-12 00:00:00'),
(10024, '6565', 'dsadsgd', 'disciplinary_action', '2025-12-12 00:00:00'),
(10025, '1', 'dfgdfzgdf', 'disciplinary_action', '2025-12-12 00:00:00'),
(10028, '1', 'as\\sdcxv\\d', 'disciplinary_action', '2025-12-12 00:00:00'),
(10029, '4444', 'wdsc', 'clear', '2025-12-13 11:08:12'),
(10030, 'dtu15r1135', 'no', 'clear', '2026-01-10 20:25:25'),
(10031, 'kl-0-0', 'no', 'clear', '2026-01-10 20:57:48'),
(10032, '4423', 'n', 'disciplinary_action', '2026-01-10 20:59:06');

-- --------------------------------------------------------

--
-- Table structure for table `election_date`
--

CREATE TABLE `election_date` (
  `date` date NOT NULL,
  `u_id` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `election_date`
--

INSERT INTO `election_date` (`date`, `u_id`) VALUES
('2017-06-09', '111'),
('2025-12-14', '111'),
('2026-01-19', '1001');

-- --------------------------------------------------------

--
-- Table structure for table `election_requests`
--

CREATE TABLE `election_requests` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `department_code` varchar(10) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `election_requests`
--

INSERT INTO `election_requests` (`id`, `student_id`, `student_name`, `department_code`, `cgpa`, `requested_by`, `status`, `created_at`) VALUES
(1, 'STU-2026-045', 'Marcus Wright', 'EE', 3.25, 4444, 'pending', '2026-01-13 07:10:48'),
(2, 'STU-2026-078', 'David Kim', 'ME', 3.80, 4444, 'pending', '2026-01-13 07:10:48');

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `p_id` int(21) NOT NULL,
  `title` varchar(30) NOT NULL,
  `content` varchar(10000) NOT NULL,
  `posted_by` varchar(30) NOT NULL,
  `date` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`p_id`, `title`, `content`, `posted_by`, `date`) VALUES
(11, 'voting system', 'please select my party ', 'etabeba', '11/09/2025'),
(13, 'welcome to voting system', 'good chance ', 'etabeba', '13/09/2025'),
(16, 'vdv', 'cxvxc v', 'System Admin', '2025-12-11 22:17:12'),
(17, 'cvdc', 'dfasdv', 'System Admin', '2025-12-11 22:17:32'),
(18, 'welcome to voting system', 'ddddddddddd', 'ds', '12/01/2026'),
(19, 'welcome to voting system', 'please voting you', 'kiru', '12/01/2026'),
(20, 'vote', 'ppfkffffffffffffffffjmf\r\nfdfffdddddddddddd', 'System Admin', '2026-01-13 16:16:47');

-- --------------------------------------------------------

--
-- Table structure for table `e_requests`
--

CREATE TABLE `e_requests` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `cgpa` decimal(3,2) NOT NULL,
  `submitted_by` varchar(100) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `processed_at` datetime DEFAULT NULL,
  `processed_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `e_requests`
--

INSERT INTO `e_requests` (`id`, `student_id`, `student_name`, `department`, `cgpa`, `submitted_by`, `submitted_at`, `status`, `processed_at`, `processed_by`, `notes`) VALUES
(1, 'STU-2026-001', 'Alice Johnson', 'CS', 3.75, '4444 - Department Officer', '2026-01-13 20:30:00', 'approved', '2026-01-13 23:47:56', 'dtu14r1136', 'daw'),
(2, 'STU-2026-045', 'Marcus Wright', 'EE', 3.25, '4444 - Department Officer', '2026-01-13 20:30:00', 'approved', '2026-01-13 23:49:54', 'dtu14r1136', ''),
(3, 'STU-2026-078', 'David Kim', 'ME', 3.80, '4444 - Department Officer', '2026-01-13 20:30:00', 'approved', '2026-01-13 23:49:09', 'dtu14r1136', ''),
(4, 'STU-2026-099', 'Emma Wilson', 'BA', 2.90, '4444 - Department Officer', '2026-01-13 20:30:00', 'rejected', '2026-01-13 23:41:15', 'dtu14r1136', 'des'),
(5, 'STU-2026-123', 'James Miller', 'CE', 3.10, '4444 - Department Officer', '2026-01-13 20:30:00', 'approved', '2026-01-13 23:59:13', 'dtu14r1136', ''),
(6, 'dtu14r1135', 'dealegn tibebu', 'CS', 3.00, '4444 - Department Officer', '2026-01-13 21:04:27', 'approved', '2026-01-14 08:02:42', 'dtu14r1136', 'good'),
(7, 'STU-2026-045', 'Marcus Wright', 'EE', 3.25, '4444 - Department Officer', '2026-01-14 05:22:01', 'rejected', '2026-01-14 16:36:27', 'dtu14r1136', ''),
(8, 'STU-2026-078', 'David Kim', 'ME', 3.80, '4444 - Department Officer', '2026-01-14 05:22:01', 'pending', NULL, NULL, NULL),
(9, 'STU-2026-099', 'Emma Wilson', 'BA', 2.90, '4444 - Department Officer', '2026-01-14 05:22:01', 'approved', '2026-01-14 11:17:39', 'dtu14r1136', ''),
(10, 'STU-2026-123', 'James Miller', 'CE', 3.10, '4444 - Department Officer', '2026-01-14 05:22:01', 'rejected', '2026-01-19 09:17:35', 'dtu14r1136', ''),
(11, '122123', 'desa', 'BA', 3.00, '4444 - Department Officer', '2026-01-14 05:29:54', 'rejected', '2026-01-14 16:38:20', 'dtu14r1136', ''),
(12, 'dtu1221', 'desa', 'BA', 3.00, '4444 - Department Officer', '2026-01-14 05:36:33', 'rejected', '2026-01-14 09:00:51', 'dtu14r1136', ''),
(13, '1221', 'DDSSA', 'BA', 3.00, '4444 - Department Officer', '2026-01-14 05:43:44', 'approved', '2026-01-14 08:54:50', 'dtu14r1136', ''),
(14, 'ad12', 'dsaaaaaaaaaaaaa', 'IT', 3.00, '4444 - Department Officer', '2026-01-14 05:59:23', 'approved', '2026-01-14 09:00:39', 'dtu14r1136', ''),
(15, '2112', '\\cx', 'CE', 3.00, '4444 - Department Officer', '2026-01-14 07:53:54', 'approved', '2026-01-14 15:40:26', 'dtu14r1136', ''),
(16, '3232', 'yop', 'IT', 2.98, '4444 - Department Officer', '2026-01-14 08:19:21', 'approved', '2026-01-14 11:20:06', 'dtu14r1136', ''),
(17, '0909', 'dani', 'CS', 3.99, '4444 - Department Officer', '2026-01-14 08:25:35', 'approved', '2026-01-14 11:26:27', 'dtu14r1136', ''),
(18, 'wick', 'jo', 'ME', 2.82, '4444 - Department Officer', '2026-01-14 12:21:18', 'approved', '2026-01-14 15:22:26', 'dtu14r1136', ''),
(19, '5656', 'dave', 'BA', 3.40, '4444 - Department Officer', '2026-01-14 13:01:47', 'approved', '2026-01-14 16:02:26', 'dtu14r1136', ''),
(20, '4554', 'mom', 'CS', 2.97, '4444 - Department Officer', '2026-01-15 07:49:46', 'approved', '2026-01-15 10:51:01', 'dtu14r1136', ''),
(21, '12231', 'sad man', 'Information Technology', 2.97, '4444 - Department Officer', '2026-01-15 12:57:16', 'approved', '2026-01-15 15:58:07', '1010', ''),
(22, '4312', 'habte', 'CS', 3.00, '4444 - Department Officer', '2026-01-15 17:13:52', 'approved', '2026-01-15 20:18:34', 'dtu14r1136', ''),
(23, '0980', 'dani', 'IT', 3.00, '4444 - Department Officer', '2026-01-15 17:37:05', 'approved', '2026-01-15 20:38:12', 'dtu14r1136', ''),
(24, '12231', 'dsaa', 'Information Technology', 2.97, '4444 - Department Officer', '2026-01-16 20:38:04', 'approved', '2026-01-18 23:37:38', 'dtu14r1136', ''),
(25, '11123', 'desalegn', 'IT', 3.00, '4444 - Department Officer', '2026-01-18 20:36:20', 'approved', '2026-01-18 23:37:27', 'dtu14r1136', '');

-- --------------------------------------------------------

--
-- Table structure for table `nomination_logs`
--

CREATE TABLE `nomination_logs` (
  `id` int(11) NOT NULL,
  `candidate_id` varchar(50) NOT NULL,
  `candidate_name` varchar(100) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `performed_by` varchar(100) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `department` varchar(100) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nomination_logs`
--

INSERT INTO `nomination_logs` (`id`, `candidate_id`, `candidate_name`, `action`, `performed_by`, `timestamp`, `department`, `cgpa`, `status`) VALUES
(7, 'STU-2026-099', 'Emma Wilson', 'officer_rejected', 'dtu14r1136', '2026-01-13 23:41:15', 'BA', 2.90, 'rejected'),
(8, 'STU-2026-001', 'Alice Johnson', 'officer_approved', 'dtu14r1136', '2026-01-13 23:47:56', 'CS', 3.75, 'approved'),
(9, 'STU-2026-078', 'David Kim', 'officer_approved', 'dtu14r1136', '2026-01-13 23:49:09', 'ME', 3.80, 'approved'),
(10, 'STU-2026-045', 'Marcus Wright', 'officer_approved', 'dtu14r1136', '2026-01-13 23:49:54', 'EE', 3.25, 'approved'),
(12, 'dtu14r1135', 'dealegn tibebu', 'department_submission', '4444', '2026-01-14 00:04:27', 'CS', 3.00, 'pending'),
(13, 'dtu14r1135', 'dealegn tibebu', 'officer_approved', 'dtu14r1136', '2026-01-14 08:02:42', 'CS', 3.00, 'approved'),
(18, '122123', 'desa', 'department_submission', '4444', '2026-01-14 08:29:54', 'BA', 3.00, 'pending'),
(19, 'dtu1221', 'desa', 'department_submission', '4444', '2026-01-14 08:36:33', 'BA', 3.00, 'pending'),
(22, 'ad12', 'dsaaaaaaaaaaaaa', 'department_submission', '4444', '2026-01-14 08:59:23', 'IT', 3.00, 'pending'),
(23, 'ad12', 'dsaaaaaaaaaaaaa', 'officer_approved', 'dtu14r1136', '2026-01-14 09:00:39', 'IT', 3.00, 'approved'),
(24, 'dtu1221', 'desa', 'officer_rejected', 'dtu14r1136', '2026-01-14 09:00:51', 'BA', 3.00, 'rejected'),
(27, '3232', 'yop', 'department_submission', '4444', '2026-01-14 11:19:21', 'IT', 2.98, 'pending'),
(28, '3232', 'yop', 'officer_approved', 'dtu14r1136', '2026-01-14 11:20:06', 'IT', 2.98, 'approved'),
(29, '0909', 'dani', 'department_submission', '4444', '2026-01-14 11:25:35', 'CS', 3.99, 'pending'),
(30, '0909', 'dani', 'officer_approved', 'dtu14r1136', '2026-01-14 11:26:27', 'CS', 3.99, 'approved'),
(32, 'wick', 'jo', 'officer_approved', 'dtu14r1136', '2026-01-14 15:22:26', 'ME', 2.82, 'approved'),
(33, '2112', '\\cx', 'officer_approved', 'dtu14r1136', '2026-01-14 15:40:26', 'CE', 3.00, 'approved'),
(34, '122123', '', 'officer_submitted', 'dtu14r1136', '2026-01-14 15:47:46', '', 0.00, 'pending'),
(35, '5656', 'dave', 'department_submission', '4444', '2026-01-14 16:01:47', 'BA', 3.40, 'pending'),
(36, '5656', 'dave', 'officer_approved', 'dtu14r1136', '2026-01-14 16:02:26', 'BA', 3.40, 'approved'),
(37, 'STU-2026-045', 'Marcus Wright', 'officer_rejected', 'dtu14r1136', '2026-01-14 16:36:27', 'EE', 3.25, 'rejected'),
(38, '122123', 'desa', 'officer_rejected', 'dtu14r1136', '2026-01-14 16:38:20', 'BA', 3.00, 'rejected'),
(39, '4554', 'mom', 'department_submission', '4444', '2026-01-15 10:49:46', 'CS', 2.97, 'pending'),
(40, '4554', 'mom', 'officer_approved', 'dtu14r1136', '2026-01-15 10:51:01', 'CS', 2.97, 'approved'),
(41, '12231', 'sad man', 'department_submission', '4444', '2026-01-15 15:57:16', 'Information Technology', 2.97, 'pending'),
(42, '12231', 'sad man', 'officer_approved', '1010', '2026-01-15 15:58:07', 'Information Technology', 2.97, 'approved'),
(43, '4312', 'habte', 'department_submission', '4444', '2026-01-15 20:13:52', 'CS', 3.00, 'pending'),
(44, '4312', 'habte', 'officer_approved', 'dtu14r1136', '2026-01-15 20:18:34', 'CS', 3.00, 'approved'),
(45, '0980', 'dani', 'department_submission', '4444', '2026-01-15 20:37:05', 'IT', 3.00, 'pending'),
(46, '0980', 'dani', 'officer_approved', 'dtu14r1136', '2026-01-15 20:38:12', 'IT', 3.00, 'approved'),
(47, '12231', 'dsaa', 'department_submission', '4444', '2026-01-16 23:38:04', 'Information Technology', 2.97, 'pending'),
(48, '11123', 'desalegn', 'department_submission', '4444', '2026-01-18 23:36:20', 'IT', 3.00, 'pending'),
(49, '11123', 'desalegn', 'officer_approved', 'dtu14r1136', '2026-01-18 23:37:27', 'IT', 3.00, 'approved'),
(50, '12231', 'dsaa', 'officer_approved', 'dtu14r1136', '2026-01-18 23:37:38', 'Information Technology', 2.97, 'approved'),
(51, 'STU-2026-123', 'James Miller', 'officer_rejected', 'dtu14r1136', '2026-01-19 09:17:35', 'CE', 3.10, 'rejected');

-- --------------------------------------------------------

--
-- Table structure for table `nominees`
--

CREATE TABLE `nominees` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `student_email` varchar(255) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `nominated_by` varchar(255) DEFAULT NULL,
  `nomination_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending',
  `officer_id` varchar(50) DEFAULT NULL,
  `sent_to_department` tinyint(1) DEFAULT 0,
  `department_sent_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request`
--

CREATE TABLE `request` (
  `requestID` int(11) NOT NULL,
  `candidateID` varchar(30) DEFAULT NULL,
  `officeID` varchar(30) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `discipline_status` varchar(20) DEFAULT 'pending',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `student_name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request`
--

INSERT INTO `request` (`requestID`, `candidateID`, `officeID`, `submitted_at`, `discipline_status`, `reviewed_at`, `review_notes`, `status`, `student_name`, `department`, `cgpa`) VALUES
(1, '1221', 'dtu14r1136', '2025-10-08 14:41:19', 'disciplinary_action', '2026-01-12 07:44:41', 'hggggkjkj', 'pending', NULL, NULL, NULL),
(15, '999', '888', '2025-12-12 20:40:05', 'clear', '2026-01-10 19:36:40', 'ssss', 'pending', NULL, NULL, NULL),
(16, '10', 'fgfgf', '2025-12-12 23:13:17', 'clear', '2026-01-10 19:35:19', 'aaaaaaaaaaaaaaaaaaa', 'pending', NULL, NULL, NULL),
(17, '3493', '0909', '2025-12-12 23:19:01', 'clear', '2026-01-10 19:37:17', 'fffffffffffff', 'pending', NULL, NULL, NULL),
(18, '76545', '412423', '2025-12-12 22:28:30', 'clear', '2026-01-10 18:54:35', 'hh', 'pending', NULL, NULL, NULL),
(19, '9990', '5555', '2025-12-13 13:39:31', 'clear', '2026-01-10 19:33:26', 'gdssksksk', 'pending', NULL, NULL, NULL),
(20, '8989', '9899', '2025-12-13 13:48:14', 'disciplinary_action', '2025-12-13 20:25:25', 'klejhk', 'pending', NULL, NULL, NULL),
(21, '09912', '080706', '2025-12-13 18:52:40', 'clear', '2026-01-10 18:39:45', 'k', 'pending', NULL, NULL, NULL),
(23, 'dtu15r1135', '1221', '2026-01-10 18:09:13', 'disciplinary_action', '2026-01-11 05:42:43', 'kal', 'pending', NULL, NULL, NULL),
(24, '1111w', '1221', '2026-01-11 07:15:29', 'clear', '2026-01-11 06:18:18', 'lk', 'pending', NULL, NULL, NULL),
(25, 'd22222222', '1221', '2026-01-11 08:33:07', 'clear', '2026-01-11 07:42:27', 'gggggggggg', 'pending', NULL, NULL, NULL),
(30, '53331', '543', '2026-01-11 12:27:00', 'disciplinary_action', '2026-01-12 07:44:08', 'hjm', 'pending', NULL, NULL, NULL),
(31, '53331', '543', '2026-01-11 12:27:10', 'clear', '2026-01-12 11:11:03', 'fff', 'pending', NULL, NULL, NULL),
(32, '53331', '543', '2026-01-11 12:27:36', 'clear', '2026-01-11 18:15:36', 'jjjjjjjjjjjjjjjjjjjjjjjj', 'pending', NULL, NULL, NULL),
(33, '101', '1221', '2026-01-11 14:20:13', 'approved', NULL, NULL, 'pending', NULL, NULL, NULL),
(56, 'dtu14r1135', 'dtu14r1136', '2026-01-14 08:02:42', 'clear', '2026-01-18 20:39:49', 'c', 'pending', NULL, NULL, NULL),
(57, '1221', 'dtu14r1136', '2026-01-14 08:54:50', 'clear', '2026-01-18 20:39:35', 'c', 'pending', NULL, NULL, NULL),
(58, 'ad12', 'dtu14r1136', '2026-01-14 09:00:39', 'clear', '2026-01-15 17:02:49', 'no', 'pending', NULL, NULL, NULL),
(59, 'STU-2026-099', 'dtu14r1136', '2026-01-14 11:17:39', 'clear', '2026-01-15 17:02:40', 'no', 'pending', NULL, NULL, NULL),
(60, '3232', 'dtu14r1136', '2026-01-14 11:20:06', 'clear', '2026-01-15 17:02:29', 'no', 'pending', NULL, NULL, NULL),
(61, '0909', 'dtu14r1136', '2026-01-14 11:26:27', 'clear', '2026-01-14 08:30:04', 'no', 'pending', NULL, NULL, NULL),
(62, 'wick', 'dtu14r1136', '2026-01-14 15:22:26', 'clear', '2026-01-14 12:24:13', 'no', 'pending', NULL, NULL, NULL),
(63, '2112', 'dtu14r1136', '2026-01-14 15:40:26', 'disciplinary_action', '2026-01-14 13:03:38', 'dsad', 'pending', NULL, NULL, NULL),
(64, '122123', '212121', '2026-01-14 13:47:46', 'clear', '2026-01-14 12:49:22', 'no', 'pending', NULL, NULL, NULL),
(65, '5656', 'dtu14r1136', '2026-01-14 16:02:26', 'clear', '2026-01-14 13:03:18', 'fffs', 'pending', NULL, NULL, NULL),
(66, '4554', 'dtu14r1136', '2026-01-15 10:51:01', 'clear', '2026-01-15 07:53:57', 'sa', 'pending', NULL, NULL, NULL),
(67, '12231', '1010', '2026-01-15 15:58:07', 'clear', '2026-01-15 12:58:45', 'no', 'pending', NULL, NULL, NULL),
(68, '4312', 'dtu14r1136', '2026-01-15 20:18:34', 'clear', '2026-01-15 17:20:03', 'no', 'pending', NULL, NULL, NULL),
(69, '0980', 'dtu14r1136', '2026-01-15 20:38:12', 'clear', '2026-01-15 17:39:56', 'no', 'pending', NULL, NULL, NULL),
(70, '11123', 'dtu14r1136', '2026-01-18 23:37:27', 'clear', '2026-01-18 20:39:00', 'no', 'pending', NULL, NULL, NULL),
(71, '12231', 'dtu14r1136', '2026-01-18 23:37:38', 'clear', '2026-01-18 20:39:28', 'c', 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `result`
--

CREATE TABLE `result` (
  `vid` varchar(20) NOT NULL,
  `u_id` varchar(22) NOT NULL,
  `fname` varchar(20) NOT NULL,
  `mname` varchar(20) NOT NULL,
  `year` varchar(50) NOT NULL,
  `choice` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `result`
--

INSERT INTO `result` (`vid`, `u_id`, `fname`, `mname`, `year`, `choice`) VALUES
('12', '12', 'aa', 'bb', 'DT', '980'),
('12211', '12211', 'dessie', 'tibebu', 'Second Year', '980'),
('12212', '12212', 'habte', 'tibebu', 'Second Year', '980'),
('12213', '12213', 'gildo', 'd', 'Second Year', '980'),
('2232', '2232', 'gildo', 'tibebu', 'Second Year', '980'),
('23', '23', 'cc', 'bb', '', '1234'),
('2312', '2312', 'desu', 'tib', 'Second Year', '980'),
('9986', '9986', 'dessie', 'tibebu', 'Fourth Year', '980');

-- --------------------------------------------------------

--
-- Table structure for table `send_request`
--

CREATE TABLE `send_request` (
  `request_id` int(20) NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `request_type` varchar(100) NOT NULL,
  `target_id` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `date_sent` datetime NOT NULL,
  `status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `send_request`
--

INSERT INTO `send_request` (`request_id`, `sender_name`, `request_type`, `target_id`, `details`, `date_sent`, `status`) VALUES
(0, 'Discipline Committee', 'Deactivate Voter', '333', 'fdv', '0000-00-00 00:00:00', '');

-- --------------------------------------------------------

--
-- Table structure for table `student_discipline_records`
--

CREATE TABLE `student_discipline_records` (
  `record_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `incident_date` date NOT NULL,
  `incident_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('low','medium','high') NOT NULL,
  `status` enum('pending','resolved','warning_issued','suspended','expelled') DEFAULT 'pending',
  `action_taken` text DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_discipline_records`
--

INSERT INTO `student_discipline_records` (`record_id`, `student_id`, `student_name`, `incident_date`, `incident_type`, `description`, `severity`, `status`, `action_taken`, `resolved_date`, `created_at`, `updated_at`) VALUES
(1, 'a111', 'desa', '2026-01-11', 'tef', 'ddddfkkfkf', 'low', 'warning_issued', 'ff', '2026-01-11', '2026-01-11 08:06:22', '2026-01-11 08:06:22');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `fname` varchar(25) NOT NULL,
  `mname` varchar(25) NOT NULL,
  `lname` varchar(25) NOT NULL,
  `u_id` varchar(15) NOT NULL,
  `sex` varchar(10) NOT NULL,
  `age` varchar(25) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(30) NOT NULL,
  `role` enum('admin','officer','department','discipline_committee','candidate') NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` int(1) NOT NULL,
  `station` varchar(25) NOT NULL,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`fname`, `mname`, `lname`, `u_id`, `sex`, `age`, `phone`, `email`, `role`, `username`, `password`, `status`, `station`, `login_attempts`, `last_attempt`) VALUES
('group', 'three', 'members', '1001', 'male', '25', '0920202020', 'group3@gmail.com', 'admin', 'admin', '$2y$10$sX1FEYn.UFEEshkkeDoilu8q05y4EQt.i0eY6aMBOGcuVZ5rAljoK', 1, 'N/A', 0, NULL),
('group', 'three', 'members', '1010', 'female', '24', '0930303030', 'group3m@gmail.com', 'officer', 'office', '$2y$10$AT0A33MwyEtXw/Qw8mYi4u4/cKD8cnCg8any0FovdXfjaEctRHio2', 1, 'N/A', 0, NULL),
('GROUP', 'THREE', 'MEMBERS', '1011', 'male', '22', '0940404040', 'GROUP3@gmail.com', 'discipline_committee', 'committee', '$2y$10$a5BHRKSNqgSguyA/nz3gN.2sUOJZdq3plaSIS0N.Db8VTaX0pyQum', 1, 'N/A', 0, NULL),
('meseret', 'afework', 'goshu', '111', 'female', '21', '0936680161', 'mesi@gmail.com', 'admin', 'mis', '$2y$10$6ORQeb/fRrmAZlhEO6oRzO4piPXcdRozE1qZTs7ck//qa3i6CQIu6', 1, 'betel', 0, NULL),
('Group', 'Three', 'Members', '1112', 'female', '26', '0950505050', 'Goup3@gmail.com', 'department', 'rep', '$2y$10$8CPeMZ398O370ehWlNju1.BbHnxkWf73ko24skbwXN4VBMLvAlfxq', 1, 'N/A', 0, NULL),
('musba', 'wabela', 'ahmed', '139', 'male', '34', '0910101010', 'musba@gmail.com', 'officer', 'musba', '$2y$10$aJAnHoFi33z9mIsWZwiId.tHLtFwmmx.pN97auSzukkpN9n5mOC.q', 1, 'arada', 0, NULL),
('belay', 'gebre', 'abebayehu', '164', 'male', '23', '0913101010', 'bell@gmail.com', 'officer', 'bell', '$2y$10$dd/nwnVi43N8E7XJSjz.zedWIaErdKIx1SikJDx5iH2wRLbumM86C', 1, 'hossana', 0, NULL),
('yoni', 'arg', 'gr', '2121', 'male', '25', '0926247453', 'yoni@gmail.com', 'discipline_committee', 'yoni', '$2y$10$FHP9mMP1dRY6B5Ew909Mue42ZxZY0.yJIgcvnusWTdsr1bkbcmlW2', 1, 'N/A', 0, NULL),
('yoni', 'ar', 'vv', '4444', 'male', '54', '0990909090', 'mick@gmail.com', 'department', 'dep', '$2y$10$0zSztLC/LYVoKkF3Vtyt5.KQHzhFR5mmoe/jCfm9e90NNAmHjUwT6', 1, 'N/A', 0, NULL),
('melkamu', 'mengistu', 'kebede', '456', 'male', '36', '0914562389', 'melke@gmail.com', 'officer', 'melke', 'e10adc3949ba59abbe56e057f20f883e', 1, 'heto', 0, NULL),
('dessie', 'tibebu', 'yayeh', 'dtu14r1136', 'male', '24', '0946084669', 'dtibebu551@gmail.com', 'officer', 'tibebu', '$2y$10$gij/5BLDToOt83Qc/4YFwOc3wc5Am8Mqage7Ed4Y6AgEHvnVhnOki', 1, 'dtu', 0, NULL),
('Desalegn', 'yayeh yayeh de s', 'tibebu', 'r14dtu1135', 'F', '23', '0937884156', 'dessiet70@gmail.com', 'officer', 'desa', '$2y$10$AY1BM.4QnvrKpxISLqpVSe2KhvYq1Qy2tOwioHqD9.FAwUc2TH/4S', 1, 'control', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `voter`
--

CREATE TABLE `voter` (
  `vid` int(11) NOT NULL,
  `u_id` varchar(30) NOT NULL,
  `fname` varchar(25) NOT NULL,
  `mname` varchar(25) NOT NULL,
  `lname` varchar(25) NOT NULL,
  `age` int(10) NOT NULL,
  `sex` varchar(8) NOT NULL,
  `year` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `phone` varchar(12) NOT NULL,
  `email` varchar(25) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(25) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `voter`
--

INSERT INTO `voter` (`vid`, `u_id`, `fname`, `mname`, `lname`, `age`, `sex`, `year`, `department`, `phone`, `email`, `username`, `password`, `status`, `is_active`) VALUES
(12, '1010', 'aa', 'bb', 'cc', 22, 'male', 'DT', 'student', '0932445465', 'aa@gmail.com', 'aaa', '$2y$10$guzEY4srQR9U.FN3Cr1Q/ONT0pb7WVt9EA1nU.tQj.XzYpH33BDly', '1', 1),
(23, '1010', 'cc', 'bb', 'aa', 23, 'male', 'DT', 'student', '0990909087', 'abcd@gmail.com', 'abc', '$2y$10$ffNKKGcWvOtSEhDjyYIxPOOWJ4./ECj.xUDRkYKrVXDoRS/lGYKha', '1', 1),
(1010, '164', 'meseret', 'sisay', 'manaye', 23, 'female', '', 'gov\'t', '0936680161', 'mesi@gmail.com', 'khan', 'b4a0393fa3dcca2a1d94b8d8d483e3f0', '1', 1),
(2232, 'dtu14r1136', 'gildo', 'tibebu', 'yayeh', 22, 'male', 'Second Year', 'Information Technology', '0937884157', 'kirubeltsegaye80@gmail.co', 'gildo', '$2y$10$IUTchKPOKv3RUxFpNXvgSORMltfICDRyf101Ndbm3tcwito4ONB7q', '0', 1),
(2312, 'dtu14r1136', 'desu', 'tib', 'd', 23, 'male', 'Second Year', 'Software Engineering', '0990909060', 'tibebud18@gmail.com', 'desu', '$2y$10$58SfLGS8AtIN/dvwn3KHw.AQsS3A8Fhrr5WbwIk2IoBWYPCA0P4PC', '1', 1),
(3232, '164', 'abebe', 'zenebe', 'manaye', 22, 'male', '', 'gov\'t', '0922740103', 'etab@gmail.com', 'bb', '$2y$10$ZkF.ua.LXzuc0xTryuYvFeYaSMEzJb5BVe4GZ/gjcj2KxxibFJ2me', '1', 1),
(3333, 'dtu14r1136', 'dessie', 'd', 'yayeh', 43, 'male', 'DT', 'student', '0926247453', 'tibebud18@gmail.com', 'abcd', '$2y$10$pbBhpVo01/cJBHf.4w8/mu9Dq.1hFCN.7A1bNOi2L1S6sXKVpTInK', '1', 1),
(8888, 'dtu14r1136', 'ab', 'cd', 'ef', 24, 'male', 'DT', 'student', '0924400356', 'kb@gmail.com', 'abcde', '$2y$10$Kc7qo4RFOFD2V49pKpb2su/pQp/CMBiRqsN1xJgMXM3VCgGaz0jhe', '1', 1),
(9979, '139', 'desalegn', 'tibebu', 'yayeh', 24, 'male', '', 'student', '0937884156', 'dessiet70@gmail.com', 'desa', '$2y$10$OlROWQCryuSgjQ4YnbM4vOuN/U4vtlEhEtQkkX5sR0gi2kCxZ8Anm', '1', 1),
(9980, '139', 'desalegn', 'tibebu', 'yayeh', 24, 'male', '', 'student', '0946084669', 'dessiet70@gmail.com', 'dessa', '$2y$10$OMWfOLdbKuRSXk/AL3OIF.XQsknGiDcKC9or6U2TdPW0y6imMrS2O', '1', 1),
(9981, '139', 'dessiet', 'tibebu', 'yayeh', 24, 'male', '', 'student', '0975524798', 'dtibebu551@gmail.com', 'dessiet', '$2y$10$LS1alDacCg9CUG2a5qdxf.x4v88LtbUEaYkGfhnwz35TbU9/tsEf6', '1', 1),
(9982, 'dtu14r1136', 'sada', 'tibebu', 'hs', 35, 'male', 'DT', 'student', '0963146941', 'tibebud18@gmail.com', 'sada', '$2y$10$EpZObo5SMwaKM59ZzF.CRO.YDM2lFK80hAkFv61K3qVg/KUX2LaqO', '1', 1),
(9983, '139', 'de', 'd', 'd', 24, 'female', 'DT', 'student', '0987455657', 'dtibebu551@gmail.com', 'tt', '$2y$10$d6WH5cxD9.bNF5Zp/dRkF.oOwkMcyjWEhVh765V.IOfU75LhZ2OpG', '1', 1),
(9984, 'dtu14r1136', 'dessiets', 'sss', 'aaaaa', 25, 'female', 'DT', 'student', '0937884159', 'dessiet70@gmail.com', 'de', '$2y$10$selxcD1jiKRYnxSGFmdoI.7hMk4KY31QwzVZlrKJGsGOkdD9JfRPm', '1', 1),
(9985, 'dtu14r1136', 'dessiet', 'tibebu', 'yayeh', 25, 'male', 'DT', 'student', '0990909094', 'tibebud18@gmail.com', 'dess', '$2y$10$5YJgyQl9ejBQt1BNLXOKQutz4aXzkH55XL6gWrifcojaNTyf09wkS', '1', 1),
(9986, 'dtu14r1136', 'dessie', 'tibebu', 'yayeh', 25, 'male', 'Fourth Year', 'Information Technology', '0983405905', 'dessiet70@gmail.com', 'desalegn1', '$2y$10$IVBAsYXOsilR4N2BvQyP/u6gCtDU0nZtcwQMckuCDIncMtqyz3TNe', '1', 1),
(12211, 'dtu14r1136', 'dessie', 'tibebu', 'yayeh', 25, 'male', 'Second Year', 'Information Technology', '0937884150', 'dessiet70@gmail.com', 'vote', '$2y$10$ohuZmqFH2Vc9Rm5.cZwr5OVQtC3GGqfqA90ZNd.DpSlK8Hpjh9jQW', '1', 1),
(12212, 'dtu14r1136', 'habte', 'tibebu', 'yayeh', 23, 'male', 'Second Year', 'Information Technology', '0975524792', 'tibebud18@gmail.com', 'habte', '$2y$10$xttSBcAGukTKA.k7VhNvnOTbsZL0K0cvN0AmTS0ErSd2YQLskKbai', '1', 1),
(12213, 'dtu14r1136', 'gildo', 'd', 'bb', 25, 'male', 'Second Year', 'Information Technology', '0937884130', 'dessiet708@gmail.com', 'gildo1', '$2y$10$7MN5M79z0Hhin8cWLWiYTutNUOD1JgBSlBErkOq9eQxVMfkMnRDb6', '1', 1);

-- --------------------------------------------------------

--
-- Table structure for table `voter_reg_date`
--

CREATE TABLE `voter_reg_date` (
  `start` date NOT NULL,
  `end` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `voter_reg_date`
--

INSERT INTO `voter_reg_date` (`start`, `end`) VALUES
('2026-01-18', '2026-01-19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `candidate`
--
ALTER TABLE `candidate`
  ADD PRIMARY KEY (`c_id`),
  ADD KEY `u_id` (`u_id`),
  ADD KEY `u_id_2` (`u_id`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`c_id`),
  ADD KEY `u_id` (`u_id`);

--
-- Indexes for table `department_nominees`
--
ALTER TABLE `department_nominees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_students`
--
ALTER TABLE `department_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_cgpa` (`cgpa`);

--
-- Indexes for table `discipline_records`
--
ALTER TABLE `discipline_records`
  ADD PRIMARY KEY (`recordID`);

--
-- Indexes for table `election_date`
--
ALTER TABLE `election_date`
  ADD KEY `u_id` (`u_id`);

--
-- Indexes for table `election_requests`
--
ALTER TABLE `election_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`p_id`);

--
-- Indexes for table `e_requests`
--
ALTER TABLE `e_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_submitted_at` (`submitted_at`);

--
-- Indexes for table `nomination_logs`
--
ALTER TABLE `nomination_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_candidate_id` (`candidate_id`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `nominees`
--
ALTER TABLE `nominees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sent` (`sent_to_department`);

--
-- Indexes for table `request`
--
ALTER TABLE `request`
  ADD PRIMARY KEY (`requestID`);

--
-- Indexes for table `result`
--
ALTER TABLE `result`
  ADD PRIMARY KEY (`vid`),
  ADD KEY `vid` (`vid`),
  ADD KEY `vid_2` (`vid`),
  ADD KEY `u_id` (`u_id`),
  ADD KEY `u_id_2` (`u_id`),
  ADD KEY `u_id_3` (`u_id`),
  ADD KEY `u_id_4` (`u_id`),
  ADD KEY `u_id_5` (`u_id`),
  ADD KEY `u_id_6` (`u_id`),
  ADD KEY `u_id_7` (`u_id`);

--
-- Indexes for table `send_request`
--
ALTER TABLE `send_request`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `student_discipline_records`
--
ALTER TABLE `student_discipline_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_incident_date` (`incident_date`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`u_id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `voter`
--
ALTER TABLE `voter`
  ADD PRIMARY KEY (`vid`),
  ADD KEY `u_id` (`u_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `candidate`
--
ALTER TABLE `candidate`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12232;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `c_id` int(22) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `department_nominees`
--
ALTER TABLE `department_nominees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department_students`
--
ALTER TABLE `department_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discipline_records`
--
ALTER TABLE `discipline_records`
  MODIFY `recordID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10033;

--
-- AUTO_INCREMENT for table `election_requests`
--
ALTER TABLE `election_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `p_id` int(21) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `e_requests`
--
ALTER TABLE `e_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `nomination_logs`
--
ALTER TABLE `nomination_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `nominees`
--
ALTER TABLE `nominees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request`
--
ALTER TABLE `request`
  MODIFY `requestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `student_discipline_records`
--
ALTER TABLE `student_discipline_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `voter`
--
ALTER TABLE `voter`
  MODIFY `vid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12214;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `user` (`u_id`);

--
-- Constraints for table `election_date`
--
ALTER TABLE `election_date`
  ADD CONSTRAINT `election_date_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `user` (`u_id`);

--
-- Constraints for table `voter`
--
ALTER TABLE `voter`
  ADD CONSTRAINT `voter_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `user` (`u_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
