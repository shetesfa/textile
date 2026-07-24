-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: textile_db
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
-- Current Database: `textile_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `textile_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `textile_db`;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `status` enum('present','absent') DEFAULT 'absent',
  `daily_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_date` (`employee_id`,`work_date`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (106,1,'2026-05-28','present',350.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:05'),(107,2,'2026-05-28','present',280.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:05'),(108,3,'2026-05-28','present',220.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:05'),(109,4,'2026-05-28','present',350.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:05'),(110,5,'2026-05-28','absent',0.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:15'),(111,6,'2026-05-28','absent',0.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:15'),(112,7,'2026-05-28','present',280.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:05'),(113,8,'2026-05-28','present',350.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:05'),(114,9,'2026-05-28','present',180.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:05'),(115,10,'2026-05-28','absent',0.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:15'),(116,11,'2026-05-28','absent',0.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:16'),(117,12,'2026-05-28','present',350.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:05'),(118,13,'2026-05-28','present',180.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:05'),(119,14,'2026-05-28','present',280.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:05'),(120,15,'2026-05-28','present',220.00,3,'2026-05-28 21:50:05','2026-05-28 21:50:05');
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_level_history`
--

DROP TABLE IF EXISTS `employee_level_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_level_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `level` varchar(20) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `effective_from` date NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_emp_date` (`employee_id`,`effective_from`),
  CONSTRAINT `employee_level_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_level_history`
--

LOCK TABLES `employee_level_history` WRITE;
/*!40000 ALTER TABLE `employee_level_history` DISABLE KEYS */;
INSERT INTO `employee_level_history` VALUES (1,1,'A',350.00,'2026-05-01',NULL,'2026-05-28 22:58:30'),(2,2,'B',280.00,'2026-05-02',NULL,'2026-05-28 22:58:30'),(3,3,'C',220.00,'2026-05-03',NULL,'2026-05-28 22:58:30'),(4,4,'A',350.00,'2026-05-04',NULL,'2026-05-28 22:58:30'),(5,5,'B',280.00,'2026-05-05',NULL,'2026-05-28 22:58:30'),(6,6,'C',220.00,'2026-05-06',NULL,'2026-05-28 22:58:30'),(7,7,'B',280.00,'2026-05-07',NULL,'2026-05-28 22:58:30'),(8,8,'A',350.00,'2026-05-08',NULL,'2026-05-28 22:58:30'),(9,9,'D',180.00,'2026-05-09',NULL,'2026-05-28 22:58:30'),(10,10,'C',220.00,'2026-05-10',NULL,'2026-05-28 22:58:30'),(11,11,'B',280.00,'2026-05-11',NULL,'2026-05-28 22:58:30'),(12,12,'A',350.00,'2026-05-12',NULL,'2026-05-28 22:58:30'),(13,13,'D',180.00,'2026-05-13',NULL,'2026-05-28 22:58:30'),(14,14,'B',280.00,'2026-05-14',NULL,'2026-05-28 22:58:30'),(15,15,'C',220.00,'2026-05-15',NULL,'2026-05-28 22:58:30');
/*!40000 ALTER TABLE `employee_level_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `level` char(1) NOT NULL DEFAULT 'D',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'Kebede Tsegaye','0921111111','Senior Tailor','A',1,'2026-05-01 05:00:00'),(2,'Almaz Bekele','0921111112','Tailor','B',1,'2026-05-02 05:00:00'),(3,'Fikru Tesfaye','0921111113','Machine Operator','C',1,'2026-05-03 05:00:00'),(4,'Hirut Wondimu','0921111114','Sewing Specialist','A',1,'2026-05-04 05:00:00'),(5,'Solomon Admasu','0921111115','Quality Controller','B',1,'2026-05-05 05:00:00'),(6,'Meseret Assefa','0921111116','Fabric Cutter','C',1,'2026-05-06 05:00:00'),(7,'Tadesse Desta','0921111117','Tailor','B',1,'2026-05-07 05:00:00'),(8,'Birtukan Mulugeta','0921111118','Finishing Specialist','A',1,'2026-05-08 05:00:00'),(9,'Yonas Ayele','0921111119','Machine Operator','D',1,'2026-05-09 05:00:00'),(10,'Mekdes Lemma','0921111120','Assistant Tailor','C',1,'2026-05-10 05:00:00'),(11,'Temesgen Bekele','0921111121','Senior Machine Operator','B',1,'2026-05-11 05:00:00'),(12,'Selam Teshome','0921111122','Pattern Maker','A',1,'2026-05-12 05:00:00'),(13,'Biruk Alemu','0921111123','Tailor','D',1,'2026-05-13 05:00:00'),(14,'Genet Assefa','0921111124','Fabric Inspector','B',1,'2026-05-14 05:00:00'),(15,'Henok Girma','0921111125','Packing Specialist','C',1,'2026-05-15 05:00:00');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_updates`
--

DROP TABLE IF EXISTS `order_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_updates_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_updates`
--

LOCK TABLES `order_updates` WRITE;
/*!40000 ALTER TABLE `order_updates` DISABLE KEYS */;
INSERT INTO `order_updates` VALUES (1,1,'new','accepted','Order accepted',2,'2026-05-28 17:03:32'),(2,1,'accepted','working','Production started',2,'2026-05-28 17:03:36'),(3,1,'working','half_finished','50 units completed',2,'2026-05-28 17:03:51'),(4,1,'half_finished','finished','Completed: 99 / 100',2,'2026-05-28 17:04:37'),(5,2,'new','accepted','Large order accepted',4,'2026-05-30 07:00:00'),(6,2,'accepted','working','Production started',2,'2026-06-01 06:00:00'),(7,3,'new','accepted','Quality reviewed',5,'2026-05-30 11:00:00'),(8,3,'accepted','working','Cutting phase',4,'2026-05-31 05:00:00'),(9,3,'working','half_finished','180 units done',2,'2026-06-02 07:15:00'),(10,4,'new','accepted','Contract signed',2,'2026-05-31 06:00:00'),(11,5,'new','accepted','Sample approved',5,'2026-05-31 12:00:00'),(12,5,'accepted','working','Mass production',4,'2026-06-03 06:00:00'),(13,7,'new','accepted','Accepted by management',4,'2026-06-01 08:00:00'),(14,7,'accepted','working','Production line allocated',5,'2026-06-01 10:00:00'),(15,7,'working','half_finished','450 units completed',2,'2026-06-01 13:00:00'),(16,8,'new','accepted','Technical specs reviewed',2,'2026-06-02 06:00:00'),(17,8,'accepted','working','200 units cut and sewn',4,'2026-06-03 08:00:00'),(18,14,'accepted','working',NULL,2,'2026-05-28 20:49:04');
/*!40000 ALTER TABLE `order_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `client_name` varchar(100) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `target_quantity` int(11) NOT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('new','accepted','working','half_finished','finished') DEFAULT 'new',
  `completed_quantity` int(11) DEFAULT 0,
  `incomplete_reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'ORD-260528-742','Tesfa Textile','School Uniform',100,'2026-06-06','finished',99,'accidently 1 we cant',1,'2026-05-28 17:04:37','2026-05-28 16:43:26','2026-05-28 17:04:37'),(2,'ORD-260529-001','Addis Ababa University','Graduation Gown',500,'2026-07-15','working',250,NULL,2,NULL,'2026-05-29 06:00:00','2026-06-01 11:30:00'),(3,'ORD-260529-002','Ethiopian Airlines','Flight Attendant Uniform',300,'2026-08-01','half_finished',180,NULL,4,NULL,'2026-05-29 07:00:00','2026-06-02 07:15:00'),(4,'ORD-260530-003','Dashen Bank','Corporate Shirts',1000,'2026-07-30','accepted',0,NULL,2,NULL,'2026-05-30 08:00:00','2026-05-30 08:00:00'),(5,'ORD-260530-004','Hilton Hotel','Staff Uniform',200,'2026-06-25','working',85,NULL,5,NULL,'2026-05-30 10:00:00','2026-06-03 06:00:00'),(6,'ORD-260531-005','Ministry of Education','School Uniforms',5000,'2026-09-15','new',0,NULL,1,NULL,'2026-05-31 05:30:00','2026-05-31 05:30:00'),(7,'ORD-260531-006','Commercial Bank of Ethiopia','Security Uniforms',800,'2026-07-20','half_finished',450,NULL,4,NULL,'2026-05-31 11:00:00','2026-06-01 13:00:00'),(8,'ORD-260601-007','Ethio Telecom','Technician Uniforms',600,'2026-08-10','working',200,NULL,2,NULL,'2026-06-01 06:00:00','2026-06-03 08:00:00'),(9,'ORD-260601-008','Sheger Construction','Safety Vests',300,'2026-06-30','accepted',0,NULL,5,NULL,'2026-06-01 08:00:00','2026-06-01 08:00:00'),(10,'ORD-260602-009','Lucy Restaurant','Aprons & Caps',150,'2026-06-20','new',0,NULL,1,NULL,'2026-06-02 05:00:00','2026-06-02 05:00:00'),(11,'ORD-260602-010','Zemen Bank','Ties & Scarves',400,'2026-07-25','working',120,NULL,4,NULL,'2026-06-02 10:00:00','2026-06-03 05:00:00'),(12,'ORD-260603-011','UNICEF Ethiopia','Children Jackets',2000,'2026-10-01','new',0,NULL,2,NULL,'2026-06-03 06:00:00','2026-06-03 06:00:00'),(13,'ORD-260603-012','Ethiopian Shipping Line','Captain Uniforms',50,'2026-07-10','half_finished',35,NULL,5,NULL,'2026-06-03 11:00:00','2026-06-04 07:00:00'),(14,'ORD-260604-013','Africa Insurance','Company Uniforms',350,'2026-08-05','working',0,NULL,4,NULL,'2026-06-04 05:30:00','2026-05-28 20:49:04'),(15,'ORD-260604-014','Bole Medhanialem Church','Choir Robes',120,'2026-06-28','working',60,NULL,2,NULL,'2026-06-04 07:00:00','2026-06-05 06:00:00'),(16,'ORD-260605-015','National Palace','Presidential Guard Uniform',200,'2026-09-01','new',0,NULL,1,NULL,'2026-06-05 08:00:00','2026-06-05 08:00:00');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_by` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_levels`
--

DROP TABLE IF EXISTS `salary_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` char(1) NOT NULL,
  `label` varchar(100) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `level` (`level`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_levels`
--

LOCK TABLES `salary_levels` WRITE;
/*!40000 ALTER TABLE `salary_levels` DISABLE KEYS */;
INSERT INTO `salary_levels` VALUES (1,'A','Excellent / ßëáßîúßê¥ ßîÑßê⌐',350.00,'2026-05-28 19:41:27'),(2,'B','Good / ßîÑßê⌐',280.00,'2026-05-28 19:41:27'),(3,'C','Average / ßêÿßè½ßè¿ßêêßè¢',220.00,'2026-05-28 19:41:27'),(4,'D','Beginner / ßîÇßê¢ßê¬',180.00,'2026-05-28 19:41:27');
/*!40000 ALTER TABLE `salary_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','manager','writer') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Tesfahun Bayih','tesfa','$2y$10$n6fgG0Uv3kvRvPmHuG/J9udB4PaCN8VdgxfUW60XM3Jv8aLHLzHRC','manager','0943854325',1,1,'2026-05-28 20:35:17'),(3,'Bayih','baye','$2y$10$sHWdTl.zWzZMv5kgSQqQkep95ZZj8UXa.beGhEPkCm/0H8cY2Wi.W','writer','0912121212',2,1,'2026-05-28 20:35:17'),(4,'Abebe Kebede','abebe_m','$2y$10$bCe7bOPV6dsQgFSPgprMrOi.TPibD/qwnqwUExim8xk4ZOcxy3ZH2','manager','0913123456',1,1,'2026-05-28 20:35:17'),(5,'Tigist Desta','tigist_m','$2y$10$bCe7bOPV6dsQgFSPgprMrOi.TPibD/qwnqwUExim8xk4ZOcxy3ZH2','manager','0914123457',1,1,'2026-05-28 20:35:17'),(6,'Meron Alemu','meron_w','$2y$10$bCe7bOPV6dsQgFSPgprMrOi.TPibD/qwnqwUExim8xk4ZOcxy3ZH2','writer','0915123458',2,1,'2026-05-28 20:35:17'),(7,'Dawit Hailu','dawit_w','$2y$10$bCe7bOPV6dsQgFSPgprMrOi.TPibD/qwnqwUExim8xk4ZOcxy3ZH2','writer','0916123459',4,1,'2026-05-28 20:35:17'),(8,'Helen Tekle','helen_w','$2y$10$bCe7bOPV6dsQgFSPgprMrOi.TPibD/qwnqwUExim8xk4ZOcxy3ZH2','writer','0917123460',5,1,'2026-05-28 20:35:17'),(11,'System Owner','owner','$2y$10$bCe7bOPV6dsQgFSPgprMrOi.TPibD/qwnqwUExim8xk4ZOcxy3ZH2','owner',NULL,NULL,1,'2026-05-28 20:39:05');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'textile_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-25  2:07:37
