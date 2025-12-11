-- phpMyAdmin SQL Dump
-- TaskFlow Database Schema (snake_case naming convention)
-- Converted from camelCase to snake_case
-- Generated: December 10, 2025

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
-- Table structure for table `phase_task`
--

CREATE TABLE `phase_task` (
  `id` int(11) NOT NULL,
  `public_id` binary(16) NOT NULL,
  `phase_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `start_date_time` datetime NOT NULL,
  `completion_date_time` datetime NOT NULL,
  `actual_completion_date_time` datetime DEFAULT NULL,
  `status` varchar(25) NOT NULL CHECK (`status` in ('pending','onGoing','completed','delayed','cancelled')),
  `priority` varchar(25) NOT NULL CHECK (`priority` in ('low','medium','high')),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `phase_task`
--
DELIMITER $$
CREATE TRIGGER `check_phase_task_dates_before_update` BEFORE UPDATE ON `phase_task` FOR EACH ROW BEGIN
        -- Only validate start_date_time if it was actually changed
        IF NEW.start_date_time <> OLD.start_date_time THEN IF NEW.start_date_time IS NOT NULL AND NEW.start_date_time < CURRENT_DATE() THEN SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT
        = 'start_date_time cannot be in the past.' ;
        END IF ;
    END IF ;
    -- Only validate completion_date_time if it was actually changed
    IF NEW.completion_date_time <> OLD.completion_date_time THEN IF NEW.completion_date_time IS NOT NULL AND NEW.completion_date_time <= NEW.start_date_time THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'completion_date_time must be later than start_date_time.' ;
END IF ;
END IF ;
-- Only validate actual_completion_date_time if it was actually changed
IF NEW.actual_completion_date_time <> OLD.actual_completion_date_time THEN IF NEW.actual_completion_date_time IS NOT NULL AND NEW.actual_completion_date_time <= NEW.start_date_time THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'actual_completion_date_time must be later than start_date_time.' ;
END IF ;
END IF ;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `complete_phase_task_trigger` BEFORE UPDATE ON `phase_task` FOR EACH ROW BEGIN
        IF NEW.status = 'completed' THEN
    SET NEW
        .actual_completion_date_time = CURRENT_TIMESTAMP ;
    END IF ; END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `phase_task_worker`
--

CREATE TABLE `phase_task_worker` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

CREATE TABLE `project` (
  `id` int(11) NOT NULL,
  `public_id` binary(16) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `budget` decimal(21,4) NOT NULL,
  `start_date_time` datetime NOT NULL,
  `completion_date_time` datetime NOT NULL,
  `actual_completion_date_time` datetime DEFAULT NULL,
  `status` varchar(25) NOT NULL CHECK (`status` in ('pending','onGoing','completed','delayed','cancelled')),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `project`
--
DELIMITER $$
CREATE TRIGGER `cancel_project` AFTER UPDATE ON `project` FOR EACH ROW BEGIN
    -- Only act when status changes to 'cancelled'
    IF NEW.status = 'cancelled' AND OLD.status <> 'cancelled' THEN
        -- Cancel all phases
        UPDATE project_phase
        SET status = 'cancelled'
        WHERE project_id = NEW.id;

        -- Cancel all tasks in all phases of this project
        UPDATE phase_task
        SET status = 'cancelled'
        WHERE phase_id IN (
            SELECT id FROM project_phase WHERE project_id = NEW.id
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `check_project_dates_before_update` BEFORE UPDATE ON `project` FOR EACH ROW BEGIN
        -- Only validate start_date_time if it was actually changed
        IF NEW.start_date_time <> OLD.start_date_time THEN IF NEW.start_date_time IS NOT NULL AND NEW.start_date_time < CURRENT_DATE() THEN SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT
        = 'start_date_time cannot be in the past.' ;
        END IF ;
    END IF ;
    -- Only validate completion_date_time if it was actually changed
    IF NEW.completion_date_time <> OLD.completion_date_time THEN IF NEW.completion_date_time IS NOT NULL AND NEW.completion_date_time <= NEW.start_date_time THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'completion_date_time must be later than start_date_time.' ;
END IF ;
END IF ;
-- Only validate actual_completion_date_time if it was actually changed
IF NEW.actual_completion_date_time <> OLD.actual_completion_date_time THEN IF NEW.actual_completion_date_time IS NOT NULL AND NEW.actual_completion_date_time <= NEW.start_date_time THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'actual_completion_date_time must be later than start_date_time.' ;
END IF ;
END IF ;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `complete_project_trigger` BEFORE UPDATE ON `project` FOR EACH ROW BEGIN
        IF NEW.status = 'completed' THEN
    SET NEW
        .actual_completion_date_time = CURRENT_TIMESTAMP ;
    END IF ; END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `project_phase`
--

CREATE TABLE `project_phase` (
  `id` int(11) NOT NULL,
  `public_id` binary(16) NOT NULL,
  `project_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `start_date_time` datetime NOT NULL,
  `completion_date_time` datetime NOT NULL,
  `actual_completion_date_time` datetime DEFAULT NULL,
  `status` varchar(25) NOT NULL CHECK (`status` in ('pending','onGoing','completed','delayed','cancelled'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `project_phase`
--
DELIMITER $$
CREATE TRIGGER `check_project_phase_dates_before_update` BEFORE UPDATE ON `project_phase` FOR EACH ROW BEGIN
        -- Only validate start_date_time if it was actually changed
        IF NEW.start_date_time <> OLD.start_date_time THEN IF NEW.start_date_time IS NOT NULL AND NEW.start_date_time < CURRENT_DATE() THEN SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT
        = 'start_date_time cannot be in the past.' ;
        END IF ;
    END IF ;
    -- Only validate completion_date_time if it was actually changed
    IF NEW.completion_date_time <> OLD.completion_date_time THEN IF NEW.completion_date_time IS NOT NULL AND NEW.completion_date_time <= NEW.start_date_time THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'completion_date_time must be later than start_date_time.' ;
END IF ;
END IF ;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `complete_project_phase_trigger` BEFORE UPDATE ON `project_phase` FOR EACH ROW BEGIN
        IF NEW.status = 'completed' THEN
    SET NEW
        .actual_completion_date_time = CURRENT_TIMESTAMP ;
    END IF ; END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `project_worker`
--

CREATE TABLE `project_worker` (
  `id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `status` varchar(25) DEFAULT NULL CHECK (`status` in ('assigned','terminated'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `project_worker`
--
DELIMITER $$
CREATE TRIGGER `terminate_worker_tasks_on_project_worker_termination` AFTER UPDATE ON `project_worker` FOR EACH ROW BEGIN
    -- When a worker is terminated from a project, terminate all their active task assignments in that project
    IF NEW.status = 'terminated' AND OLD.status <> 'terminated' THEN
        UPDATE `phase_task_worker` AS ptw
        INNER JOIN `phase_task` AS pt ON ptw.task_id = pt.id
        INNER JOIN `project_phase` AS pp ON pt.phase_id = pp.id
        SET ptw.status = 'terminated'
        WHERE ptw.worker_id = NEW.worker_id
        AND pp.project_id = NEW.project_id
        AND ptw.status = 'assigned';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `temporary_link`
--

CREATE TABLE `temporary_link` (
  `id` int(11) NOT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `public_id` binary(16) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` varchar(10) NOT NULL CHECK (`gender` in ('male','female')),
  `birth_date` date NOT NULL,
  `role` varchar(20) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `bio` varchar(1000) DEFAULT NULL,
  `profile_link` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `user`
--
DELIMITER $$
CREATE TRIGGER `anonymize_user_on_delete` BEFORE UPDATE ON `user` FOR EACH ROW BEGIN
    -- When deleted_at is set to a non-NULL value, anonymize sensitive user attributes
    IF NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL THEN
        -- Anonymize personal information
        SET NEW.first_name = 'Deleted';
        SET NEW.middle_name = NULL;
        SET NEW.last_name = 'User';
        SET NEW.email = CONCAT(SUBSTRING(REPLACE(UUID(), '-', ''), 1, 5), '_del@deleted.local');
        SET NEW.contact_number = SUBSTRING(REPLACE(UUID(), '-', ''), 1, 11);
        SET NEW.bio = NULL;
        SET NEW.profile_link = NULL;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `check_user_age_before_insert` BEFORE INSERT ON `user` FOR EACH ROW BEGIN
    IF NEW.birth_date > DATE_SUB(CURDATE(), INTERVAL 18 YEAR) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'User must be at least 18 years old.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `delete_job_titles_on_user_delete` AFTER UPDATE ON `user` FOR EACH ROW BEGIN
    -- When a user is deleted (deleted_at is set), remove all their job titles
    IF NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL THEN
        DELETE FROM `user_job_title` WHERE user_id = NEW.id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_job_title`
--

CREATE TABLE `user_job_title` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `phase_task`
--
ALTER TABLE `phase_task`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `public_id` (`public_id`),
  ADD KEY `phase_id` (`phase_id`),
  ADD KEY `idx_phase_task_phase_id` (`phase_id`),
  ADD KEY `idx_phase_task_status` (`status`),
  ADD KEY `idx_phase_task_priority` (`priority`);
ALTER TABLE `phase_task` ADD FULLTEXT KEY `name` (`name`,`description`);

--
-- Indexes for table `phase_task_worker`
--
ALTER TABLE `phase_task_worker`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_id_worker_id` (`task_id`,`worker_id`),
  ADD KEY `worker_id` (`worker_id`),
  ADD KEY `idx_phase_task_worker_task_id` (`task_id`),
  ADD KEY `idx_phase_task_worker_worker_id` (`worker_id`),
  ADD KEY `idx_phase_task_worker_status` (`status`);

--
-- Indexes for table `project`
--
ALTER TABLE `project`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `public_id` (`public_id`),
  ADD KEY `project_manager_id_index` (`manager_id`);
ALTER TABLE `project` ADD FULLTEXT KEY `project_fulltext_index` (`name`,`description`);

--
-- Indexes for table `project_phase`
--
ALTER TABLE `project_phase`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `public_id` (`public_id`),
  ADD KEY `project_phase_project_id_index` (`project_id`);

--
-- Indexes for table `project_worker`
--
ALTER TABLE `project_worker`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `worker_id` (`worker_id`,`project_id`),
  ADD KEY `project_worker_worker_id_index` (`worker_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `temporary_link`
--
ALTER TABLE `temporary_link`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD UNIQUE KEY `user_email` (`user_email`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_contact_number` (`contact_number`),
  ADD UNIQUE KEY `public_id` (`public_id`);
ALTER TABLE `user` ADD FULLTEXT KEY `user_full_text_index` (`first_name`,`middle_name`,`last_name`,`bio`,`email`);

--
-- Indexes for table `user_job_title`
--
ALTER TABLE `user_job_title`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`title`),
  ADD KEY `user_job_title_id_index` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `phase_task`
--
ALTER TABLE `phase_task`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phase_task_worker`
--
ALTER TABLE `phase_task_worker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project`
--
ALTER TABLE `project`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_phase`
--
ALTER TABLE `project_phase`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_worker`
--
ALTER TABLE `project_worker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `temporary_link`
--
ALTER TABLE `temporary_link`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_job_title`
--
ALTER TABLE `user_job_title`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `phase_task`
--
ALTER TABLE `phase_task`
  ADD CONSTRAINT `phase_task_ibfk_1` FOREIGN KEY (`phase_id`) REFERENCES `project_phase` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `phase_task_worker`
--
ALTER TABLE `phase_task_worker`
  ADD CONSTRAINT `phase_task_worker_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `phase_task` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `phase_task_worker_ibfk_2` FOREIGN KEY (`worker_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `project`
--
ALTER TABLE `project`
  ADD CONSTRAINT `project_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_phase`
--
ALTER TABLE `project_phase`
  ADD CONSTRAINT `project_phase_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_worker`
--
ALTER TABLE `project_worker`
  ADD CONSTRAINT `project_worker_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_worker_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `temporary_link`
--
ALTER TABLE `temporary_link`
  ADD CONSTRAINT `temporary_link_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `user` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `user_job_title`
--
ALTER TABLE `user_job_title`
  ADD CONSTRAINT `user_job_title_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
