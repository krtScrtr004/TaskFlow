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
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phase_task`
--

LOCK TABLES `phase_task` WRITE;
/*!40000 ALTER TABLE `phase_task` DISABLE KEYS */;
INSERT INTO `phase_task` VALUES (22,_binary 'f\Z ;FJKÇQ\\É¶2w',55,'Task 1',NULL,'2026-01-11 00:00:00','2026-01-12 00:00:00',NULL,'delayed','low','2026-01-11 22:34:45','2026-01-15 15:06:22'),(23,_binary '\";Å$pCûû¡2≠°π6\‰',55,'Task Edited 1','Flank drumstick meatloaf pork loin short loin.  Buffalo pork venison chicken pastrami sirloin ball tip jowl flank fatback meatball salami.  Short ribs t-bone bresaola shoulder.  Pork loin turducken ham hock jowl short loin sirloin meatball salami filet mignon shoulder swine.  Frankfurter bresaola chicken porchetta boudin chuck beef drumstick meatloaf jowl short loin kielbasa swine beef ribs.','2026-01-15 00:00:00','2026-01-18 00:00:00',NULL,'delayed','high','2026-01-15 15:16:50','2026-01-20 22:02:29');
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
/*!50003 CREATE*/ /*!50017 DEFINER=`dbeaver`@`localhost`*/ /*!50003 TRIGGER `check_phase_task_dates_before_update` BEFORE UPDATE ON `phase_task` FOR EACH ROW BEGIN
        
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
/*!50003 CREATE*/ /*!50017 DEFINER=`dbeaver`@`localhost`*/ /*!50003 TRIGGER `complete_phase_task_trigger` BEFORE UPDATE ON `phase_task` FOR EACH ROW BEGIN
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
-- Table structure for table `phase_task_budget`
--

DROP TABLE IF EXISTS `phase_task_budget`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phase_task_budget` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `estimated_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `actual_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `note` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_id` (`task_id`),
  CONSTRAINT `fk_task_budget_task` FOREIGN KEY (`task_id`) REFERENCES `phase_task` (`id`) ON DELETE CASCADE,
  CONSTRAINT `phase_task_budget_chk_1` CHECK ((`estimated_cost` >= 0)),
  CONSTRAINT `phase_task_budget_chk_2` CHECK ((`actual_cost` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phase_task_budget`
--

LOCK TABLES `phase_task_budget` WRITE;
/*!40000 ALTER TABLE `phase_task_budget` DISABLE KEYS */;
INSERT INTO `phase_task_budget` VALUES (4,22,2000.0000,0.0000,NULL,'2026-01-11 22:34:45','2026-01-11 22:34:45'),(5,23,10001.0000,0.0000,'Shankle pancetta venison chuck shoulder capicola.  Prosciutto porchetta picanha, burgdoggen turkey jowl fatback corned beef landjaeger cow meatball tri-tip venison.  Leberkas spare ribs landjaeger prosciutto brisket short ribs corned beef burgdoggen cow pork chop.  Pig tail short ribs drumstick picanha chislic swine, shankle filet mignon doner.','2026-01-15 15:16:50','2026-01-20 22:10:53');
/*!40000 ALTER TABLE `phase_task_budget` ENABLE KEYS */;
UNLOCK TABLES;

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
  `estimated_hour` decimal(8,2) NOT NULL DEFAULT '0.00',
  `actual_hour` decimal(8,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_id_worker_id` (`task_id`,`worker_id`),
  KEY `worker_id` (`worker_id`),
  KEY `idx_phase_task_worker_task_id` (`task_id`),
  KEY `idx_phase_task_worker_worker_id` (`worker_id`),
  KEY `idx_phase_task_worker_status` (`status`),
  CONSTRAINT `phase_task_worker_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `phase_task` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `phase_task_worker_ibfk_2` FOREIGN KEY (`worker_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `phase_task_worker_ch_act` CHECK ((`actual_hour` >= 0)),
  CONSTRAINT `phase_task_worker_ch_est` CHECK ((`estimated_hour` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phase_task_worker`
--

LOCK TABLES `phase_task_worker` WRITE;
/*!40000 ALTER TABLE `phase_task_worker` DISABLE KEYS */;
INSERT INTO `phase_task_worker` VALUES (14,22,2,'assigned',8.00,0.00,'2026-01-11 22:34:47','2026-01-11 22:34:47'),(15,23,2,'terminated',3.00,0.00,'2026-01-15 15:16:50','2026-01-20 22:22:34'),(17,23,3,'terminated',5.00,0.00,'2026-01-20 22:11:01','2026-01-24 16:42:19');
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
  `max_worker` int NOT NULL DEFAULT '10',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_id` (`public_id`),
  KEY `project_manager_id_index` (`manager_id`),
  FULLTEXT KEY `project_fulltext_index` (`name`,`description`),
  CONSTRAINT `project_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_chk_1` CHECK ((`status` in (_utf8mb4'pending',_utf8mb4'onGoing',_utf8mb4'completed',_utf8mb4'delayed',_utf8mb4'cancelled'))),
  CONSTRAINT `project_chk_2` CHECK ((`max_worker` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project`
--

LOCK TABLES `project` WRITE;
/*!40000 ALTER TABLE `project` DISABLE KEYS */;
INSERT INTO `project` VALUES (37,_binary 'm•\'\⁄HéF∂,\ác\Ô',1,'Trump\'s Tower','Shoulder tail short loin pork loin drumstick pork chop tenderloin shank alcatra buffalo pig ham hock turkey.  Prosciutto pork loin tenderloin turkey beef pastrami tri-tip.  Tail short ribs flank spare ribs, pork drumstick rump salami alcatra pork chop brisket.  Ham hock bresaola venison, chislic ball tip capicola landjaeger jerky sirloin frankfurter pork chop pig ribeye.  Cow rump landjaeger tail porchetta, brisket turducken pork belly leberkas buffalo pig boudin.  Brisket cow pork chop, venison chicken meatloaf spare ribs ribeye pancetta pork corned beef.Consequat Harum omn.',75000.0000,'2025-12-25 00:00:00','2026-12-25 00:00:00',NULL,'ongoing',10,'2025-12-25 18:30:56','2025-12-31 16:42:04');
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
/*!50003 CREATE*/ /*!50017 DEFINER=`dbeaver`@`localhost`*/ /*!50003 TRIGGER `check_project_dates_before_update` BEFORE UPDATE ON `project` FOR EACH ROW BEGIN
        
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
/*!50003 CREATE*/ /*!50017 DEFINER=`dbeaver`@`localhost`*/ /*!50003 TRIGGER `complete_project_trigger` BEFORE UPDATE ON `project` FOR EACH ROW BEGIN
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
/*!50003 CREATE*/ /*!50017 DEFINER=`dbeaver`@`localhost`*/ /*!50003 TRIGGER `cancel_project` AFTER UPDATE ON `project` FOR EACH ROW BEGIN
    
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
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_id` (`public_id`),
  KEY `project_phase_project_id_index` (`project_id`),
  CONSTRAINT `project_phase_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_phase_chk_1` CHECK ((`status` in (_utf8mb4'pending',_utf8mb4'onGoing',_utf8mb4'completed',_utf8mb4'delayed',_utf8mb4'cancelled')))
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_phase`
--

LOCK TABLES `project_phase` WRITE;
/*!40000 ALTER TABLE `project_phase` DISABLE KEYS */;
INSERT INTO `project_phase` VALUES (55,_binary '§¸\⁄¿-\÷K”åpùºj\‹\€3',37,'The Fall of Saigon','Chuck pastrami sausage short ribs, landjaeger capicola doner ham hock fatback shoulder salami chicken ball tip swine pig.  Beef venison frankfurter sirloin bresaola jerky sausage pancetta spare ribs brisket boudin filet mignon.  Short ribs pork belly hamburger swine jerky capicola chicken leberkas ham tongue venison alcatra doner.  Filet mignon cupim tongue ball tip, t-bone spare ribs buffalo alcatra pork belly corned beef.  Turducken ham hock pork belly ham, sirloin burgdoggen kevin short loin chicken hamburger chislic.  Bresaola pancetta pork belly tri-tip sausage jowl burgdoggen kevin leberkas.  Filet mignon salami swine, andouille alcatra capicola ball tip brisket hamburger shankle corned beef ribeye drumstick sausage.','2025-12-25 00:00:00','2026-03-22 00:00:00',NULL,'ongoing','2025-12-25 18:30:57','2025-12-31 16:42:26'),(59,_binary ',`âBEå`Åk\Z≠¬Ç',37,'Noli De Castro Pics','Boudin pig picanha meatloaf andouille short ribs rump pork chop flank cupim shankle buffalo drumstick.  Frankfurter turducken jowl cow meatball hamburger.  Chuck chislic ham hock kielbasa tenderloin alcatra.  Alcatra short ribs pork chop doner, short loin burgdoggen kevin meatloaf turducken drumstick pig tri-tip.','2026-06-17 00:00:00','2026-08-17 00:00:00',NULL,'pending','2025-12-25 21:16:29','2026-01-24 16:43:36');
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
/*!50003 CREATE*/ /*!50017 DEFINER=`dbeaver`@`localhost`*/ /*!50003 TRIGGER `check_project_phase_dates_before_update` BEFORE UPDATE ON `project_phase` FOR EACH ROW BEGIN
        
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
/*!50003 CREATE*/ /*!50017 DEFINER=`dbeaver`@`localhost`*/ /*!50003 TRIGGER `complete_project_phase_trigger` BEFORE UPDATE ON `project_phase` FOR EACH ROW BEGIN
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
-- Table structure for table `project_phase_budget`
--

DROP TABLE IF EXISTS `project_phase_budget`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_phase_budget` (
  `id` int NOT NULL AUTO_INCREMENT,
  `phase_id` int NOT NULL,
  `budget` decimal(21,4) NOT NULL DEFAULT '0.0000',
  `contingency_rate` decimal(5,2) NOT NULL DEFAULT '10.00',
  `note` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phase_id` (`phase_id`),
  CONSTRAINT `fk_phase_budget_phase` FOREIGN KEY (`phase_id`) REFERENCES `project_phase` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_phase_budget_chk_1` CHECK ((`budget` >= 0)),
  CONSTRAINT `project_phase_budget_chk_2` CHECK ((`contingency_rate` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_phase_budget`
--

LOCK TABLES `project_phase_budget` WRITE;
/*!40000 ALTER TABLE `project_phase_budget` DISABLE KEYS */;
INSERT INTO `project_phase_budget` VALUES (26,55,13000.0000,6.00,'Aut nostrum necessit','2025-12-25 18:30:57','2025-12-25 21:44:49'),(30,59,30000.0000,7.00,NULL,'2025-12-25 21:16:29','2025-12-25 21:16:29');
/*!40000 ALTER TABLE `project_phase_budget` ENABLE KEYS */;
UNLOCK TABLES;

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
  `default_rate` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_id` (`worker_id`,`project_id`),
  KEY `project_worker_worker_id_index` (`worker_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `project_worker_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_worker_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_worker_chk_1` CHECK ((`status` in (_utf8mb4'assigned',_utf8mb4'terminated'))),
  CONSTRAINT `project_worker_chk_2` CHECK (((`default_rate` >= 0) and (`default_rate` <= 999999999)))
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_worker`
--

LOCK TABLES `project_worker` WRITE;
/*!40000 ALTER TABLE `project_worker` DISABLE KEYS */;
INSERT INTO `project_worker` VALUES (28,2,37,'assigned',162.00,'2026-01-05 22:40:42',NULL),(29,3,37,'assigned',78.00,'2026-01-19 22:39:21','2026-01-24 16:42:58');
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
/*!50003 CREATE*/ /*!50017 DEFINER=`dbeaver`@`localhost`*/ /*!50003 TRIGGER `terminate_worker_tasks_on_project_worker_termination` AFTER UPDATE ON `project_worker` FOR EACH ROW BEGIN
    
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
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`,`endpoint`),
  CONSTRAINT `rate_limiter_chk_1` CHECK ((`expires_at` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=285 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_limiter`
--

LOCK TABLES `rate_limiter` WRITE;
/*!40000 ALTER TABLE `rate_limiter` DISABLE KEYS */;
INSERT INTO `rate_limiter` VALUES (272,'127.0.0.1','/endpoint/projects/6d04a51b-27da-4812-8e46-b62cf08763ef/phases/a4fcdac0-2dd6-4bd3-8c70-9dbc6adcdb33/tasks/223b8124-7003-439e-9ec1-32ada1b936e4:PATCH',1,1768918986,'2026-01-20 22:18:24','2026-01-20 22:22:06'),(273,'127.0.0.1','/endpoint/projects/6d04a51b-27da-4812-8e46-b62cf08763ef/workers?status=assigned&excludeProjectTerminated=true&offset=2:GET',1,1769260604,'2026-01-20 23:10:20','2026-01-24 21:15:44'),(274,'127.0.0.1','/endpoint/projects/6d04a51b-27da-4812-8e46-b62cf08763ef/workers?status=assigned&key=&offset=2:GET',1,1769260604,'2026-01-20 23:10:20','2026-01-24 21:15:44'),(275,'127.0.0.1','/endpoint/projects/6d04a51b-27da-4812-8e46-b62cf08763ef/workers/32bab677-1767-4cc5-bd50-348b5628bd84:GET',2,1768922454,'2026-01-20 23:19:51','2026-01-20 23:19:54'),(276,'127.0.0.1','/endpoint/projects/6d04a51b-27da-4812-8e46-b62cf08763ef/workers/3a47359d-c34d-4360-a56b-09777fa6ad2a:GET',1,1768922461,'2026-01-20 23:20:01',NULL),(277,'127.0.0.1','/endpoint/projects/6d04a51b-27da-4812-8e46-b62cf08763ef/manager:GET',1,1768922464,'2026-01-20 23:20:04',NULL),(278,'127.0.0.1','/endpoint/users?status=unassigned&role=worker&excludeProjectTerminated=true&projectReferenceId=6d04a51b-27da-4812-8e46-b62cf08763ef&offset=0&key=:GET',1,1769260605,'2026-01-20 23:29:01','2026-01-24 21:15:45'),(279,'127.0.0.1','/endpoint/users?status=unassigned&role=worker&excludeProjectTerminated=true&projectReferenceId=6d04a51b-27da-4812-8e46-b62cf08763ef&offset=0:GET',1,1769260607,'2026-01-20 23:29:02','2026-01-24 21:15:47'),(280,'127.0.0.1','/endpoint/auth/login:POST',1,1769261443,'2026-01-24 15:01:51','2026-01-24 21:15:43'),(281,'127.0.0.1','/endpoint/projects/6d04a51b-27da-4812-8e46-b62cf08763ef:PATCH',2,1769244258,'2026-01-24 16:37:59','2026-01-24 16:43:18'),(282,'127.0.0.1','/endpoint/projects/6d04a51b-27da-4812-8e46-b62cf08763ef/workers?status=assigned&excludeProjectTerminated=true&offset=1:GET',1,1769244201,'2026-01-24 16:42:21',NULL),(283,'127.0.0.1','/endpoint/projects/6d04a51b-27da-4812-8e46-b62cf08763ef/workers?status=assigned&key=&offset=1:GET',1,1769244201,'2026-01-24 16:42:21',NULL),(284,'127.0.0.1','/endpoint/projects/6d04a51b-27da-4812-8e46-b62cf08763ef/workers?status=unassigned&excludeProjectTerminated=true&key=&offset=0:GET',1,1769244205,'2026-01-24 16:42:25',NULL);
/*!40000 ALTER TABLE `rate_limiter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resource_type`
--

DROP TABLE IF EXISTS `resource_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unit',
  `default_rate` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  CONSTRAINT `resource_type_chk_2` CHECK ((`default_rate` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_type`
--

LOCK TABLES `resource_type` WRITE;
/*!40000 ALTER TABLE `resource_type` DISABLE KEYS */;
INSERT INTO `resource_type` VALUES (1,'Labor','All labor services including general, skilled, and professional work','hour',200.0000,'2026-01-09 00:00:00','2026-01-09 20:59:17'),(2,'Equipment Rental','General equipment and machinery rental','hour',600.0000,'2026-01-09 00:00:00','2026-01-09 20:59:17'),(3,'Raw Materials','Basic materials and supplies','unit',300.0000,'2026-01-09 00:00:00',NULL),(4,'Office Supplies','Stationery, printing, and office materials','unit',150.0000,'2026-01-09 00:00:00',NULL),(5,'Software License','Software tools and application licenses','license',2500.0000,'2026-01-09 00:00:00',NULL),(6,'Transportation','Vehicle rental and logistics services','day',500.0000,'2026-01-09 00:00:00','2026-01-09 20:59:17'),(7,'Utilities','Power, water, internet, and utility services','month',15000.0000,'2026-01-09 00:00:00','2026-01-09 20:59:17'),(8,'Miscellaneous','Other project-related expenses','unit',500.0000,'2026-01-09 00:00:00',NULL);
/*!40000 ALTER TABLE `resource_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_resource`
--

DROP TABLE IF EXISTS `task_resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_resource` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `resource_type_id` int NOT NULL,
  `task_worker_id` int DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_rate` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `estimated_unit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `actual_unit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `note` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `public_id` binary(16) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_task_resource_task` (`task_id`),
  KEY `idx_task_resource_type` (`resource_type_id`),
  KEY `fk_task_resource_worker` (`task_worker_id`),
  CONSTRAINT `fk_task_resource_task` FOREIGN KEY (`task_id`) REFERENCES `phase_task` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_task_resource_type` FOREIGN KEY (`resource_type_id`) REFERENCES `resource_type` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_task_resource_worker` FOREIGN KEY (`task_worker_id`) REFERENCES `phase_task_worker` (`id`),
  CONSTRAINT `task_resource_ch_act` CHECK ((`actual_unit` >= 0)),
  CONSTRAINT `task_resource_ch_est` CHECK ((`estimated_unit` >= 0)),
  CONSTRAINT `task_resource_chk_1` CHECK ((`quantity` > 0)),
  CONSTRAINT `task_resource_chk_2` CHECK ((`unit_rate` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_resource`
--

LOCK TABLES `task_resource` WRITE;
/*!40000 ALTER TABLE `task_resource` DISABLE KEYS */;
INSERT INTO `task_resource` VALUES (5,22,1,14,1.00,0.0000,8.00,0.00,NULL,'2026-01-11 22:34:49','2026-01-11 22:34:49',_binary '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0'),(6,23,1,15,1.00,83.2300,3.00,0.00,NULL,'2026-01-15 15:16:50','2026-01-15 15:16:50',_binary '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0');
/*!40000 ALTER TABLE `task_resource` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temporary_link`
--

LOCK TABLES `temporary_link` WRITE;
/*!40000 ALTER TABLE `temporary_link` DISABLE KEYS */;
INSERT INTO `temporary_link` VALUES (1,'xates@mailinator.com','21843ab2ece9d5765b843717d5af493be7a65079bbaa0635de03403e5419b25e','2025-12-18 19:39:25',NULL),(2,'naroneboqa@mailinator.com','0efc44dfe2bfe46bca9700d08b0f8c6b584b6aa362b99dd75c76160f2d97f3b9','2025-12-19 18:26:09',NULL),(3,'zugon@mailinator.com','3f3ef2c9221e4c8b66989c84d4322fb29e33b8e453a168aae54e733d930365c2','2026-01-19 21:59:26',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,_binary '∏Ÿµˇo•F¶Ø\Ã*_\Ô','Lunea','Branden Zamora','Hansen','female','2000-04-07','projectManager','+1 (933) 847-3547','xates@mailinator.com','$argon2id$v=19$m=65536,t=4,p=1$eVdPMnQxZXJqUklNZDhEaw$++uVaojhH7mh2rnSvbMvAfOPH9Qt8TzR0qODEMOxHz4',NULL,NULL,'2025-12-18 19:39:25','2025-12-18 19:40:24','2025-12-18 19:39:25',NULL),(2,_binary ':G5ù\√MC`•k	w¶≠*','Hermione','Demetria Hayden','Beck','male','2005-08-01','worker','+1 (555) 813-2967','naroneboqa@mailinator.com','$argon2id$v=19$m=65536,t=4,p=1$U2VCWU91WnhxTGpHRVd6TA$LZXt0rS/XR+Dg/UA5df/iVJu1AmxFEwqmcFH9XEoyHg',NULL,NULL,'2025-12-19 18:26:09','2025-12-19 18:26:44','2025-12-19 18:26:09',NULL),(3,_binary '2∫∂wgL≈ΩP4ãV(ΩÑ','Zephr','Tarik Knox','Peck','male','1977-02-22','worker','+1 (621) 345-8588','zugon@mailinator.com','$argon2id$v=19$m=65536,t=4,p=1$QjFOV0ZXR0I5UzhjZlhiLw$T8+smbjKyS4Hri2whmzsccYouewjIjQr8sKiHVAqOOE',NULL,NULL,'2026-01-19 21:59:26','2026-01-19 22:00:24','2026-01-19 00:00:00',NULL);
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
/*!50003 CREATE*/ /*!50017 DEFINER=`dbeaver`@`localhost`*/ /*!50003 TRIGGER `check_user_age_before_insert` BEFORE INSERT ON `user` FOR EACH ROW BEGIN
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
/*!50003 CREATE*/ /*!50017 DEFINER=`dbeaver`@`localhost`*/ /*!50003 TRIGGER `anonymize_user_on_delete` BEFORE UPDATE ON `user` FOR EACH ROW BEGIN
    
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
/*!50003 CREATE*/ /*!50017 DEFINER=`dbeaver`@`localhost`*/ /*!50003 TRIGGER `delete_job_titles_on_user_delete` AFTER UPDATE ON `user` FOR EACH ROW BEGIN
    
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_job_title`
--

LOCK TABLES `user_job_title` WRITE;
/*!40000 ALTER TABLE `user_job_title` DISABLE KEYS */;
INSERT INTO `user_job_title` VALUES (1,1,'Id quidem tempor adi','2025-12-18 19:39:25','2025-12-18 19:39:25'),(2,2,'Dolorem est vero id','2025-12-19 18:26:09','2025-12-19 18:26:09'),(3,2,'Data Analyst','2025-12-22 14:17:11','2025-12-22 14:17:11'),(4,3,'Voluptatibus sed qui','2026-01-19 21:59:26','2026-01-19 21:59:26');
/*!40000 ALTER TABLE `user_job_title` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-24 21:20:58
