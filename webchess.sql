-- phpMyAdmin SQL Dump
-- version 4.3.0-dev
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 07, 2014 at 10:26 PM
-- Server version: 5.6.13-log
-- PHP Version: 5.5.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `chess`
--

-- --------------------------------------------------------

--
-- Table structure for table `wc2_chat`
--

DROP TABLE IF EXISTS `wc2_chat`;
CREATE TABLE IF NOT EXISTS `wc2_chat` (
	`c_game_id` int(11) NOT NULL DEFAULT '0',
	`c_player_id` int(11) NOT NULL DEFAULT '0',
	`c_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`c_message` text NOT NULL,
	`c_private` enum('','Yes','No') NOT NULL DEFAULT 'No'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_game`
--

DROP TABLE IF EXISTS `wc2_game`;
CREATE TABLE IF NOT EXISTS `wc2_game` (
	`g_id` int(11) NOT NULL,
	`g_id960` smallint(3) NOT NULL DEFAULT '518',
	`g_white_player_id` int(11) NOT NULL DEFAULT '0',
	`g_black_player_id` int(11) NOT NULL DEFAULT '0',
	`g_game_message` enum('','Player Invited','Invite Declined','Draw','Player Resigned','Checkmate') NOT NULL DEFAULT '',
	`g_message_from` enum('','black','white') NOT NULL DEFAULT '',
	`g_date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`g_last_move` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_history`
--

DROP TABLE IF EXISTS `wc2_history`;
CREATE TABLE IF NOT EXISTS `wc2_history` (
	`h_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`h_game_id` int(11) NOT NULL DEFAULT '0',
	`h_fen` varchar(100) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_message`
--

DROP TABLE IF EXISTS `wc2_message`;
CREATE TABLE IF NOT EXISTS `wc2_message` (
	`m_id` int(11) NOT NULL,
	`m_game_id` int(11) NOT NULL DEFAULT '0',
	`m_type` enum('undo','draw') NOT NULL DEFAULT 'undo',
	`m_status` enum('request','approved','denied') NOT NULL DEFAULT 'request',
	`m_destination` enum('black','white') NOT NULL DEFAULT 'black'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_player`
--

DROP TABLE IF EXISTS `wc2_player`;
CREATE TABLE IF NOT EXISTS `wc2_player` (
	`p_id` int(11) NOT NULL,
	`p_password` varchar(255) NOT NULL DEFAULT '',
	`p_first_name` varchar(20) NOT NULL DEFAULT '',
	`p_last_name` varchar(20) NOT NULL DEFAULT '',
	`p_username` varchar(20) NOT NULL DEFAULT '',
	`p_is_admin` tinyint(1) NOT NULL DEFAULT '0',
	`p_history` varchar(25) NOT NULL DEFAULT 'pgn',
	`p_theme` varchar(25) NOT NULL DEFAULT 'plain',
	`p_auto_reload` smallint(6) NOT NULL DEFAULT '0',
	`p_email` varchar(50) DEFAULT NULL,
	`p_max_games` smallint(6) NOT NULL DEFAULT '5',
	`p_show_last_move` tinyint(1) NOT NULL DEFAULT '1',
	`p_wins` int(5) NOT NULL DEFAULT '0',
	`p_draws` int(5) NOT NULL DEFAULT '0',
	`p_losses` int(5) NOT NULL DEFAULT '0',
	`p_rating` mediumint(4) NOT NULL DEFAULT '1500',
	`p_ident` varchar(32) DEFAULT NULL,
	`p_token` varchar(32) DEFAULT NULL,
	`p_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_stat`
--

DROP TABLE IF EXISTS `wc2_stat`;
CREATE TABLE IF NOT EXISTS `wc2_stat` (
	`s_id` int(11) NOT NULL DEFAULT '0',
	`s_moves` int(11) DEFAULT NULL,
	`s_days` int(11) DEFAULT NULL,
	`s_streak` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_talk`
--

DROP TABLE IF EXISTS `wc2_talk`;
CREATE TABLE IF NOT EXISTS `wc2_talk` (
	`message_id` int(10) unsigned NOT NULL,
	`subject` varchar(255) NOT NULL DEFAULT '',
	`message` text NOT NULL,
	`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_talk_glue`
--

DROP TABLE IF EXISTS `wc2_talk_glue`;
CREATE TABLE IF NOT EXISTS `wc2_talk_glue` (
	`message_glue_id` int(10) unsigned NOT NULL,
	`message_id` int(10) unsigned NOT NULL DEFAULT '0',
	`from_id` int(10) unsigned NOT NULL DEFAULT '0',
	`to_id` int(10) unsigned NOT NULL DEFAULT '0',
	`send_date` datetime DEFAULT NULL,
	`expire_date` datetime DEFAULT NULL,
	`view_date` datetime DEFAULT NULL,
	`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`deleted` tinyint(1) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `wc2_chat`
--
ALTER TABLE `wc2_chat`
 ADD KEY `game_id` (`c_game_id`);

--
-- Indexes for table `wc2_game`
--
ALTER TABLE `wc2_game`
 ADD PRIMARY KEY (`g_id`);

--
-- Indexes for table `wc2_history`
--
ALTER TABLE `wc2_history`
 ADD KEY `h_game_id` (`h_game_id`);

--
-- Indexes for table `wc2_message`
--
ALTER TABLE `wc2_message`
 ADD PRIMARY KEY (`m_id`);

--
-- Indexes for table `wc2_player`
--
ALTER TABLE `wc2_player`
 ADD PRIMARY KEY (`p_id`),
 ADD UNIQUE KEY `username` (`p_username`);

--
-- Indexes for table `wc2_stat`
--
ALTER TABLE `wc2_stat`
 ADD KEY `s_id` (`s_id`);

--
-- Indexes for table `wc2_talk`
--
ALTER TABLE `wc2_talk`
 ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `wc2_talk_glue`
--
ALTER TABLE `wc2_talk_glue`
 ADD PRIMARY KEY (`message_glue_id`),
 ADD KEY `outbox` (`from_id`,`message_id`),
 ADD KEY `inbox` (`to_id`,`message_id`),
 ADD KEY `created` (`create_date`),
 ADD KEY `expire_date` (`expire_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `wc2_game`
--
ALTER TABLE `wc2_game`
MODIFY `g_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `wc2_message`
--
ALTER TABLE `wc2_message`
MODIFY `m_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `wc2_player`
--
ALTER TABLE `wc2_player`
MODIFY `p_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `wc2_talk`
--
ALTER TABLE `wc2_talk`
MODIFY `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `wc2_talk_glue`
--
ALTER TABLE `wc2_talk_glue`
MODIFY `message_glue_id` int(10) unsigned NOT NULL AUTO_INCREMENT;

