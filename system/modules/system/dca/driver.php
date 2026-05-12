<?php

$GLOBALS['TABLE']['driver']=array (
	'table' => 'driver',
	'list'  => array(
		'order' => 'driver,shortdriver',
		'width' => '',
        'edit'  => true
	),
    'allowAjax' => 1,
	'fields' => array (
		'id' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['driver']['id']
		),
		'driver' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['driver']['driver']
		),
		'shortdriver' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['driver']['shortdriver']
		),
		'grupp' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['driver']['grupp']
		),
		'ordning' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['driver']['ordning']
		),
		'ordinary' => array(
			'type'     => 'int',
			'caption'  => $GLOBALS['LANG']['driver']['ordinary']
		),
		'active' => array(
			'type'     => 'checkbox',
			'caption'  => $GLOBALS['LANG']['driver']['active']
		),
		'companyid' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['companyid'],
                        'value' => getCompanyID()
		),
		'deleted' => array(
			'type'     => 'hidden',
			'caption'  => ''
		)


	)
);
