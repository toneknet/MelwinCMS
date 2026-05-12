/*CREATE TABLE `hbook_company` (
`id` int(11) NOT NULL auto_increment,
`namn` varchar(45) NULL default NULL,
`ordning` int(2) NOT NULL default '99',
`companyid` int(11) NULL default '0',
`deleted` tinyint(1) NULL default '0',
`dbprefix` varchar(10) NULL default 'hbook_',
`prefix` varchar(10) NULL default NULL,
`settings` blob DEFAULT NULL,
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;*/


CREATE TABLE `hbook_company` (
  `id` int(11) NOT NULL auto_increment,
  `namn` varchar(45) NULL default NULL,
  `ordning` int(2) NOT NULL default '99',
  `companyid` int(11) NULL default '0',
  `deleted` tinyint(1) NULL default '0',
  `dbprefix` varchar(10) NULL default 'hbook_',
  `prefix` varchar(10) NULL default NULL,
  `settings` blob NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
