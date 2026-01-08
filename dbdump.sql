-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: localhost    Database: gamedb
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cards`
--

DROP TABLE IF EXISTS `cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `suit` enum('hearts','diamonds','clubs','spades') NOT NULL,
  `value` enum('A','2','3','4','5','6','7','8','9','10','J','Q','K') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cards`
--

LOCK TABLES `cards` WRITE;
/*!40000 ALTER TABLE `cards` DISABLE KEYS */;
INSERT INTO `cards` VALUES (1,'hearts','A'),(2,'hearts','2'),(3,'hearts','3'),(4,'hearts','4'),(5,'hearts','5'),(6,'hearts','6'),(7,'hearts','7'),(8,'hearts','8'),(9,'hearts','9'),(10,'hearts','10'),(11,'hearts','J'),(12,'hearts','Q'),(13,'hearts','K'),(14,'diamonds','A'),(15,'diamonds','2'),(16,'diamonds','3'),(17,'diamonds','4'),(18,'diamonds','5'),(19,'diamonds','6'),(20,'diamonds','7'),(21,'diamonds','8'),(22,'diamonds','9'),(23,'diamonds','10'),(24,'diamonds','J'),(25,'diamonds','Q'),(26,'diamonds','K'),(27,'clubs','A'),(28,'clubs','2'),(29,'clubs','3'),(30,'clubs','4'),(31,'clubs','5'),(32,'clubs','6'),(33,'clubs','7'),(34,'clubs','8'),(35,'clubs','9'),(36,'clubs','10'),(37,'clubs','J'),(38,'clubs','Q'),(39,'clubs','K'),(40,'spades','A'),(41,'spades','2'),(42,'spades','3'),(43,'spades','4'),(44,'spades','5'),(45,'spades','6'),(46,'spades','7'),(47,'spades','8'),(48,'spades','9'),(49,'spades','10'),(50,'spades','J'),(51,'spades','Q'),(52,'spades','K');
/*!40000 ALTER TABLE `cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game_cards`
--

DROP TABLE IF EXISTS `game_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_cards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` int NOT NULL,
  `card_id` int NOT NULL,
  `location` enum('deck','p1_hand','p2_hand','table','p1_captured','p2_captured') NOT NULL DEFAULT 'deck',
  `played_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_game_card` (`game_id`,`card_id`),
  KEY `card_id` (`card_id`),
  CONSTRAINT `game_cards_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  CONSTRAINT `game_cards_ibfk_2` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_cards`
--

LOCK TABLES `game_cards` WRITE;
/*!40000 ALTER TABLE `game_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `game_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `games`
--

DROP TABLE IF EXISTS `games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `games` (
  `id` int NOT NULL AUTO_INCREMENT,
  `status` enum('active','finished') DEFAULT 'active',
  `player1_id` int NOT NULL,
  `player2_id` int NOT NULL,
  `current_turn` int DEFAULT NULL,
  `p1_xeri_count` int DEFAULT '0',
  `p2_xeri_count` int DEFAULT '0',
  `first_round_done` tinyint(1) DEFAULT '0',
  `p1_xeri_bales_count` int DEFAULT '0',
  `p2_xeri_bales_count` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `player1_id` (`player1_id`),
  KEY `player2_id` (`player2_id`),
  KEY `current_turn` (`current_turn`),
  CONSTRAINT `games_ibfk_1` FOREIGN KEY (`player1_id`) REFERENCES `players` (`id`),
  CONSTRAINT `games_ibfk_2` FOREIGN KEY (`player2_id`) REFERENCES `players` (`id`),
  CONSTRAINT `games_ibfk_3` FOREIGN KEY (`current_turn`) REFERENCES `players` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games`
--

LOCK TABLES `games` WRITE;
/*!40000 ALTER TABLE `games` DISABLE KEYS */;
/*!40000 ALTER TABLE `games` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `players`
--

DROP TABLE IF EXISTS `players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `players` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `token` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `players`
--

LOCK TABLES `players` WRITE;
/*!40000 ALTER TABLE `players` DISABLE KEYS */;
INSERT INTO `players` VALUES (1,'giorgos','55b49d02482355ca020926be96bcbc3e','2026-01-08 00:16:27'),(2,'maria','4574eae294b17600ef8f437d9253fa83','2026-01-08 00:26:22');
/*!40000 ALTER TABLE `players` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-08 19:06:32
