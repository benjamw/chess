
--
-- Table structure for table `wc2_chat`
--

DROP TABLE IF EXISTS `wc2_chat`;
CREATE TABLE `wc2_chat` (
  `c_game_id` int(11) NOT NULL default '0',
  `c_player_id` int(11) NOT NULL default '0',
  `c_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `c_message` text collate latin1_general_ci NOT NULL,
  `c_private` enum('','Yes','No') collate latin1_general_ci NOT NULL default 'No',
  KEY `game_id` (`c_game_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Table structure for table `wc2_game`
--

DROP TABLE IF EXISTS `wc2_game`;
CREATE TABLE `wc2_game` (
  `g_id` int(11) NOT NULL auto_increment,
  `g_id960` smallint(3) NOT NULL default '518',
  `g_white_player_id` int(11) NOT NULL default '0',
  `g_black_player_id` int(11) NOT NULL default '0',
  `g_game_message` enum('','Player Invited','Invite Declined','Draw','Player Resigned','Checkmate') collate latin1_general_ci NOT NULL default '',
  `g_message_from` enum('','black','white') collate latin1_general_ci NOT NULL default '',
  `g_date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `g_last_move` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`g_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Table structure for table `wc2_history`
--

DROP TABLE IF EXISTS `wc2_history`;
CREATE TABLE `wc2_history` (
  `h_time` datetime NOT NULL default '0000-00-00 00:00:00',
  `h_game_id` int(11) NOT NULL default '0',
  `h_fen` varchar(100) collate latin1_general_ci NOT NULL default '',
  KEY `h_game_id` (`h_game_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Table structure for table `wc2_message`
--

DROP TABLE IF EXISTS `wc2_message`;
CREATE TABLE `wc2_message` (
  `m_id` int(11) NOT NULL auto_increment,
  `m_game_id` int(11) NOT NULL default '0',
  `m_type` enum('undo','draw') collate latin1_general_ci NOT NULL default 'undo',
  `m_status` enum('request','approved','denied') collate latin1_general_ci NOT NULL default 'request',
  `m_destination` enum('black','white') collate latin1_general_ci NOT NULL default 'black',
  PRIMARY KEY  (`m_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Table structure for table `wc2_player`
--

DROP TABLE IF EXISTS `wc2_player`;
CREATE TABLE `wc2_player` (
  `p_id` int(11) NOT NULL auto_increment,
  `p_password` varchar(32) collate latin1_general_ci NOT NULL default '',
  `p_first_name` varchar(20) collate latin1_general_ci NOT NULL default '',
  `p_last_name` varchar(20) collate latin1_general_ci NOT NULL default '',
  `p_username` varchar(20) collate latin1_general_ci NOT NULL default '',
  `p_is_admin` tinyint(1) NOT NULL default '0',
  `p_history` varchar(25) collate latin1_general_ci NOT NULL default 'pgn',
  `p_layout` varchar(25) collate latin1_general_ci NOT NULL default 'columns',
  `p_theme` varchar(25) collate latin1_general_ci NOT NULL default 'plain',
  `p_auto_reload` smallint(6) NOT NULL default '0',
  `p_email` varchar(50) collate latin1_general_ci default NULL,
  `p_max_games` smallint(6) NOT NULL default '5',
  `p_show_last_move` tinyint(1) NOT NULL default '1',
  `p_wins` int(5) NOT NULL default '0',
  `p_draws` int(5) NOT NULL default '0',
  `p_losses` int(5) NOT NULL default '0',
  `p_rating` mediumint(4) NOT NULL default '1500',
  `p_ident` varchar(32) collate latin1_general_ci default NULL,
  `p_token` varchar(32) collate latin1_general_ci default NULL,
  `p_created` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`p_id`),
  UNIQUE KEY `username` (`p_username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Table structure for table `wc2_stat`
--

DROP TABLE IF EXISTS `wc2_stat`;
CREATE TABLE `wc2_stat` (
  `s_id` int(11) NOT NULL default '0',
  `s_moves` int(11) default NULL,
  `s_days` int(11) default NULL,
  `s_streak` int(11) default NULL,
  KEY `s_id` (`s_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Table structure for table `wc2_talk`
--

DROP TABLE IF EXISTS `wc2_talk`;
CREATE TABLE `wc2_talk` (
  `t_id` int(11) NOT NULL auto_increment,
  `t_game_id` int(11) default NULL,
  `t_from_player_id` int(11) default NULL,
  `t_to_player_id` int(11) default NULL,
  `t_subject` varchar(255) collate latin1_general_ci default NULL,
  `t_text` longtext collate latin1_general_ci,
  `t_post_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `t_expire_date` datetime default NULL,
  `t_ack` tinyint(1) default NULL,
  `t_comm_type` smallint(6) default NULL,
  PRIMARY KEY  (`t_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
