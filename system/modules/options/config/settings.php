<?php

/*
 *  settingsfilen
 *
 * Module: options
 */

$GLOBALS['MODULE']['options']=array (
    /*'table' => true,*/
    'template'  => false,
    'form'  =>  array (
      /*'palette' =>  true,*/
    ),
    'fields' => array (
        'order' => array(
		       'type'     => 'varchar',
           'field'     => 'list[order]',
		       'caption'  => $GLOBALS['LANG']['ORDER']['order'],
           'value'    => '',
		    ),
        'width' => array(
		       'type'     => 'varchar',
           'field'     => 'list[width]',
		       'caption'  => $GLOBALS['LANG']['ORDER']['width'],
           'value'    => '',
		    ),
        'edit' => array(
		       'type'     => 'checkbox',
           'field'     => 'list[edit]',
		       'caption'  => $GLOBALS['LANG']['ORDER']['edit'],
           'value'    => '',
        ),
        'where' => array(
            'type'     => 'varchar',
            'field'     => 'list[where]',
            'caption'  => $GLOBALS['LANG']['ORDER']['where'],
            'value'    => '',
		),
	)
);
