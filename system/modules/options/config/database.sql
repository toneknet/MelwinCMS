CREATE TABLE `m_options` (
  `id` int(11) NOT NULL auto_increment,
  `option_key` varchar(100) NULL default NULL,
  `option_value` text NULL,
  `ordning` int(11) NULL default '0',
  `companyid` int(11) NULL default '0',
  `deleted` tinyint(1) NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

