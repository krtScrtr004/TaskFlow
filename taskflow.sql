-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2025 at 06:40 AM
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
-- Table structure for table `phasetask`
--

CREATE TABLE `phasetask` (
  `id` int(11) NOT NULL,
  `publicId` binary(16) NOT NULL,
  `phaseId` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `startDateTime` datetime NOT NULL,
  `completionDateTime` datetime NOT NULL,
  `actualCompletionDateTime` datetime DEFAULT NULL,
  `status` varchar(25) NOT NULL CHECK (`status` in ('pending','onGoing','completed','delayed','cancelled')),
  `priority` varchar(25) NOT NULL CHECK (`priority` in ('low','medium','high')),
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `phasetask`
--
DELIMITER $$
CREATE TRIGGER `checkPhaseTaskDatesBeforeUpdate` BEFORE UPDATE ON `phasetask` FOR EACH ROW BEGIN
        -- Only validate startDateTime if it was actually changed
        IF NEW.startDateTime <> OLD.startDateTime THEN IF NEW.startDateTime IS NOT NULL AND NEW.startDateTime < CURRENT_DATE() THEN SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT
        = 'startDateTime cannot be in the past.' ;
        END IF ;
    END IF ;
    -- Only validate completionDateTime if it was actually changed
    IF NEW.completionDateTime <> OLD.completionDateTime THEN IF NEW.completionDateTime IS NOT NULL AND NEW.completionDateTime <= NEW.startDateTime THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'completionDateTime must be later than startDateTime.' ;
END IF ;
END IF ;
-- Only validate actualCompletionDateTime if it was actually changed
IF NEW.actualCompletionDateTime <> OLD.actualCompletionDateTime THEN IF NEW.actualCompletionDateTime IS NOT NULL AND NEW.actualCompletionDateTime <= NEW.startDateTime THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'actualCompletionDateTime must be later than startDateTime.' ;
END IF ;
END IF ;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `completePhaseTaskTrigger` BEFORE UPDATE ON `phasetask` FOR EACH ROW BEGIN
        IF NEW.status = 'completed' THEN
    SET NEW
        .actualCompletionDateTime = CURRENT_TIMESTAMP ;
    END IF ; END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `phasetaskworker`
--

CREATE TABLE `phasetaskworker` (
  `id` int(11) NOT NULL,
  `taskId` int(11) NOT NULL,
  `workerId` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

CREATE TABLE `project` (
  `id` int(11) NOT NULL,
  `publicId` binary(16) NOT NULL,
  `managerId` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
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
        -- Cancel all phases
        UPDATE projectPhase
        SET status = 'cancelled'
        WHERE projectId = NEW.id;

        -- Cancel all tasks in all phases of this project
        UPDATE phaseTask
        SET status = 'cancelled'
        WHERE phaseId IN (
            SELECT id FROM projectPhase WHERE projectId = NEW.id
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `checkProjectDatesBeforeUpdate` BEFORE UPDATE ON `project` FOR EACH ROW BEGIN
        -- Only validate startDateTime if it was actually changed
        IF NEW.startDateTime <> OLD.startDateTime THEN IF NEW.startDateTime IS NOT NULL AND NEW.startDateTime < CURRENT_DATE() THEN SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT
        = 'startDateTime cannot be in the past.' ;
        END IF ;
    END IF ;
    -- Only validate completionDateTime if it was actually changed
    IF NEW.completionDateTime <> OLD.completionDateTime THEN IF NEW.completionDateTime IS NOT NULL AND NEW.completionDateTime <= NEW.startDateTime THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'completionDateTime must be later than startDateTime.' ;
END IF ;
END IF ;
-- Only validate actualCompletionDateTime if it was actually changed
IF NEW.actualCompletionDateTime <> OLD.actualCompletionDateTime THEN IF NEW.actualCompletionDateTime IS NOT NULL AND NEW.actualCompletionDateTime <= NEW.startDateTime THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'actualCompletionDateTime must be later than startDateTime.' ;
END IF ;
END IF ;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `completeProjectTrigger` BEFORE UPDATE ON `project` FOR EACH ROW BEGIN
        IF NEW.status = 'completed' THEN
    SET NEW
        .actualCompletionDateTime = CURRENT_TIMESTAMP ;
    END IF ; END
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
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `startDateTime` datetime NOT NULL,
  `completionDateTime` datetime NOT NULL,
  `actualCompletionDateTime` datetime DEFAULT NULL,
  `status` varchar(25) NOT NULL CHECK (`status` in ('pending','onGoing','completed','delayed','cancelled'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `projectphase`
--
DELIMITER $$
CREATE TRIGGER `checkProjectPhaseDatesBeforeUpdate` BEFORE UPDATE ON `projectphase` FOR EACH ROW BEGIN
        -- Only validate startDateTime if it was actually changed
        IF NEW.startDateTime <> OLD.startDateTime THEN IF NEW.startDateTime IS NOT NULL AND NEW.startDateTime < CURRENT_DATE() THEN SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT
        = 'startDateTime cannot be in the past.' ;
        END IF ;
    END IF ;
    -- Only validate completionDateTime if it was actually changed
    IF NEW.completionDateTime <> OLD.completionDateTime THEN IF NEW.completionDateTime IS NOT NULL AND NEW.completionDateTime <= NEW.startDateTime THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'completionDateTime must be later than startDateTime.' ;
END IF ;
END IF ;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `completeProjectPhaseTrigger` BEFORE UPDATE ON `projectphase` FOR EACH ROW BEGIN
        IF NEW.status = 'completed' THEN
    SET NEW
        .actualCompletionDateTime = CURRENT_TIMESTAMP ;
    END IF ; END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `projectworker`
--

CREATE TABLE `projectworker` (
  `id` int(11) NOT NULL,
  `workerId` int(11) NOT NULL,
  `projectId` int(11) NOT NULL,
  `status` varchar(25) DEFAULT NULL CHECK (`status` in ('assigned','terminated'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `projectworker`
--
DELIMITER $$
CREATE TRIGGER `terminateWorkerTasksOnProjectWorkerTermination` AFTER UPDATE ON `projectworker` FOR EACH ROW BEGIN
    -- When a worker is terminated from a project, terminate all their active task assignments in that project
    IF NEW.status = 'terminated' AND OLD.status <> 'terminated' THEN
        UPDATE `phaseTaskWorker` AS ptw
        INNER JOIN `phaseTask` AS pt ON ptw.taskId = pt.id
        INNER JOIN `projectPhase` AS pp ON pt.phaseId = pp.id
        SET ptw.status = 'terminated'
        WHERE ptw.workerId = NEW.workerId
        AND pp.projectId = NEW.projectId
        AND ptw.status = 'assigned';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `temporarylink`
--

CREATE TABLE `temporarylink` (
  `id` int(11) NOT NULL,
  `userEmail` varchar(255) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `publicId` binary(16) DEFAULT NULL,
  `firstName` varchar(50) NOT NULL,
  `middleName` varchar(50) DEFAULT NULL,
  `lastName` varchar(50) NOT NULL,
  `gender` varchar(10) NOT NULL CHECK (`gender` in ('male','female')),
  `birthDate` date NOT NULL,
  `role` varchar(20) NOT NULL,
  `contactNumber` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `bio` varchar(1000) DEFAULT NULL,
  `profileLink` varchar(255) DEFAULT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `confirmedAt` datetime DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `user`
--
DELIMITER $$
CREATE TRIGGER `anonymizeUserOnDelete` BEFORE UPDATE ON `user` FOR EACH ROW BEGIN
    -- When deletedAt is set to a non-NULL value, anonymize sensitive user attributes
    IF NEW.deletedAt IS NOT NULL AND OLD.deletedAt IS NULL THEN
        -- Anonymize personal information
        SET NEW.firstName = 'Deleted';
        SET NEW.middleName = NULL;
        SET NEW.lastName = 'User';
        SET NEW.email = CONCAT(SUBSTRING(REPLACE(UUID(), '-', ''), 1, 5), '_del@deleted.local');
        SET NEW.contactNumber = SUBSTRING(REPLACE(UUID(), '-', ''), 1, 11);
        SET NEW.bio = NULL;
        SET NEW.profileLink = NULL;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `checkUserAgeBeforeInsert` BEFORE INSERT ON `user` FOR EACH ROW BEGIN
    IF NEW.birthDate > DATE_SUB(CURDATE(), INTERVAL 18 YEAR) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'User must be at least 18 years old.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `deleteJobTitlesOnUserDelete` AFTER UPDATE ON `user` FOR EACH ROW BEGIN
    -- When a user is deleted (deletedAt is set), remove all their job titles
    IF NEW.deletedAt IS NOT NULL AND OLD.deletedAt IS NULL THEN
        DELETE FROM `userJobTitle` WHERE userId = NEW.id;
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
-- Indexes for table `phasetask`
--
ALTER TABLE `phasetask`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `publicId` (`publicId`),
  ADD KEY `phaseId` (`phaseId`),
  ADD KEY `idx_phaseTask_phaseId` (`phaseId`),
  ADD KEY `idx_phaseTask_status` (`status`),
  ADD KEY `idx_phaseTask_priority` (`priority`);
ALTER TABLE `phasetask` ADD FULLTEXT KEY `name` (`name`,`description`);

--
-- Indexes for table `phasetaskworker`
--
ALTER TABLE `phasetaskworker`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `taskId_workerId` (`taskId`,`workerId`),
  ADD KEY `workerId` (`workerId`),
  ADD KEY `idx_phaseTaskWorker_taskId` (`taskId`),
  ADD KEY `idx_phaseTaskWorker_workerId` (`workerId`),
  ADD KEY `idx_phaseTaskWorker_status` (`status`);

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
-- Indexes for table `projectworker`
--
ALTER TABLE `projectworker`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `workerId` (`workerId`,`projectId`),
  ADD KEY `projectWorkerWorkerIdIndex` (`workerId`),
  ADD KEY `projectId` (`projectId`);

--
-- Indexes for table `temporarylink`
--
ALTER TABLE `temporarylink`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniqueToken` (`token`),
  ADD UNIQUE KEY `userEmail` (`userEmail`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uniqueContactNumber` (`contactNumber`),
  ADD UNIQUE KEY `publicId` (`publicId`);
ALTER TABLE `user` ADD FULLTEXT KEY `userFullTextIndex` (`firstName`,`middleName`,`lastName`,`bio`,`email`);

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
-- AUTO_INCREMENT for table `phasetask`
--
ALTER TABLE `phasetask`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phasetaskworker`
--
ALTER TABLE `phasetaskworker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project`
--
ALTER TABLE `project`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projectphase`
--
ALTER TABLE `projectphase`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projectworker`
--
ALTER TABLE `projectworker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `temporarylink`
--
ALTER TABLE `temporarylink`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `userjobtitle`
--
ALTER TABLE `userjobtitle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `phasetask`
--
ALTER TABLE `phasetask`
  ADD CONSTRAINT `phaseTask_ibfk_1` FOREIGN KEY (`phaseId`) REFERENCES `projectphase` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `phasetaskworker`
--
ALTER TABLE `phasetaskworker`
  ADD CONSTRAINT `phaseTaskWorker_ibfk_1` FOREIGN KEY (`taskId`) REFERENCES `phasetask` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `phaseTaskWorker_ibfk_2` FOREIGN KEY (`workerId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `projectworker`
--
ALTER TABLE `projectworker`
  ADD CONSTRAINT `projectworker_ibfk_1` FOREIGN KEY (`workerId`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projectworker_ibfk_2` FOREIGN KEY (`projectId`) REFERENCES `project` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `temporarylink`
--
ALTER TABLE `temporarylink`
  ADD CONSTRAINT `temporarylink_ibfk_1` FOREIGN KEY (`userEmail`) REFERENCES `user` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `userjobtitle`
--
ALTER TABLE `userjobtitle`
  ADD CONSTRAINT `userjobtitle_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
