-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2025 at 03:15 PM
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
-- Database: `taskflow`
--

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

CREATE TABLE `project` (
  `id` int(11) NOT NULL,
  `publicId` binary(16) NOT NULL,
  `managerId` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `budget` decimal(21,4) NOT NULL,
  `startDateTime` datetime NOT NULL,
  `completionDateTime` datetime NOT NULL,
  `actualCompletionDateTime` datetime DEFAULT NULL,
  `status` varchar(25) NOT NULL CHECK (`status` in ('pending','onGoing','completed','delayed','cancelled')),
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `project`
--
DELIMITER $$
CREATE TRIGGER `cancelProject` AFTER UPDATE ON `project` FOR EACH ROW BEGIN
    -- Only act when status changes to 'cancelled'
    IF NEW.status = 'cancelled' AND OLD.status <> 'cancelled' THEN
        UPDATE projectWorker
        SET status = 'unassigned'
        WHERE projectId = NEW.id;

        UPDATE projectPhase
        SET status = 'cancelled'
        WHERE projectId = NEW.id;

        UPDATE projectTask
        SET status = 'cancelled'
        WHERE projectId = NEW.id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `checkProjectDatesBeforeInsert` BEFORE INSERT ON `project` FOR EACH ROW BEGIN
    -- 1. Check that startDateTime is not in the past
    IF NEW.startDateTime < NOW() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'startDateTime cannot be in the past.';
    END IF;

    -- 2. Check that completionDateTime is later than startDateTime
    IF NEW.completionDateTime <= NEW.startDateTime THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'completionDateTime must be later than startDateTime.';
    END IF;

    -- 3. Check actualCompletionDateTime only if provided
    IF NEW.actualCompletionDateTime IS NOT NULL
       AND NEW.actualCompletionDateTime <= NEW.startDateTime THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'actualCompletionDateTime must be later than startDateTime.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `checkProjectDatesBeforeUpdate` BEFORE UPDATE ON `project` FOR EACH ROW BEGIN
    -- Only validate when relevant fields are being updated
    IF (NEW.startDateTime <> OLD.startDateTime
        OR NEW.completionDateTime <> OLD.completionDateTime
        OR NEW.actualCompletionDateTime <> OLD.actualCompletionDateTime) THEN
        
        -- 1. startDateTime cannot be in the past
        IF NEW.startDateTime < NOW() THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'startDateTime cannot be in the past.';
        END IF;

        -- 2. completionDateTime must be later than startDateTime
        IF NEW.completionDateTime <= NEW.startDateTime THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'completionDateTime must be later than startDateTime.';
        END IF;

        -- 3. actualCompletionDateTime (if provided) must be later than startDateTime
        IF NEW.actualCompletionDateTime IS NOT NULL
           AND NEW.actualCompletionDateTime <= NEW.startDateTime THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'actualCompletionDateTime must be later than startDateTime.';
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `projectphase`
--

CREATE TABLE `projectphase` (
  `id` int(11) NOT NULL,
  `publicId` binary(16) NOT NULL,
  `projectId` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `startDateTime` datetime NOT NULL,
  `completionDateTime` datetime NOT NULL,
  `status` varchar(25) NOT NULL CHECK (`status` in ('pending','onGoing','completed','delayed','cancelled'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `projectphase`
--
DELIMITER $$
CREATE TRIGGER `checkProjectPhaseDatesBeforeInsert` BEFORE INSERT ON `projectphase` FOR EACH ROW BEGIN
    IF NEW.startDateTime < CURRENT_TIMESTAMP THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'startDateTime cannot be in the past.';
    END IF;

    IF NEW.completionDateTime <= NEW.startDateTime THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'completionDateTime must be later than startDateTime.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `checkProjectPhaseDatesBeforeUpdate` BEFORE UPDATE ON `projectphase` FOR EACH ROW BEGIN
    IF NEW.startDateTime < NOW() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'startDateTime cannot be in the past.';
    END IF;

    IF NEW.completionDateTime <= NEW.startDateTime THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'completionDateTime must be later than startDateTime.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `projecttask`
--

CREATE TABLE `projecttask` (
  `id` int(11) NOT NULL,
  `publicId` binary(16) NOT NULL,
  `projectId` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `startDateTime` datetime NOT NULL,
  `completionDateTime` datetime NOT NULL,
  `actualCompletionDateTime` datetime DEFAULT NULL,
  `status` varchar(25) NOT NULL CHECK (`status` in ('pending','onGoing','completed','delayed','cancelled')),
  `priority` varchar(25) NOT NULL CHECK (`priority` in ('low','medium','high')),
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `projecttask`
--
DELIMITER $$
CREATE TRIGGER `cancelTask` AFTER UPDATE ON `projecttask` FOR EACH ROW BEGIN
    -- Only act when task status changes to 'cancelled'
    IF NEW.status = 'cancelled' AND OLD.status <> 'cancelled' THEN
        UPDATE projectTaskWorker
        SET status = 'unassigned'
        WHERE taskId = NEW.id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `checkProjectTaskDatesBeforeInsert` BEFORE INSERT ON `projecttask` FOR EACH ROW BEGIN
    -- 1. Check that startDateTime is not in the past
    IF NEW.startDateTime < NOW() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'startDateTime cannot be in the past.';
    END IF;

    -- 2. Check that completionDateTime is later than startDateTime
    IF NEW.completionDateTime <= NEW.startDateTime THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'completionDateTime must be later than startDateTime.';
    END IF;

    -- 3. Check actualCompletionDateTime only if provided
    IF NEW.actualCompletionDateTime IS NOT NULL
       AND NEW.actualCompletionDateTime <= NEW.startDateTime THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'actualCompletionDateTime must be later than startDateTime.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `checkProjectTaskDatesBeforeUpdate` BEFORE UPDATE ON `projecttask` FOR EACH ROW BEGIN
    -- Only validate when relevant fields are being updated
    IF (NEW.startDateTime <> OLD.startDateTime
        OR NEW.completionDateTime <> OLD.completionDateTime
        OR NEW.actualCompletionDateTime <> OLD.actualCompletionDateTime) THEN
        
        -- 1. startDateTime cannot be in the past
        IF NEW.startDateTime < NOW() THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'startDateTime cannot be in the past.';
        END IF;

        -- 2. completionDateTime must be later than startDateTime
        IF NEW.completionDateTime <= NEW.startDateTime THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'completionDateTime must be later than startDateTime.';
        END IF;

        -- 3. actualCompletionDateTime (if provided) must be later than startDateTime
        IF NEW.actualCompletionDateTime IS NOT NULL
           AND NEW.actualCompletionDateTime <= NEW.startDateTime THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'actualCompletionDateTime must be later than startDateTime.';
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `projecttaskworker`
--

CREATE TABLE `projecttaskworker` (
  `id` int(11) NOT NULL,
  `workerId` int(11) NOT NULL,
  `taskId` int(11) NOT NULL,
  `status` varchar(25) NOT NULL CHECK (`status` in ('assigned','unassigned','terminated'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projectworker`
--

CREATE TABLE `projectworker` (
  `id` int(11) NOT NULL,
  `workerId` int(11) NOT NULL,
  `projectId` int(11) NOT NULL,
  `status` varchar(25) NOT NULL CHECK (`status` in ('assigned','unassigned','terminated'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `publicId` binary(16) DEFAULT NULL,
  `firstName` varchar(255) NOT NULL,
  `middleName` varchar(255) DEFAULT NULL,
  `lastName` varchar(255) NOT NULL,
  `gender` varchar(10) NOT NULL CHECK (`gender` in ('male','female')),
  `birthDate` date NOT NULL,
  `role` varchar(20) NOT NULL,
  `contactNumber` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `bio` varchar(500) DEFAULT NULL,
  `profileLink` varchar(255) DEFAULT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `user`
--
DELIMITER $$
CREATE TRIGGER `checkUserAgeBeforeInsert` BEFORE INSERT ON `user` FOR EACH ROW BEGIN
    IF NEW.birthDate > DATE_SUB(CURDATE(), INTERVAL 18 YEAR) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'User must be at least 18 years old.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `userjobtitle`
--

CREATE TABLE `userjobtitle` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `project`
--
ALTER TABLE `project`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `publicId` (`publicId`),
  ADD KEY `projectManagerIdIndex` (`managerId`);
ALTER TABLE `project` ADD FULLTEXT KEY `projectFulltextIndex` (`name`,`description`);

--
-- Indexes for table `projectphase`
--
ALTER TABLE `projectphase`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `publicId` (`publicId`),
  ADD KEY `projectPhaseProjectIdIndex` (`projectId`);

--
-- Indexes for table `projecttask`
--
ALTER TABLE `projecttask`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `publicId` (`publicId`),
  ADD KEY `projectTaskProjectIdIndex` (`projectId`);
ALTER TABLE `projecttask` ADD FULLTEXT KEY `projectTaskFulltextIndex` (`name`,`description`);

--
-- Indexes for table `projecttaskworker`
--
ALTER TABLE `projecttaskworker`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `workerId` (`workerId`,`taskId`),
  ADD KEY `projectTaskWorkerWorkerIdIndex` (`workerId`),
  ADD KEY `taskId` (`taskId`);

--
-- Indexes for table `projectworker`
--
ALTER TABLE `projectworker`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `workerId` (`workerId`,`projectId`),
  ADD KEY `projectWorkerWorkerIdIndex` (`workerId`),
  ADD KEY `projectId` (`projectId`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `publicId` (`publicId`);
ALTER TABLE `user` ADD FULLTEXT KEY `userFulltextIndex` (`firstName`,`middleName`,`lastName`,`bio`,`email`);

--
-- Indexes for table `userjobtitle`
--
ALTER TABLE `userjobtitle`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `userId` (`userId`,`title`),
  ADD KEY `userJobTitleIdIndex` (`userId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `project`
--
ALTER TABLE `project`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `projectphase`
--
ALTER TABLE `projectphase`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `projecttask`
--
ALTER TABLE `projecttask`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projecttaskworker`
--
ALTER TABLE `projecttaskworker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projectworker`
--
ALTER TABLE `projectworker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `userjobtitle`
--
ALTER TABLE `userjobtitle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `project`
--
ALTER TABLE `project`
  ADD CONSTRAINT `project_ibfk_1` FOREIGN KEY (`managerId`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projectphase`
--
ALTER TABLE `projectphase`
  ADD CONSTRAINT `projectphase_ibfk_1` FOREIGN KEY (`projectId`) REFERENCES `project` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projecttask`
--
ALTER TABLE `projecttask`
  ADD CONSTRAINT `projecttask_ibfk_1` FOREIGN KEY (`projectId`) REFERENCES `project` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projecttaskworker`
--
ALTER TABLE `projecttaskworker`
  ADD CONSTRAINT `projecttaskworker_ibfk_1` FOREIGN KEY (`workerId`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projecttaskworker_ibfk_2` FOREIGN KEY (`taskId`) REFERENCES `projecttask` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projectworker`
--
ALTER TABLE `projectworker`
  ADD CONSTRAINT `projectworker_ibfk_1` FOREIGN KEY (`workerId`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projectworker_ibfk_2` FOREIGN KEY (`projectId`) REFERENCES `project` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `userjobtitle`
--
ALTER TABLE `userjobtitle`
  ADD CONSTRAINT `userjobtitle_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
