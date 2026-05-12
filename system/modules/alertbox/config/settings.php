<?php

/*
 *  settingsfilen
 *
 * Module: listandedit
 */

$GLOBALS['MODULE']['alertbox']=array (
    'table' => false,
    'template'  => false,
    'form'  =>  array (
      'palette' =>  false,
    ),
    'fields' => array (
      'type' => array(
  			'type'     => 'select',
        'field'     => 'list[type]',
  			'caption'  => $GLOBALS['LANG']['alertbox']['type'],
        'options'  => array(''=>'Inget','error' => 'Error','warning' => 'Warning','success' => 'Success','notice' => 'Notice'),
        //'value'    => '',
  		),
      'text' => array(
  			'type'     => 'varchar',
        'field'     => 'list[text]',
  			'caption'  => $GLOBALS['LANG']['alertbox']['text'],
        //'value'    => '',
  		),
	  )
);
