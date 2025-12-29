-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 14, 2025 at 01:08 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Foundora`
--

-- --------------------------------------------------------

--
-- Table structure for table `Funding`
--

CREATE TABLE `Funding` (
  `Funding_id` int(11) NOT NULL,
  `Investor_id` int(11) NOT NULL,
  `Startup_id` int(11) NOT NULL,
  `Amount` decimal(12,2) NOT NULL,
  `Date` date NOT NULL,
  `Status` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Investor_Profile`
--

CREATE TABLE `Investor_Profile` (
  `Investor_id` int(11) NOT NULL,
  `User_id` int(11) NOT NULL,
  `Investor_name` varchar(100) NOT NULL,
  `Investor_type` varchar(50) DEFAULT NULL,
  `Investor_range` varchar(50) DEFAULT NULL,
  `Sector_of_interest` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Meeting`
--

CREATE TABLE `Meeting` (
  `Meeting_id` int(11) NOT NULL,
  `Startup_id` int(11) NOT NULL,
  `Investor_id` int(11) NOT NULL,
  `Scheduled_time` datetime NOT NULL,
  `Status` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Message`
--

CREATE TABLE `Message` (
  `Message_id` int(11) NOT NULL,
  `Sender_id` int(11) NOT NULL,
  `Receiver_id` int(11) NOT NULL,
  `Content` text NOT NULL,
  `Timestamp` datetime DEFAULT current_timestamp(),
  `Seen_status` tinyint(1) DEFAULT 0
) ;

-- --------------------------------------------------------

--
-- Table structure for table `Startup_Profile`
--

CREATE TABLE `Startup_Profile` (
  `Startup_id` int(11) NOT NULL,
  `User_id` int(11) NOT NULL,
  `Startup_name` varchar(100) NOT NULL,
  `Founder_name` varchar(100) NOT NULL,
  `Industry` varchar(50) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Stage` varchar(50) DEFAULT NULL,
  `Funding_needed` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `User`
--

CREATE TABLE `User` (
  `User_id` int(11) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `User_type` enum('Startup','Investor') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Funding`
--
ALTER TABLE `Funding`
  ADD PRIMARY KEY (`Funding_id`),
  ADD KEY `Investor_id` (`Investor_id`),
  ADD KEY `Startup_id` (`Startup_id`);

--
-- Indexes for table `Investor_Profile`
--
ALTER TABLE `Investor_Profile`
  ADD PRIMARY KEY (`Investor_id`),
  ADD KEY `User_id` (`User_id`);

--
-- Indexes for table `Meeting`
--
ALTER TABLE `Meeting`
  ADD PRIMARY KEY (`Meeting_id`),
  ADD KEY `Startup_id` (`Startup_id`),
  ADD KEY `Investor_id` (`Investor_id`);

--
-- Indexes for table `Message`
--
ALTER TABLE `Message`
  ADD PRIMARY KEY (`Message_id`),
  ADD KEY `Sender_id` (`Sender_id`),
  ADD KEY `Receiver_id` (`Receiver_id`);

--
-- Indexes for table `Startup_Profile`
--
ALTER TABLE `Startup_Profile`
  ADD PRIMARY KEY (`Startup_id`),
  ADD UNIQUE KEY `Startup_name` (`Startup_name`),
  ADD KEY `User_id` (`User_id`);

--
-- Indexes for table `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`User_id`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Funding`
--
ALTER TABLE `Funding`
  MODIFY `Funding_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Investor_Profile`
--
ALTER TABLE `Investor_Profile`
  MODIFY `Investor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Meeting`
--
ALTER TABLE `Meeting`
  MODIFY `Meeting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Message`
--
ALTER TABLE `Message`
  MODIFY `Message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Startup_Profile`
--
ALTER TABLE `Startup_Profile`
  MODIFY `Startup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `User`
--
ALTER TABLE `User`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Funding`
--
ALTER TABLE `Funding`
  ADD CONSTRAINT `funding_ibfk_1` FOREIGN KEY (`Investor_id`) REFERENCES `Investor_Profile` (`Investor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `funding_ibfk_2` FOREIGN KEY (`Startup_id`) REFERENCES `Startup_Profile` (`Startup_id`) ON DELETE CASCADE;

--
-- Constraints for table `Investor_Profile`
--
ALTER TABLE `Investor_Profile`
  ADD CONSTRAINT `investor_profile_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `User` (`User_id`) ON DELETE CASCADE;

--
-- Constraints for table `Meeting`
--
ALTER TABLE `Meeting`
  ADD CONSTRAINT `meeting_ibfk_1` FOREIGN KEY (`Startup_id`) REFERENCES `Startup_Profile` (`Startup_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meeting_ibfk_2` FOREIGN KEY (`Investor_id`) REFERENCES `Investor_Profile` (`Investor_id`) ON DELETE CASCADE;

--
-- Constraints for table `Message`
--
ALTER TABLE `Message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`Sender_id`) REFERENCES `User` (`User_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_ibfk_2` FOREIGN KEY (`Receiver_id`) REFERENCES `User` (`User_id`) ON DELETE CASCADE;

--
-- Constraints for table `Startup_Profile`
--
ALTER TABLE `Startup_Profile`
  ADD CONSTRAINT `startup_profile_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `User` (`User_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
