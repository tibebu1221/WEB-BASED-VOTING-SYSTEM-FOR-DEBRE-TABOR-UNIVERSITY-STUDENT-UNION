-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2025 at 05:13 PM
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
-- Table structure for table `candidate`
--

CREATE TABLE `candidate` (
  `c_id` int(11) NOT NULL,
  `u_id` varchar(22) NOT NULL,
  `fname` varchar(20) NOT NULL,
  `mname` varchar(20) NOT NULL,
  `lname` varchar(20) NOT NULL,
  `sex` varchar(10) NOT NULL,
  `age` int(11) NOT NULL,
  `work` varchar(20) NOT NULL,
  `education` varchar(30) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(20) NOT NULL,
  `experience` varchar(25) NOT NULL,
  `candidate_photo` varchar(40) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `candidate`
--

INSERT INTO `candidate` (`c_id`, `u_id`, `fname`, `mname`, `lname`, `sex`, `age`, `work`, `education`, `phone`, `email`, `experience`, `candidate_photo`, `username`, `password`) VALUES
(10, '456', 'etabeba', 'sisay', 'manaye', 'fmale', 24, 'doctor', 'master', '0922740114', 'etabeba@gmail.com', '2 year', '1e.jpg', 'etabeba', 'c8aa4044804e8d4c995c0ee44f039bb5'),
(1011, '164', 'belay ', 'g/medhin', 'kebede', 'male', 22, 'IT_programmer', 'phd', '0922366801', 'belay@gmail.com', '4years', '1b.jpg', 'abebe', 'e10adc3949ba59abbe56e057f20f883e'),
(1212, '1212', 'dessie', 'tibebu', 'yayeh', 'male', 35, 'student', 'degree', '0998787788', 'dtibebu551@gmail.com', '2', 'Uploads/candidates/candidate_1212.jpg', 'ttt', '$2y$10$m9pK4e1S3KFcMi3ieOUTKepE2EqR.xh0RV95OW1hTA7FXlZkOMVQO'),
(1221, 'dtu14r1136', 'dessieti', 'tibebu', 'yayeh', 'male', 34, 'student', 'degree', '0946084668', 'kirubeltsegaye80@gma', '2', '1.jpg', 'tibebud', '$2y$10$otAXNmxxpx4yLyhpFRUlv.GAMtqkTsiL8Y5VwzE/9kGpXoyQuXTpq'),
(1224, 'dtu14r1136', 'dessieta', 'tibebu', 'yayeh', 'male', 35, 'student', 'degree', '0975524798', 'kirubeltsegaye80@gma', '2', '4.jpg', 'dtu', '$2y$10$z6XaeZdu3MGWygog36c7UuMYJgMRxtArwHSDW6gklZk4mIIF088km'),
(1234, 'dtu14r1136', 'dt', 'dd', 'sd', 'male', 23, 'student', 'degree', '0941423932', 'kirubeltsegaye80@gma', '2', 'Uploads/candidates/candidate_1234.jpg', 'dtuu', '$2y$10$pQXPvx9DJIC0ldVzgojAnuIpzjsrmBbqjMv4mVSKwxvNpBo4D8Uqy'),
(3208, '164', 'etabeba', 'sisay', 'manaye', 'female', 22, 'doctor', 'phd', '0922740114', 'etab@gmail.com', '2years', '1a.JPG', 'melke', 'e10adc3949ba59abbe56e057f20f883e'),
(3489, '164', 'bini', 'abebe', 'kebede', 'male', 23, 'IT_programmer', 'phd', '0936680103', 'mesi@gmail.com', '2years', '1e.jpg', 'meseret', 'e10adc3949ba59abbe56e057f20f883e');

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
('2025-10-07', '2025-10-30');

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
(29, '111', 'sada tibebu', 'tibebud18@gmail.com', 'king of king sada candidate', '14/09/2025', 'read');

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
(0, 'C001', 'No issues', 'clear', '2025-10-07 10:00:00'),
(1, '1221', 'not discipline record    ', 'approve ', '2025-10-09 18:37:17');

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
('2025-10-24', 'r14dtu1135');

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `p_id` int(21) NOT NULL,
  `c_id` int(22) NOT NULL,
  `title` varchar(30) NOT NULL,
  `content` varchar(10000) NOT NULL,
  `posted_by` varchar(30) NOT NULL,
  `date` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`p_id`, `c_id`, `title`, `content`, `posted_by`, `date`) VALUES
(11, 10, 'voting system', 'please select my party ', 'etabeba', '11/09/2025'),
(13, 10, 'welcome to voting system', 'good chance ', 'etabeba', '13/09/2025');

-- --------------------------------------------------------

--
-- Table structure for table `request`
--

CREATE TABLE `request` (
  `requestID` int(11) NOT NULL,
  `candidateID` varchar(30) DEFAULT NULL,
  `officeID` varchar(30) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `discipline_status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request`
--

INSERT INTO `request` (`requestID`, `candidateID`, `officeID`, `status`, `submitted_at`, `discipline_status`) VALUES
(1, '1221', 'dtu14r1136', 'approved', '2025-10-08 14:41:19', 'pending'),
(2, '1212', 'dtu14r1136', 'pending', '2025-10-08 15:04:22', 'pending'),
(3, '1', '1221', 'pending', '2025-10-12 19:58:51', 'pending'),
(4, '22', '1000', 'pending', '2025-10-12 19:59:02', 'pending'),
(5, '101', 'dtu14r1136', 'approved', '2025-10-12 22:30:35', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `result`
--

CREATE TABLE `result` (
  `vid` varchar(20) NOT NULL,
  `u_id` varchar(22) NOT NULL,
  `fname` varchar(20) NOT NULL,
  `mname` varchar(20) NOT NULL,
  `station` varchar(30) NOT NULL,
  `choice` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `result`
--

INSERT INTO `result` (`vid`, `u_id`, `fname`, `mname`, `station`, `choice`) VALUES
('9979', '9979', 'desalegn', 'tibebu', 'arada', '1224'),
('9980', '9980', 'desalegn', 'tibebu', 'arada', '1224'),
('9981', '9981', 'dessiet', 'tibebu', 'DTU SU', '1224'),
('9982', '9982', 'sada', 'tibebu', 'DTU SU', '1224'),
('9983', '9983', 'de', 'd', 'DTU SU', '3208');

-- --------------------------------------------------------

--
-- Table structure for table `station`
--

CREATE TABLE `station` (
  `psid` int(20) NOT NULL,
  `u_id` varchar(20) NOT NULL,
  `psname` varchar(25) NOT NULL,
  `city` varchar(30) DEFAULT NULL,
  `kebele` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `station`
--

INSERT INTO `station` (`psid`, `u_id`, `psname`, `city`, `kebele`) VALUES
(16, '139', 'DTU SU', NULL, NULL),
(17, '139', 'dddddd', NULL, NULL),
(18, '139', 'tibebud', 'DT', '09'),
(19, '139', 'dtu', 'DT', 'dt');

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
  `role` enum('admin','officer','discipline_committee') NOT NULL DEFAULT 'officer',
  `username` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` int(1) NOT NULL,
  `station` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`fname`, `mname`, `lname`, `u_id`, `sex`, `age`, `phone`, `email`, `role`, `username`, `password`, `status`, `station`) VALUES
('meseret', 'afework', 'goshu', '111', 'female', '21', '0936680161', 'mesi@gmail.com', 'admin', 'mis', '$2y$10$2GBMGbjLbVUImgF4s0i0Oe2khICPneQ4XVoWnsYRKkG/PwqHB5b9G', 1, 'betel'),
('yop', 'man', 'city', '1111', 'male', '25', '0945343253', 'mesi@gmail.com', 'officer', 'yop', '$2y$10$kMDaQ3aUIMuzNygqHHOo7urN7PiJVPghzv7lmUb/yaaBsBWUcRCa.', 1, 'man'),
('dessie', 'tibebu', 'yayeh', '1134', 'male', '23', '0946084660', 'dessiet70@gmail.com', '', 'jojo', '$2y$10$53bzPIUxu7y1gugJL4rc9ubryVoupkFQIGdR3i4WC8qQRtbgPG3Xe', 1, 'dtu'),
('dessie', 'tib', 'yayeh', '1135', 'male', '25', '0943492521', 'dtibebu551@gmail.com', 'officer', 'dessie', '$2y$10$GudvWhg9nEzZMai3osxzPuS/spRZuDo88g3HyTNhzfdeEwI9wyTzS', 1, 'dtu'),
('yo', 'aa', 'bb', '1212', 'male', '26', '0900003322', 'mesi@gmail.com', 'discipline_committee', 'aaa', '$2y$10$1E5hUFl0xaDxENmr6rMrB.VUzJjsxNLTH65945WFWxxhucSvDz/3i', 1, 'dtu'),
('musba', 'wabela', 'ahmed', '139', 'male', '34', '0910101010', 'musba@gmail.com', 'officer', 'musba', '$2y$10$aJAnHoFi33z9mIsWZwiId.tHLtFwmmx.pN97auSzukkpN9n5mOC.q', 1, 'arada'),
('belay', 'gebre', 'abebayehu', '164', 'male', '23', '0913101010', 'bell@gmail.com', 'officer', 'bell', 'aa29bbeec37a88793c1f7faa7ba3ba61', 1, 'hossana'),
('etabeba', 'sisay', 'manaye', '3202', 'fmale', '23', '0922740114', 'etabeba@gmail.com', 'admin', 'etabeba', '$2y$10$UPUZPfO0wb33i6L/gxcgxuFj2hT111wL5JeqRXT8AmeuAorQzcPlW', 1, 'arada'),
('melkamu', 'mengistu', 'kebede', '456', 'male', '36', '0914562389', 'melke@gmail.com', 'officer', 'melke', 'e10adc3949ba59abbe56e057f20f883e', 1, 'heto'),
('bb', 'cc', 'dd', '555', 'female', '43', '0900008866', 'mesi@gmail.com', '', 'ddd', '$2y$10$jsUpcSHJ9vURwW/6DZ6Rp.QpXW5nh7lZQwc0y0bvA8V6elTOHMPQm', 1, 'dtu'),
('dessie', 'tibebu', 'yayeh', 'dtu14r1136', 'male', '24', '0946084669', 'dtibebu551@gmail.com', 'officer', 'tibebu', '$2y$10$gij/5BLDToOt83Qc/4YFwOc3wc5Am8Mqage7Ed4Y6AgEHvnVhnOki', 1, 'dtu'),
('Desalegn', 'yayeh yayeh de s', 'tibebu', 'r14dtu1135', 'F', '23', '0937884156', 'dessiet70@gmail.com', 'officer', 'desa', '$2y$10$AY1BM.4QnvrKpxISLqpVSe2KhvYq1Qy2tOwioHqD9.FAwUc2TH/4S', 1, 'control');

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
  `city` varchar(20) DEFAULT NULL,
  `work` varchar(23) NOT NULL,
  `phone` varchar(12) NOT NULL,
  `email` varchar(25) NOT NULL,
  `station` varchar(30) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `voter`
--

INSERT INTO `voter` (`vid`, `u_id`, `fname`, `mname`, `lname`, `age`, `sex`, `city`, `work`, `phone`, `email`, `station`, `username`, `password`, `status`) VALUES
(1010, '164', 'meseret', 'sisay', 'manaye', 23, 'female', NULL, 'gov\'t', '0936680161', 'mesi@gmail.com', 'arada', 'khan', 'b4a0393fa3dcca2a1d94b8d8d483e3f0', '1'),
(3232, '164', 'abebe', 'zenebe', 'manaye', 22, 'male', NULL, 'gov\'t', '0922740103', 'etab@gmail.com', 'bobcho', 'bb', '0d71b347cbf9f2aba03cd97b6d059475', '1'),
(9979, '139', 'desalegn', 'tibebu', 'yayeh', 24, 'male', NULL, 'student', '0937884156', 'dessiet70@gmail.com', 'arada', 'desa', '$2y$10$OlROWQCryuSgjQ4YnbM4vOuN/U4vtlEhEtQkkX5sR0gi2kCxZ8Anm', '1'),
(9980, '139', 'desalegn', 'tibebu', 'yayeh', 24, 'male', NULL, 'student', '0946084669', 'dessiet70@gmail.com', 'arada', 'dessa', '$2y$10$OMWfOLdbKuRSXk/AL3OIF.XQsknGiDcKC9or6U2TdPW0y6imMrS2O', '1'),
(9981, '139', 'dessiet', 'tibebu', 'yayeh', 24, 'male', NULL, 'student', '0975524798', 'dtibebu551@gmail.com', 'DTU SU', 'dessiet', '$2y$10$LS1alDacCg9CUG2a5qdxf.x4v88LtbUEaYkGfhnwz35TbU9/tsEf6', '1'),
(9982, 'dtu14r1136', 'sada', 'tibebu', 'hs', 35, 'male', 'DT', 'student', '0963146941', 'tibebud18@gmail.com', 'DTU SU', 'sada', '$2y$10$EpZObo5SMwaKM59ZzF.CRO.YDM2lFK80hAkFv61K3qVg/KUX2LaqO', '1'),
(9983, '139', 'de', 'd', 'd', 24, 'female', 'DT', 'student', '0987455657', 'dtibebu551@gmail.com', 'DTU SU', 'tt', '$2y$10$x2gTrjhY8sgieGUMyrd4uOT/puZZwrVrv/F2GpQFNQNVFQKaDG0Zi', '1');

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
('2025-10-07', '2025-10-30');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`p_id`),
  ADD KEY `c_id` (`c_id`),
  ADD KEY `c_id_2` (`c_id`),
  ADD KEY `c_id_3` (`c_id`);

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
-- Indexes for table `station`
--
ALTER TABLE `station`
  ADD PRIMARY KEY (`psid`),
  ADD KEY `u_id` (`u_id`);

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
-- AUTO_INCREMENT for table `candidate`
--
ALTER TABLE `candidate`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3490;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `c_id` int(22) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `p_id` int(21) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `request`
--
ALTER TABLE `request`
  MODIFY `requestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `station`
--
ALTER TABLE `station`
  MODIFY `psid` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `voter`
--
ALTER TABLE `voter`
  MODIFY `vid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9984;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `candidate`
--
ALTER TABLE `candidate`
  ADD CONSTRAINT `candidate_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `user` (`u_id`);

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
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`c_id`) REFERENCES `candidate` (`c_id`);

--
-- Constraints for table `station`
--
ALTER TABLE `station`
  ADD CONSTRAINT `station_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `user` (`u_id`);

--
-- Constraints for table `voter`
--
ALTER TABLE `voter`
  ADD CONSTRAINT `voter_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `user` (`u_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
