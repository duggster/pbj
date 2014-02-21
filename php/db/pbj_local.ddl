
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `pbj` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `pbj`;
DROP TABLE IF EXISTS `communication_preference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_preference` (
  `communication_preference_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `preference_type` varchar(20) DEFAULT NULL,
  `handle` varchar(250) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT NULL,
  `is_primary` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`communication_preference_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `communication_preference_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=261 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `is_published` tinyint(4) DEFAULT NULL,
  `html_description` text CHARACTER SET latin1,
  `email_address` varchar(150) DEFAULT NULL,
  `event_when` varchar(250) DEFAULT NULL,
  `event_where` varchar(250) DEFAULT NULL,
  `is_show_info` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=176 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_email`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_email` (
  `event_email_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `from_address` varchar(255) DEFAULT NULL,
  `to_addresses` text CHARACTER SET latin1,
  `cc_addresses` text CHARACTER SET latin1,
  `body` mediumtext CHARACTER SET latin1,
  `stripped_body` mediumtext CHARACTER SET latin1,
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`event_email_id`),
  KEY `event_email_ibfk_1` (`event_id`),
  CONSTRAINT `event_email_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_message` (
  `event_message_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `parent_message_id` int(11) DEFAULT NULL,
  `message_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `message` text CHARACTER SET latin1,
  PRIMARY KEY (`event_message_id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `event_message_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=657 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_web_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_web_module` (
  `event_id` int(11) NOT NULL DEFAULT '0',
  `web_module_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`event_id`,`web_module_id`),
  CONSTRAINT `event_web_module_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `guest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guest` (
  `guest_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `is_organizer` tinyint(4) DEFAULT '0',
  `notify_pref` varchar(20) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `guest_link_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`guest_id`),
  KEY `fk_guest_user` (`user_id`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=832 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `guest_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guest_link` (
  `guest_link_id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`guest_link_id`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(4) DEFAULT NULL,
  `user_family_id` int(11) DEFAULT NULL,
  `google_id` varchar(50) DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL,
  `kid_status` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=266 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `web_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `web_module` (
  `web_module_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) DEFAULT NULL,
  `controller_name` varchar(250) DEFAULT NULL,
  `is_event_default` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`web_module_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `web_module_prop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `web_module_prop` (
  `web_module_prop_id` int(11) NOT NULL AUTO_INCREMENT,
  `web_module_id` int(11) NOT NULL,
  `prop_name` varchar(120) DEFAULT NULL,
  `prop_value` mediumtext CHARACTER SET latin1,
  `readonly` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`web_module_prop_id`),
  KEY `web_module_prop_ibfk_1` (`web_module_id`),
  CONSTRAINT `web_module_prop_ibfk_1` FOREIGN KEY (`web_module_id`) REFERENCES `web_module` (`web_module_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `web_module_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `web_module_role` (
  `web_module_role_id` int(11) NOT NULL AUTO_INCREMENT,
  `web_module_id` int(11) DEFAULT NULL,
  `role` varchar(80) DEFAULT NULL,
  `action` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`web_module_role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

