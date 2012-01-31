
DROP TABLE IF EXISTS `wc2_talk`;
CREATE TABLE IF NOT EXISTS `wc2_talk` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `message` text COLLATE latin1_general_ci NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;



DROP TABLE IF EXISTS `wc2_talk_glue`;
CREATE TABLE IF NOT EXISTS `wc2_talk_glue` (
  `message_glue_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `from_id` int(10) unsigned NOT NULL DEFAULT '0',
  `to_id` int(10) unsigned NOT NULL DEFAULT '0',
  `send_date` datetime DEFAULT NULL,
  `expire_date` datetime DEFAULT NULL,
  `view_date` datetime DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`message_glue_id`),
  KEY `outbox` (`from_id`,`message_id`),
  KEY `created` (`create_date`),
  KEY `expire_date` (`expire_date`),
  KEY `inbox` (`to_id`,`from_id`,`send_date`,`deleted`),
  KEY `message_id` (`message_id`,`to_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;


