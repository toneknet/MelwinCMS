CREATE TABLE `m_groups` (
`id` int(11) NOT NULL auto_increment,
`namn` varchar(45) NULL default NULL,
`description` text NULL,
`settings` blob NULL,
`companyid` int(11) NULL default '0',
`deleted` tinyint(1) NULL default '0',
`ordning` int(2) NOT NULL default '99',
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
