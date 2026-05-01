-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: student_evaluation_for_teacher_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `academic_year`
--

DROP TABLE IF EXISTS `academic_year`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_year` (
  `academic_year_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `academic_year_status` enum('Active','Inactive','Archived') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`academic_year_id`),
  UNIQUE KEY `academic_year_name` (`academic_year_name`),
  CONSTRAINT `chk_academic_year_dates` CHECK (`start_date` < `end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_year`
--

LOCK TABLES `academic_year` WRITE;
/*!40000 ALTER TABLE `academic_year` DISABLE KEYS */;
INSERT INTO `academic_year` VALUES (1,'2025 to 2026','2025-08-01','2026-07-31','Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `academic_year` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `admin_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `admin_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `admin_status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `admin_number` (`admin_number`),
  CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,1,'ADM001','System',NULL,'Administrator','System Administrator','System Admin','Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement`
--

DROP TABLE IF EXISTS `announcement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement` (
  `announcement_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by_admin_id` bigint(20) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `announcement_status` enum('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`announcement_id`),
  KEY `fk_announcement_admin` (`created_by_admin_id`),
  KEY `idx_announcement_status` (`announcement_status`,`published_at`),
  CONSTRAINT `fk_announcement_admin` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement`
--

LOCK TABLES `announcement` WRITE;
/*!40000 ALTER TABLE `announcement` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcement` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_announcement_au
AFTER UPDATE ON announcement
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Update',
        'announcement',
        NEW.announcement_id,
        JSON_OBJECT('title', OLD.title, 'announcement_status', OLD.announcement_status),
        JSON_OBJECT('title', NEW.title, 'announcement_status', NEW.announcement_status)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `announcement_target`
--

DROP TABLE IF EXISTS `announcement_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_target` (
  `announcement_target_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `target_role_id` bigint(20) unsigned DEFAULT NULL,
  `target_department_id` bigint(20) unsigned DEFAULT NULL,
  `target_course_id` bigint(20) unsigned DEFAULT NULL,
  `target_section_id` bigint(20) unsigned DEFAULT NULL,
  `target_faculty_id` bigint(20) unsigned DEFAULT NULL,
  `target_student_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`announcement_target_id`),
  KEY `fk_target_announcement` (`announcement_id`),
  KEY `fk_target_role` (`target_role_id`),
  KEY `fk_target_department` (`target_department_id`),
  KEY `fk_target_course` (`target_course_id`),
  KEY `fk_target_section` (`target_section_id`),
  KEY `fk_target_faculty` (`target_faculty_id`),
  KEY `fk_target_student` (`target_student_id`),
  CONSTRAINT `fk_target_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcement` (`announcement_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_target_course` FOREIGN KEY (`target_course_id`) REFERENCES `course` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_target_department` FOREIGN KEY (`target_department_id`) REFERENCES `department` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_target_faculty` FOREIGN KEY (`target_faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_target_role` FOREIGN KEY (`target_role_id`) REFERENCES `role` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_target_section` FOREIGN KEY (`target_section_id`) REFERENCES `section` (`section_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_target_student` FOREIGN KEY (`target_student_id`) REFERENCES `student` (`student_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_target`
--

LOCK TABLES `announcement_target` WRITE;
/*!40000 ALTER TABLE `announcement_target` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcement_target` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `audit_log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action_type` enum('Insert','Update','Delete','Login','Logout','Release','Backup','Report','System') NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` bigint(20) unsigned DEFAULT NULL,
  `old_value_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value_json`)),
  `new_value_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value_json`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`audit_log_id`),
  KEY `idx_audit_table_record` (`table_name`,`record_id`),
  KEY `idx_audit_user_action` (`user_id`,`action_type`,`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,NULL,'Insert','user',1,NULL,'{\"user_id\": 1, \"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 04:00:56'),(2,1,'Insert','user',2,NULL,'{\"user_id\": 2, \"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}','127.0.0.1','2026-05-01 04:00:56'),(3,1,'Insert','user',3,NULL,'{\"user_id\": 3, \"university_id\": \"FAC002\", \"email\": \"juan.santos@sample.edu\", \"account_status\": \"Active\"}','127.0.0.1','2026-05-01 04:00:56'),(4,1,'Update','department',1,'{\"department_code\": \"CCS\", \"department_name\": \"College of Computer Studies\", \"department_status\": \"Active\"}','{\"department_code\": \"CCS\", \"department_name\": \"College of Computer Studies\", \"department_status\": \"Active\"}','127.0.0.1','2026-05-01 04:00:56'),(5,1,'Insert','user',4,NULL,'{\"user_id\": 4, \"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}','127.0.0.1','2026-05-01 04:00:56'),(6,1,'Insert','user',5,NULL,'{\"user_id\": 5, \"university_id\": \"24-001235\", \"email\": \"ana.reyes@sample.edu\", \"account_status\": \"Active\"}','127.0.0.1','2026-05-01 04:00:56'),(7,1,'Release','evaluation_result_release',1,NULL,'{\"release_name\": \"Second Semester Initial Result Release\", \"release_status\": \"Released\", \"released_at\": \"2026-05-01 12:00:57\"}','127.0.0.1','2026-05-01 04:00:57'),(8,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 06:34:37'),(9,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 07:41:25'),(10,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:03:39'),(11,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:03:59'),(12,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:17:01'),(13,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:17:47'),(14,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:18:49'),(15,NULL,'Update','user',2,'{\"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:40:05'),(16,NULL,'Update','user',2,'{\"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:40:45'),(17,NULL,'Update','user',2,'{\"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:42:12'),(18,NULL,'Update','user',4,'{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:52:30'),(19,NULL,'Update','user',4,'{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:53:57'),(20,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:56:20'),(21,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:58:05'),(22,NULL,'Update','user',2,'{\"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 08:58:25'),(23,NULL,'Update','user',4,'{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:00:37'),(24,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:06:16'),(25,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:07:02'),(26,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:12:31'),(27,NULL,'Update','user',4,'{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:12:46'),(28,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:12:59'),(29,NULL,'Update','user',2,'{\"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:13:15'),(30,NULL,'Update','user',4,'{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:13:37'),(31,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:17:18'),(32,NULL,'Update','user',2,'{\"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"FAC001\", \"email\": \"maria.delacruz@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:17:49'),(33,NULL,'Update','user',4,'{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:18:02'),(34,NULL,'Update','user',4,'{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"24-001234\", \"email\": \"mario.domingo@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:18:59'),(35,NULL,'Update','user',1,'{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}','{\"university_id\": \"ADM001\", \"email\": \"admin@sample.edu\", \"account_status\": \"Active\"}',NULL,'2026-05-01 09:19:23');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backup_record`
--

DROP TABLE IF EXISTS `backup_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_record` (
  `backup_record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `backup_file_name` varchar(255) NOT NULL,
  `backup_file_path` varchar(255) NOT NULL,
  `backup_size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `backup_status` enum('Completed','Failed','Deleted') NOT NULL DEFAULT 'Completed',
  `created_by_admin_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`backup_record_id`),
  KEY `fk_backup_admin` (`created_by_admin_id`),
  CONSTRAINT `fk_backup_admin` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_record`
--

LOCK TABLES `backup_record` WRITE;
/*!40000 ALTER TABLE `backup_record` DISABLE KEYS */;
/*!40000 ALTER TABLE `backup_record` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_backup_ai
AFTER INSERT ON backup_record
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Backup',
        'backup_record',
        NEW.backup_record_id,
        NULL,
        JSON_OBJECT('backup_file_name', NEW.backup_file_name, 'backup_status', NEW.backup_status)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `backup_restore_log`
--

DROP TABLE IF EXISTS `backup_restore_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_restore_log` (
  `backup_restore_log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `backup_record_id` bigint(20) unsigned NOT NULL,
  `performed_by_admin_id` bigint(20) unsigned NOT NULL,
  `action_type` enum('Download','Restore','Delete') NOT NULL,
  `action_status` enum('Success','Failed') NOT NULL,
  `message` text DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`backup_restore_log_id`),
  KEY `fk_backup_log_record` (`backup_record_id`),
  KEY `fk_backup_log_admin` (`performed_by_admin_id`),
  CONSTRAINT `fk_backup_log_admin` FOREIGN KEY (`performed_by_admin_id`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_backup_log_record` FOREIGN KEY (`backup_record_id`) REFERENCES `backup_record` (`backup_record_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_restore_log`
--

LOCK TABLES `backup_restore_log` WRITE;
/*!40000 ALTER TABLE `backup_restore_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `backup_restore_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course`
--

DROP TABLE IF EXISTS `course`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course` (
  `course_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint(20) unsigned NOT NULL,
  `course_code` varchar(30) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `course_description` text DEFAULT NULL,
  `number_of_year_level` tinyint(3) unsigned NOT NULL,
  `course_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`course_id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `idx_course_department_status` (`department_id`,`course_status`),
  CONSTRAINT `fk_course_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_course_year_level` CHECK (`number_of_year_level` between 1 and 6)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course`
--

LOCK TABLES `course` WRITE;
/*!40000 ALTER TABLE `course` DISABLE KEYS */;
INSERT INTO `course` VALUES (1,1,'BSIT','Bachelor of Science in Information Technology','Four year program focused on practical IT skills and technologies',4,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `course` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_course_au
AFTER UPDATE ON course
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Update',
        'course',
        NEW.course_id,
        JSON_OBJECT('course_code', OLD.course_code, 'course_name', OLD.course_name, 'course_status', OLD.course_status),
        JSON_OBJECT('course_code', NEW.course_code, 'course_name', NEW.course_name, 'course_status', NEW.course_status)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `course_subject`
--

DROP TABLE IF EXISTS `course_subject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_subject` (
  `course_subject_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `year_level` tinyint(3) unsigned NOT NULL,
  `term_name` enum('First Semester','Second Semester','Summer') NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `course_subject_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`course_subject_id`),
  UNIQUE KEY `uq_course_subject_identity` (`course_id`,`subject_id`,`year_level`,`term_name`),
  KEY `fk_course_subject_subject` (`subject_id`),
  CONSTRAINT `fk_course_subject_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_course_subject_subject` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_course_subject_year_level` CHECK (`year_level` between 1 and 6)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_subject`
--

LOCK TABLES `course_subject` WRITE;
/*!40000 ALTER TABLE `course_subject` DISABLE KEYS */;
INSERT INTO `course_subject` VALUES (1,1,1,2,'Second Semester',1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(2,1,2,2,'Second Semester',1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `course_subject` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department`
--

DROP TABLE IF EXISTS `department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department` (
  `department_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_code` varchar(30) NOT NULL,
  `department_name` varchar(150) NOT NULL,
  `department_head_faculty_id` bigint(20) unsigned DEFAULT NULL,
  `department_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `department_code` (`department_code`),
  KEY `fk_department_head_faculty` (`department_head_faculty_id`),
  CONSTRAINT `fk_department_head_faculty` FOREIGN KEY (`department_head_faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department`
--

LOCK TABLES `department` WRITE;
/*!40000 ALTER TABLE `department` DISABLE KEYS */;
INSERT INTO `department` VALUES (1,'CCS','College of Computer Studies',1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `department` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_department_au
AFTER UPDATE ON department
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Update',
        'department',
        NEW.department_id,
        JSON_OBJECT('department_code', OLD.department_code, 'department_name', OLD.department_name, 'department_status', OLD.department_status),
        JSON_OBJECT('department_code', NEW.department_code, 'department_name', NEW.department_name, 'department_status', NEW.department_status)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `evaluation_category`
--

DROP TABLE IF EXISTS `evaluation_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_category` (
  `evaluation_category_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_description` text DEFAULT NULL,
  `category_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`evaluation_category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_category`
--

LOCK TABLES `evaluation_category` WRITE;
/*!40000 ALTER TABLE `evaluation_category` DISABLE KEYS */;
INSERT INTO `evaluation_category` VALUES (1,'Teaching Effectiveness','Clarity and effectiveness of teaching','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(2,'Subject Mastery','Knowledge and accuracy in the subject','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(3,'Preparedness','Preparedness and organization','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(4,'Engagement','Class participation and motivation','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(5,'Professionalism','Professional conduct and respect','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(6,'Feedback and Assessment','Assessment, grading, and feedback','Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `evaluation_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_form`
--

DROP TABLE IF EXISTS `evaluation_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_form` (
  `evaluation_form_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_period_id` bigint(20) unsigned NOT NULL,
  `form_title` varchar(150) NOT NULL,
  `form_version` int(10) unsigned NOT NULL DEFAULT 1,
  `form_description` text DEFAULT NULL,
  `form_status` enum('Draft','Active','Inactive','Archived') NOT NULL DEFAULT 'Draft',
  `created_by_admin_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`evaluation_form_id`),
  UNIQUE KEY `uq_form_version` (`evaluation_period_id`,`form_version`),
  KEY `fk_form_admin` (`created_by_admin_id`),
  KEY `idx_form_period_status` (`evaluation_period_id`,`form_status`),
  CONSTRAINT `fk_form_admin` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_form_period` FOREIGN KEY (`evaluation_period_id`) REFERENCES `evaluation_period` (`evaluation_period_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_form`
--

LOCK TABLES `evaluation_form` WRITE;
/*!40000 ALTER TABLE `evaluation_form` DISABLE KEYS */;
INSERT INTO `evaluation_form` VALUES (1,1,'Faculty Evaluation Form',1,'Standard teacher evaluation form for students','Active',1,'2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `evaluation_form` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_form_category`
--

DROP TABLE IF EXISTS `evaluation_form_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_form_category` (
  `evaluation_form_category_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_form_id` bigint(20) unsigned NOT NULL,
  `evaluation_category_id` bigint(20) unsigned NOT NULL,
  `weight_percent` decimal(5,2) NOT NULL,
  `display_order` int(10) unsigned NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `form_category_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`evaluation_form_category_id`),
  UNIQUE KEY `uq_form_category` (`evaluation_form_id`,`evaluation_category_id`),
  KEY `fk_form_category_category` (`evaluation_category_id`),
  CONSTRAINT `fk_form_category_category` FOREIGN KEY (`evaluation_category_id`) REFERENCES `evaluation_category` (`evaluation_category_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_form_category_form` FOREIGN KEY (`evaluation_form_id`) REFERENCES `evaluation_form` (`evaluation_form_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_category_weight` CHECK (`weight_percent` >= 0 and `weight_percent` <= 100)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_form_category`
--

LOCK TABLES `evaluation_form_category` WRITE;
/*!40000 ALTER TABLE `evaluation_form_category` DISABLE KEYS */;
INSERT INTO `evaluation_form_category` VALUES (1,1,1,20.00,1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(2,1,2,20.00,2,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(3,1,3,15.00,3,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(4,1,4,15.00,4,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(5,1,5,15.00,5,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(6,1,6,15.00,6,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `evaluation_form_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_form_item`
--

DROP TABLE IF EXISTS `evaluation_form_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_form_item` (
  `evaluation_form_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_form_category_id` bigint(20) unsigned NOT NULL,
  `evaluation_item_id` bigint(20) unsigned DEFAULT NULL,
  `statement_text_snapshot` text NOT NULL,
  `display_order` int(10) unsigned NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `form_item_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`evaluation_form_item_id`),
  KEY `fk_form_item_category` (`evaluation_form_category_id`),
  KEY `fk_form_item_item` (`evaluation_item_id`),
  CONSTRAINT `fk_form_item_category` FOREIGN KEY (`evaluation_form_category_id`) REFERENCES `evaluation_form_category` (`evaluation_form_category_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_form_item_item` FOREIGN KEY (`evaluation_item_id`) REFERENCES `evaluation_item` (`evaluation_item_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_form_item`
--

LOCK TABLES `evaluation_form_item` WRITE;
/*!40000 ALTER TABLE `evaluation_form_item` DISABLE KEYS */;
INSERT INTO `evaluation_form_item` VALUES (1,1,1,'The teacher explains concepts clearly and effectively.',1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(2,2,2,'The teacher demonstrates deep knowledge of the subject matter.',1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(3,3,3,'The teacher comes to class well prepared.',1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(4,4,4,'The teacher encourages student participation and discussion.',1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(5,5,5,'The teacher maintains a respectful and professional attitude.',1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(6,6,6,'The teacher gives helpful feedback and fair assessment.',1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `evaluation_form_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_guideline`
--

DROP TABLE IF EXISTS `evaluation_guideline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_guideline` (
  `evaluation_guideline_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_period_id` bigint(20) unsigned NOT NULL,
  `guideline_title` varchar(150) NOT NULL,
  `guideline_content` text NOT NULL,
  `version_number` int(10) unsigned NOT NULL DEFAULT 1,
  `guideline_status` enum('Draft','Active','Inactive','Archived') NOT NULL DEFAULT 'Draft',
  `created_by_admin_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`evaluation_guideline_id`),
  UNIQUE KEY `uq_guideline_version` (`evaluation_period_id`,`version_number`),
  KEY `fk_guideline_admin` (`created_by_admin_id`),
  CONSTRAINT `fk_guideline_admin` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_guideline_period` FOREIGN KEY (`evaluation_period_id`) REFERENCES `evaluation_period` (`evaluation_period_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_guideline`
--

LOCK TABLES `evaluation_guideline` WRITE;
/*!40000 ALTER TABLE `evaluation_guideline` DISABLE KEYS */;
INSERT INTO `evaluation_guideline` VALUES (1,1,'Student Evaluation Guidelines','Be honest, respectful, objective, and evaluate all assigned teachers. Submitted evaluations cannot be edited.',1,'Active',1,'2026-05-01 04:00:56');
/*!40000 ALTER TABLE `evaluation_guideline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_item`
--

DROP TABLE IF EXISTS `evaluation_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_item` (
  `evaluation_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_category_id` bigint(20) unsigned NOT NULL,
  `statement_text` text NOT NULL,
  `item_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`evaluation_item_id`),
  KEY `fk_item_category` (`evaluation_category_id`),
  CONSTRAINT `fk_item_category` FOREIGN KEY (`evaluation_category_id`) REFERENCES `evaluation_category` (`evaluation_category_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_item`
--

LOCK TABLES `evaluation_item` WRITE;
/*!40000 ALTER TABLE `evaluation_item` DISABLE KEYS */;
INSERT INTO `evaluation_item` VALUES (1,1,'The teacher explains concepts clearly and effectively.','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(2,2,'The teacher demonstrates deep knowledge of the subject matter.','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(3,3,'The teacher comes to class well prepared.','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(4,4,'The teacher encourages student participation and discussion.','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(5,5,'The teacher maintains a respectful and professional attitude.','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(6,6,'The teacher gives helpful feedback and fair assessment.','Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `evaluation_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_period`
--

DROP TABLE IF EXISTS `evaluation_period`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_period` (
  `evaluation_period_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL,
  `period_name` varchar(150) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `period_status` enum('Draft','Open','Ongoing','Closed','Archived') NOT NULL DEFAULT 'Draft',
  `opened_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `closed_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`evaluation_period_id`),
  KEY `fk_period_opened_admin` (`opened_by_admin_id`),
  KEY `fk_period_closed_admin` (`closed_by_admin_id`),
  KEY `idx_period_term_status` (`term_id`,`period_status`),
  CONSTRAINT `fk_period_closed_admin` FOREIGN KEY (`closed_by_admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_period_opened_admin` FOREIGN KEY (`opened_by_admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_period_term` FOREIGN KEY (`term_id`) REFERENCES `term` (`term_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_period_dates` CHECK (`start_date` < `end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_period`
--

LOCK TABLES `evaluation_period` WRITE;
/*!40000 ALTER TABLE `evaluation_period` DISABLE KEYS */;
INSERT INTO `evaluation_period` VALUES (1,1,'Second Semester Teacher Evaluation','2026-04-01','2026-05-15','Open',1,NULL,'2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `evaluation_period` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_period_au
AFTER UPDATE ON evaluation_period
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Update',
        'evaluation_period',
        NEW.evaluation_period_id,
        JSON_OBJECT('period_name', OLD.period_name, 'period_status', OLD.period_status),
        JSON_OBJECT('period_name', NEW.period_name, 'period_status', NEW.period_status)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `evaluation_response`
--

DROP TABLE IF EXISTS `evaluation_response`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_response` (
  `evaluation_response_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_evaluation_task_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `teaching_assignment_id` bigint(20) unsigned NOT NULL,
  `evaluation_period_id` bigint(20) unsigned NOT NULL,
  `evaluation_form_id` bigint(20) unsigned NOT NULL,
  `response_status` enum('Draft','Submitted','Voided') NOT NULL DEFAULT 'Draft',
  `total_score` decimal(8,2) DEFAULT NULL,
  `average_score` decimal(5,2) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`evaluation_response_id`),
  UNIQUE KEY `student_evaluation_task_id` (`student_evaluation_task_id`),
  KEY `fk_response_student` (`student_id`),
  KEY `fk_response_form` (`evaluation_form_id`),
  KEY `idx_response_period_status` (`evaluation_period_id`,`response_status`),
  KEY `idx_response_assignment_status` (`teaching_assignment_id`,`response_status`),
  CONSTRAINT `fk_response_assignment` FOREIGN KEY (`teaching_assignment_id`) REFERENCES `teaching_assignment` (`teaching_assignment_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_response_form` FOREIGN KEY (`evaluation_form_id`) REFERENCES `evaluation_form` (`evaluation_form_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_response_period` FOREIGN KEY (`evaluation_period_id`) REFERENCES `evaluation_period` (`evaluation_period_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_response_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_response_task` FOREIGN KEY (`student_evaluation_task_id`) REFERENCES `student_evaluation_task` (`student_evaluation_task_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_response`
--

LOCK TABLES `evaluation_response` WRITE;
/*!40000 ALTER TABLE `evaluation_response` DISABLE KEYS */;
INSERT INTO `evaluation_response` VALUES (1,1,1,1,1,1,'Submitted',28.00,4.67,'2026-05-01 12:00:57','2026-05-01 04:00:57','2026-05-01 04:00:57');
/*!40000 ALTER TABLE `evaluation_response` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_response_bu
BEFORE UPDATE ON evaluation_response
FOR EACH ROW
BEGIN
    IF OLD.response_status = 'Submitted'
       AND COALESCE(@allow_submitted_response_change, 0) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Submitted evaluation response cannot be changed.';
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
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_response_bd
BEFORE DELETE ON evaluation_response
FOR EACH ROW
BEGIN
    IF OLD.response_status = 'Submitted' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Submitted evaluation response cannot be deleted.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `evaluation_response_answer`
--

DROP TABLE IF EXISTS `evaluation_response_answer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_response_answer` (
  `evaluation_response_answer_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_response_id` bigint(20) unsigned NOT NULL,
  `evaluation_form_item_id` bigint(20) unsigned NOT NULL,
  `rating_scale_option_id` bigint(20) unsigned NOT NULL,
  `rating_value` tinyint(3) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`evaluation_response_answer_id`),
  UNIQUE KEY `uq_response_form_item` (`evaluation_response_id`,`evaluation_form_item_id`),
  KEY `fk_answer_rating` (`rating_scale_option_id`),
  KEY `idx_answer_form_item` (`evaluation_form_item_id`),
  CONSTRAINT `fk_answer_form_item` FOREIGN KEY (`evaluation_form_item_id`) REFERENCES `evaluation_form_item` (`evaluation_form_item_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_answer_rating` FOREIGN KEY (`rating_scale_option_id`) REFERENCES `rating_scale_option` (`rating_scale_option_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_answer_response` FOREIGN KEY (`evaluation_response_id`) REFERENCES `evaluation_response` (`evaluation_response_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_answer_rating_value` CHECK (`rating_value` between 1 and 5)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_response_answer`
--

LOCK TABLES `evaluation_response_answer` WRITE;
/*!40000 ALTER TABLE `evaluation_response_answer` DISABLE KEYS */;
INSERT INTO `evaluation_response_answer` VALUES (1,1,1,5,5,'2026-05-01 04:00:57','2026-05-01 04:00:57'),(2,1,2,5,5,'2026-05-01 04:00:57','2026-05-01 04:00:57'),(3,1,3,4,4,'2026-05-01 04:00:57','2026-05-01 04:00:57'),(4,1,4,5,5,'2026-05-01 04:00:57','2026-05-01 04:00:57'),(5,1,5,4,4,'2026-05-01 04:00:57','2026-05-01 04:00:57'),(6,1,6,5,5,'2026-05-01 04:00:57','2026-05-01 04:00:57');
/*!40000 ALTER TABLE `evaluation_response_answer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_response_comment`
--

DROP TABLE IF EXISTS `evaluation_response_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_response_comment` (
  `evaluation_response_comment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_response_id` bigint(20) unsigned NOT NULL,
  `comment_text` text DEFAULT NULL,
  `is_visible_to_faculty` tinyint(1) NOT NULL DEFAULT 1,
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  `moderation_status` enum('Pending','Approved','Hidden','Flagged') NOT NULL DEFAULT 'Approved',
  `submitted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`evaluation_response_comment_id`),
  UNIQUE KEY `evaluation_response_id` (`evaluation_response_id`),
  KEY `idx_comment_visibility` (`is_visible_to_faculty`,`moderation_status`),
  CONSTRAINT `fk_comment_response` FOREIGN KEY (`evaluation_response_id`) REFERENCES `evaluation_response` (`evaluation_response_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_response_comment`
--

LOCK TABLES `evaluation_response_comment` WRITE;
/*!40000 ALTER TABLE `evaluation_response_comment` DISABLE KEYS */;
INSERT INTO `evaluation_response_comment` VALUES (1,1,'The teacher explains lessons clearly and gives helpful feedback.',1,0,'Approved','2026-05-01 12:00:57');
/*!40000 ALTER TABLE `evaluation_response_comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_response_event`
--

DROP TABLE IF EXISTS `evaluation_response_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_response_event` (
  `evaluation_response_event_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_response_id` bigint(20) unsigned DEFAULT NULL,
  `student_evaluation_task_id` bigint(20) unsigned NOT NULL,
  `created_by_user_id` bigint(20) unsigned NOT NULL,
  `event_type` enum('Draft Saved','Answer Cleared','Proceeded To Review','Submitted','Reset Progress','Logout Warning Shown') NOT NULL,
  `event_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`evaluation_response_event_id`),
  KEY `fk_event_response` (`evaluation_response_id`),
  KEY `fk_event_task` (`student_evaluation_task_id`),
  KEY `fk_event_user` (`created_by_user_id`),
  CONSTRAINT `fk_event_response` FOREIGN KEY (`evaluation_response_id`) REFERENCES `evaluation_response` (`evaluation_response_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_event_task` FOREIGN KEY (`student_evaluation_task_id`) REFERENCES `student_evaluation_task` (`student_evaluation_task_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_event_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_response_event`
--

LOCK TABLES `evaluation_response_event` WRITE;
/*!40000 ALTER TABLE `evaluation_response_event` DISABLE KEYS */;
INSERT INTO `evaluation_response_event` VALUES (1,1,1,4,'Draft Saved','Student saved draft answers.','2026-05-01 04:00:57'),(2,1,1,4,'Submitted','Student confirmed final submission.','2026-05-01 04:00:57');
/*!40000 ALTER TABLE `evaluation_response_event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_result_release`
--

DROP TABLE IF EXISTS `evaluation_result_release`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_result_release` (
  `evaluation_result_release_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_period_id` bigint(20) unsigned NOT NULL,
  `release_name` varchar(150) NOT NULL,
  `release_status` enum('Draft','Released','Unreleased','Revoked') NOT NULL DEFAULT 'Draft',
  `released_by_admin_id` bigint(20) unsigned NOT NULL,
  `released_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`evaluation_result_release_id`),
  KEY `fk_release_admin` (`released_by_admin_id`),
  KEY `idx_release_period_status` (`evaluation_period_id`,`release_status`),
  CONSTRAINT `fk_release_admin` FOREIGN KEY (`released_by_admin_id`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_release_period` FOREIGN KEY (`evaluation_period_id`) REFERENCES `evaluation_period` (`evaluation_period_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_result_release`
--

LOCK TABLES `evaluation_result_release` WRITE;
/*!40000 ALTER TABLE `evaluation_result_release` DISABLE KEYS */;
INSERT INTO `evaluation_result_release` VALUES (1,1,'Second Semester Initial Result Release','Released',1,'2026-05-01 12:00:57','2026-05-01 04:00:57');
/*!40000 ALTER TABLE `evaluation_result_release` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_result_release_ai
AFTER INSERT ON evaluation_result_release
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Release',
        'evaluation_result_release',
        NEW.evaluation_result_release_id,
        NULL,
        JSON_OBJECT('release_name', NEW.release_name, 'release_status', NEW.release_status, 'released_at', NEW.released_at)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `faculty`
--

DROP TABLE IF EXISTS `faculty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faculty` (
  `faculty_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `faculty_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `academic_rank` varchar(100) DEFAULT NULL,
  `employment_status` varchar(100) DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `faculty_status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`faculty_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `faculty_number` (`faculty_number`),
  KEY `idx_faculty_department_status` (`department_id`,`faculty_status`),
  KEY `idx_faculty_name` (`last_name`,`first_name`),
  CONSTRAINT `fk_faculty_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_faculty_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faculty`
--

LOCK TABLES `faculty` WRITE;
/*!40000 ALTER TABLE `faculty` DISABLE KEYS */;
INSERT INTO `faculty` VALUES (1,2,'FAC001','Maria Teresa',NULL,'Dela Cruz','Maria Teresa  Dela Cruz',1,'Associate Professor','Associate Professor','Full Time','09170000001','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(2,3,'FAC002','Juan',NULL,'Santos','Juan  Santos',1,'Assistant Professor','Assistant Professor','Full Time','09170000002','Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `faculty` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_faculty_au
AFTER UPDATE ON faculty
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Update',
        'faculty',
        NEW.faculty_id,
        JSON_OBJECT('faculty_number', OLD.faculty_number, 'full_name', OLD.full_name, 'faculty_status', OLD.faculty_status),
        JSON_OBJECT('faculty_number', NEW.faculty_number, 'full_name', NEW.full_name, 'faculty_status', NEW.faculty_status)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `faculty_evaluation_result`
--

DROP TABLE IF EXISTS `faculty_evaluation_result`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faculty_evaluation_result` (
  `faculty_evaluation_result_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_result_release_id` bigint(20) unsigned NOT NULL,
  `teaching_assignment_id` bigint(20) unsigned NOT NULL,
  `evaluation_period_id` bigint(20) unsigned NOT NULL,
  `evaluation_form_id` bigint(20) unsigned NOT NULL,
  `eligible_student_count` int(10) unsigned NOT NULL DEFAULT 0,
  `submitted_response_count` int(10) unsigned NOT NULL DEFAULT 0,
  `pending_response_count` int(10) unsigned NOT NULL DEFAULT 0,
  `participation_rate` decimal(6,2) NOT NULL DEFAULT 0.00,
  `overall_average_score` decimal(5,2) DEFAULT NULL,
  `result_status` enum('Released','Unreleased','Revoked') NOT NULL DEFAULT 'Unreleased',
  `computed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `released_at` datetime DEFAULT NULL,
  PRIMARY KEY (`faculty_evaluation_result_id`),
  UNIQUE KEY `uq_faculty_result_release_assignment` (`evaluation_result_release_id`,`teaching_assignment_id`),
  KEY `fk_faculty_result_period` (`evaluation_period_id`),
  KEY `fk_faculty_result_form` (`evaluation_form_id`),
  KEY `idx_faculty_result_assignment_status` (`teaching_assignment_id`,`result_status`),
  CONSTRAINT `fk_faculty_result_assignment` FOREIGN KEY (`teaching_assignment_id`) REFERENCES `teaching_assignment` (`teaching_assignment_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_faculty_result_form` FOREIGN KEY (`evaluation_form_id`) REFERENCES `evaluation_form` (`evaluation_form_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_faculty_result_period` FOREIGN KEY (`evaluation_period_id`) REFERENCES `evaluation_period` (`evaluation_period_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_faculty_result_release` FOREIGN KEY (`evaluation_result_release_id`) REFERENCES `evaluation_result_release` (`evaluation_result_release_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faculty_evaluation_result`
--

LOCK TABLES `faculty_evaluation_result` WRITE;
/*!40000 ALTER TABLE `faculty_evaluation_result` DISABLE KEYS */;
INSERT INTO `faculty_evaluation_result` VALUES (1,1,1,1,1,2,1,1,50.00,4.67,'Released','2026-05-01 12:00:57','2026-05-01 12:00:57'),(2,1,2,1,1,2,0,2,0.00,NULL,'Released','2026-05-01 12:00:57','2026-05-01 12:00:57');
/*!40000 ALTER TABLE `faculty_evaluation_result` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faculty_result_category_score`
--

DROP TABLE IF EXISTS `faculty_result_category_score`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faculty_result_category_score` (
  `faculty_result_category_score_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `faculty_evaluation_result_id` bigint(20) unsigned NOT NULL,
  `evaluation_form_category_id` bigint(20) unsigned NOT NULL,
  `average_score` decimal(5,2) DEFAULT NULL,
  `percentage_score` decimal(6,2) DEFAULT NULL,
  `rating_description` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`faculty_result_category_score_id`),
  UNIQUE KEY `uq_result_category_score` (`faculty_evaluation_result_id`,`evaluation_form_category_id`),
  KEY `fk_category_score_form_category` (`evaluation_form_category_id`),
  CONSTRAINT `fk_category_score_form_category` FOREIGN KEY (`evaluation_form_category_id`) REFERENCES `evaluation_form_category` (`evaluation_form_category_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_category_score_result` FOREIGN KEY (`faculty_evaluation_result_id`) REFERENCES `faculty_evaluation_result` (`faculty_evaluation_result_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faculty_result_category_score`
--

LOCK TABLES `faculty_result_category_score` WRITE;
/*!40000 ALTER TABLE `faculty_result_category_score` DISABLE KEYS */;
INSERT INTO `faculty_result_category_score` VALUES (1,1,1,5.00,100.00,'Excellent'),(2,1,2,5.00,100.00,'Excellent'),(3,1,3,4.00,80.00,'Very Good'),(4,1,4,5.00,100.00,'Excellent'),(5,1,5,4.00,80.00,'Very Good'),(6,1,6,5.00,100.00,'Excellent');
/*!40000 ALTER TABLE `faculty_result_category_score` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faculty_result_item_score`
--

DROP TABLE IF EXISTS `faculty_result_item_score`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faculty_result_item_score` (
  `faculty_result_item_score_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `faculty_evaluation_result_id` bigint(20) unsigned NOT NULL,
  `evaluation_form_item_id` bigint(20) unsigned NOT NULL,
  `average_score` decimal(5,2) DEFAULT NULL,
  `response_count` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`faculty_result_item_score_id`),
  UNIQUE KEY `uq_result_item_score` (`faculty_evaluation_result_id`,`evaluation_form_item_id`),
  KEY `fk_item_score_form_item` (`evaluation_form_item_id`),
  CONSTRAINT `fk_item_score_form_item` FOREIGN KEY (`evaluation_form_item_id`) REFERENCES `evaluation_form_item` (`evaluation_form_item_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_item_score_result` FOREIGN KEY (`faculty_evaluation_result_id`) REFERENCES `faculty_evaluation_result` (`faculty_evaluation_result_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faculty_result_item_score`
--

LOCK TABLES `faculty_result_item_score` WRITE;
/*!40000 ALTER TABLE `faculty_result_item_score` DISABLE KEYS */;
INSERT INTO `faculty_result_item_score` VALUES (1,1,1,5.00,1),(2,1,2,5.00,1),(3,1,3,4.00,1),(4,1,4,5.00,1),(5,1,5,4.00,1),(6,1,6,5.00,1);
/*!40000 ALTER TABLE `faculty_result_item_score` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faculty_result_rating_distribution`
--

DROP TABLE IF EXISTS `faculty_result_rating_distribution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faculty_result_rating_distribution` (
  `faculty_result_rating_distribution_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `faculty_evaluation_result_id` bigint(20) unsigned NOT NULL,
  `rating_scale_option_id` bigint(20) unsigned NOT NULL,
  `rating_count` int(10) unsigned NOT NULL DEFAULT 0,
  `rating_percentage` decimal(6,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`faculty_result_rating_distribution_id`),
  UNIQUE KEY `uq_result_rating_distribution` (`faculty_evaluation_result_id`,`rating_scale_option_id`),
  KEY `fk_distribution_rating` (`rating_scale_option_id`),
  CONSTRAINT `fk_distribution_rating` FOREIGN KEY (`rating_scale_option_id`) REFERENCES `rating_scale_option` (`rating_scale_option_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_distribution_result` FOREIGN KEY (`faculty_evaluation_result_id`) REFERENCES `faculty_evaluation_result` (`faculty_evaluation_result_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faculty_result_rating_distribution`
--

LOCK TABLES `faculty_result_rating_distribution` WRITE;
/*!40000 ALTER TABLE `faculty_result_rating_distribution` DISABLE KEYS */;
INSERT INTO `faculty_result_rating_distribution` VALUES (1,1,1,0,0.00),(2,1,2,0,0.00),(3,1,3,0,0.00),(4,1,4,2,33.33),(5,1,5,4,66.67),(8,2,1,0,0.00),(9,2,2,0,0.00),(10,2,3,0,0.00),(11,2,4,2,0.00),(12,2,5,4,0.00);
/*!40000 ALTER TABLE `faculty_result_rating_distribution` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `generated_report`
--

DROP TABLE IF EXISTS `generated_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `generated_report` (
  `generated_report_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `generated_by_user_id` bigint(20) unsigned NOT NULL,
  `report_type` enum('Overall Summary','Faculty Summary','Subject Summary','Criteria Summary','Department Summary','Course Summary','Section Summary','Completion Summary') NOT NULL,
  `report_format` enum('Preview','PDF','Excel','Print') NOT NULL,
  `filter_academic_year_id` bigint(20) unsigned DEFAULT NULL,
  `filter_term_id` bigint(20) unsigned DEFAULT NULL,
  `filter_department_id` bigint(20) unsigned DEFAULT NULL,
  `filter_course_id` bigint(20) unsigned DEFAULT NULL,
  `filter_year_level` tinyint(3) unsigned DEFAULT NULL,
  `filter_section_id` bigint(20) unsigned DEFAULT NULL,
  `filter_subject_id` bigint(20) unsigned DEFAULT NULL,
  `filter_faculty_id` bigint(20) unsigned DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `generated_report_status` enum('Generated','Failed') NOT NULL DEFAULT 'Generated',
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`generated_report_id`),
  KEY `fk_report_academic_year` (`filter_academic_year_id`),
  KEY `fk_report_term` (`filter_term_id`),
  KEY `fk_report_department` (`filter_department_id`),
  KEY `fk_report_course` (`filter_course_id`),
  KEY `fk_report_section` (`filter_section_id`),
  KEY `fk_report_subject` (`filter_subject_id`),
  KEY `fk_report_faculty` (`filter_faculty_id`),
  KEY `idx_report_generated_by` (`generated_by_user_id`,`report_type`,`generated_at`),
  CONSTRAINT `fk_report_academic_year` FOREIGN KEY (`filter_academic_year_id`) REFERENCES `academic_year` (`academic_year_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_report_course` FOREIGN KEY (`filter_course_id`) REFERENCES `course` (`course_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_report_department` FOREIGN KEY (`filter_department_id`) REFERENCES `department` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_report_faculty` FOREIGN KEY (`filter_faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_report_section` FOREIGN KEY (`filter_section_id`) REFERENCES `section` (`section_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_report_subject` FOREIGN KEY (`filter_subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_report_term` FOREIGN KEY (`filter_term_id`) REFERENCES `term` (`term_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_report_user` FOREIGN KEY (`generated_by_user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `generated_report`
--

LOCK TABLES `generated_report` WRITE;
/*!40000 ALTER TABLE `generated_report` DISABLE KEYS */;
INSERT INTO `generated_report` VALUES (1,1,'Overall Summary','Preview',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Generated','2026-05-01 04:00:57'),(2,2,'Faculty Summary','Preview',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'Generated','2026-05-01 04:00:57');
/*!40000 ALTER TABLE `generated_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guideline_acceptance`
--

DROP TABLE IF EXISTS `guideline_acceptance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guideline_acceptance` (
  `guideline_acceptance_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_guideline_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `evaluation_period_id` bigint(20) unsigned NOT NULL,
  `accepted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`guideline_acceptance_id`),
  UNIQUE KEY `uq_student_period_acceptance` (`student_id`,`evaluation_period_id`),
  KEY `fk_acceptance_guideline` (`evaluation_guideline_id`),
  KEY `fk_acceptance_period` (`evaluation_period_id`),
  CONSTRAINT `fk_acceptance_guideline` FOREIGN KEY (`evaluation_guideline_id`) REFERENCES `evaluation_guideline` (`evaluation_guideline_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_acceptance_period` FOREIGN KEY (`evaluation_period_id`) REFERENCES `evaluation_period` (`evaluation_period_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_acceptance_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guideline_acceptance`
--

LOCK TABLES `guideline_acceptance` WRITE;
/*!40000 ALTER TABLE `guideline_acceptance` DISABLE KEYS */;
INSERT INTO `guideline_acceptance` VALUES (1,1,1,1,'2026-05-01 12:00:56','127.0.0.1');
/*!40000 ALTER TABLE `guideline_acceptance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification` (
  `notification_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `announcement_id` bigint(20) unsigned DEFAULT NULL,
  `notification_title` varchar(150) NOT NULL,
  `notification_message` text NOT NULL,
  `notification_type` enum('Announcement','Reminder','System','Result Release') NOT NULL DEFAULT 'System',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `fk_notification_announcement` (`announcement_id`),
  KEY `idx_notification_user_read` (`user_id`,`is_read`,`created_at`),
  CONSTRAINT `fk_notification_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcement` (`announcement_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification`
--

LOCK TABLES `notification` WRITE;
/*!40000 ALTER TABLE `notification` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rating_scale_option`
--

DROP TABLE IF EXISTS `rating_scale_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rating_scale_option` (
  `rating_scale_option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_form_id` bigint(20) unsigned NOT NULL,
  `rating_value` tinyint(3) unsigned NOT NULL,
  `rating_label` varchar(100) NOT NULL,
  `score_value` decimal(5,2) NOT NULL,
  `display_order` int(10) unsigned NOT NULL,
  PRIMARY KEY (`rating_scale_option_id`),
  UNIQUE KEY `uq_rating_form_value` (`evaluation_form_id`,`rating_value`),
  CONSTRAINT `fk_rating_form` FOREIGN KEY (`evaluation_form_id`) REFERENCES `evaluation_form` (`evaluation_form_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_rating_value` CHECK (`rating_value` between 1 and 5)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rating_scale_option`
--

LOCK TABLES `rating_scale_option` WRITE;
/*!40000 ALTER TABLE `rating_scale_option` DISABLE KEYS */;
INSERT INTO `rating_scale_option` VALUES (1,1,1,'Strongly Disagree',1.00,1),(2,1,2,'Disagree',2.00,2),(3,1,3,'Neutral',3.00,3),(4,1,4,'Agree',4.00,4),(5,1,5,'Strongly Agree',5.00,5);
/*!40000 ALTER TABLE `rating_scale_option` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role` (
  `role_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `role_description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role`
--

LOCK TABLES `role` WRITE;
/*!40000 ALTER TABLE `role` DISABLE KEYS */;
INSERT INTO `role` VALUES (1,'Student','Student evaluator role','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(2,'Faculty','Faculty result viewer role','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(3,'Admin','System administrator role','Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_profile`
--

DROP TABLE IF EXISTS `school_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_profile` (
  `school_profile_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_name` varchar(150) NOT NULL,
  `school_logo_path` varchar(255) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `updated_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`school_profile_id`),
  KEY `fk_school_profile_admin` (`updated_by_admin_id`),
  CONSTRAINT `fk_school_profile_admin` FOREIGN KEY (`updated_by_admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_profile`
--

LOCK TABLES `school_profile` WRITE;
/*!40000 ALTER TABLE `school_profile` DISABLE KEYS */;
/*!40000 ALTER TABLE `school_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `section`
--

DROP TABLE IF EXISTS `section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `section` (
  `section_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `year_level` tinyint(3) unsigned NOT NULL,
  `maximum_student_count` smallint(5) unsigned NOT NULL DEFAULT 50,
  `adviser_faculty_id` bigint(20) unsigned DEFAULT NULL,
  `section_status` enum('Active','Inactive','Closed') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `uq_section_identity` (`course_id`,`term_id`,`year_level`,`section_name`),
  KEY `fk_section_term` (`term_id`),
  KEY `fk_section_adviser` (`adviser_faculty_id`),
  KEY `idx_section_course_term` (`course_id`,`term_id`,`year_level`),
  CONSTRAINT `fk_section_adviser` FOREIGN KEY (`adviser_faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_section_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_section_term` FOREIGN KEY (`term_id`) REFERENCES `term` (`term_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_section_year_level` CHECK (`year_level` between 1 and 6)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `section`
--

LOCK TABLES `section` WRITE;
/*!40000 ALTER TABLE `section` DISABLE KEYS */;
INSERT INTO `section` VALUES (1,1,1,'BSIT 2A',2,40,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `section` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_section_au
AFTER UPDATE ON section
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Update',
        'section',
        NEW.section_id,
        JSON_OBJECT('section_name', OLD.section_name, 'year_level', OLD.year_level, 'section_status', OLD.section_status),
        JSON_OBJECT('section_name', NEW.section_name, 'year_level', NEW.year_level, 'section_status', NEW.section_status)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `section_subject_offering`
--

DROP TABLE IF EXISTS `section_subject_offering`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `section_subject_offering` (
  `section_subject_offering_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `created_by_admin_id` bigint(20) unsigned NOT NULL,
  `offering_status` enum('Active','Inactive','Closed') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`section_subject_offering_id`),
  UNIQUE KEY `uq_section_subject_term` (`section_id`,`subject_id`,`term_id`),
  KEY `fk_offering_term` (`term_id`),
  KEY `fk_offering_admin` (`created_by_admin_id`),
  KEY `idx_offering_section_term` (`section_id`,`term_id`,`offering_status`),
  KEY `idx_offering_subject_term` (`subject_id`,`term_id`,`offering_status`),
  CONSTRAINT `fk_offering_admin` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_offering_section` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_offering_subject` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_offering_term` FOREIGN KEY (`term_id`) REFERENCES `term` (`term_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `section_subject_offering`
--

LOCK TABLES `section_subject_offering` WRITE;
/*!40000 ALTER TABLE `section_subject_offering` DISABLE KEYS */;
INSERT INTO `section_subject_offering` VALUES (1,1,1,1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(2,1,2,1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `section_subject_offering` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_offering_au
AFTER UPDATE ON section_subject_offering
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Update',
        'section_subject_offering',
        NEW.section_subject_offering_id,
        JSON_OBJECT('offering_status', OLD.offering_status),
        JSON_OBJECT('offering_status', NEW.offering_status)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student` (
  `student_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `current_year_level` tinyint(3) unsigned NOT NULL,
  `current_section_id` bigint(20) unsigned DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `student_status` enum('Active','Inactive','Suspended','Restricted') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `student_number` (`student_number`),
  KEY `fk_student_current_section` (`current_section_id`),
  KEY `idx_student_course_section` (`course_id`,`current_section_id`,`current_year_level`),
  KEY `idx_student_status` (`student_status`),
  CONSTRAINT `fk_student_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_student_current_section` FOREIGN KEY (`current_section_id`) REFERENCES `section` (`section_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_student_year_level` CHECK (`current_year_level` between 1 and 6)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student`
--

LOCK TABLES `student` WRITE;
/*!40000 ALTER TABLE `student` DISABLE KEYS */;
INSERT INTO `student` VALUES (1,4,'24-001234','Mario','C','Domingo','Mario C Domingo',1,2,1,'09180000001','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(2,5,'24-001235','Ana',NULL,'Reyes','Ana  Reyes',1,2,1,'09180000002','Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `student` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_student_au
AFTER UPDATE ON student
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Update',
        'student',
        NEW.student_id,
        JSON_OBJECT('student_number', OLD.student_number, 'full_name', OLD.full_name, 'student_status', OLD.student_status),
        JSON_OBJECT('student_number', NEW.student_number, 'full_name', NEW.full_name, 'student_status', NEW.student_status)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `student_evaluation_task`
--

DROP TABLE IF EXISTS `student_evaluation_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_evaluation_task` (
  `student_evaluation_task_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `teaching_assignment_id` bigint(20) unsigned NOT NULL,
  `evaluation_period_id` bigint(20) unsigned NOT NULL,
  `task_status` enum('Pending','Draft','Submitted','Reset') NOT NULL DEFAULT 'Pending',
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `started_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `last_saved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`student_evaluation_task_id`),
  UNIQUE KEY `uq_student_assignment_period` (`student_id`,`teaching_assignment_id`,`evaluation_period_id`),
  KEY `fk_task_period` (`evaluation_period_id`),
  KEY `idx_task_student_period_status` (`student_id`,`evaluation_period_id`,`task_status`),
  KEY `idx_task_assignment_period_status` (`teaching_assignment_id`,`evaluation_period_id`,`task_status`),
  CONSTRAINT `fk_task_assignment` FOREIGN KEY (`teaching_assignment_id`) REFERENCES `teaching_assignment` (`teaching_assignment_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_task_period` FOREIGN KEY (`evaluation_period_id`) REFERENCES `evaluation_period` (`evaluation_period_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_task_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_evaluation_task`
--

LOCK TABLES `student_evaluation_task` WRITE;
/*!40000 ALTER TABLE `student_evaluation_task` DISABLE KEYS */;
INSERT INTO `student_evaluation_task` VALUES (1,1,1,1,'Submitted','2026-05-01 12:00:56','2026-05-01 12:00:57','2026-05-01 12:00:57','2026-05-01 12:00:57'),(2,1,2,1,'Pending','2026-05-01 12:00:56',NULL,NULL,NULL),(3,2,1,1,'Pending','2026-05-01 12:00:56',NULL,NULL,NULL),(4,2,2,1,'Pending','2026-05-01 12:00:56',NULL,NULL,NULL);
/*!40000 ALTER TABLE `student_evaluation_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_section_enrollment`
--

DROP TABLE IF EXISTS `student_section_enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_section_enrollment` (
  `student_section_enrollment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `section_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `enrollment_status` enum('Active','Dropped','Transferred','Completed') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_section_enrollment_id`),
  UNIQUE KEY `uq_student_term_enrollment` (`student_id`,`term_id`),
  KEY `fk_enrollment_term` (`term_id`),
  KEY `idx_enrollment_section_term` (`section_id`,`term_id`,`enrollment_status`),
  CONSTRAINT `fk_enrollment_section` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_enrollment_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_enrollment_term` FOREIGN KEY (`term_id`) REFERENCES `term` (`term_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_section_enrollment`
--

LOCK TABLES `student_section_enrollment` WRITE;
/*!40000 ALTER TABLE `student_section_enrollment` DISABLE KEYS */;
INSERT INTO `student_section_enrollment` VALUES (1,1,1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(2,2,1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `student_section_enrollment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subject`
--

DROP TABLE IF EXISTS `subject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subject` (
  `subject_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint(20) unsigned NOT NULL,
  `subject_code` varchar(30) NOT NULL,
  `subject_title` varchar(150) NOT NULL,
  `subject_description` text DEFAULT NULL,
  `subject_unit` decimal(3,1) NOT NULL DEFAULT 3.0,
  `subject_type` varchar(50) DEFAULT NULL,
  `subject_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`subject_id`),
  UNIQUE KEY `subject_code` (`subject_code`),
  KEY `idx_subject_department_status` (`department_id`,`subject_status`),
  CONSTRAINT `fk_subject_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_subject_unit` CHECK (`subject_unit` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subject`
--

LOCK TABLES `subject` WRITE;
/*!40000 ALTER TABLE `subject` DISABLE KEYS */;
INSERT INTO `subject` VALUES (1,1,'IT221','Human Computer Interaction','Human centered interface design and usability',3.0,'Major','Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(2,1,'IT223','Information Management','Database concepts and information management',3.0,'Major','Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `subject` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_subject_au
AFTER UPDATE ON subject
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Update',
        'subject',
        NEW.subject_id,
        JSON_OBJECT('subject_code', OLD.subject_code, 'subject_title', OLD.subject_title, 'subject_status', OLD.subject_status),
        JSON_OBJECT('subject_code', NEW.subject_code, 'subject_title', NEW.subject_title, 'subject_status', NEW.subject_status)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `system_setting`
--

DROP TABLE IF EXISTS `system_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_setting` (
  `system_setting_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(100) DEFAULT NULL,
  `updated_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`system_setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `fk_setting_admin` (`updated_by_admin_id`),
  CONSTRAINT `fk_setting_admin` FOREIGN KEY (`updated_by_admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_setting`
--

LOCK TABLES `system_setting` WRITE;
/*!40000 ALTER TABLE `system_setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_setting` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_setting_au
AFTER UPDATE ON system_setting
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Update',
        'system_setting',
        NEW.system_setting_id,
        JSON_OBJECT('setting_key', OLD.setting_key, 'setting_value', OLD.setting_value),
        JSON_OBJECT('setting_key', NEW.setting_key, 'setting_value', NEW.setting_value)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `teaching_assignment`
--

DROP TABLE IF EXISTS `teaching_assignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teaching_assignment` (
  `teaching_assignment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `section_subject_offering_id` bigint(20) unsigned NOT NULL,
  `faculty_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `created_by_admin_id` bigint(20) unsigned NOT NULL,
  `assignment_status` enum('Active','Inactive','Closed') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`teaching_assignment_id`),
  UNIQUE KEY `uq_teaching_assignment` (`section_subject_offering_id`,`faculty_id`,`term_id`),
  KEY `fk_assignment_term` (`term_id`),
  KEY `fk_assignment_admin` (`created_by_admin_id`),
  KEY `idx_assignment_faculty_term` (`faculty_id`,`term_id`,`assignment_status`),
  KEY `idx_assignment_offering_term` (`section_subject_offering_id`,`term_id`,`assignment_status`),
  CONSTRAINT `fk_assignment_admin` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admin` (`admin_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_assignment_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_assignment_offering` FOREIGN KEY (`section_subject_offering_id`) REFERENCES `section_subject_offering` (`section_subject_offering_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_assignment_term` FOREIGN KEY (`term_id`) REFERENCES `term` (`term_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teaching_assignment`
--

LOCK TABLES `teaching_assignment` WRITE;
/*!40000 ALTER TABLE `teaching_assignment` DISABLE KEYS */;
INSERT INTO `teaching_assignment` VALUES (1,1,1,1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56'),(2,2,2,1,1,'Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `teaching_assignment` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_assignment_au
AFTER UPDATE ON teaching_assignment
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Update',
        'teaching_assignment',
        NEW.teaching_assignment_id,
        JSON_OBJECT('assignment_status', OLD.assignment_status),
        JSON_OBJECT('assignment_status', NEW.assignment_status)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `term`
--

DROP TABLE IF EXISTS `term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `term` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `term_name` enum('First Semester','Second Semester','Summer') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `term_status` enum('Active','Inactive','Closed','Archived') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`term_id`),
  UNIQUE KEY `uq_term_year_name` (`academic_year_id`,`term_name`),
  CONSTRAINT `fk_term_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_year` (`academic_year_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_term_dates` CHECK (`start_date` < `end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `term`
--

LOCK TABLES `term` WRITE;
/*!40000 ALTER TABLE `term` DISABLE KEYS */;
INSERT INTO `term` VALUES (1,1,'Second Semester','2026-01-10','2026-05-30','Active','2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `term` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `two_factor_challenge`
--

DROP TABLE IF EXISTS `two_factor_challenge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `two_factor_challenge` (
  `two_factor_challenge_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `challenge_type` enum('QR Code','PIN','Passkey') NOT NULL,
  `challenge_code_hash` varchar(255) NOT NULL,
  `expiration_at` datetime NOT NULL,
  `verification_at` datetime DEFAULT NULL,
  `challenge_status` enum('Pending','Verified','Expired','Failed') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`two_factor_challenge_id`),
  KEY `fk_two_factor_user` (`user_id`),
  CONSTRAINT `fk_two_factor_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `two_factor_challenge`
--

LOCK TABLES `two_factor_challenge` WRITE;
/*!40000 ALTER TABLE `two_factor_challenge` DISABLE KEYS */;
INSERT INTO `two_factor_challenge` VALUES (1,1,'PIN','$2y$10$xUyEshx8KKSWpb4AfAvpqu0xY.CC20FRW4UO03FCbTs47qIia6Upu','2026-05-01 15:50:59','2026-05-01 15:41:25','Verified','2026-05-01 07:40:59'),(2,1,'PIN','$2y$10$Iz5UAUvQkbK7w8I8K52wv.g0D5dVnI.Vqg2C0mlUT//M2iRt2Uetm','2026-05-01 15:51:39',NULL,'Expired','2026-05-01 07:41:39'),(3,1,'PIN','$2y$10$cCeraE342eQB6zE9YlD9i.3.2DFl974ZeUX.7kTaT.eV0MuApsl6G','2026-05-01 16:03:29',NULL,'Expired','2026-05-01 07:53:29'),(4,1,'PIN','$2y$10$EtucZRXbj3zPfn5CVs3Pqu5toM82tZVmJw24iZNpO1/sR3VHJTgiG','2026-05-01 16:13:39','2026-05-01 16:03:39','Verified','2026-05-01 08:03:39'),(5,1,'PIN','$2y$10$X6jf7nSRv14EapGblkJ94uWGoHHD5Ezb9qGJinhKSJUstH.98Zm9K','2026-05-01 16:13:49','2026-05-01 16:03:59','Verified','2026-05-01 08:03:49'),(6,1,'PIN','$2y$10$WS8cmYuE/LZvbScFhVksKuC3qW77s7mveOM3u.cEQr6didGK6mlwy','2026-05-01 16:24:15','2026-05-01 16:17:01','Verified','2026-05-01 08:14:15'),(7,1,'PIN','$2y$10$l5YWCWyrzHJmiw7VHOHCp.owNotLVMmun7Vuu.sncRkBMWOWv4ACO','2026-05-01 16:27:14','2026-05-01 16:17:47','Verified','2026-05-01 08:17:14'),(8,1,'PIN','$2y$10$nLl591HvTH2uXRpMsYHWlecCRHRMvJujRGOr/WK1HXovyYmz5kDAC','2026-05-01 16:27:53','2026-05-01 16:18:49','Verified','2026-05-01 08:17:53'),(9,2,'PIN','$2y$10$YZuE8tUn9nOMZpwTiBJrpeKt1O2DHe74fUaTSLPt.yNNril/bYCYC','2026-05-01 16:50:40','2026-05-01 16:40:45','Verified','2026-05-01 08:40:40'),(10,2,'PIN','$2y$10$VHA0BWgm.N.aXZRQsW4/BOJqS8soV9LaqmIR2Nd4.aNlZi5bz7MKu','2026-05-01 16:52:00','2026-05-01 16:42:12','Verified','2026-05-01 08:42:00'),(11,4,'PIN','$2y$10$LKjG1uVrBM/4HTOfhAexPelprqrVsGpx7C1/chs78AE223I1Kle/6','2026-05-01 17:04:08',NULL,'Expired','2026-05-01 08:54:08'),(12,1,'PIN','$2y$10$Ny/FFW9hdvUe1.pMUYI6TOkzpMBj8W/IgDWsbzXkSHt/7DV02Jag2','2026-05-01 17:05:44',NULL,'Expired','2026-05-01 08:55:44'),(13,1,'PIN','$2y$10$64/04Gk7wvgtA0qlgXTmDOBqjMNhcGKTfCB6xLwlEkD4sEnudckca','2026-05-01 17:06:03','2026-05-01 16:56:20','Verified','2026-05-01 08:56:03'),(14,1,'PIN','$2y$10$7kmJbFOknMJoxKpk9TXoOeXiN5TJQ3/SHB22Vpd7H8tFMo13XwirW','2026-05-01 17:08:01','2026-05-01 16:58:05','Verified','2026-05-01 08:58:01'),(15,2,'PIN','$2y$10$bmMUbWQ/H18nQ6spemTQSuPAX9On6pA22RF0Dxzv7MKRQYXnCLTQy','2026-05-01 17:08:16','2026-05-01 16:58:25','Verified','2026-05-01 08:58:16'),(16,1,'PIN','$2y$10$aISiY8qVi03GuLTYp.WHCOhWBHa2ZWuab8tylcVUOSKwWsckAMZCi','2026-05-01 17:09:38',NULL,'Expired','2026-05-01 08:59:38'),(17,4,'PIN','$2y$10$M9ICVYbGqe4XiK.e2McwoOLZjYrJFiolK7IiYJc6gpSLFZoRMIViG','2026-05-01 17:10:32','2026-05-01 17:00:37','Verified','2026-05-01 09:00:32'),(18,1,'PIN','$2y$10$qTjg60P2GjrQQD2/BqoL1OSkPv8iYQxlsX/kvF.i9GtXfw4jwhqW2','2026-05-01 17:16:10','2026-05-01 17:06:16','Verified','2026-05-01 09:06:10'),(19,1,'PIN','$2y$10$hrHecBjNBQP.RPC6bHb.FukzRMGtc3iphIBsQENe92V.39TzgH6Tm','2026-05-01 17:16:58','2026-05-01 17:07:02','Verified','2026-05-01 09:06:58'),(20,1,'PIN','$2y$10$EvWFVxDp.NT2TCf0eN6nQOt7sj5ilDSLTOfSDqG52CtIaYWpSuoHK','2026-05-01 17:22:26','2026-05-01 17:12:31','Verified','2026-05-01 09:12:26'),(21,4,'PIN','$2y$10$cbfP7CXyuKnCfYZBJalZR.ajuN6jUPXj09gDMk1bHdroQLwvxw9rK','2026-05-01 17:22:41','2026-05-01 17:12:46','Verified','2026-05-01 09:12:41'),(22,1,'PIN','$2y$10$d7xYsGwSgyfkTc.B3Q4yY.oQfb.pL.fv5QPNbAO/lp/oH/qoLPkpa','2026-05-01 17:22:56','2026-05-01 17:12:59','Verified','2026-05-01 09:12:56'),(23,2,'PIN','$2y$10$WOU/x01JI6kCURexyLIsHeMsGoEE9yUCJJp5GLI6jjnQ8ZRoiUxhC','2026-05-01 17:23:12','2026-05-01 17:13:15','Verified','2026-05-01 09:13:12'),(24,4,'PIN','$2y$10$XLsLTYAgdAdjJUM8YItU9erMTSrnMFeZx7ZLmrQSM2dDEMnIx65rK','2026-05-01 17:23:31','2026-05-01 17:13:37','Verified','2026-05-01 09:13:31'),(25,1,'PIN','$2y$10$gDImODCIJ0otAuvM2LNOg.B9mUCuiT23f5wo11Uvr.3d/BbnaKtVu','2026-05-01 17:27:15','2026-05-01 17:17:18','Verified','2026-05-01 09:17:15'),(26,2,'PIN','$2y$10$OJYPQWcZ.TSpv9SABQydneBdL1DctHbjQcDLkJSidiQU.ez4t9YDy','2026-05-01 17:27:46','2026-05-01 17:17:49','Verified','2026-05-01 09:17:46'),(27,4,'PIN','$2y$10$zc.k8jtE1dYtKOILH/TbHOcNnfdrsUA8jAOCYWTU72zf7M9I5sd9a','2026-05-01 17:27:59','2026-05-01 17:18:02','Verified','2026-05-01 09:17:59'),(28,4,'PIN','$2y$10$9.70AOA3mBQa3xLOR39xYejNcdWgsZ7/I1HFMuqENiVxYqhzn9EFm','2026-05-01 17:28:56','2026-05-01 17:18:59','Verified','2026-05-01 09:18:56'),(29,1,'PIN','$2y$10$h5//LmzU/vBqdiEEE9v3IOrMCMW9ZHaYfCiqCFoQt0m3xOIbAT6PS','2026-05-01 17:29:16','2026-05-01 17:19:23','Verified','2026-05-01 09:19:16');
/*!40000 ALTER TABLE `two_factor_challenge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `university_id` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `account_status` enum('Active','Inactive','Suspended','Restricted') NOT NULL DEFAULT 'Active',
  `is_two_factor_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `university_id` (`university_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_user_role_status` (`role_id`,`account_status`),
  KEY `idx_user_university_status` (`university_id`,`account_status`),
  CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,3,'ADM001','admin@sample.edu','$2y$10$sH/VUIbUd/xyELlqmyXWBOTTTZBEbjM2NP2quqZ6djdrrLDlhribW','Active',1,'2026-05-01 17:19:23','2026-05-01 04:00:56','2026-05-01 09:19:23'),(2,2,'FAC001','maria.delacruz@sample.edu','$2y$10$LvGsTEwJwxhfvZXec9Kee.4zHHB376woZ.vxzPp5KTIVb05dLijWS','Active',1,'2026-05-01 17:17:49','2026-05-01 04:00:56','2026-05-01 09:17:49'),(3,2,'FAC002','juan.santos@sample.edu','sample_hash_faculty_2','Active',1,NULL,'2026-05-01 04:00:56','2026-05-01 04:00:56'),(4,1,'24-001234','mario.domingo@sample.edu','$2y$10$ilxCfT5U1wsvPwkaf58SVew0A23lqh2VRW4tER/oU13tV74X9BsO.','Active',1,'2026-05-01 17:18:59','2026-05-01 04:00:56','2026-05-01 09:18:59'),(5,1,'24-001235','ana.reyes@sample.edu','sample_hash_student_2','Active',1,NULL,'2026-05-01 04:00:56','2026-05-01 04:00:56');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_user_ai
AFTER INSERT ON `user`
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Insert',
        'user',
        NEW.user_id,
        NULL,
        JSON_OBJECT('user_id', NEW.user_id, 'university_id', NEW.university_id, 'email', NEW.email, 'account_status', NEW.account_status)
    );
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
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_user_au
AFTER UPDATE ON `user`
FOR EACH ROW
BEGIN
    CALL sp_write_audit(
        'Update',
        'user',
        NEW.user_id,
        JSON_OBJECT('university_id', OLD.university_id, 'email', OLD.email, 'account_status', OLD.account_status),
        JSON_OBJECT('university_id', NEW.university_id, 'email', NEW.email, 'account_status', NEW.account_status)
    );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `user_preference`
--

DROP TABLE IF EXISTS `user_preference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_preference` (
  `user_preference_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `theme_mode` enum('Light','Dark') NOT NULL DEFAULT 'Light',
  `notification_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_preference_id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_preference_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_preference`
--

LOCK TABLES `user_preference` WRITE;
/*!40000 ALTER TABLE `user_preference` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_preference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_session`
--

DROP TABLE IF EXISTS `user_session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_session` (
  `user_session_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `session_token_hash` varchar(255) NOT NULL,
  `device_information` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `login_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expiration_at` datetime NOT NULL,
  `logout_at` datetime DEFAULT NULL,
  `session_status` enum('Active','Expired','Logged Out','Revoked') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`user_session_id`),
  KEY `fk_user_session_user` (`user_id`),
  CONSTRAINT `fk_user_session_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_session`
--

LOCK TABLES `user_session` WRITE;
/*!40000 ALTER TABLE `user_session` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_admin_submission_monitoring`
--

DROP TABLE IF EXISTS `v_admin_submission_monitoring`;
/*!50001 DROP VIEW IF EXISTS `v_admin_submission_monitoring`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_admin_submission_monitoring` AS SELECT
 1 AS `student_id`,
  1 AS `student_number`,
  1 AS `student_name`,
  1 AS `course_code`,
  1 AS `year_level`,
  1 AS `section_name`,
  1 AS `total_required_evaluations`,
  1 AS `completed_evaluations`,
  1 AS `pending_evaluations`,
  1 AS `completion_status` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_faculty_anonymous_comment`
--

DROP TABLE IF EXISTS `v_faculty_anonymous_comment`;
/*!50001 DROP VIEW IF EXISTS `v_faculty_anonymous_comment`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_faculty_anonymous_comment` AS SELECT
 1 AS `faculty_id`,
  1 AS `subject_code`,
  1 AS `subject_title`,
  1 AS `section_name`,
  1 AS `average_score`,
  1 AS `submitted_at`,
  1 AS `comment_text`,
  1 AS `moderation_status` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_faculty_released_result`
--

DROP TABLE IF EXISTS `v_faculty_released_result`;
/*!50001 DROP VIEW IF EXISTS `v_faculty_released_result`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_faculty_released_result` AS SELECT
 1 AS `faculty_evaluation_result_id`,
  1 AS `faculty_id`,
  1 AS `faculty_name`,
  1 AS `subject_code`,
  1 AS `subject_title`,
  1 AS `section_name`,
  1 AS `course_code`,
  1 AS `year_level`,
  1 AS `eligible_student_count`,
  1 AS `submitted_response_count`,
  1 AS `pending_response_count`,
  1 AS `participation_rate`,
  1 AS `overall_average_score`,
  1 AS `release_status`,
  1 AS `released_at` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_report_summary`
--

DROP TABLE IF EXISTS `v_report_summary`;
/*!50001 DROP VIEW IF EXISTS `v_report_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_report_summary` AS SELECT
 1 AS `academic_year_name`,
  1 AS `term_name`,
  1 AS `department_code`,
  1 AS `course_code`,
  1 AS `year_level`,
  1 AS `section_name`,
  1 AS `subject_code`,
  1 AS `subject_title`,
  1 AS `faculty_id`,
  1 AS `faculty_name`,
  1 AS `eligible_student_count`,
  1 AS `submitted_response_count`,
  1 AS `pending_response_count`,
  1 AS `participation_rate`,
  1 AS `overall_average_score`,
  1 AS `release_status`,
  1 AS `released_at` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_student_assigned_evaluation`
--

DROP TABLE IF EXISTS `v_student_assigned_evaluation`;
/*!50001 DROP VIEW IF EXISTS `v_student_assigned_evaluation`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_student_assigned_evaluation` AS SELECT
 1 AS `student_evaluation_task_id`,
  1 AS `student_id`,
  1 AS `student_number`,
  1 AS `student_name`,
  1 AS `faculty_id`,
  1 AS `faculty_name`,
  1 AS `subject_id`,
  1 AS `subject_code`,
  1 AS `subject_title`,
  1 AS `section_id`,
  1 AS `section_name`,
  1 AS `course_code`,
  1 AS `year_level`,
  1 AS `task_status`,
  1 AS `average_score`,
  1 AS `submitted_at` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_admin_submission_monitoring`
--

/*!50001 DROP VIEW IF EXISTS `v_admin_submission_monitoring`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_admin_submission_monitoring` AS select `st`.`student_id` AS `student_id`,`st`.`student_number` AS `student_number`,`st`.`full_name` AS `student_name`,`c`.`course_code` AS `course_code`,`sec`.`year_level` AS `year_level`,`sec`.`section_name` AS `section_name`,count(`setask`.`student_evaluation_task_id`) AS `total_required_evaluations`,sum(case when `setask`.`task_status` = 'Submitted' then 1 else 0 end) AS `completed_evaluations`,sum(case when `setask`.`task_status` <> 'Submitted' then 1 else 0 end) AS `pending_evaluations`,case when count(`setask`.`student_evaluation_task_id`) = sum(case when `setask`.`task_status` = 'Submitted' then 1 else 0 end) then 'Completed' else 'Pending' end AS `completion_status` from ((((`student` `st` join `student_section_enrollment` `sse` on(`sse`.`student_id` = `st`.`student_id`)) join `section` `sec` on(`sec`.`section_id` = `sse`.`section_id`)) join `course` `c` on(`c`.`course_id` = `sec`.`course_id`)) left join `student_evaluation_task` `setask` on(`setask`.`student_id` = `st`.`student_id`)) group by `st`.`student_id`,`st`.`student_number`,`st`.`full_name`,`c`.`course_code`,`sec`.`year_level`,`sec`.`section_name` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_faculty_anonymous_comment`
--

/*!50001 DROP VIEW IF EXISTS `v_faculty_anonymous_comment`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_faculty_anonymous_comment` AS select `f`.`faculty_id` AS `faculty_id`,`sub`.`subject_code` AS `subject_code`,`sub`.`subject_title` AS `subject_title`,`sec`.`section_name` AS `section_name`,`er`.`average_score` AS `average_score`,`er`.`submitted_at` AS `submitted_at`,`erc`.`comment_text` AS `comment_text`,`erc`.`moderation_status` AS `moderation_status` from ((((((`evaluation_response_comment` `erc` join `evaluation_response` `er` on(`er`.`evaluation_response_id` = `erc`.`evaluation_response_id`)) join `teaching_assignment` `ta` on(`ta`.`teaching_assignment_id` = `er`.`teaching_assignment_id`)) join `faculty` `f` on(`f`.`faculty_id` = `ta`.`faculty_id`)) join `section_subject_offering` `sso` on(`sso`.`section_subject_offering_id` = `ta`.`section_subject_offering_id`)) join `subject` `sub` on(`sub`.`subject_id` = `sso`.`subject_id`)) join `section` `sec` on(`sec`.`section_id` = `sso`.`section_id`)) where `er`.`response_status` = 'Submitted' and `erc`.`is_visible_to_faculty` = 1 and `erc`.`moderation_status` = 'Approved' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_faculty_released_result`
--

/*!50001 DROP VIEW IF EXISTS `v_faculty_released_result`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_faculty_released_result` AS select `fer`.`faculty_evaluation_result_id` AS `faculty_evaluation_result_id`,`f`.`faculty_id` AS `faculty_id`,`f`.`full_name` AS `faculty_name`,`sub`.`subject_code` AS `subject_code`,`sub`.`subject_title` AS `subject_title`,`sec`.`section_name` AS `section_name`,`c`.`course_code` AS `course_code`,`sec`.`year_level` AS `year_level`,`fer`.`eligible_student_count` AS `eligible_student_count`,`fer`.`submitted_response_count` AS `submitted_response_count`,`fer`.`pending_response_count` AS `pending_response_count`,`fer`.`participation_rate` AS `participation_rate`,`fer`.`overall_average_score` AS `overall_average_score`,`err`.`release_status` AS `release_status`,`err`.`released_at` AS `released_at` from (((((((`faculty_evaluation_result` `fer` join `evaluation_result_release` `err` on(`err`.`evaluation_result_release_id` = `fer`.`evaluation_result_release_id`)) join `teaching_assignment` `ta` on(`ta`.`teaching_assignment_id` = `fer`.`teaching_assignment_id`)) join `faculty` `f` on(`f`.`faculty_id` = `ta`.`faculty_id`)) join `section_subject_offering` `sso` on(`sso`.`section_subject_offering_id` = `ta`.`section_subject_offering_id`)) join `subject` `sub` on(`sub`.`subject_id` = `sso`.`subject_id`)) join `section` `sec` on(`sec`.`section_id` = `sso`.`section_id`)) join `course` `c` on(`c`.`course_id` = `sec`.`course_id`)) where `err`.`release_status` = 'Released' and `fer`.`result_status` = 'Released' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_report_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_report_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_report_summary` AS select `ay`.`academic_year_name` AS `academic_year_name`,`t`.`term_name` AS `term_name`,`d`.`department_code` AS `department_code`,`c`.`course_code` AS `course_code`,`sec`.`year_level` AS `year_level`,`sec`.`section_name` AS `section_name`,`sub`.`subject_code` AS `subject_code`,`sub`.`subject_title` AS `subject_title`,`f`.`faculty_id` AS `faculty_id`,`f`.`full_name` AS `faculty_name`,`fer`.`eligible_student_count` AS `eligible_student_count`,`fer`.`submitted_response_count` AS `submitted_response_count`,`fer`.`pending_response_count` AS `pending_response_count`,`fer`.`participation_rate` AS `participation_rate`,`fer`.`overall_average_score` AS `overall_average_score`,`err`.`release_status` AS `release_status`,`err`.`released_at` AS `released_at` from (((((((((((`faculty_evaluation_result` `fer` join `evaluation_result_release` `err` on(`err`.`evaluation_result_release_id` = `fer`.`evaluation_result_release_id`)) join `evaluation_period` `ep` on(`ep`.`evaluation_period_id` = `fer`.`evaluation_period_id`)) join `term` `t` on(`t`.`term_id` = `ep`.`term_id`)) join `academic_year` `ay` on(`ay`.`academic_year_id` = `t`.`academic_year_id`)) join `teaching_assignment` `ta` on(`ta`.`teaching_assignment_id` = `fer`.`teaching_assignment_id`)) join `faculty` `f` on(`f`.`faculty_id` = `ta`.`faculty_id`)) join `section_subject_offering` `sso` on(`sso`.`section_subject_offering_id` = `ta`.`section_subject_offering_id`)) join `subject` `sub` on(`sub`.`subject_id` = `sso`.`subject_id`)) join `department` `d` on(`d`.`department_id` = `sub`.`department_id`)) join `section` `sec` on(`sec`.`section_id` = `sso`.`section_id`)) join `course` `c` on(`c`.`course_id` = `sec`.`course_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_student_assigned_evaluation`
--

/*!50001 DROP VIEW IF EXISTS `v_student_assigned_evaluation`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_student_assigned_evaluation` AS select `setask`.`student_evaluation_task_id` AS `student_evaluation_task_id`,`st`.`student_id` AS `student_id`,`st`.`student_number` AS `student_number`,`st`.`full_name` AS `student_name`,`f`.`faculty_id` AS `faculty_id`,`f`.`full_name` AS `faculty_name`,`sub`.`subject_id` AS `subject_id`,`sub`.`subject_code` AS `subject_code`,`sub`.`subject_title` AS `subject_title`,`sec`.`section_id` AS `section_id`,`sec`.`section_name` AS `section_name`,`c`.`course_code` AS `course_code`,`sec`.`year_level` AS `year_level`,`setask`.`task_status` AS `task_status`,`er`.`average_score` AS `average_score`,`er`.`submitted_at` AS `submitted_at` from ((((((((`student_evaluation_task` `setask` join `student` `st` on(`st`.`student_id` = `setask`.`student_id`)) join `teaching_assignment` `ta` on(`ta`.`teaching_assignment_id` = `setask`.`teaching_assignment_id`)) join `faculty` `f` on(`f`.`faculty_id` = `ta`.`faculty_id`)) join `section_subject_offering` `sso` on(`sso`.`section_subject_offering_id` = `ta`.`section_subject_offering_id`)) join `subject` `sub` on(`sub`.`subject_id` = `sso`.`subject_id`)) join `section` `sec` on(`sec`.`section_id` = `sso`.`section_id`)) join `course` `c` on(`c`.`course_id` = `sec`.`course_id`)) left join `evaluation_response` `er` on(`er`.`student_evaluation_task_id` = `setask`.`student_evaluation_task_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-01 21:27:37
