-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: taskflow
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `phase_task`
--

DROP TABLE IF EXISTS `phase_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phase_task` (
  `id` int NOT NULL AUTO_INCREMENT,
  `public_id` binary(16) NOT NULL,
  `phase_id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date_time` datetime NOT NULL,
  `completion_date_time` datetime NOT NULL,
  `actual_completion_date_time` datetime DEFAULT NULL,
  `status` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_id` (`public_id`),
  KEY `phase_id` (`phase_id`),
  KEY `idx_phase_task_phase_id` (`phase_id`),
  KEY `idx_phase_task_status` (`status`),
  KEY `idx_phase_task_priority` (`priority`),
  FULLTEXT KEY `name` (`name`,`description`),
  CONSTRAINT `phase_task_ibfk_1` FOREIGN KEY (`phase_id`) REFERENCES `project_phase` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `phase_task_chk_1` CHECK ((`status` in (_utf8mb4'pending',_utf8mb4'onGoing',_utf8mb4'completed',_utf8mb4'delayed',_utf8mb4'cancelled'))),
  CONSTRAINT `phase_task_chk_2` CHECK ((`priority` in (_utf8mb4'low',_utf8mb4'medium',_utf8mb4'high')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phase_task`
--

LOCK TABLES `phase_task` WRITE;
/*!40000 ALTER TABLE `phase_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `phase_task` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `check_phase_task_dates_before_update` BEFORE UPDATE ON `phase_task` FOR EACH ROW BEGIN
        
        IF NEW.start_date_time <> OLD.start_date_time THEN IF NEW.start_date_time IS NOT NULL AND NEW.start_date_time < CURRENT_DATE() THEN SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT
        = 'start_date_time cannot be in the past.' ;
        END IF ;
    END IF ;
    
    IF NEW.completion_date_time <> OLD.completion_date_time THEN IF NEW.completion_date_time IS NOT NULL AND NEW.completion_date_time <= NEW.start_date_time THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'completion_date_time must be later than start_date_time.' ;
END IF ;
END IF ;

IF NEW.actual_completion_date_time <> OLD.actual_completion_date_time THEN IF NEW.actual_completion_date_time IS NOT NULL AND NEW.actual_completion_date_time <= NEW.start_date_time THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'actual_completion_date_time must be later than start_date_time.' ;
END IF ;
END IF ;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `complete_phase_task_trigger` BEFORE UPDATE ON `phase_task` FOR EACH ROW BEGIN
        IF NEW.status = 'completed' THEN
    SET NEW
        .actual_completion_date_time = CURRENT_TIMESTAMP ;
    END IF ; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `phase_task_worker`
--

DROP TABLE IF EXISTS `phase_task_worker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phase_task_worker` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `worker_id` int NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_id_worker_id` (`task_id`,`worker_id`),
  KEY `worker_id` (`worker_id`),
  KEY `idx_phase_task_worker_task_id` (`task_id`),
  KEY `idx_phase_task_worker_worker_id` (`worker_id`),
  KEY `idx_phase_task_worker_status` (`status`),
  CONSTRAINT `phase_task_worker_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `phase_task` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `phase_task_worker_ibfk_2` FOREIGN KEY (`worker_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phase_task_worker`
--

LOCK TABLES `phase_task_worker` WRITE;
/*!40000 ALTER TABLE `phase_task_worker` DISABLE KEYS */;
/*!40000 ALTER TABLE `phase_task_worker` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project`
--

DROP TABLE IF EXISTS `project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project` (
  `id` int NOT NULL AUTO_INCREMENT,
  `public_id` binary(16) NOT NULL,
  `manager_id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget` decimal(21,4) NOT NULL,
  `start_date_time` datetime NOT NULL,
  `completion_date_time` datetime NOT NULL,
  `actual_completion_date_time` datetime DEFAULT NULL,
  `status` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_id` (`public_id`),
  KEY `project_manager_id_index` (`manager_id`),
  FULLTEXT KEY `project_fulltext_index` (`name`,`description`),
  CONSTRAINT `project_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_chk_1` CHECK ((`status` in (_utf8mb4'pending',_utf8mb4'onGoing',_utf8mb4'completed',_utf8mb4'delayed',_utf8mb4'cancelled')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project`
--

LOCK TABLES `project` WRITE;
/*!40000 ALTER TABLE `project` DISABLE KEYS */;
/*!40000 ALTER TABLE `project` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `check_project_dates_before_update` BEFORE UPDATE ON `project` FOR EACH ROW BEGIN
        
        IF NEW.start_date_time <> OLD.start_date_time THEN IF NEW.start_date_time IS NOT NULL AND NEW.start_date_time < CURRENT_DATE() THEN SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT
        = 'start_date_time cannot be in the past.' ;
        END IF ;
    END IF ;
    
    IF NEW.completion_date_time <> OLD.completion_date_time THEN IF NEW.completion_date_time IS NOT NULL AND NEW.completion_date_time <= NEW.start_date_time THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'completion_date_time must be later than start_date_time.' ;
END IF ;
END IF ;

IF NEW.actual_completion_date_time <> OLD.actual_completion_date_time THEN IF NEW.actual_completion_date_time IS NOT NULL AND NEW.actual_completion_date_time <= NEW.start_date_time THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'actual_completion_date_time must be later than start_date_time.' ;
END IF ;
END IF ;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `complete_project_trigger` BEFORE UPDATE ON `project` FOR EACH ROW BEGIN
        IF NEW.status = 'completed' THEN
    SET NEW
        .actual_completion_date_time = CURRENT_TIMESTAMP ;
    END IF ; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `cancel_project` AFTER UPDATE ON `project` FOR EACH ROW BEGIN
    
    IF NEW.status = 'cancelled' AND OLD.status <> 'cancelled' THEN
        
        UPDATE project_phase
        SET status = 'cancelled'
        WHERE project_id = NEW.id;

        
        UPDATE phase_task
        SET status = 'cancelled'
        WHERE phase_id IN (
            SELECT id FROM project_phase WHERE project_id = NEW.id
        );
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `project_phase`
--

DROP TABLE IF EXISTS `project_phase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_phase` (
  `id` int NOT NULL AUTO_INCREMENT,
  `public_id` binary(16) NOT NULL,
  `project_id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date_time` datetime NOT NULL,
  `completion_date_time` datetime NOT NULL,
  `actual_completion_date_time` datetime DEFAULT NULL,
  `status` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_id` (`public_id`),
  KEY `project_phase_project_id_index` (`project_id`),
  CONSTRAINT `project_phase_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_phase_chk_1` CHECK ((`status` in (_utf8mb4'pending',_utf8mb4'onGoing',_utf8mb4'completed',_utf8mb4'delayed',_utf8mb4'cancelled')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_phase`
--

LOCK TABLES `project_phase` WRITE;
/*!40000 ALTER TABLE `project_phase` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_phase` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `check_project_phase_dates_before_update` BEFORE UPDATE ON `project_phase` FOR EACH ROW BEGIN
        
        IF NEW.start_date_time <> OLD.start_date_time THEN IF NEW.start_date_time IS NOT NULL AND NEW.start_date_time < CURRENT_DATE() THEN SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT
        = 'start_date_time cannot be in the past.' ;
        END IF ;
    END IF ;
    
    IF NEW.completion_date_time <> OLD.completion_date_time THEN IF NEW.completion_date_time IS NOT NULL AND NEW.completion_date_time <= NEW.start_date_time THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'completion_date_time must be later than start_date_time.' ;
END IF ;
END IF ;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `complete_project_phase_trigger` BEFORE UPDATE ON `project_phase` FOR EACH ROW BEGIN
        IF NEW.status = 'completed' THEN
    SET NEW
        .actual_completion_date_time = CURRENT_TIMESTAMP ;
    END IF ; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `project_worker`
--

DROP TABLE IF EXISTS `project_worker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_worker` (
  `id` int NOT NULL AUTO_INCREMENT,
  `worker_id` int NOT NULL,
  `project_id` int NOT NULL,
  `status` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_id` (`worker_id`,`project_id`),
  KEY `project_worker_worker_id_index` (`worker_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `project_worker_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_worker_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_worker_chk_1` CHECK ((`status` in (_utf8mb4'assigned',_utf8mb4'terminated')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_worker`
--

LOCK TABLES `project_worker` WRITE;
/*!40000 ALTER TABLE `project_worker` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_worker` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `terminate_worker_tasks_on_project_worker_termination` AFTER UPDATE ON `project_worker` FOR EACH ROW BEGIN
    
    IF NEW.status = 'terminated' AND OLD.status <> 'terminated' THEN
        UPDATE `phase_task_worker` AS ptw
        INNER JOIN `phase_task` AS pt ON ptw.task_id = pt.id
        INNER JOIN `project_phase` AS pp ON pt.phase_id = pp.id
        SET ptw.status = 'terminated'
        WHERE ptw.worker_id = NEW.worker_id
        AND pp.project_id = NEW.project_id
        AND ptw.status = 'assigned';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `rate_limiter`
--

DROP TABLE IF EXISTS `rate_limiter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rate_limiter` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `endpoint` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `count` int NOT NULL DEFAULT '1',
  `expires_at` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`,`endpoint`),
  CONSTRAINT `rate_limiter_chk_1` CHECK ((`expires_at` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_limiter`
--

LOCK TABLES `rate_limiter` WRITE;
/*!40000 ALTER TABLE `rate_limiter` DISABLE KEYS */;
/*!40000 ALTER TABLE `rate_limiter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temporary_link`
--

DROP TABLE IF EXISTS `temporary_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `temporary_link` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  UNIQUE KEY `user_email` (`user_email`),
  CONSTRAINT `temporary_link_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `user` (`email`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temporary_link`
--

LOCK TABLES `temporary_link` WRITE;
/*!40000 ALTER TABLE `temporary_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `temporary_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `public_id` binary(16) DEFAULT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date NOT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bio` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `confirmed_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `unique_contact_number` (`contact_number`),
  UNIQUE KEY `public_id` (`public_id`),
  FULLTEXT KEY `user_full_text_index` (`first_name`,`middle_name`,`last_name`,`bio`,`email`),
  CONSTRAINT `user_chk_1` CHECK ((`gender` in (_utf8mb4'male',_utf8mb4'female')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `check_user_age_before_insert` BEFORE INSERT ON `user` FOR EACH ROW BEGIN
    IF NEW.birth_date > DATE_SUB(CURDATE(), INTERVAL 18 YEAR) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'User must be at least 18 years old.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `anonymize_user_on_delete` BEFORE UPDATE ON `user` FOR EACH ROW BEGIN
    
    IF NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL THEN
        
        SET NEW.first_name = 'Deleted';
        SET NEW.middle_name = NULL;
        SET NEW.last_name = 'User';
        SET NEW.email = CONCAT(SUBSTRING(REPLACE(UUID(), '-', ''), 1, 5), '_del@deleted.local');
        SET NEW.contact_number = SUBSTRING(REPLACE(UUID(), '-', ''), 1, 11);
        SET NEW.bio = NULL;
        SET NEW.profile_link = NULL;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER `delete_job_titles_on_user_delete` AFTER UPDATE ON `user` FOR EACH ROW BEGIN
    
    IF NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL THEN
        DELETE FROM `user_job_title` WHERE user_id = NEW.id;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `user_job_title`
--

DROP TABLE IF EXISTS `user_job_title`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_job_title` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`title`),
  KEY `user_job_title_id_index` (`user_id`),
  CONSTRAINT `user_job_title_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_job_title`
--

LOCK TABLES `user_job_title` WRITE;
/*!40000 ALTER TABLE `user_job_title` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_job_title` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'taskflow'
--
/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;
/*!50106 DROP EVENT IF EXISTS `update_phase_status_daily` */;
DELIMITER ;;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;;
/*!50003 SET character_set_client  = utf8mb4 */ ;;
/*!50003 SET character_set_results = utf8mb4 */ ;;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;;
/*!50003 SET @saved_time_zone      = @@time_zone */ ;;
/*!50003 SET time_zone             = 'SYSTEM' */ ;;
/*!50106 CREATE*/ /*!50117 */ /*!50106 EVENT `update_phase_status_daily` ON SCHEDULE EVERY 1 DAY STARTS '2025-12-17 00:10:00' ON COMPLETION PRESERVE ENABLE COMMENT 'Automatically updates phase status based on start_date_time and completion_date_time' DO BEGIN
    
    UPDATE `project_phase`
    SET `status` = 'onGoing'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) > CURRENT_DATE;
    
    
    UPDATE `project_phase`
    SET `status` = 'delayed'
    WHERE `status` = 'onGoing'
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
    
    
    UPDATE `project_phase`
    SET `status` = 'delayed'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
    
    
    UPDATE `project_phase` AS pp
    SET `status` = 'completed',
        `actual_completion_date_time` = CURRENT_TIMESTAMP
    WHERE `status` IN ('onGoing', 'delayed')
      AND (DATE(`actual_completion_date_time`)) IS NULL
      AND NOT EXISTS (
          SELECT 1
          FROM `phase_task` AS pt
          WHERE pt.phase_id = pp.id
            AND pt.status != 'completed'
            AND pt.status != 'cancelled'
      )
      AND EXISTS (
          SELECT 1
          FROM `phase_task` AS pt2
          WHERE pt2.phase_id = pp.id
            AND pt2.status = 'completed'
      );
END */ ;;
/*!50003 SET time_zone             = @saved_time_zone */ ;;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;;
/*!50003 SET character_set_client  = @saved_cs_client */ ;;
/*!50003 SET character_set_results = @saved_cs_results */ ;;
/*!50003 SET collation_connection  = @saved_col_connection */ ;;
/*!50106 DROP EVENT IF EXISTS `update_project_status_daily` */;;
DELIMITER ;;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;;
/*!50003 SET character_set_client  = utf8mb4 */ ;;
/*!50003 SET character_set_results = utf8mb4 */ ;;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;;
/*!50003 SET @saved_time_zone      = @@time_zone */ ;;
/*!50003 SET time_zone             = 'SYSTEM' */ ;;
/*!50106 CREATE*/ /*!50117 */ /*!50106 EVENT `update_project_status_daily` ON SCHEDULE EVERY 1 DAY STARTS '2025-12-17 00:15:00' ON COMPLETION PRESERVE ENABLE COMMENT 'Automatically updates project status based on start_date_time and completion_date_time' DO BEGIN
    
    UPDATE `project`
    SET `status` = 'onGoing'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) > CURRENT_DATE;
    
    
    UPDATE `project`
    SET `status` = 'delayed'
    WHERE `status` = 'onGoing'
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
    
    
    UPDATE `project`
    SET `status` = 'delayed'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
    
    
    UPDATE `project` AS p
    SET `status` = 'completed',
        `actual_completion_date_time` = CURRENT_TIMESTAMP
    WHERE `status` IN ('onGoing', 'delayed')
      AND (DATE(`actual_completion_date_time`)) IS NULL
      AND NOT EXISTS (
          SELECT 1
          FROM `project_phase` AS pp
          WHERE pp.project_id = p.id
            AND pp.status != 'completed'
            AND pp.status != 'cancelled'
      )
      AND EXISTS (
          SELECT 1
          FROM `project_phase` AS pp2
          WHERE pp2.project_id = p.id
            AND pp2.status = 'completed'
      );
END */ ;;
/*!50003 SET time_zone             = @saved_time_zone */ ;;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;;
/*!50003 SET character_set_client  = @saved_cs_client */ ;;
/*!50003 SET character_set_results = @saved_cs_results */ ;;
/*!50003 SET collation_connection  = @saved_col_connection */ ;;
/*!50106 DROP EVENT IF EXISTS `update_task_status_daily` */;;
DELIMITER ;;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;;
/*!50003 SET character_set_client  = utf8mb4 */ ;;
/*!50003 SET character_set_results = utf8mb4 */ ;;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;;
/*!50003 SET @saved_time_zone      = @@time_zone */ ;;
/*!50003 SET time_zone             = 'SYSTEM' */ ;;
/*!50106 CREATE*/ /*!50117 */ /*!50106 EVENT `update_task_status_daily` ON SCHEDULE EVERY 1 DAY STARTS '2025-12-17 00:05:00' ON COMPLETION PRESERVE ENABLE COMMENT 'Automatically updates task status based on start_date_time and completion_date_time' DO BEGIN
    
    UPDATE `phase_task`
    SET `status` = 'onGoing'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) > CURRENT_DATE;
    
    
    UPDATE `phase_task`
    SET `status` = 'delayed'
    WHERE `status` = 'onGoing'
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
    
    
    
    UPDATE `phase_task`
    SET `status` = 'delayed'
    WHERE `status` = 'pending'
      AND DATE(`start_date_time`) <= CURRENT_DATE
      AND DATE(`completion_date_time`) < CURRENT_DATE
      AND (DATE(`actual_completion_date_time`)) IS NULL;
      
    
    
END */ ;;
/*!50003 SET time_zone             = @saved_time_zone */ ;;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;;
/*!50003 SET character_set_client  = @saved_cs_client */ ;;
/*!50003 SET character_set_results = @saved_cs_results */ ;;
/*!50003 SET collation_connection  = @saved_col_connection */ ;;
/*!50106 DROP EVENT IF EXISTS `update_task_status_hourly` */;;
DELIMITER ;;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;;
/*!50003 SET character_set_client  = utf8mb4 */ ;;
/*!50003 SET character_set_results = utf8mb4 */ ;;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;;
/*!50003 SET @saved_time_zone      = @@time_zone */ ;;
/*!50003 SET time_zone             = 'SYSTEM' */ ;;
/*!50106 CREATE*/ /*!50117 */ /*!50106 EVENT `update_task_status_hourly` ON SCHEDULE EVERY 1 HOUR STARTS '2025-12-16 06:00:00' ON COMPLETION PRESERVE ENABLE COMMENT 'Hourly task status updates during business hours for more immediate feedback' DO BEGIN
    
    IF HOUR(CURRENT_DATE) BETWEEN 6 AND 22 THEN
        
        UPDATE `phase_task`
        SET `status` = 'onGoing'
        WHERE `status` = 'pending'
          AND `start_date_time` <= CURRENT_DATE
          AND `completion_date_time` > CURRENT_DATE;
        
        
        UPDATE `phase_task`
        SET `status` = 'delayed'
        WHERE `status` = 'onGoing'
          AND `completion_date_time` < CURRENT_DATE
          AND `actual_completion_date_time` IS NULL;
        
        
        UPDATE `phase_task`
        SET `status` = 'delayed'
        WHERE `status` = 'pending'
          AND `start_date_time` <= CURRENT_DATE
          AND `completion_date_time` < CURRENT_DATE
          AND `actual_completion_date_time` IS NULL;
    END IF;
END */ ;;
/*!50003 SET time_zone             = @saved_time_zone */ ;;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;;
/*!50003 SET character_set_client  = @saved_cs_client */ ;;
/*!50003 SET character_set_results = @saved_cs_results */ ;;
/*!50003 SET collation_connection  = @saved_col_connection */ ;;
DELIMITER ;
/*!50106 SET TIME_ZONE= @save_time_zone */ ;

--
-- Dumping routines for database 'taskflow'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-17 16:53:47
