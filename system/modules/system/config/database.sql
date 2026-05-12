CREATE TABLE `hbook_categories` (
`id` int(11) NOT NULL auto_increment,
`name` varchar(45) NULL default NULL,
`groups` int(11) NULL default NULL,
`ordning` int(11) NULL default '0',
`companyid` int(11) NULL default '0',
`deleted` tinyint(1) NULL default '0',
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `hbook_help` (
`id` int(11) NOT NULL auto_increment,
`type` varchar(45) NULL default NULL,
`title` varchar(45) NULL default NULL,
`text` text NULL,
PRIMARY KEY  (`id`),
UNIQUE KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `hbook_page` (
`id` int(11) NOT NULL auto_increment,
`pid` int(11) NULL default '0',
`src` varchar(45) NULL default NULL,
`label` varchar(45) NULL default NULL,
`ordning` int(11) NULL default '0',
`isstartpage` int(11) NULL default '0',
`visible` int(1) NULL default '1',
`jumpTo` int(11) NULL default '0',
`companyid` int(11) NULL default '0',
`deleted` tinyint(1) NULL default '0',
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `hbook_page_content` (
`id` int(11) NOT NULL auto_increment,
`pid` int(11) NULL default NULL,
`target` varchar(45) NULL default NULL,
`module` varchar(45) NULL default NULL,
`visible` int(1) NULL default '1',
`ordning` int(11) NULL default NULL,
`settings` blob NULL,
`companyid` int(11) NULL default '0',
`deleted` tinyint(1) NULL default '0',
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `hbook_service` (
`id` int(11) NOT NULL auto_increment,
`atgard` text NULL,
`regnr` int(11) NULL default NULL,
`regnrtext` varchar(10) NULL default NULL,
`mil` int(11) NULL default NULL,
`datum` date NULL default NULL,
`tid` varchar(45) NULL default NULL,
`signatur` varchar(45) NULL default NULL,
`deleted` tinyint(1) NULL default '0',
`ordning` int(2) NOT NULL default '99',
`companyid` int(11) NULL default '0',
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



/*CREATE TABLE `hbook_logs` (
  `id` int(11) NOT NULL auto_increment,
  `datum` timestamp NOT NULL default 'current_timestamp()',
  `uid` int(5) NULL default '0',
  `action` blob NULL,
  `ordning` int(2) NOT NULL default '99',
  `companyid` int(11) NULL default '1',
  `deleted` tinyint(1) NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;*/


/*CREATE TABLE `hbook_logs` (
  `id` int(11) NOT NULL auto_increment,
  `datum` timestamp default CURRENT_TIMESTAMP,
  `uid` int(5) NULL default '0',
  `action` blob DEFAULT NULL,
  `ordning` int(2) NOT NULL default '99',
  `companyid` int(11) NULL default '0',
  `deleted` tinyint(1) NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;*/

/*CREATE TABLE `hbook_logs` (
  `id` int(11) NOT NULL auto_increment,
  `datum` datetime DEFAULT current_timestamp(),
  `uid` int(5) DEFAULT 0,
  `action` varchar(255) NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;*/







/*CREATE TABLE `hbook_user` (
`id` int(11) NOT NULL auto_increment,
`user` varchar(20) NOT NULL default '',
`pass` varchar(20) NOT NULL default '',
`namn` varchar(50) NOT NULL default '',
`groups` blob NOT NULL,
`superadmin` tinyint(1) NULL default '0',
`_foretag` int(11) unsigned NULL default '0',
`referens` varchar(100) NULL default '',
`securepass` varchar(150) NULL default '',
`driverid` int(11) NULL default '0',
`shortdriver` varchar(3) NULL default '',
`grupp` int(2) NULL default '0',
`ordning` int(2) NULL default '99',
`ordinary` varchar(10) NULL default '',
`active` tinyint(1) NULL default '1',
`deleted` tinyint(1) NULL default '0',
`email` varchar(50) NULL default '',
`companyid` int(11) NULL default '0',
`lostpassword` varchar(150) NULL default '',
`changepwdnextlogin` tinyint(1) NULL default '0',
`sendemail` tinyint(1) NULL default '0'
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;*/

CREATE TABLE `hbook_user` (
  `id` int(11) NOT NULL auto_increment,
  `user` varchar(20) NOT NULL default '',
  `pass` varchar(20) NOT NULL default '',
  `namn` varchar(50) NOT NULL default '',
  `groups` blob NOT NULL,
  `superadmin` tinyint(1) NULL default '0',
  `_foretag` int(11) unsigned NULL default '0',
  `referens` varchar(100) NULL default '',
  `securepass` varchar(150) NULL default '',
  `driverid` int(11) NULL default '0',
  `shortdriver` varchar(3) NULL default '',
  `grupp` int(2) NULL default '0',
  `ordning` int(2) NULL default '99',
  `ordinary` varchar(10) NULL default '',
  `active` tinyint(1) NULL default '1',
  `deleted` tinyint(1) NULL default '0',
  `email` varchar(50) NULL default '',
  `companyid` int(11) NULL default '0',
  `lostpassword` varchar(150) NULL default '',
  `changepwdnextlogin` tinyint(1) NULL default '0',
  `sendemail` tinyint(1) NULL default '0',
  `drivercard` varchar(20) NULL default NULL,
  `settings` blob NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `hbook_vehicle` (
`id` int(11) NOT NULL auto_increment,
`regnr` varchar(10) NOT NULL default '',
`kategori` int(11) NULL default '0',
`b_name` varchar(45) NULL default NULL,
`b_id` int(11) NULL default '0',
`b_color` varchar(10) NULL default NULL,
`b_visible` tinyint(1) NULL default '0',
`b_exist` tinyint(1) NULL default '0',
`ordning` int(11) NULL default '0',
`active` tinyint(1) NULL default '1',
`deleted` tinyint(1) NULL default '0',
`companyid` int(11) NULL default '0',
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `hbook_options` (
`id` int(11) NOT NULL auto_increment,
`option_key` varchar(100) NULL default NULL,
`option_value` text NULL,
`ordning` int(11) NULL default '0',
`companyid` int(11) NULL default '0',
`deleted` tinyint(1) NULL default '0',
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `hbook_ajax` (
  `id` int(11) NOT NULL auto_increment,
  `auth` int(11) NOT NULL default '1',
  `name` varchar(45) NOT NULL default NULL,
  `script` varchar(200) NOT NULL default NULL,
  `ordning` int(2) NOT NULL default '99',
  `companyid` int(11) NULL default '0',
  `deleted` tinyint(1) NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
