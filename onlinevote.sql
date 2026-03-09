-- phpMyAdmin SQL Dump
-- version 2.9.2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Jun 02, 2017 at 10:22 AM
-- Server version: 5.0.27
-- PHP Version: 5.2.1
-- 
-- Database: `onlinevote`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `candidate`
-- 

CREATE TABLE `candidate` (
  `c_id` int(11) NOT NULL auto_increment,
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
  `party_name` varchar(20) NOT NULL,
  `party_symbol` blob NOT NULL,
  `candidate_photo` varchar(40) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY  (`c_id`),
  KEY `u_id` (`u_id`),
  KEY `u_id_2` (`u_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=3490 ;

-- 
-- Dumping data for table `candidate`
-- 

INSERT INTO `candidate` (`c_id`, `u_id`, `fname`, `mname`, `lname`, `sex`, `age`, `work`, `education`, `phone`, `email`, `experience`, `party_name`, `party_symbol`, `candidate_photo`, `username`, `password`) VALUES 
(10, '456', 'etabeba', 'sisay', 'manaye', 'fmale', 24, 'doctor', 'master', '0922740114', 'etabeba@gmail.com', '2 year', 'Ehadg', 0x342e6a7067, '1e.jpg', 'ett', 'e10adc3949ba59abbe56e057f20f883e'),
(1011, '164', 'belay ', 'g/medhin', 'kebede', 'male', 22, 'IT_programmer', 'phd', '0922366801', 'belay@gmail.co,', '4years', 'Andnet', 0x372e6a7067, '1b.jpg', 'bb', '81dc9bdb52d04dc20036dbd8313ed055'),
(3208, '164', 'etabeba', 'sisay', 'manaye', 'female', 22, 'doctor', 'phd', '0922740114', 'etab@gmail.com', '2years', 'semayawii', 0x352e6a7067, '1a.JPG', 'ee', '81dc9bdb52d04dc20036dbd8313ed055'),
(3489, '164', 'bini', 'abebe', 'kebede', 'male', 23, 'IT_programmer', 'phd', '0936680103', 'mesi@gmail.com', '2years', 'Andnety', 0x312e6a7067, '1e.jpg', 'yy', 'e10adc3949ba59abbe56e057f20f883e');

-- --------------------------------------------------------

-- 
-- Table structure for table `comment`
-- 

CREATE TABLE `comment` (
  `c_id` int(22) NOT NULL auto_increment,
  `u_id` varchar(200) NOT NULL,
  `name` varchar(60) NOT NULL,
  `email` varchar(20) NOT NULL,
  `content` varchar(500) NOT NULL,
  `date` varchar(20) NOT NULL,
  `status` varchar(10) NOT NULL,
  PRIMARY KEY  (`c_id`),
  KEY `u_id` (`u_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=19 ;

-- 
-- Dumping data for table `comment`
-- 

INSERT INTO `comment` (`c_id`, `u_id`, `name`, `email`, `content`, `date`, `status`) VALUES 
(12, '111', 'meseret', 'mesi@gmail.com', 'thanks for the service', '10/06/2017', 'read'),
(13, '111', 'etabeba sisay', 'etab@gmail.com', 'nice election date for all', '19/05/2017', 'read'),
(18, '111', 'hgi hvghhg hgbjh', 'opo@gmail.com', 'hgjdykj sdhfbmgb sbndvhfkjbvf', '02/06/2017', 'unread');

-- --------------------------------------------------------

-- 
-- Table structure for table `election_date`
-- 

CREATE TABLE `election_date` (
  `date` date NOT NULL,
  `u_id` varchar(15) NOT NULL,
  KEY `u_id` (`u_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `election_date`
-- 

INSERT INTO `election_date` (`date`, `u_id`) VALUES 
('2017-06-18', '1244');

-- --------------------------------------------------------

-- 
-- Table structure for table `event`
-- 

CREATE TABLE `event` (
  `p_id` int(21) NOT NULL auto_increment,
  `c_id` int(22) NOT NULL,
  `title` varchar(30) NOT NULL,
  `content` varchar(10000) NOT NULL,
  `posted_by` varchar(30) NOT NULL,
  `date` varchar(20) NOT NULL,
  PRIMARY KEY  (`p_id`),
  KEY `c_id` (`c_id`),
  KEY `c_id_2` (`c_id`),
  KEY `c_id_3` (`c_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

-- 
-- Dumping data for table `event`
-- 

INSERT INTO `event` (`p_id`, `c_id`, `title`, `content`, `posted_by`, `date`) VALUES 
(2, 10, 'for each hossana people', 'select me and truest me i never do any  activity ', 'etabeba', '10/06/2017'),
(6, 1011, 'Notice', '&#4616;&#4614;&#4659;&#4821;&#4755; &#4752;&#4811;&#4650;&#4814;&#4733; &#4704;&#4633;&#4617; &#4840;2009 &#4819;.&#4637; &#4637;&#4653;&#4907; &#4840;&#4634;&#4779;&#4612;&#4848;&#4813; &#4656;&#4756; 1-2009 &#4819;.&#4637;  &#4661;&#4616;&#4614;&#4752; &#4609;&#4619;&#4733;&#4609;&#4637; &#4776;&#4768;&#4609;&#4753; &#4829;&#4877;&#4869;&#4725; &#4773;&#4752;&#4853;&#4723;&#4853;&#4653;&#4873; &#4661;&#4757;&#4621; &#4704;&#4725;&#4613;&#4725;&#4755; &#4773;&#4757;&#4872;&#4621;&#4923;&#4616;&#4757; &#4768;&#4757;&#4853;&#4752;&#4725; &#4947;&#4653;&#4722; &#4845;&#4637;&#4648;&#4897;&#4961;&#4961;', 'belay ', '24/05/2017');

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
  `choice` varchar(20) NOT NULL,
  PRIMARY KEY  (`vid`),
  KEY `vid` (`vid`),
  KEY `vid_2` (`vid`),
  KEY `u_id` (`u_id`),
  KEY `u_id_2` (`u_id`),
  KEY `u_id_3` (`u_id`),
  KEY `u_id_4` (`u_id`),
  KEY `u_id_5` (`u_id`),
  KEY `u_id_6` (`u_id`),
  KEY `u_id_7` (`u_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `result`
-- 

INSERT INTO `result` (`vid`, `u_id`, `fname`, `mname`, `station`, `choice`) VALUES 
('1010', '1010', 'meseret', 'sisay', 'arada', 'Andente'),
('110', '111', 'etabeba', 'sisay', 'manaye', 'ehadg'),
('1567', '1567', 'bini', 'ret', 'mil_anba', 'Ehadg'),
('3162', '3162', 'bell', 'nati', 'arada', 'semayawii'),
('3208', '3208', 'bini', 'sisay', 'sechiduna', 'Ehadg'),
('3232', '3232', 'abebe', 'zenebe', 'bobcho', 'Ehadg'),
('7700', '7700', 'abebe', 'kebede', 'betel', 'semayawii'),
('7778', '7778', 'meseret', 'sisay', 'arada', 'semayaww'),
('7779', '7779', 'abebe', 'sisay', 'arada', 'Ehadg');

-- --------------------------------------------------------

-- 
-- Table structure for table `station`
-- 

CREATE TABLE `station` (
  `psid` int(20) NOT NULL auto_increment,
  `u_id` varchar(20) NOT NULL,
  `psname` varchar(25) NOT NULL,
  `kebele` varchar(25) NOT NULL,
  `city` varchar(25) NOT NULL,
  PRIMARY KEY  (`psid`),
  KEY `u_id` (`u_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

-- 
-- Dumping data for table `station`
-- 

INSERT INTO `station` (`psid`, `u_id`, `psname`, `kebele`, `city`) VALUES 
(6, '456', 'sechiduna', '07', 'hossana'),
(7, '456', 'betel', '09', 'hossana'),
(8, '456', 'arada', '09', 'hossana'),
(9, '456', 'lichenba', '06', 'hossana'),
(10, '456', 'mil_anba', '02', 'hassana'),
(11, '456', 'heto', '03', 'hossana'),
(12, '456', 'jelo_nerem', '09', 'hossana'),
(13, '456', 'bobcho', '09', 'hassana');

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
  `role` varchar(20) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` int(1) NOT NULL,
  `station` varchar(25) NOT NULL,
  PRIMARY KEY  (`u_id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `user`
-- 

INSERT INTO `user` (`fname`, `mname`, `lname`, `u_id`, `sex`, `age`, `phone`, `email`, `role`, `username`, `password`, `status`, `station`) VALUES 
('meseret', 'afework', 'goshu', '111', 'female', '21', '0936680161', 'mesi@gmail.com', 'admin', 'mis', '5863e37d709e1caf67f34e2d13b0045d', 1, 'betel'),
('bini', 'sisay', 'kebede', '1244', 'male', '22', '0922740116', 'mesi@gmail.com', 'admin', 'admin', '20bdbdca272ff1f808fc0671a7f63251', 1, ''),
('abebe', 'zenebe', 'goshu', '1290', 'male', '22', '0922740149', 'mebs@gmail.com', 'admin', 'fff', 'e10adc3949ba59abbe56e057f20f883e', 1, ''),
('musba', 'wabela', 'ahmed', '139', 'male', '34', '0913791700', 'musba@gmail.com', 'officer', 'musba', '091111', 1, 'arada'),
('belay', 'gebre', 'abebayehu', '164', 'male', '23', '0913231608', 'bell@gmail.com', 'officer', 'bell', 'aa29bbeec37a88793c1f7faa7ba3ba61', 1, 'hossana'),
('etabeba', 'sisay', 'manaye', '3202', 'fmale', '23', '0922740114', 'etabeba@gmail.com', 'admin', 'et', '092274', 1, 'arada'),
('bini', 'sisay', 'manaye', '3290', 'male', '22', '0922740114', 'etab@gmail.com', 'admin', 'bell', '123456', 1, ''),
('melkamu', 'mengistu', 'kebede', '456', 'male', '36', '0914562389', 'melke@gmail.com', 'officer', 'melke', '091010', 1, 'heto');

-- --------------------------------------------------------

-- 
-- Table structure for table `voter`
-- 

CREATE TABLE `voter` (
  `vid` int(11) NOT NULL auto_increment,
  `u_id` varchar(30) NOT NULL,
  `fname` varchar(25) NOT NULL,
  `mname` varchar(25) NOT NULL,
  `lname` varchar(25) NOT NULL,
  `age` int(10) NOT NULL,
  `city` varchar(10) NOT NULL,
  `sex` varchar(8) NOT NULL,
  `work` varchar(23) NOT NULL,
  `phone` varchar(12) NOT NULL,
  `email` varchar(25) NOT NULL,
  `station` varchar(30) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(25) NOT NULL,
  PRIMARY KEY  (`vid`),
  KEY `u_id` (`u_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=9978 ;

-- 
-- Dumping data for table `voter`
-- 

INSERT INTO `voter` (`vid`, `u_id`, `fname`, `mname`, `lname`, `age`, `city`, `sex`, `work`, `phone`, `email`, `station`, `username`, `password`, `status`) VALUES 
(1010, '164', 'meseret', 'sisay', 'manaye', 23, 'Hossana', 'female', 'gov''t', '0936680161', 'mesi@gmail.com', 'arada', 'cc', 'd0970714757783e6cf17b26fb8e2298f', '1'),
(1110, '164', 'etabeba', 'sisay', 'manaye', 23, 'hossana', 'fmale', 'student', '0922740114', 'etabeba@gmail.com', 'betel', 'bb', '20bdbdca272ff1f808fc0671a7f63251', '0'),
(1567, '164', 'bini', 'ret', 'iuy', 22, 'Hossana', 'male', 'gov''t', '0936680145', 'mesi@gmail.com', 'mil_anba', 'mm', 'e10adc3949ba59abbe56e057f20f883e', '1'),
(3162, '164', 'bell', 'nati', 'mesay', 54, 'Hossana', 'male', 'student', '0913283284', 'madiva1984@gmail.com', 'arada', 'washun', 'e10adc3949ba59abbe56e057f20f883e', '1'),
(3206, '164', 'bini', 'abebe', 'manaye', 23, 'Hossana', 'male', 'student', '0922740187', 'etabeba@gmail.com', 'sechiduna', 'bb', 'e10adc3949ba59abbe56e057f20f883e', '0'),
(3232, '164', 'abebe', 'zenebe', 'manaye', 22, 'Hossana', 'male', 'gov''t', '0922740103', 'etab@gmail.com', 'bobcho', 'bb', '0d71b347cbf9f2aba03cd97b6d059475', '1'),
(9977, '164', 'bini', 'sisay', 'kebede', 56, 'Hossana', 'male', 'gov''t', '3456678889', 'ets@gmail.com', 'sechiduna', 'ss', 'e10adc3949ba59abbe56e057f20f883e', '0');

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
