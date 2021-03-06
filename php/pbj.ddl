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
) ENGINE=InnoDB AUTO_INCREMENT=162 DEFAULT CHARSET=utf8;

CREATE TABLE `event` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `is_published` tinyint(4) DEFAULT NULL,
  `html_description` text CHARACTER SET latin1,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8;

CREATE TABLE `event_web_module` (
  `event_id` int(11) NOT NULL DEFAULT '0',
  `web_module_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`event_id`,`web_module_id`),
  CONSTRAINT `event_web_module_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `guest` (
  `guest_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `is_organizer` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`guest_id`),
  KEY `fk_guest_user` (`user_id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `fk_guest_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `guest_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=176 DEFAULT CHARSET=utf8;

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(4) DEFAULT NULL,
  `user_family_id` int(11) DEFAULT NULL,
  `google_id` varchar(50) DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=166 DEFAULT CHARSET=utf8;

CREATE TABLE `web_module` (
  `web_module_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) DEFAULT NULL,
  `controller_name` varchar(250) DEFAULT NULL,
  `is_event_default` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`web_module_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

